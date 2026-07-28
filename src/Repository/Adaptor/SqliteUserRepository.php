<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use Doctrine\DBAL\Connection;

final class SqliteUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function add(UserEntity $user): void
    {
        $this->persist($user);
    }

    public function update(UserEntity $user): void
    {
        $this->persist($user);
    }

    public function findByEmailAndGroupId(
        EmailValueObject $email,
        UuidValueObject $groupId,
    ): ?UserEntity {
        $record = $this->connection->fetchAssociative(
            'SELECT * FROM users WHERE email = ?',
            [$email->value],
        );

        return $record === false ? null : $this->mapRecordToUser($record);
    }

    public function findById(string $id): ?UserEntity
    {
        $record = $this->connection->fetchAssociative(
            'SELECT * FROM users WHERE id = ?',
            [$id],
        );

        return $record === false ? null : $this->mapRecordToUser($record);
    }

    public function findAllByGroupId(string $groupId): array
    {
        $records = $this->connection->fetchAllAssociative(
            'SELECT * FROM users WHERE group_id = ?',
            [$groupId],
        );

        return array_map($this->mapRecordToUser(...), $records);
    }

    private function mapRecordToUser(array $record): UserEntity
    {
        $user = new UserEntity(
            (new UuidValueObject($this->validator))($record['id'])
        );
        $user->setEmail(
            (new EmailValueObject($this->validator))($record['email'])
        );
        $user->setHashedPassword($record['password']);
        $user->setContactInfo((new ContactInfoValueObject($this->validator))($record['contact_info']));
        $user->setGroupId((new UuidValueObject($this->validator))($record['group_id']));

        return $user;
    }

    private function persist(UserEntity $user): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO users (id, email, password, contact_info, group_id)
                VALUES (:id, :email, :password, :contactInfo, :groupId)
                ON CONFLICT(id) DO UPDATE SET
                    email        = excluded.email,
                    password     = excluded.password,
                    contact_info = excluded.contact_info,
                    group_id     = excluded.group_id
                SQL,
            [
                'id' => $user->getId()->value,
                'email' => $user->getEmail()->value,
                'password' => $user->getHashedPassword(),
                'contactInfo' => $user->getContactInfo()->value,
                'groupId' => $user->getGroupId()->value,
            ],
        );
    }
}
