<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/back")
 */
class PageController extends AbstractController
{
    /**
     * @Route("/", name="back_home")
     */
    public function index()
    {
        return $this->render('back/page/index.html.twig', [
        ]);
    }
}
