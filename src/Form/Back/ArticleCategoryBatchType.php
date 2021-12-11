<?php

namespace App\Form\Back;

use App\Entity\ArticleCategory;
use App\Manager\Back\ArticleCategoryManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleCategoryBatchType extends AbstractType
{
    /**
     * @var ArticleCategoryManager     */
    private $articleCategoryManager;

    public function __construct(ArticleCategoryManager $articleCategoryManager)
    {
        $this->articleCategoryManager = $articleCategoryManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('article_categories', EntityType::class, [
                'label' => false,
                'choice_label' => false,
                'class' => ArticleCategory::class,
                'choices' => $options['article_categories'],
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
                $result = $this->articleCategoryManager->validationBatchForm($event->getForm());
                if (true !== $result) {
                    $event->getForm()->addError(new FormError($result));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'article_categories' => null,
            'translation_domain' => 'back_messages',
        ]);
    }
}
