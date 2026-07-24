<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\ContactInfoValueObject;
use App\Domain\ValueObject\EmailValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;

final class FileUserRepository implements UserRepositoryInterface
{
    private readonly string $storagePath;

    public function __construct(
        string $projectDir,
        private readonly ValidatorInterface $validator,
    ) {
        $this->storagePath = $projectDir.'/var/data/users.json';
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
    ): ?UserEntity
    {
        $record = $this->readRecords()[$email->value] ?? null;

        return $record === null ? null : $this->mapRecordToUser($record);
    }

    public function findById(string $id): ?UserEntity
    {
        foreach ($this->readRecords() as $record) {
            if ($record['id'] === $id) {
                return $this->mapRecordToUser($record);
            }
        }

        return null;
    }

    public function findAllByGroupId(string $groupId): array
    {
        $records = array_filter(
            $this->readRecords(),
            static fn (array $record): bool => $record['groupId'] === $groupId,
        );

        return array_map($this->mapRecordToUser(...), array_values($records));
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
        $user->setContactInfo((new ContactInfoValueObject($this->validator))($record['contactInfo']));
        $user->setGroupId((new UuidValueObject($this->validator))($record['groupId']));

        return $user;
    }

    private function persist(UserEntity $user): void
    {
        $records = $this->readRecords();

        $records[$user->getEmail()->value] = [
            'id' => $user->getId()->value,
            'email' => $user->getEmail()->value,
            'password' => $user->getHashedPassword(),
            'contactInfo' => $user->getContactInfo()->value,
            'groupId' => $user->getGroupId()->value,
        ];

        $this->writeRecords($records);
    }

    private function readRecords(): array
    {
        if (!is_file($this->storagePath)) {
            return [];
        }

        $contents = file_get_contents($this->storagePath);

        return $contents === false || $contents === '' ? [] : json_decode($contents, true, flags: \JSON_THROW_ON_ERROR);
    }

    private function writeRecords(array $records): void
    {
        if (!is_dir(\dirname($this->storagePath))) {
            mkdir(\dirname($this->storagePath), recursive: true);
        }

        file_put_contents(
            $this->storagePath,
            json_encode($records, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT),
            \LOCK_EX
        );
    }
}
