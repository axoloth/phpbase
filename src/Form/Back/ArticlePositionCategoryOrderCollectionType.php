<?php

namespace App\Form\Back;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticlePositionCategoryOrderCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('articlePositionCategories', CollectionType::class, [
                'label' => false,
                'entry_options' => [
                    //'label' => false,
                    'position' => $options['position'],
                ],
                'entry_type' => ArticlePositionCategoryOrderType::class,
                'data' => $options['article_position_categories'],
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
            'article_position_categories' => null,
            'position' => null,
            'translation_domain' => 'back_messages',
        ]);
    }

    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        foreach ($view['articlePositionCategories']->children as $key => &$childView) {
            if ('positionArticle' == $options['position']) {
                $childView->vars['label'] = $childView->vars['value']->getArticle()->getTitle();
            } elseif ('positionCategory' == $options['position']) {
                $childView->vars['label'] = $childView->vars['value']->getCategory()->getName();
            }
        }
    }
}
