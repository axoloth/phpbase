<?php

namespace App\EventListener;

use App\Repository\ArticleCategoryRepository;
use App\Repository\ConfigRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

class GlobalDataListener
{
    private $configRepository;
    private $articleCategoryRepository;
    private $environment;

    public function __construct(ConfigRepository $configRepository, ArticleCategoryRepository $articleCategoryRepository, Environment $environment)
    {
        $this->configRepository = $configRepository;
        $this->articleCategoryRepository = $articleCategoryRepository;
        $this->environment = $environment;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $this->environment->addGlobal('config', $this->configRepository->findOneByName('app')->get());
        $this->environment->addGlobal('menu_categories', $this->articleCategoryRepository->findMenuRoots());
    }
}
