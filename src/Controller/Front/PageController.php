<?php

namespace App\Controller\Front;

use App\Entity\Article;
use App\Entity\ContactMessage;
use App\Form\Front\ContactMessageType;
use App\Mailer\Mailer;
use App\Repository\ArticleCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageController extends AbstractController
{
    /**
     * @Route("/", name="front_home")
     */
    public function index(ArticleCategoryRepository $articleCategoryRepository)
    {
        return $this->render('front/page/index.html.twig', [
            'categories' => $articleCategoryRepository->findByDisplayedHome(true),
        ]);
    }

    /**
     * @Route("/contact", name="front_page_contact", methods="GET|POST")
     */
    public function contact(Request $request, TranslatorInterface $translator, Mailer $mailer)
    {
        $contactMessage = new ContactMessage();
        $form = $this->createForm(ContactMessageType::class, $contactMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contactMessage);
            $em->flush();
            $mailer->sendContactMessage($contactMessage);
            $msg = $translator->trans('contact_message.create.flash.success', [], 'front_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('front_home');
        }

        return $this->render('front/page/contact.html.twig', [
            'contact_message' => $contactMessage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}", priority=-100, name="front_page_article", methods="GET")
     */
    public function article(Article $article)
    {
        return $this->render('front/page/article.html.twig', [
            'article' => $article,
        ]);
    }
}