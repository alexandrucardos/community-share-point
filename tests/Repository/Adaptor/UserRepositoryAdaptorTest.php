<?php

declare(strict_types=1);

namespace App\Tests\Repository\Adaptor;

use App\Domain\User\UserEntity;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Entity\User;
use App\Repository\Adaptor\UserRepositoryAdaptor;
use App\Repository\UserRepository;
use App\Service\ValidationService\ValidatorService;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Drives the domain-facing adaptor against a real (in-memory) Doctrine ORM
 * stack so the User record mapping and the Record <-> domain translation are
 * both exercised.
 */
final class UserRepositoryAdaptorTest extends TestCase
{
    private const GROUP_ID = '94926cac-00e0-4e5f-8633-87b9918a90e4';

    private UserRepositoryAdaptor $repository;
    private ValidatorService $validator;
    private Stub&ManagerRegistry $registry;

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

        $this->repository = new UserRepositoryAdaptor(
            new UserRepository($this->registry),
            $this->validator,
        );
    }

    public function testFindByEmailAndGroupIdReturnsNullWhenNoUserWasStored(): void
    {
        $found = $this->repository->findByEmailAndGroupId(
            $this->email('missing@example.com'),
            $this->uuid(self::GROUP_ID),
        );

        self::assertNull($found);
    }

    public function testAddThenFindByEmailAndGroupIdReturnsTheStoredUser(): void
    {
        $this->repository->add($this->buildUser('11111111-1111-4111-8111-111111111111', 'jane.doe@example.com'));

        $found = $this->repository->findByEmailAndGroupId(
            $this->email('jane.doe@example.com'),
            $this->uuid(self::GROUP_ID),
        );

        self::assertNotNull($found);
        self::assertSame('11111111-1111-4111-8111-111111111111', $found->getId()->value);
        self::assertSame('jane.doe@example.com', $found->getEmail()->value);
        self::assertSame('hashed-password', $found->getHashedPassword());
        self::assertSame('+40 700 000 000', $found->getContactInfo()->value);
        self::assertSame(self::GROUP_ID, $found->getGroupId()->value);
    }

    public function testFindByEmailAndGroupIdDoesNotReturnUsersFromAnotherGroup(): void
    {
        $this->repository->add($this->buildUser('11111111-1111-4111-8111-111111111111', 'jane.doe@example.com'));

        $found = $this->repository->findByEmailAndGroupId(
            $this->email('jane.doe@example.com'),
            $this->uuid('aaaaaaaa-1111-4111-8111-111111111111'),
        );

        self::assertNull($found);
    }

    public function testUpdateOverwritesTheExistingRow(): void
    {
        $id = '22222222-2222-4222-8222-222222222222';

        $this->repository->add($this->buildUser($id, 'before@example.com'));
        $this->repository->update($this->buildUser($id, 'after@example.com'));

        $reloaded = $this->repository->findByEmailAndGroupId(
            $this->email('after@example.com'),
            $this->uuid(self::GROUP_ID),
        );
        self::assertNotNull($reloaded);
        self::assertSame($id, $reloaded->getId()->value);
        self::assertSame('after@example.com', $reloaded->getEmail()->value);
    }

    public function testUpdateDoesNotDuplicateRows(): void
    {
        $id = '22222222-2222-4222-8222-222222222222';

        $this->repository->add($this->buildUser($id, 'same@example.com'));
        $this->repository->update($this->buildUser($id, 'same@example.com'));

        $reloaded = $this->repository->findByEmailAndGroupId(
            $this->email('same@example.com'),
            $this->uuid(self::GROUP_ID),
        );
        self::assertNotNull($reloaded);
        self::assertSame($id, $reloaded->getId()->value);
    }

    public function testAddPersistsTheRecordWithTheExpectedFieldValues(): void
    {
        $entityManager = $this->registry->getManagerForClass(User::class);

        $this->repository->add($this->buildUser('33333333-3333-4333-8333-333333333333', 'persisted@example.com'));

        /** @var User|null $record */
        $record = $entityManager->find(User::class, '33333333-3333-4333-8333-333333333333');
        self::assertNotNull($record);
        self::assertSame('persisted@example.com', $record->email);
        self::assertSame('hashed-password', $record->password);
        self::assertSame('+40 700 000 000', $record->contactInfo);
        self::assertSame(self::GROUP_ID, $record->groupId);
    }

    private function buildUser(string $id, string $email): UserEntity
    {
        $user = new UserEntity($this->uuid($id));
        $user->setEmail($this->email($email));
        $user->setHashedPassword('hashed-password');
        $user->setContactInfo((new ContactInfoValueObject($this->validator))('+40 700 000 000'));
        $user->setGroupId($this->uuid(self::GROUP_ID));

        return $user;
    }

    private function email(string $value): EmailValueObject
    {
        return (new EmailValueObject($this->validator))($value);
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
