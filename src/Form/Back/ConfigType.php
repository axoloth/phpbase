<?php

namespace App\Form\Back;

use App\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $builder->getData();

        $builder
            ->add('name', TextType::class, [
                'label' => 'config.label.name',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'config.label.type',
                'choices' => [
                    'string' => 'string',
                    'integer' => 'integer',
                    'number' => 'number',
                    'json' => 'json',
                ],
                'data' => $config->getType(),
                'mapped' => false,
            ])
            ->add('value', TextareaType::class, [
                'label' => 'config.label.value',
                'data' => $config->display(),
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
            'translation_domain' => 'back_messages',
            'constraints' => [
                new Callback([$this, 'validate']),
            ],
        ]);
    }

    /**
     * Callback
     * Validation dynamique du formulaire sur plusieurs champs.
     *
     * @param array $data
     */
    public function validate($data, ExecutionContextInterface $context)
    {
        /**
         * @var \Symfony\Component\Form\Form $form
         */
        $form = $context->getRoot();
        if ('json' == $form->get('type')->getData()) {
            $validator = Validation::createValidator();
            $violations = $validator->validate($form->get('value')->getData(), [new Json([])]);
            if (0 !== count($violations)) {
                foreach ($violations as $violation) {
                    $form->get('value')->addError(new FormError($violation->getMessage()));
                }
            }
        }
    }
}
