<?php

declare(strict_types=1);

namespace App\Tests\Repository\Adaptor;

use App\Domain\Item\ItemEntity;
use App\Domain\ValueObject\UuidValueObject;
use App\Repository\Adaptor\DoctrineItemRepository;
use App\Repository\Adaptor\ItemImageResolver;
use App\Repository\Doctrine\ItemRecordRepository;
use App\Service\ValidationService\ValidatorService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class DoctrineItemRepositoryTest extends TestCase
{
    private const USER_A = '11111111-1111-4111-8111-111111111111';

    private DoctrineItemRepository $repository;
    private ValidatorService $validator;

    protected function setUp(): void
    {
        $this->validator = new ValidatorService($this->createTranslator());

        $config = ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__, 3).'/src/Repository/Doctrine/Entity'],
            isDevMode: true,
        );
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        $packages = $this->createStub(Packages::class);
        $packages->method('getUrl')->willReturnCallback(static fn (string $path): string => '/assets/'.$path);

        $this->repository = new DoctrineItemRepository(
            new ItemRecordRepository($registry),
            $this->validator,
            new ItemImageResolver($packages),
        );
    }

    public function testAddThenFindByIdUsesTheAssetUrlWhenAnImageExists(): void
    {
        $this->repository->add($this->buildItem('a1111111-1111-4111-8111-111111111111', self::USER_A, 'Drill', 'photo.png'));

        $found = $this->repository->findById('a1111111-1111-4111-8111-111111111111');

        self::assertNotNull($found);
        self::assertSame('Drill', $found->getName());
        self::assertSame('/assets/images/items/photo.png', $found->getImageUrl());
    }

    public function testMissingImageFallsBackToAnInlinePlaceholder(): void
    {
        $this->repository->add($this->buildItem('a2222222-2222-4222-8222-222222222222', self::USER_A, 'Ladder', ''));

        $found = $this->repository->findById('a2222222-2222-4222-8222-222222222222');

        self::assertNotNull($found);
        self::assertStringStartsWith('data:image/svg+xml;base64,', $found->getImageUrl());
    }

    private function buildItem(string $id, string $userId, string $name, string $imageFilename): ItemEntity
    {
        $item = new ItemEntity($id);
        $item->setUserId((new UuidValueObject($this->validator))($userId));
        $item->setName($name);
        $item->setDescription('a description');
        $item->setStatus('Available');
        $item->setImageFilename($imageFilename);

        return $item;
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
