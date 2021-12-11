<?php

namespace App\Form\Back;

use App\Entity\ArticlePositionCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticlePositionCategoryOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add($options['position'], HiddenType::class, [
                'attr' => [
                    'class' => 'position',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ArticlePositionCategory::class,
            'translation_domain' => 'back_messages',
            'position' => null,
        ]);
    }
}
