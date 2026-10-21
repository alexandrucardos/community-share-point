<?php

declare(strict_types=1);

namespace App\Tests\Repository\Adaptor;

use App\Domain\Item\ItemEntity;
use App\Domain\ValueObject\ItemDescriptionValueObject;
use App\Domain\ValueObject\ItemNameValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Entity\Item;
use App\Repository\Adaptor\ItemRepositoryAdaptor;
use App\Repository\ItemRepository;
use App\Service\Image\ImageUploader;
use App\Service\ValidationService\ValidatorService;
use AsyncAws\S3\S3Client;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bridge\Doctrine\Types\UuidType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Drives the domain-facing adaptor against a real (in-memory) Doctrine ORM
 * stack so the Item record mapping and the Record <-> domain translation are
 * both exercised. S3 requests use a mock HTTP client so tests never contact AWS.
 */
final class ItemRepositoryAdaptorTest extends TestCase
{
    private const USER_ID = '11111111-1111-4111-8111-111111111111';

    private ItemRepositoryAdaptor $repository;
    private ValidatorService $validator;
    private Stub&ManagerRegistry $registry;
    private array $s3Requests = [];

    protected function setUp(): void
    {
        $this->validator = new ValidatorService($this->createTranslator());

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $config = ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__, 3).'/src/Entity'],
            isDevMode: true,
        );
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $this->registry = $this->createStub(ManagerRegistry::class);
        $this->registry->method('getManagerForClass')->willReturn($entityManager);

        $s3Client = new S3Client(
            [
                'accessKeyId' => 'test-key',
                'accessKeySecret' => 'test-secret',
                'region' => 'us-east-1',
            ],
            null,
            new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
                $this->s3Requests[] = ['method' => $method, 'url' => $url];

                return new MockResponse('', ['http_code' => 204]);
            }),
        );

        $imageUploader = new ImageUploader($s3Client, 'test-bucket', 'items');

        $this->repository = new ItemRepositoryAdaptor(
            new ItemRepository($this->registry),
            $this->validator,
            $imageUploader,
        );
    }

    public function testFindByIdReturnsNullWhenNoItemWasStored(): void
    {
        self::assertNull($this->repository->findById('aaaaaaaa-1111-4111-8111-111111111111'));
    }

    public function testAddThenFindByIdReturnsTheStoredItem(): void
    {
        $id = 'a1111111-1111-4111-8111-111111111111';

        $this->repository->add($this->buildItem($id, 'Drill'));

        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame($id, $found->getId()->value);
        self::assertSame(self::USER_ID, $found->getUserId()->value);
        self::assertSame('Drill', $found->getName()->value);
        self::assertSame('a description', $found->getDescription()->value);
        self::assertSame('Available', $found->getStatus());
        self::assertSame('', $found->getFileName());
    }

    public function testUpdateOverwritesTheExistingRow(): void
    {
        $id = 'a2222222-2222-4222-8222-222222222222';

        $this->repository->add($this->buildItem($id, 'Before'));

        $item = $this->repository->findById($id);
        self::assertNotNull($item);
        $item->setName(new ItemNameValueObject($this->validator, 'After'));
        $item->setStatus('Reserved');
        $this->repository->update($item);

        $reloaded = $this->repository->findById($id);

        self::assertNotNull($reloaded);
        self::assertSame('After', $reloaded->getName()->value);
        self::assertSame('Reserved', $reloaded->getStatus());
        self::assertSame('a description', $reloaded->getDescription()->value);
    }

    public function testUpdateWithoutANewFileKeepsTheStoredImageFilename(): void
    {
        $id = 'a3333333-3333-4333-8333-333333333333';

        $this->repository->add($this->buildItem($id, 'Drill'));

        $item = $this->repository->findById($id);
        self::assertNotNull($item);
        $item->setName(new ItemNameValueObject($this->validator, 'Renamed drill'));
        $this->repository->update($item);

        $reloaded = $this->repository->findById($id);

        self::assertNotNull($reloaded);
        self::assertSame('Renamed drill', $reloaded->getName()->value);
        self::assertSame('', $reloaded->getFileName());
    }

    public function testDeleteRemovesTheItemFromTheDatabaseAndItsImageFromS3(): void
    {
        $id = 'a5555555-5555-4555-8555-555555555555';
        $this->repository->add($this->buildItem($id, 'Drill'));

        $entityManager = $this->registry->getManagerForClass(Item::class);
        /** @var Item $record */
        $record = $entityManager->find(Item::class, $id);
        $record->imageFilename = 'drill.png';
        $entityManager->flush();

        $item = $this->repository->findById($id);
        self::assertNotNull($item);

        $this->repository->delete($item);

        self::assertNull($this->repository->findById($id));
        self::assertCount(1, $this->s3Requests);
        self::assertSame('DELETE', $this->s3Requests[0]['method']);
        self::assertSame('/items/drill.png', parse_url($this->s3Requests[0]['url'], PHP_URL_PATH));
        self::assertStringContainsString('test-bucket', (string) parse_url($this->s3Requests[0]['url'], PHP_URL_HOST));
    }

    public function testDeleteWithoutAnImageOnlyRemovesTheDatabaseRecord(): void
    {
        $id = 'a6666666-6666-4666-8666-666666666666';
        $item = $this->buildItem($id, 'Ladder');
        $this->repository->add($item);

        $this->repository->delete($item);

        self::assertNull($this->repository->findById($id));
        self::assertSame([], $this->s3Requests);
    }

    public function testAddPersistsTheRecordWithTheExpectedFieldValues(): void
    {
        $entityManager = $this->registry->getManagerForClass(Item::class);
        $id = 'a4444444-4444-4444-8444-444444444444';

        $this->repository->add($this->buildItem($id, 'Ladder'));

        /** @var Item|null $record */
        $record = $entityManager->find(Item::class, $id);
        self::assertNotNull($record);
        self::assertSame(self::USER_ID, $record->userId);
        self::assertSame('Ladder', $record->name);
        self::assertSame('a description', $record->description);
        self::assertSame('Available', $record->status);
        self::assertSame('', $record->imageFilename);
    }

    private function buildItem(string $id, string $name): ItemEntity
    {
        $item = new ItemEntity($this->uuid($id));
        $item->setUserId($this->uuid(self::USER_ID));
        $item->setName(new ItemNameValueObject($this->validator, $name));
        $item->setDescription(new ItemDescriptionValueObject($this->validator, 'a description'));
        $item->setStatus('Available');

        return $item;
    }

    private function uuid(string $value): UuidValueObject
    {
        return (new UuidValueObject($this->validator))($value);
    }

    private function createTranslator(): Translator
    {
        $translator = new Translator('ro');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            dirname(__DIR__, 3).'/translations/messages.ro.yaml',
            'ro',
        );

        return $translator;
    }
}
