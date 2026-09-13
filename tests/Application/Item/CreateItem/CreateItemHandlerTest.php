<?php

declare(strict_types=1);

namespace App\Tests\Application\Item\CreateItem;

use App\Application\Item\CreateItem\CreateItemCommand;
use App\Application\Item\CreateItem\CreateItemHandler;
use App\Application\Item\FileInfoDto;
use App\Domain\Event\DomainEventPublisherInterface;
use App\Domain\Item\Event\ItemCreatedEvent;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\Item\ItemStatus;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\ItemDescriptionValueObject;
use App\Domain\ValueObject\ItemNameValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Service\ValidationService\ValidatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

final class CreateItemHandlerTest extends TestCase
{
    private const USER_ID = '11111111-1111-4111-8111-111111111111';
    private const ITEM_ID = '22222222-2222-4222-8222-222222222222';

    private ItemRepositoryInterface&MockObject $itemRepository;
    private DomainEventPublisherInterface&MockObject $domainEventPublisher;
    private CreateItemHandler $handler;

    protected function setUp(): void
    {
        $this->itemRepository = $this->createMock(ItemRepositoryInterface::class);
        $this->domainEventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $validator = new ValidatorService($this->createTranslator());

        $uuid = $this->createStub(UuidInterface::class);
        $uuid->method('generate')->willReturn(self::ITEM_ID);

        $this->handler = new CreateItemHandler(
            $this->itemRepository,
            new ItemNameValueObject($validator),
            new ItemDescriptionValueObject($validator),
            $uuid,
            new UuidValueObject($validator),
            $validator,
            $this->domainEventPublisher,
        );
    }

    public function testHandlePublishesItemCreatedEventWithoutAnImageWhenNoFileIsAttached(): void
    {
        $this->itemRepository->expects($this->once())->method('add');

        $this->domainEventPublisher->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (ItemCreatedEvent $event): bool {
                self::assertSame('item.created', $event->eventName());
                self::assertSame('item', $event->aggregateType());
                self::assertSame(self::ITEM_ID, $event->aggregateId());
                // getFileName() stays uninitialized when nothing was uploaded.
                self::assertNull($event->imageFilename);
                self::assertSame([
                    'item_id' => self::ITEM_ID,
                    'user_id' => self::USER_ID,
                    'name' => 'Drill',
                    'description' => 'A cordless drill',
                    'status' => 'Available',
                    'image_filename' => null,
                ], $event->payload());

                return true;
            }));

        $this->handler->handle(new CreateItemCommand(
            userId: self::USER_ID,
            name: 'Drill',
            description: 'A cordless drill',
            status: ItemStatus::Available,
        ));
    }

    public function testHandlePublishesTheStoredImageNameWhenAFileIsAttached(): void
    {
        $this->domainEventPublisher->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (ItemCreatedEvent $event): bool {
                self::assertSame('photo.png', $event->imageFilename);

                return true;
            }));

        $this->handler->handle(new CreateItemCommand(
            userId: self::USER_ID,
            name: 'Drill',
            description: 'A cordless drill',
            status: ItemStatus::Reserved,
            fileInfo: new FileInfoDto('photo.png', 'png', 'binary-content'),
        ));
    }

    public function testHandleDoesNotPublishWhenValidationFails(): void
    {
        $this->itemRepository->expects($this->never())->method('add');
        $this->domainEventPublisher->expects($this->never())->method('publish');

        $this->expectException(\InvalidArgumentException::class);

        $this->handler->handle(new CreateItemCommand(
            userId: self::USER_ID,
            name: '',
            description: 'A cordless drill',
            status: ItemStatus::Available,
        ));
    }

    private function createTranslator(): Translator
    {
        $translator = new Translator('ro');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            dirname(__DIR__, 4).'/translations/messages.ro.yaml',
            'ro',
        );

        return $translator;
    }
}
