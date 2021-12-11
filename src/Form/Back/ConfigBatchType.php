<?php

namespace App\Form\Back;

use App\Entity\Config;
use App\Manager\Back\ConfigManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigBatchType extends AbstractType
{
    /**
     * @var ConfigManager     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('configs', EntityType::class, [
                'label' => false,
                'choice_label' => false,
                'class' => Config::class,
                'choices' => $options['configs'],
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
                $result = $this->configManager->validationBatchForm($event->getForm());
                if (true !== $result) {
                    $event->getForm()->addError(new FormError($result));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'configs' => null,
            'translation_domain' => 'back_messages',
        ]);
    }
}
