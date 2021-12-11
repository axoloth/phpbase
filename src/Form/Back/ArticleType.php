<?php

namespace App\Form\Back;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Repository\ArticleCategoryRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'article.label.title',
            ])
            ->add('description', TextType::class, [
                'label' => 'article.label.description',
            ])
            ->add('content', CKEditorType::class, [
                'label' => 'article.label.content',
            ])
            ->add('categories', EntityType::class, [
                'label' => 'article.label.categories',
                'label_html' => true,
                'class' => ArticleCategory::class,
                'query_builder' => function (ArticleCategoryRepository $er) {
                    return $er->createQueryBuilder('c')

                            ->orderBy('c.name', 'ASC');
                },
                'data' => $options['categories'],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'translation_domain' => 'back_messages',
            'categories' => [],
        ]);
    }
}
