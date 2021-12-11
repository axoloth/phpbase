<?php

namespace App\EntityListener;

use App\Entity\ArticleCategory;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleCategoryEntityListener
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function prePersist(ArticleCategory $articleCategory, LifecycleEventArgs $event)
    {
        $articleCategory->computeSlug($this->slugger);
    }

    public function preUpdate(ArticleCategory $articleCategory, LifecycleEventArgs $event)
    {
        $articleCategory->computeSlug($this->slugger);
    }
}