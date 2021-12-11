<?php

namespace App\Form\Back;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleCategoryOrderCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('categories', CollectionType::class, [
                'label' => false,
                'entry_options' => [
                    //'label' => false,
                ],
                'entry_type' => ArticleCategoryOrderType::class,
                'data' => $options['categories'],
                'allow_add' => false,
                'allow_delete' => false,
                'prototype' => false,
                'attr' => [
                    'class' => 'collection-selector',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'categories' => null,
            'translation_domain' => 'back_messages',
        ]);
    }

    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        foreach ($view['categories']->children as $key => &$childView) {
            $childView->vars['label'] = $childView->vars['value']->getName();
        }
    }
}
