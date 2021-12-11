<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/locale")
 */
class LocaleController extends AbstractController
{
    /**
     * @Route("/update", name="locale_update")
     */
    public function update(Session $session, Request $request, UrlGeneratorInterface $urlGenerator)
    {
        $locale = $request->query->get('lang');
        $request->setLocale($locale);
        $session->set('_locale', $locale);
        $backRoute = $request->query->get('route');
        $backParams = $request->query->get('params', null);
        $backQuery = $request->query->get('query', null);
        $parameters = [];
        if (null !== $backParams) {
            $parameters = $backParams;
        }
        if (null !== $backQuery) {
            $parameters = array_merge($parameters, $backQuery);
        }
        if (!array_key_exists('_locale', $parameters)) {
            array_merge($parameters, ['_locale' => $locale]);
        }
        $url = $urlGenerator->generate($backRoute, $parameters);

        return $this->redirect($url);
    }
}
