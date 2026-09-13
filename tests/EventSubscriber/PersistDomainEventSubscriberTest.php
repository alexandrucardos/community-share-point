<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Application\User\GetUser\UserView;
use App\Domain\Event\DomainEventInterface;
use App\Domain\Item\Event\ItemCreatedEvent;
use App\Domain\UuidInterface;
use App\Domain\User\Event\UserCreatedEvent;
use App\Entity\EventLog;
use App\EventSubscriber\PersistDomainEventSubscriber;
use App\Repository\EventLogRepository;
use App\Service\Security\SecurityUser;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Drives the subscriber against a real (in-memory) Doctrine ORM stack, so the
 * whole path is exercised: published event -> subscriber -> repository ->
 * event_log row.
 */
final class PersistDomainEventSubscriberTest extends TestCase
{
    private const EVENT_ROW_ID = '33333333-3333-4333-8333-333333333333';
    private const USER_ID = '11111111-1111-4111-8111-111111111111';

    private EntityManager $entityManager;
    private EventLogRepository $eventLogRepository;
    private UuidInterface&Stub $uuid;

    protected function setUp(): void
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
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

        $this->eventLogRepository = new EventLogRepository($registry);

        $this->uuid = $this->createStub(UuidInterface::class);
        $this->uuid->method('generate')->willReturn(self::EVENT_ROW_ID);
    }

    public function testSubscribesToEveryDomainEventThroughTheDomainInterfacesEventName(): void
    {
        self::assertSame(
            [DomainEventInterface::class => 'onDomainEvent'],
            PersistDomainEventSubscriber::getSubscribedEvents(),
        );
    }

    public function testOnDomainEventAppendsARowDescribingTheEvent(): void
    {
        $subscriber = $this->subscriberFor($this->securityUser(self::USER_ID));

        $event = new ItemCreatedEvent(
            itemId: '22222222-2222-4222-8222-222222222222',
            userId: self::USER_ID,
            name: 'Drill',
            description: 'A cordless drill',
            status: 'Available',
            imageFilename: null,
        );

        $subscriber->onDomainEvent($event);

        $record = $this->reload();
        self::assertSame('item.created', $record->eventName);
        self::assertSame('item', $record->aggregateType);
        self::assertSame('22222222-2222-4222-8222-222222222222', $record->aggregateId);
        self::assertSame(self::USER_ID, $record->actorId);
        self::assertSame($event->payload(), $record->payload);
        self::assertSame($event->occurredAt()->getTimestamp(), $record->occurredAt->getTimestamp());
    }

    public function testOnDomainEventRecordsTheSignedInUserAsTheActor(): void
    {
        $subscriber = $this->subscriberFor($this->securityUser(self::USER_ID));

        $subscriber->onDomainEvent($this->itemCreatedEvent());

        self::assertSame(self::USER_ID, $this->reload()->actorId);
    }

    public function testOnDomainEventLeavesTheActorEmptyWhenNoUserIsSignedIn(): void
    {
        $subscriber = $this->subscriberFor(null);

        $subscriber->onDomainEvent($this->itemCreatedEvent());

        self::assertNull($this->reload()->actorId);
    }

    public function testOnDomainEventNeverStoresCredentials(): void
    {
        $subscriber = $this->subscriberFor($this->securityUser(self::USER_ID));

        $subscriber->onDomainEvent(new UserCreatedEvent(
            userId: self::USER_ID,
            email: 'jane.doe@example.com',
            contactInfo: '+40 700 000 000',
            groupId: '44444444-4444-4444-8444-444444444444',
        ));

        $payload = $this->reload()->payload;
        self::assertSame('jane.doe@example.com', $payload['email']);

        foreach (array_keys($payload) as $key) {
            self::assertStringNotContainsStringIgnoringCase('password', $key);
        }
    }

    private function subscriberFor(?SecurityUser $user): PersistDomainEventSubscriber
    {
        return new PersistDomainEventSubscriber(
            $this->eventLogRepository,
            $this->uuid,
            $this->tokenStorageFor($user),
        );
    }

    private function itemCreatedEvent(): ItemCreatedEvent
    {
        return new ItemCreatedEvent(
            itemId: '22222222-2222-4222-8222-222222222222',
            userId: self::USER_ID,
            name: 'Drill',
            description: 'A cordless drill',
            status: 'Available',
            imageFilename: null,
        );
    }

    private function reload(): EventLog
    {
        $this->entityManager->clear();

        $record = $this->entityManager->find(EventLog::class, self::EVENT_ROW_ID);
        self::assertInstanceOf(EventLog::class, $record);

        return $record;
    }

    private function tokenStorageFor(?SecurityUser $user): TokenStorageInterface
    {
        $storage = $this->createStub(TokenStorageInterface::class);

        if ($user === null) {
            return $storage;
        }

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $storage->method('getToken')->willReturn($token);

        return $storage;
    }

    private function securityUser(string $id): SecurityUser
    {
        return new SecurityUser(new UserView(
            id: $id,
            hashedPassword: 'hashed-password',
            contactInfo: '+40 700 000 000',
            email: 'actor@example.com',
            groupId: '44444444-4444-4444-8444-444444444444',
        ));
    }
}
