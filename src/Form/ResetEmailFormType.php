<?php

namespace App\Form;

use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResetEmailFormType extends AbstractType
{
    private $userRepository;
    private $translator;

    public function __construct(UserRepository $userRepository, TranslatorInterface $translator)
    {
        $this->userRepository = $userRepository;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['confirm']) {
            $builder
                ->add('password', PasswordType::class, [
                    'label' => 'reset_email.label.password',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'registration.message.not_blank',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'registration.message.password_length_min',
                            // max length allowed by Symfony for security reasons
                            'max' => 4096,
                        ]),
                        new UserPassword([
                            'message' => 'reset_email.message.password_wrong',
                        ]),
                    ],
                ])
            ;
        } else {
            $builder
                ->add('email', RepeatedType::class, [
                    'type' => EmailType::class,
                    'invalid_message' => 'reset_email.message.repeated_email_invalid',
                    'first_options' => ['label' => 'reset_email.label.email'],
                    'second_options' => ['label' => 'reset_email.label.repeat_email'],
                    'required' => true,
                ])
                ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                    $email = $event->getForm()->get('email')->getData();
                    $user = $this->userRepository->findOneByEmail($email);
                    if (null !== $user) {
                        $event->getForm()->addError(new FormError($this->translator->trans('reset_email.message.email_already_in_use', [], 'validators')));
                    }
                })
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'security',
            'confirm' => false,
        ]);
    }
}
