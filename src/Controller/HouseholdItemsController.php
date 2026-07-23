<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\AddItem\AddItemCommand;
use App\Application\AddItem\AddItemHandler;
use App\Application\ListGroupItems\ListGroupItemsHandler;
use App\Application\ListGroupItems\ListGroupItemsQuery;
use App\Application\ListUserItems\ListUserItemsHandler;
use App\Application\ListUserItems\ListUserItemsQuery;
use App\Application\UpdateItem\UpdateItemCommand;
use App\Application\UpdateItem\UpdateItemHandler;
use App\Domain\Item\Exception\ItemAccessDeniedException;
use App\Domain\Item\Exception\ItemNotFoundException;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\Item\ItemStatus;
use App\Domain\User\UserEntity;
use App\Infrastructure\Security\SecurityUser;
use App\Service\Image\ImageUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HouseholdItemsController extends AbstractController
{
    private const IMAGE_SUBDIRECTORY = 'images/items';

    #[Route('/items', name: 'household_items_index', methods: ['GET'])]
    public function index(ListUserItemsHandler $listUserItemsHandler): Response
    {
        return $this->render('household_items/index.html.twig', [
            'items' => $listUserItemsHandler->handle(new ListUserItemsQuery($this->currentUser()->getId()->value)),
        ]);
    }

    #[Route('/items/group', name: 'household_items_group', methods: ['GET'])]
    public function group(ListGroupItemsHandler $listGroupItemsHandler): Response
    {
        return $this->render('household_items/group.html.twig', [
            'items' => $listGroupItemsHandler->handle(new ListGroupItemsQuery($this->currentUser()->getGroupId())),
            'currentUserId' => $this->currentUser()->getId()->value,
        ]);
    }

    #[Route('/items/new', name: 'household_items_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AddItemHandler $addItemHandler, ImageUploader $imageUploader): Response
    {
        $errors = [];
        $name = '';
        $description = '';
        $status = ItemStatus::Available->value;
        $currentImageUrl = null;

        if ($request->isMethod('POST')) {
            $name = (string) $request->request->get('name', '');
            $description = (string) $request->request->get('description', '');
            $status = (string) $request->request->get('status', '');
            $imageFile = $request->files->get('image');

            if (!$this->isCsrfTokenValid('item_form', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Invalid or expired form submission, please try again.';
            } else {
                try {
                    $imageFilename = $imageFile !== null
                        ? $imageUploader->upload($imageFile, self::IMAGE_SUBDIRECTORY)
                        : null;

                    $addItemHandler->handle(new AddItemCommand(
                        userId: $this->currentUser()->getId()->value,
                        name: $name,
                        description: $description,
                        status: ItemStatus::from($status),
                        imageFilename: $imageFilename,
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
    public function edit(Request $request, string $id, ItemRepositoryInterface $itemRepository, UpdateItemHandler $updateItemHandler, ImageUploader $imageUploader): Response
    {
        $item = $itemRepository->findById($id);

        if ($item === null || $item->getUserId() !== $this->currentUser()->getId()->value) {
            throw $this->createNotFoundException('Item not found.');
        }

        $errors = [];
        $name = $item->getName();
        $description = $item->getDescription();
        $status = $item->getStatus();
        $currentImageUrl = $item->getImageUrl();

        if ($request->isMethod('POST')) {
            $name = (string) $request->request->get('name', '');
            $description = (string) $request->request->get('description', '');
            $status = (string) $request->request->get('status', '');
            $imageFile = $request->files->get('image');

            if (!$this->isCsrfTokenValid('item_form', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Invalid or expired form submission, please try again.';
            } else {
                try {
                    $imageFilename = $imageFile !== null
                        ? $imageUploader->upload($imageFile, self::IMAGE_SUBDIRECTORY)
                        : null;

                    $updateItemHandler->handle(new UpdateItemCommand(
                        itemId: $id,
                        userId: $this->currentUser()->getId()->value,
                        name: $name,
                        description: $description,
                        status: ItemStatus::from($status),
                        imageFilename: $imageFilename,
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

    private function currentUser(): UserEntity
    {
        $securityUser = $this->getUser();

        if (!$securityUser instanceof SecurityUser) {
            throw $this->createAccessDeniedException();
        }

        return $securityUser->getUser();
    }
}
