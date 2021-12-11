<?php

namespace App\Form\Front;

use App\Entity\ContactMessage;
use App\Form\Recaptcha3SubmitType;
use App\Repository\ConfigRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactMessageType extends AbstractType
{
    private $recaptchaActivated;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->recaptchaActivated = $configRepository->findOneByName('app')->get()['recaptcha_activated'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'contact_message.label.firstname',
            ])
            ->add('lastname', TextType::class, [
                'label' => 'contact_message.label.lastname',
            ])
            ->add('email', EmailType::class, [
                'label' => 'contact_message.label.email',
            ])
            ->add('phone', TelType::class, [
                'label' => 'contact_message.label.phone',
            ])
            ->add('subject', TextType::class, [
                'label' => 'contact_message.label.subject',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'contact_message.label.message',
                'attr' => [
                    'rows' => '5',
                    'cols' => '50',
                ],
            ]);
        if ($this->recaptchaActivated) {
            $builder
            ->add('submit', Recaptcha3SubmitType::class, [
                'parent_builder' => $builder,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
            'translation_domain' => 'front_messages',
        ]);
    }
}