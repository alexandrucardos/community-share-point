<?php

declare(strict_types=1);

namespace App\Tests\Service\UserRepository;

use App\Domain\User\UserEntity;
use App\Domain\ValueObject\EmailValueObject;
use App\Service\UserRepository\FileUserRepository;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class FileUserRepositoryTest extends TestCase
{
    private string $projectDir;
    private ValidatorService $validator;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/file-user-repository-test-'.uniqid();
        $this->validator = new ValidatorService($this->createTranslator());
    }

    protected function tearDown(): void
    {
        $usersFile = $this->projectDir.'/var/data/users.json';
        if (is_file($usersFile)) {
            unlink($usersFile);
        }
        if (is_dir($this->projectDir.'/var/data')) {
            rmdir($this->projectDir.'/var/data');
        }
        if (is_dir($this->projectDir.'/var')) {
            rmdir($this->projectDir.'/var');
        }
        if (is_dir($this->projectDir)) {
            rmdir($this->projectDir);
        }
    }

    public function testFindByEmailReturnsNullWhenNoUserWasStored(): void
    {
        $repository = $this->createRepository();

        self::assertNull($repository->findByEmail('missing@example.com'));
    }

    public function testAddThenFindByEmailReturnsTheStoredUser(): void
    {
        $repository = $this->createRepository();

        $user = new UserEntity('user-id');
        $user->setEmail($this->email('jane.doe@example.com'));
        $user->setPassword('hashed-password');
        $user->setContactInfo('+40 700 000 000');
        $user->setGroupId('group-id');

        $repository->add($user);

        $found = $repository->findByEmail('jane.doe@example.com');

        self::assertNotNull($found);
        self::assertSame('user-id', $found->getId()->value);
        self::assertSame('jane.doe@example.com', $found->getEmail()->value);
        self::assertSame('hashed-password', $found->getPassword());
        self::assertSame('+40 700 000 000', $found->getContactInfo());
        self::assertSame('group-id', $found->getGroupId());
    }

    public function testAddPersistsAcrossRepositoryInstances(): void
    {
        $this->createRepository()->add($this->buildUser('persisted@example.com'));

        $secondInstance = $this->createRepository();

        self::assertNotNull($secondInstance->findByEmail('persisted@example.com'));
    }

    private function buildUser(string $email): UserEntity
    {
        $user = new UserEntity('id');
        $user->setEmail($this->email($email));
        $user->setPassword('hashed');
        $user->setContactInfo('');
        $user->setGroupId('');

        return $user;
    }

    private function email(string $value): EmailValueObject
    {
        return (new EmailValueObject($this->validator))($value);
    }

    private function createRepository(): FileUserRepository
    {
        return new FileUserRepository($this->projectDir, $this->validator);
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
