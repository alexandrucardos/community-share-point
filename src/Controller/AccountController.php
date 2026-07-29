<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\UpdateUser\UpdateUserCommand;
use App\Application\UpdateUser\UpdateUserHandler;
use App\Domain\User\Exception\InvalidCurrentPasswordException;
use App\Domain\User\Exception\UserNotFoundException;
use App\Service\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/group/{uuid}/account', name: 'account_edit', requirements: ['uuid' => GroupRoute::UUID], methods: ['GET', 'POST'])]
    public function edit(string $uuid, Request $request, UpdateUserHandler $updateUserHandler): Response
    {
        $securityUser = $this->getUser();

        if (!$securityUser instanceof SecurityUser) {
            throw $this->createAccessDeniedException();
        }

        $currentUser = $securityUser->getLoadUserDto();

        if (strcasecmp($uuid, $currentUser->groupId) !== 0) {
            throw $this->createAccessDeniedException();
        }
        $errors = [];
        $contactInfo = $currentUser->contactInfo;

        if ($request->isMethod('POST')) {
            $contactInfo = (string) $request->request->get('contactInfo', '');
            $currentPassword = (string) $request->request->get('current_password', '');
            $newPassword = (string) $request->request->get('new_password', '');
            $newPasswordConfirmation = (string) $request->request->get('new_password_confirmation', '');

            if (!$this->isCsrfTokenValid('account_update', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Invalid or expired form submission, please try again.';
            } elseif ($newPassword !== $newPasswordConfirmation) {
                $errors[] = 'New password confirmation does not match.';
            } else {
                try {
                    $updateUserHandler->handle(new UpdateUserCommand(
                        email: (string) $currentUser->email,
                        currentPassword: $currentPassword,
                        contactInfo: $contactInfo,
                        groupId: (string) $currentUser->groupId,
                        newPassword: $newPassword !== '' ? $newPassword : null,
                    ));

                    $this->addFlash('success', 'Your account has been updated.');

                    return $this->redirectToRoute('account_edit');
                } catch (InvalidCurrentPasswordException|UserNotFoundException|\InvalidArgumentException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        return $this->render('account/edit.html.twig', [
            'errors' => $errors,
            'email' => (string) $currentUser->email,
            'contactInfo' => $contactInfo,
        ]);
    }
}
