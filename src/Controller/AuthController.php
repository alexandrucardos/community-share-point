<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\User\CreateUser\CreateUserCommand;
use App\Application\User\CreateUser\CreateUserHandler;
use App\Application\User\RequestLoginOtp\RequestLoginOtpCommand;
use App\Application\User\RequestLoginOtp\RequestLoginOtpHandler;
use App\Domain\User\Exception\EmailAndGroupAlreadyRegisteredException;
use App\Service\Security\OtpLoginAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AuthController extends AbstractController
{
    #[Route('/group/{uuid}/login', name: 'app_login', requirements: ['uuid' => GroupRoute::UUID], methods: ['GET', 'POST'])]
    public function login(
        string $uuid,
        Request $request,
        RequestLoginOtpHandler $requestLoginOtp,
        AuthenticationUtils $authenticationUtils,
    ): Response {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('household_items_index');
        }

        $errors = [];
        $email = $authenticationUtils->getLastUsername();

        if ($request->isMethod('POST')) {
            $email = (string) $request->request->get('email', '');

            if (!$this->isCsrfTokenValid('request_login_otp', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Invalid or expired form submission, please try again.';
            } else {
                try {
                    $requestLoginOtp->handle(new RequestLoginOtpCommand(
                        email: $email,
                        groupId: $uuid,
                    ));

                    $request->getSession()->set(OtpLoginAuthenticator::SESSION_EMAIL, $email);

                    $this->addFlash('success', 'If that email is registered in this group, a login code is on its way.');

                    return $this->redirectToRoute('app_login_verify');
                } catch (\InvalidArgumentException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        return $this->render('auth/login.html.twig', [
            'errors' => $errors,
            'email' => $email,
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/group/{uuid}/login/verify', name: 'app_login_verify', requirements: ['uuid' => GroupRoute::UUID], methods: ['GET', 'POST'])]
    public function verify(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $email = (string) $request->getSession()->get(OtpLoginAuthenticator::SESSION_EMAIL, '');

        if ($email === '') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/login_verify.html.twig', [
            'email' => $email,
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
            $contactInfo = (string) $request->request->get('contactInfo', '');

            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_csrf_token'))) {
                $errors[] = 'Invalid or expired form submission, please try again.';
            } else {
                try {
                    $createUserHandler->handle(new CreateUserCommand(
                        email: $email,
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
