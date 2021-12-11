<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgetPasswordFormType;
use App\Form\ResetEmailFormType;
use App\Form\ResetPasswordFormType;
use App\Mailer\Mailer;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var LoginFormAuthenticator
     */
    private $authenticator;

    /**
     * @var GuardAuthenticatorHandler
     */
    private $guardHandler;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        UserRepository $userRepository,
        LoginFormAuthenticator $authenticator,
        GuardAuthenticatorHandler $guardHandler,
        TranslatorInterface $translator
    ) {
        $this->userRepository = $userRepository;
        $this->authenticator = $authenticator;
        $this->guardHandler = $guardHandler;
        $this->translator = $translator;
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('front_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    /**
     * @Route("/registration_confirm", name="app_registration_confirm")
     */
    public function registrationConfirm(Request $request): Response
    {
        $token = $request->query->get('token', '');
        $user = $this->userRepository->findOneByConfirmationToken($token);
        if (null === $user || false === \strpos($token, 'register')) {
            throw $this->createNotFoundException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $this->getDoctrine()->getManager()->flush();

        $msg = $this->translator->trans('registration.flash.confirmed', ['%user%' => $user], 'security');
        $this->addFlash('success', $msg);

        return $this->guardHandler->authenticateUserAndHandleSuccess(
            $user,
            $request,
            $this->authenticator,
            'main' // firewall name in security.yaml
        );
    }

    /**
     * @Route("/forget_password", name="app_forget_password")
     */
    public function forgetPassword(Request $request, Mailer $mailer): Response
    {
        $form = $this->createForm(ForgetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->userRepository
                ->findOneByEmail($form->get('email')->getData());
            if ($user) {
                $user->setConfirmationToken('forget_password_'.bin2hex(random_bytes(24)));
                $this->getDoctrine()->getManager()->flush();
                $mailer->sendForgetPassword($user, $request->getLocale());
                $msg = $this->translator->trans('forget_password.flash.check_email', ['%user%' => $user], 'security');
                $this->addFlash('success', $msg);
            }

            return $this->redirectToRoute('front_home');
        }

        return $this->render('security/forget_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reset_password/{id}", defaults={"id"=null}, name="app_reset_password")
     */
    public function resetPassword(Request $request, UserPasswordEncoderInterface $passwordEncoder, User $user = null): response
    {
        if ($token = $request->query->get('token', '')) {
            $user = $this->userRepository->findOneByConfirmationToken($token);
            if (!$user || false === \strpos($token, 'forget_password')) {
                throw $this->createNotFoundException(sprintf('The user with confirmation token "%s" does not exist', $token));
            }
        } elseif (!$user) {
            throw new LogicException('No user selected.');
        }
        $form = $this->createForm(ResetPasswordFormType::class, null, [
            'with_token' => '' !== $token,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordEncoder->encodePassword($user, $form->get('plainPassword')->getData()));
            if ($token) {
                $user->setConfirmationToken(null);
            }
            $this->getDoctrine()->getManager()->flush();
            $msg = $this->translator->trans('reset_password.flash.success', [], 'security');
            $this->addFlash('info', $msg);

            return $this->guardHandler->authenticateUserAndHandleSuccess($user, $request, $this->authenticator, 'main');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reset_email", name="app_reset_email")
     */
    public function resetEmail(Request $request, Mailer $mailer): Response
    {
        $token = $request->query->get('token');
        $form = $this->createForm(ResetEmailFormType::class, null, [
            'confirm' => $token ? true : false,
        ]);
        $form->handleRequest($request);
        if ($token) {
            $user = $this->userRepository->findOneByConfirmationToken($token);
            if (!$user) {
                throw $this->createNotFoundException(sprintf('The user with confirmation token "%s" does not exist', $token));
            }
            $tokenArray = explode('-_reset_email_-', $token);
            if (!$email = ($tokenArray[0] ?? null)) {
                throw $this->createAccessDeniedException();
            }
            if ($form->isSubmitted() && $form->isValid()) {
                $user->setEmail($email)
                    ->setConfirmationToken(null);
                $this->getDoctrine()->getManager()->flush();
                $msg = $this->translator->trans('reset_email.flash.success', ['%user%' => $user], 'security');
                $this->addFlash('success', $msg);

                return $this->guardHandler->authenticateUserAndHandleSuccess($user, $request, $this->authenticator, 'main');
            }
        } elseif ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $email = $form->get('email')->getData();
            if ($user) {
                $user->setConfirmationToken($email.'-_reset_email_-'.bin2hex(random_bytes(24)));
                $this->getDoctrine()->getManager()->flush();
                $mailer->sendResetEmailCheck($user, $email, $request->getLocale());
                $msg = $this->translator->trans('reset_email.flash.check_email', ['%user%' => $user], 'security');
                $this->addFlash('success', $msg);
            }

            return $this->redirectToRoute('front_home');
        }

        return $this->render('security/reset_email.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
