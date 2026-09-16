<?php

declare(strict_types=1);

namespace App\Tests\Service\RequestLog;

use App\Entity\RequestResponseLog;
use App\Repository\RequestResponseLogRepository;
use App\Service\RequestLog\DatabaseRequestLogReader;
use App\Service\RequestLog\PayloadTruncator;
use App\Service\RequestLog\RequestLogFilter;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class DatabaseRequestLogReaderTest extends TestCase
{
    private const LOG_ID = '55555555-5555-4555-8555-555555555555';

    private EntityManager $entityManager;
    private RequestResponseLogRepository $repository;
    private DatabaseRequestLogReader $reader;

    protected function setUp(): void
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', \Symfony\Bridge\Doctrine\Types\UuidType::class);
        }

        $config = ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__, 3).'/src/Entity'],
            isDevMode: true,
        );
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);
        $this->repository = new RequestResponseLogRepository($registry);

        $this->reader = new DatabaseRequestLogReader($this->repository, new PayloadTruncator());

        $this->appendRecord('55555555-5555-4555-8555-555555555555', '2026-09-13 08:00:00');
    }

    public function testReadsRequestAndResponseEntriesFromDatabase(): void
    {
        $entries = $this->reader->read(new RequestLogFilter());

        self::assertCount(2, $entries);
        self::assertSame('request', $entries[0]->type);
        self::assertSame(['name' => 'Drill'], $entries[0]->payload);
        self::assertSame('response', $entries[1]->type);
        self::assertSame(['id' => 'item-1', 'ok' => true], $entries[1]->payload);
        self::assertSame(201, $entries[1]->status);
    }

    public function testFiltersResponseEntriesByStatus(): void
    {
        $entries = $this->reader->read(new RequestLogFilter(status: 201));

        self::assertCount(1, $entries);
        self::assertSame('response', $entries[0]->type);
        self::assertSame(201, $entries[0]->status);
    }

    public function testSearchMatchesRequestAndResponsePayloads(): void
    {
        $entries = $this->reader->read(new RequestLogFilter(search: 'item-1'));

        self::assertCount(1, $entries);
        self::assertSame('response', $entries[0]->type);
    }

    public function testPagesEntriesNewestFirstWithTheTotalItemCount(): void
    {
        $this->appendRecord('66666666-6666-4666-8666-666666666666', '2026-09-13 07:00:00');
        $this->appendRecord('77777777-7777-4777-8777-777777777777', '2026-09-13 06:00:00');

        $page = $this->reader->readPage(new RequestLogFilter(), 2, 2);

        self::assertSame(6, $page->totalItemCount);
        self::assertCount(2, $page->entries);
        self::assertSame(['request', 'response'], array_column($page->entries, 'type'));
        self::assertSame('2026-09-13 07:00:00', $page->entries[0]->timestamp->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-13 07:00:00', $page->entries[1]->timestamp->format('Y-m-d H:i:s'));
    }

    public function testPagesAcrossTheMiddleOfARecord(): void
    {
        $this->appendRecord('66666666-6666-4666-8666-666666666666', '2026-09-13 07:00:00');
        $this->appendRecord('77777777-7777-4777-8777-777777777777', '2026-09-13 06:00:00');

        $page = $this->reader->readPage(new RequestLogFilter(), 2, 3);

        self::assertSame(6, $page->totalItemCount);
        self::assertCount(3, $page->entries);
        self::assertSame('2026-09-13 07:00:00', $page->entries[0]->timestamp->format('Y-m-d H:i:s'));
        self::assertSame('response', $page->entries[0]->type);
        self::assertSame('2026-09-13 06:00:00', $page->entries[1]->timestamp->format('Y-m-d H:i:s'));
        self::assertSame('request', $page->entries[1]->type);
        self::assertSame('response', $page->entries[2]->type);
    }

    public function testPagesOnlyResponsesWhenFilteringByStatus(): void
    {
        $this->appendRecord('66666666-6666-4666-8666-666666666666', '2026-09-13 07:00:00');

        $page = $this->reader->readPage(new RequestLogFilter(status: 201), 1, 10);

        self::assertSame(2, $page->totalItemCount);
        self::assertCount(2, $page->entries);
        self::assertSame(['response', 'response'], array_column($page->entries, 'type'));
    }

    public function testAppliesMethodRouteAndStatusCriteriaInTheQuery(): void
    {
        $this->appendRecord(
            '66666666-6666-4666-8666-666666666666',
            '2026-09-13 07:00:00',
            method: 'GET',
            uri: '/group/abc/items',
            route: 'items_list',
            status: 200,
        );

        $page = $this->reader->readPage(new RequestLogFilter(method: 'get', route: 'items_list', status: 200), 1, 10);

        self::assertSame(1, $page->totalItemCount);
        self::assertCount(1, $page->entries);
        self::assertSame('items_list', $page->entries[0]->route);
    }

    public function testSearchIsStillExactAcrossPages(): void
    {
        $this->appendRecord('66666666-6666-4666-8666-666666666666', '2026-09-13 07:00:00');
        $this->appendRecord(
            '77777777-7777-4777-8777-777777777777',
            '2026-09-13 06:00:00',
            responsePayload: ['id' => 'needle-item', 'ok' => true],
        );

        $page = $this->reader->readPage(new RequestLogFilter(search: 'needle-item'), 1, 10);

        self::assertSame(1, $page->totalItemCount);
        self::assertCount(1, $page->entries);
        self::assertSame('response', $page->entries[0]->type);
        self::assertSame('needle-item', $page->entries[0]->payload['id']);
    }

    public function testShortensLargePayloadsBeforeTheyReachTheTemplate(): void
    {
        $body = str_repeat('a', 5000);
        $this->appendRecord(
            '66666666-6666-4666-8666-666666666666',
            '2026-09-13 09:00:00',
            responsePayload: ['body' => $body],
        );

        $page = $this->reader->readPage(new RequestLogFilter(type: 'response'), 1, 10);

        self::assertSame(2, $page->totalItemCount);
        $newest = $page->entries[0];
        self::assertLessThan(5000, strlen($newest->payload['body']));
        self::assertStringContainsString('[5000 chars]', $newest->payload['body']);
    }

    /**
     * @param array<string, mixed>      $requestPayload
     * @param array<string, mixed>|null $responsePayload
     */
    private function appendRecord(
        string $id,
        string $respondedAt,
        string $method = 'POST',
        string $uri = '/items?search=drill',
        ?string $route = 'items_create',
        array $requestPayload = ['name' => 'Drill'],
        ?array $responsePayload = ['id' => 'item-1', 'ok' => true],
        ?int $status = 201,
    ): void {
        $record = new RequestResponseLog();
        $record->id = $id;
        $record->method = $method;
        $record->route = $route;
        $record->uri = $uri;
        $record->ip = '127.0.0.1';
        $record->requestPayload = $requestPayload;
        $record->responsePayload = $responsePayload;
        $record->status = $status;
        $record->contentType = 'application/json';
        $record->contentLength = 29;
        $record->durationMs = 12;
        $record->respondedAt = new \DateTimeImmutable($respondedAt);
        $this->repository->append($record);
    }
}
