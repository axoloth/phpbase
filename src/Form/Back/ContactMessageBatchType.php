<?php

namespace App\Form\Back;

use App\Entity\ContactMessage;
use App\Manager\Back\ContactMessageManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;

class ContactMessageBatchType extends AbstractType
{
    
    /**
     * 
     * @var ContactMessageManager     */
    private $contactMessageManager;
    
    /**
     *
     * @param ContactMessageManager $contactMessageManager 
     */
    public function __construct(ContactMessageManager $contactMessageManager)
    {
        $this->contactMessageManager = $contactMessageManager;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contact_messages', EntityType::class, [
                'label' => false,
                'choice_label' => false,
                'class' => ContactMessage::class,
                'choices' => $options['contact_messages'],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('action', ChoiceType::class, [
                'label' => false,
                'placeholder' => 'Action',
                'choices' => [
                    'action.delete' => 'delete',
                ],
                'multiple' => false,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {            
                $result = $this->contactMessageManager->validationBatchForm($event->getForm());
                if (true !== $result) {
                    $event->getForm()->addError(new FormError($result));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'contact_messages' => null,
            'translation_domain' => 'back_messages',
        ]);
    }
}
