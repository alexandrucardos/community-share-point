<?php

declare(strict_types=1);

namespace App\Tests\Repository\Adaptor;

use App\Domain\User\UserEntity;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Repository\Adaptor\SqliteUserRepository;
use App\Service\ValidationService\ValidatorService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class SqliteUserRepositoryTest extends TestCase
{
    private const GROUP_ID = '94926cac-00e0-4e5f-8633-87b9918a90e4';

    private Connection $connection;
    private ValidatorService $validator;

    protected function setUp(): void
    {
        $this->validator = new ValidatorService($this->createTranslator());
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE users (
                id TEXT PRIMARY KEY, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL,
                contact_info TEXT NOT NULL, group_id TEXT NOT NULL
            )
            SQL);
    }

    public function testFindByEmailReturnsNullWhenNoUserWasStored(): void
    {
        $found = $this->createRepository()->findByEmailAndGroupId(
            $this->email('missing@example.com'),
            $this->uuid(self::GROUP_ID),
        );

        self::assertNull($found);
    }

    public function testAddThenFindByEmailReturnsTheStoredUser(): void
    {
        $repository = $this->createRepository();

        $repository->add($this->buildUser('11111111-1111-4111-8111-111111111111', 'jane.doe@example.com'));

        $found = $repository->findByEmailAndGroupId(
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

    public function testUpdateOverwritesTheExistingRow(): void
    {
        $repository = $this->createRepository();
        $id = '22222222-2222-4222-8222-222222222222';

        $repository->add($this->buildUser($id, 'before@example.com'));

        $repository->update($this->buildUser($id, 'after@example.com'));

        $reloaded = $repository->findById($id);
        self::assertNotNull($reloaded);
        self::assertSame('after@example.com', $reloaded->getEmail()->value);
    }

    public function testFindAllByGroupIdReturnsEveryMemberOfTheGroup(): void
    {
        $repository = $this->createRepository();

        $repository->add($this->buildUser('33333333-3333-4333-8333-333333333333', 'a@example.com'));
        $repository->add($this->buildUser('44444444-4444-4444-8444-444444444444', 'b@example.com'));

        $members = $repository->findAllByGroupId(self::GROUP_ID);

        self::assertCount(2, $members);
    }

    private function buildUser(string $id, string $email): UserEntity
    {
        $user = new UserEntity($this->uuid($id));
        $user->setEmail($this->email($email));
        $user->setHashedPassword('hashed-password');
        $user->setContactInfo(($this->contactInfo())('+40 700 000 000'));
        $user->setGroupId($this->uuid(self::GROUP_ID));

        return $user;
    }

    private function createRepository(): SqliteUserRepository
    {
        return new SqliteUserRepository($this->connection, $this->validator);
    }

    private function email(string $value): EmailValueObject
    {
        return (new EmailValueObject($this->validator))($value);
    }

    private function uuid(string $value): UuidValueObject
    {
        return (new UuidValueObject($this->validator))($value);
    }

    private function contactInfo(): ContactInfoValueObject
    {
        return new ContactInfoValueObject($this->validator);
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
