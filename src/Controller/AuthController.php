<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\User\CreateUser\CreateUserCommand;
use App\Application\User\CreateUser\CreateUserHandler;
use App\Domain\User\Exception\EmailAndGroupAlreadyRegisteredException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AuthController extends AbstractController
{
    #[Route('/group/{uuid}/login', name: 'app_login', requirements: ['uuid' => GroupRoute::UUID], methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('household_items_index');
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('This method is intercepted by the logout key on the firewall.');
    }

    #[Route('/group/{uuid}/register', name: 'app_register', requirements: ['uuid' => GroupRoute::UUID], methods: ['GET', 'POST'])]
    public function register(string $uuid, Request $request, CreateUserHandler $createUserHandler): Response
    {
        $errors = [];
        $groupId = $uuid;

        if ($request->isMethod('POST')) {
            $email = (string) $request->request->get('email', '');
            $password = (string) $request->request->get('password', '');
            $passwordConfirmation = (string) $request->request->get('password_confirmation', '');
            $contactInfo = (string) $request->request->get('contactInfo', '');

            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Invalid or expired form submission, please try again.';
            } elseif ($password !== $passwordConfirmation) {
                $errors[] = 'Password confirmation does not match.';
            } else {
                try {
                    $createUserHandler->handle(new CreateUserCommand(
                        email: $email,
                        plainPassword: $password,
                        contactInfo: $contactInfo,
                        groupId: $groupId,
                    ));

                    $this->addFlash('success', 'Your account has been created, you can now log in.');

                    return $this->redirectToRoute('app_login');
                } catch (\InvalidArgumentException|EmailAndGroupAlreadyRegisteredException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        return $this->render('auth/register.html.twig', [
            'errors' => $errors,
            'email' => $email ?? '',
            'contactInfo' => $contactInfo ?? '',
        ]);
    }
}
