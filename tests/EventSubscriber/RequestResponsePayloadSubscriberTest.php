<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Domain\UuidInterface;
use App\Entity\RequestResponseLog;
use App\EventSubscriber\ResponsePayloadSubscriber;
use App\Repository\RequestResponseLogRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RequestResponsePayloadSubscriberTest extends TestCase
{
    private const LOG_ID = '55555555-5555-4555-8555-555555555555';

    private EntityManager $entityManager;
    private RequestResponseLogRepository $repository;
    private UuidInterface&Stub $uuid;
    private LoggerInterface&Stub $logger;
    private HttpKernelInterface&Stub $kernel;

    protected function setUp(): void
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', \Symfony\Bridge\Doctrine\Types\UuidType::class);
        }

        $config = ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__, 2).'/src/Entity'],
            isDevMode: true,
        );
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);
        $this->repository = new RequestResponseLogRepository($registry);

        $this->uuid = $this->createStub(UuidInterface::class);
        $this->uuid->method('generate')->willReturn(self::LOG_ID);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->kernel = $this->createStub(HttpKernelInterface::class);
    }

    public function testResponseSubscriberPersistsOnePairedRecord(): void
    {
        $request = Request::create('/items?search=drill', 'POST', ['name' => 'Drill', '_password' => 'secret']);
        $request->attributes->set('_route', 'items_create');

        $response = new Response('{"id":"item-1","ok":true}', 201, ['Content-Type' => 'application/json']);
        (new ResponsePayloadSubscriber($this->logger, $this->repository, $this->uuid))
            ->onKernelResponse(new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        $this->entityManager->clear();
        $record = $this->entityManager->find(RequestResponseLog::class, self::LOG_ID);

        self::assertInstanceOf(RequestResponseLog::class, $record);
        self::assertSame('POST', $record->method);
        self::assertSame('items_create', $record->route);
        self::assertSame('/items?search=drill', $record->uri);
        self::assertSame('Drill', $record->requestPayload['name']);
        self::assertSame('***', $record->requestPayload['_password']);
        self::assertSame('drill', $record->requestPayload['search']);
        self::assertSame(['id' => 'item-1', 'ok' => true], $record->responsePayload);
        self::assertSame(201, $record->status);
        self::assertSame('application/json', $record->contentType);
        self::assertNotNull($record->respondedAt);
    }

    public function testResponseSubscriberStoresNonJsonBodyInTheResponsePayloadColumn(): void
    {
        $request = Request::create('/health', 'GET');
        (new ResponsePayloadSubscriber($this->logger, $this->repository, $this->uuid))
            ->onKernelResponse(new ResponseEvent(
                $this->kernel,
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                new Response('OK', 200),
            ));

        $this->entityManager->clear();
        $record = $this->entityManager->find(RequestResponseLog::class, self::LOG_ID);

        self::assertInstanceOf(RequestResponseLog::class, $record);
        self::assertSame(['body' => 'OK'], $record->responsePayload);
    }
}
