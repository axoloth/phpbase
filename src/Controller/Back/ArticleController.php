<?php

namespace App\Controller\Back;

use App\Entity\Article;
use App\Entity\ArticlePositionCategory;
use App\Form\Back\ArticleBatchType;
use App\Form\Back\ArticleFilterType;
use App\Form\Back\ArticlePositionCategoryOrderCollectionType;
use App\Form\Back\ArticleType;
use App\Manager\Back\ArticleManager;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/back/article")
 */
class ArticleController extends AbstractController
{
    /**
     * @var ArticleRepository     */
    private $articleRepository;

    /**
     * @var ArticleManager     */
    private $articleManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ArticleRepository $articleRepository, ArticleManager $articleManager, TranslatorInterface $translator)
    {
        $this->articleRepository = $articleRepository;
        $this->articleManager = $articleManager;
        $this->translator = $translator;
    }

    /**
     * @Route("/search/{page}", name="back_article_search", methods="GET|POST")
     */
    public function search(Request $request, Session $session, $page = null)
    {
        $page ?: $page = $session->get('back_article_page', 1);
        $formFilter = $this->createForm(ArticleFilterType::class, null, ['action' => $this->generateUrl('back_article_search', ['page' => 1])]);
        $formFilter->handleRequest($request);
        $data = $this->articleManager->configFormFilter($formFilter)->getData();
        $articles = $this->articleRepository->searchBack($request, $session, $data, $page);
        $queryData = $this->articleManager->getQueryData($data);
        $formBatch = $this->createForm(ArticleBatchType::class, null, [
            'action' => $this->generateUrl('back_article_search', array_merge(['page' => $page], $queryData)),
            'articles' => $articles,
        ]);
        $formBatch->handleRequest($request);
        if ($formBatch->isSubmitted() && $formBatch->isValid()) {
            $url = $this->articleManager->dispatchBatchForm($formBatch);
            if ($url) {
                return $this->redirect($url);
            }
        }

        return $this->render('back/article/search/index.html.twig', [
            'articles' => $articles,
            'form_filter' => $formFilter->createView(),
            'form_batch' => $formBatch->createView(),
            'form_delete' => $this->createFormBuilder()->getForm()->createView(),
            'number_page' => ceil(count($articles) / $formFilter->get('number_by_page')->getData()) ?: 1,
            'page' => $page,
            'query_data' => $queryData,
        ]);
    }

    /**
     * @Route("/create", name="back_article_create", methods="GET|POST")
     */
    public function create(Request $request): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setAuthor($this->getUser());
            foreach ($form->get('categories')->getData() as $category) {
                $articlePositionCategory = new ArticlePositionCategory();
                $category->addPositionArticle($articlePositionCategory);
                $article->addPositionCategory($articlePositionCategory);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();
            $msg = $this->translator->trans('article.create.flash.success', ['%identifier%' => $article], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_article_image_update', ['id' => $article->getId()]);
        }

        return $this->render('back/article/create.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/read/{id}", name="back_article_read", methods="GET")
     */
    public function read(Article $article): Response
    {
        return $this->render('back/article/read.html.twig', [
            'article' => $article,
            'form_delete' => $this->createFormBuilder()->getForm()->createView(),
        ]);
    }

    /**
     * @Route("/update/{id}", name="back_article_update", methods="GET|POST")
     */
    public function update(Request $request, Article $article): Response
    {
        $oldCategories = $article->getCategories();
        $form = $this->createForm(ArticleType::class, $article, ['categories' => $oldCategories]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categories = $form->get('categories')->getData();
            foreach ($oldCategories as $category) {
                if (false === array_search($category, $categories, true)) {
                    $article->removeCategory($category);
                }
            }
            foreach ($categories as $category) {
                if (false === array_search($category, $oldCategories, true)) {
                    $articlePositionCategory = new ArticlePositionCategory();
                    $category->addPositionArticle($articlePositionCategory);
                    $article->addPositionCategory($articlePositionCategory);
                }
            }
            $this->getDoctrine()->getManager()->flush();
            $msg = $this->translator->trans('article.update.flash.success', [], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_article_read', ['id' => $article->getId()]);
        }

        return $this->render('back/article/update.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete", name="back_article_delete", methods="GET|POST")
     */
    public function delete(Request $request): Response
    {
        $articles = $this->articleManager->getArticles();
        $formBuilder = $this->createFormBuilder();
        $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($articles) {
            $result = $this->articleManager->validationDelete($articles);
            if (true !== $result) {
                $event->getForm()->addError(new FormError($result));
            }
        });
        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($articles as $article) {
                $em->remove($article);
            }
            try {
                $em->flush();
                $this->addFlash('success', $this->translator->trans('article.delete.flash.success', [], 'back_messages'));
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->addFlash('warning', $e->getMessage());
            }

            return $this->redirectToRoute('back_article_search');
        }

        return $this->render('back/article/delete.html.twig', [
            'articles' => $articles,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/categories/order/{id}", name="back_article_order_categories", methods="GET|POST")
     */
    public function orderCategories(Request $request, Article $article): Response
    {
        if ($article->getPositionCategories()->count() < 2) {
            $msg = $this->translator->trans('article.category_order.flash.less_than_two_subcategory', [], 'back_messages');
            $this->addFlash('warning', $msg);

            return $this->redirectToRoute('back_article_category_search');
        }
        $form = $this->createForm(ArticlePositionCategoryOrderCollectionType::class, null, [
                'article_position_categories' => $article->getPositionCategories(),
                'position' => 'positionCategory',
            ])
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $msg = $this->translator->trans('article.category_order.flash.success', [], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_article_read', ['id' => $article->getId()]);
        }

        return $this->render('back/article/category_order.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }
}
