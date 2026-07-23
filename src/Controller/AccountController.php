<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\UpdateUser\UpdateUserCommand;
use App\Application\UpdateUser\UpdateUserHandler;
use App\Domain\User\Exception\InvalidCurrentPasswordException;
use App\Domain\User\Exception\UserNotFoundException;
use App\Infrastructure\Security\SecurityUser;
use App\Service\Image\ImageUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    private const AVATAR_SUBDIRECTORY = 'images/avatars';

    #[Route('/account', name: 'account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UpdateUserHandler $updateUserHandler, ImageUploader $imageUploader): Response
    {
        $securityUser = $this->getUser();

        if (!$securityUser instanceof SecurityUser) {
            throw $this->createAccessDeniedException();
        }

        $currentUser = $securityUser->getUser();
        $errors = [];
        $contactInfo = $currentUser->getContactInfo();
        $avatarFilename = $currentUser->getAvatarFilename();

        if ($request->isMethod('POST')) {
            $contactInfo = (string) $request->request->get('contactInfo', '');
            $currentPassword = (string) $request->request->get('current_password', '');
            $newPassword = (string) $request->request->get('new_password', '');
            $newPasswordConfirmation = (string) $request->request->get('new_password_confirmation', '');
            $avatarFile = $request->files->get('avatar');
            $removeAvatar = $request->request->getBoolean('remove_avatar');

            if (!$this->isCsrfTokenValid('account_update', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Invalid or expired form submission, please try again.';
            } elseif ($newPassword !== $newPasswordConfirmation) {
                $errors[] = 'New password confirmation does not match.';
            } else {
                try {
                    $uploadedAvatarFilename = null;
                    $previousAvatarFilename = $currentUser->getAvatarFilename();

                    if ($avatarFile !== null) {
                        $uploadedAvatarFilename = $imageUploader->upload($avatarFile, self::AVATAR_SUBDIRECTORY);
                        $avatarFilename = $uploadedAvatarFilename;
                    } elseif ($removeAvatar) {
                        $avatarFilename = '';
                    }

                    $updateUserHandler->handle(new UpdateUserCommand(
                        email: (string) $currentUser->getEmail()->value,
                        currentPassword: $currentPassword,
                        contactInfo: $contactInfo,
                        newPassword: $newPassword !== '' ? $newPassword : null,
                        avatarFilename: $uploadedAvatarFilename,
                        removeAvatar: $removeAvatar,
                    ));

                    if ($previousAvatarFilename !== '' && ($uploadedAvatarFilename !== null || $removeAvatar)) {
                        $imageUploader->delete($previousAvatarFilename, self::AVATAR_SUBDIRECTORY);
                    }

                    $this->addFlash('success', 'Your account has been updated.');

                    return $this->redirectToRoute('account_edit');
                } catch (InvalidCurrentPasswordException|UserNotFoundException|\InvalidArgumentException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        return $this->render('account/edit.html.twig', [
            'errors' => $errors,
            'email' => (string) $currentUser->getEmail()->value,
            'contactInfo' => $contactInfo,
            'avatarFilename' => $avatarFilename,
        ]);
    }
}
