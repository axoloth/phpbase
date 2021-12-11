<?php

namespace App\Mailer;

use App\Entity\ContactMessage;
use App\Entity\User;
use App\Repository\ConfigRepository;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class Mailer
{
    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $fromEmail;

    /**
     * Mailer constructor.
     */
    protected $appConfig;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $router, Environment $templating, TranslatorInterface $translator, ParameterBagInterface $parameterBag, ConfigRepository $configRepository)
    {
        $this->mailer = $mailer;
        $this->router = $router;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->fromEmail = $parameterBag->get('app.from_email');
        $this->appConfig = $configRepository->findOneByName('app')->get();
    }

    public function sendRegistration(User $user, string $locale)
    {
        $url = $this->router->generate(
            'app_registration_confirm',
            [
                '_locale' => $locale,
                'token' => $user->getConfirmationToken(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $email = (new NotificationEmail())
            ->from(new Address(
                $this->fromEmail,
                $this->appConfig['name'],
            ))
            ->to($user->getEmail())
            ->subject($this->translator->trans('registration.email.subject', ['%user%' => $user], 'security'))
            ->htmlTemplate('front/email/register.html.twig')
            ->context([
                'user' => $user,
                'footer_text' => $this->appConfig['name'],
                'footer_url' => $this->router->generate(
                    'front_home',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ])
            ->action($this->translator->trans('registration.email.action', [], 'security'), $url);
        $this->mailer->send($email);
    }

    public function sendForgetPassword(User $user, string $locale)
    {
        $url = $this->router->generate(
            'app_reset_password',
            [
                '_locale' => $locale,
                'token' => $user->getConfirmationToken(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $email = (new NotificationEmail())
            ->from(new Address(
                $this->fromEmail,
                $this->appConfig['name']
            ))
            ->to($user->getEmail())
            ->subject($this->translator->trans('forget_password.email.subject', [], 'security'))
            ->htmlTemplate('security/email/forget_password.html.twig')
            ->context([
                'user' => $user,
                'footer_text' => $this->appConfig['name'],
                'footer_url' => $this->router->generate(
                    'front_home',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ])
            ->action($this->translator->trans('forget_password.email.action', [], 'security'), $url);
        $this->mailer->send($email);
    }

    public function sendResetEmailCheck(User $user, string $newEmail, string $locale)
    {
        $url = $this->router->generate(
            'app_reset_email',
            [
                'token' => $user->getConfirmationToken(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $email = (new NotificationEmail())
            ->from(new Address(
                $this->fromEmail,
                $this->appConfig['name']
            ))
            ->to($newEmail)
            ->subject($this->translator->trans('reset_email.email.subject', [], 'security'))
            ->htmlTemplate('security/email/reset_email.html.twig')
            ->context([
                'user' => $user,
                'new_email' => $newEmail,
                'footer_text' => $this->appConfig['name'],
                'footer_url' => $this->router->generate(
                    'front_home',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ])
            ->action($this->translator->trans('reset_email.email.action', [], 'security'), $url);
        $this->mailer->send($email);
    }

    public function sendInvitation(User $user, string $password)
    {
        $url = $this->router->generate(
            'app_registration_confirm',
            [
                'token' => $user->getConfirmationToken(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $email = (new NotificationEmail())
            ->from(new Address(
                $this->fromEmail,
                $this->appConfig['name'],
            ))
            ->to($user->getEmail())
            ->subject(
                $this->translator->trans('invitation.email.subject', [], 'back_messages')
            )
            ->htmlTemplate('back/email/invite.html.twig')
            ->context([
                'user' => $user,
                'password' => $password,
                'footer_text' => $this->appConfig['name'],
                'footer_url' => $this->router->generate(
                    'front_home',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ])
            ->action($this->translator->trans('invitation.email.action', [], 'back_messages'), $url);
        $this->mailer->send($email);
    }

    public function sendContactMessage(ContactMessage $contactMessage)
    {
        $email = (new NotificationEmail())
            ->from(new Address(
                $this->fromEmail,
                $this->appConfig['name'],
            ))
            ->to($this->appConfig['admin_email'])
            ->subject('🔔 Notification - Message')
            ->htmlTemplate('front/email/contact_message.html.twig')
            ->context([
                'contact_message' => $contactMessage,
                'footer_text' => $this->appConfig['name'],
                'footer_url' => $this->router->generate(
                    'front_home',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ])
            ->action("Cliquer ici pour l'ouvrir dans l'application", $this->router->generate(
                'back_contact_message_read',
                ['id' => $contactMessage->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ))
            ->importance(NotificationEmail::IMPORTANCE_MEDIUM)
            ->replyTo($contactMessage->getEmail());
        $this->mailer->send($email);
    }
}