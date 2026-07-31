<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Item\CreateItem\CreateItemCommand;
use App\Application\Item\CreateItem\CreateItemHandler;
use App\Application\Item\FileInfoDto;
use App\Application\Item\ListGroupItems\ListGroupItemsHandler;
use App\Application\Item\ListGroupItems\ListGroupItemsQuery;
use App\Application\Item\ListUserItems\ListUserItemsHandler;
use App\Application\Item\ListUserItems\ListUserItemsQuery;
use App\Application\Item\UpdateItem\UpdateItemCommand;
use App\Application\Item\UpdateItem\UpdateItemHandler;
use App\Application\User\GetUser\UserView;
use App\Domain\Item\Exception\ItemAccessDeniedException;
use App\Domain\Item\Exception\ItemNotFoundException;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\Item\ItemStatus;
use App\Domain\ValueObject\FileValueObject;
use App\Service\Image\ImageUploader;
use App\Service\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/group/{uuid}', requirements: ['uuid' => GroupRoute::UUID])]
final class HouseholdItemsController extends AbstractController
{
    private const IMAGE_SUBDIRECTORY = 'images/items';

    #[Route('/items', name: 'household_items_index', methods: ['GET'])]
    public function index(string $uuid, ListUserItemsHandler $listUserItemsHandler): Response
    {
        $this->assertGroup($uuid);

        return $this->render('household_items/index.html.twig', [
            'items' => $listUserItemsHandler->handle(new ListUserItemsQuery($this->currentUser()->id)),
        ]);
    }

    #[Route('/items/group', name: 'household_items_group', methods: ['GET'])]
    public function group(string $uuid, ListGroupItemsHandler $listGroupItemsHandler): Response
    {
        $securityUser = $this->getUser();
        $currentUserId = $securityUser instanceof SecurityUser
            ? $securityUser->getUserView()->id
            : null;

        return $this->render('household_items/group.html.twig', [
            'items' => $listGroupItemsHandler->handle(new ListGroupItemsQuery($uuid)),
            'currentUserId' => $currentUserId,
        ]);
    }

    #[Route('/items/new', name: 'household_items_new', methods: ['GET', 'POST'])]
    public function new(
        string $uuid,
        Request $request,
        CreateItemHandler $addItemHandler,
    ): Response
    {
        $this->assertGroup($uuid);

        $errors = [];
        $name = '';
        $description = '';
        $status = ItemStatus::Available->value;
        $currentImageUrl = null;

        if ($request->isMethod('POST')) {
            $name = (string) $request->request->get('name', '');
            $description = (string) $request->request->get('description', '');
            $status = (string) $request->request->get('status', '');
            /** @var UploadedFile $imageFile */
            $imageFile = $request->files->get('image');

            if (!$this->isCsrfTokenValid('item_form', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Invalid or expired form submission, please try again.';
            } else {
                try {
                    $addItemHandler->handle(new CreateItemCommand(
                        userId: $this->currentUser()->id,
                        name: $name,
                        description: $description,
                        status: ItemStatus::from($status),
                        fileInfo: $imageFile ? new FileInfoDto(
                            fileName: $imageFile->getClientOriginalName(),
                            fileExtension: strtolower($imageFile->getClientOriginalExtension()),
                            fileContent: $imageFile->getContent()
                        ) : null,
                    ));

                    $this->addFlash('success', 'Item added.');

                    return $this->redirectToRoute('household_items_index');
                } catch (\ValueError) {
                    $errors[] = 'Please choose a valid status.';
                } catch (\InvalidArgumentException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        return $this->render('household_items/form.html.twig', [
            'errors' => $errors,
            'formAction' => $this->generateUrl('household_items_new'),
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'statuses' => ItemStatus::cases(),
            'isEdit' => false,
            'currentImageUrl' => $currentImageUrl,
        ]);
    }

    #[Route('/items/{id}/edit', name: 'household_items_edit', methods: ['GET', 'POST'])]
    public function edit(string $uuid, Request $request, string $id, ItemRepositoryInterface $itemRepository, UpdateItemHandler $updateItemHandler, ImageUploader $imageUploader): Response
    {
        $this->assertGroup($uuid);

        $item = $itemRepository->findById($id);

        if ($item === null || $item->getUserId()->value !== $this->currentUser()->id) {
            throw $this->createNotFoundException('Item not found.');
        }

        $errors = [];
        $name = $item->getName();
        $description = $item->getDescription();
        $status = $item->getStatus();
        //todo add url dynamic
        $currentImageUrl = '';

        if ($request->isMethod('POST')) {
            $name = (string) $request->request->get('name', '');
            $description = (string) $request->request->get('description', '');
            $status = (string) $request->request->get('status', '');
            $imageFile = $request->files->get('image');

            if (!$this->isCsrfTokenValid('item_form', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Invalid or expired form submission, please try again.';
            } else {
                try {
                    $updateItemHandler->handle(new UpdateItemCommand(
                        itemId: $id,
                        userId: $this->currentUser()->id,
                        name: $name,
                        description: $description,
                        status: ItemStatus::from($status),
                        fileInfo: $imageFile ? new FileInfoDto(
                            fileName: $imageFile->getClientOriginalName(),
                            fileExtension: strtolower($imageFile->getClientOriginalExtension()),
                            fileContent: $imageFile->getContent()
                        ) : null,
                    ));

                    $this->addFlash('success', 'Item updated.');

                    return $this->redirectToRoute('household_items_index');
                } catch (\ValueError) {
                    $errors[] = 'Please choose a valid status.';
                } catch (ItemNotFoundException|ItemAccessDeniedException|\InvalidArgumentException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        return $this->render('household_items/form.html.twig', [
            'errors' => $errors,
            'formAction' => $this->generateUrl('household_items_edit', ['id' => $id]),
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'statuses' => ItemStatus::cases(),
            'isEdit' => true,
            'currentImageUrl' => $currentImageUrl,
        ]);
    }

    private function currentUser(): UserView
    {
        $securityUser = $this->getUser();

        if (!$securityUser instanceof SecurityUser) {
            throw $this->createAccessDeniedException();
        }

        return $securityUser->getUserView();
    }

    /**
     * The group in the URL must match the authenticated user's own group.
     */
    private function assertGroup(string $uuid): void
    {
        if (strcasecmp($uuid, $this->currentUser()->groupId) !== 0) {
            throw $this->createAccessDeniedException();
        }
    }
}
