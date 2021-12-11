<?php

namespace App\Controller\Back;

use App\Entity\ArticleCategory;
use App\Entity\ArticlePositionCategory;
use App\Form\Back\ArticleCategoryBatchType;
use App\Form\Back\ArticleCategoryFilterType;
use App\Form\Back\ArticleCategoryOrderCollectionType;
use App\Form\Back\ArticleCategoryType;
use App\Form\Back\ArticlePositionCategoryOrderCollectionType;
use App\Manager\Back\ArticleCategoryManager;
use App\Repository\ArticleCategoryRepository;
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
 * @Route("/back/article/category")
 */
class ArticleCategoryController extends AbstractController
{
    /**
     * @var ArticleCategoryRepository     */
    private $articleCategoryRepository;

    /**
     * @var ArticleCategoryManager     */
    private $articleCategoryManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ArticleCategoryRepository $articleCategoryRepository, ArticleCategoryManager $articleCategoryManager, TranslatorInterface $translator)
    {
        $this->articleCategoryRepository = $articleCategoryRepository;
        $this->articleCategoryManager = $articleCategoryManager;
        $this->translator = $translator;
    }

    /**
     * @Route("/search", name="back_article_category_search", methods="GET|POST")
     */
    public function search(Request $request, Session $session)
    {
        $formFilter = $this->createForm(ArticleCategoryFilterType::class, null, ['action' => $this->generateUrl('back_article_category_search', [])]);
        $formFilter->handleRequest($request);
        $data = $this->articleCategoryManager->configFormFilter($formFilter)->getData();
        $articleCategories = $this->articleCategoryRepository->searchBack($request, $session, $data);
        shuffle($articleCategories);
        usort($articleCategories, function (ArticleCategory $a, ArticleCategory $b) {
            $routeA = array_reverse($a->getParentCategories());
            $routeA[] = $a;
            $routeB = array_reverse($b->getParentCategories());
            $routeB[] = $b;
            $common = null;
            foreach ($routeA as $key => $node) {
                if (isset($routeB[$key])) {
                    $common = $key;
                    if ($node->getPosition() != $routeB[$key]->getPosition()) {
                        return $node->getPosition() <=> $routeB[$key]->getPosition();
                    }
                } else {
                    break;
                }
            }
            if (isset($routeB[$common + 1]) && $routeA[$common] === $routeB[$common + 1]->getParentCategory()) {
                return -1;
            }

            if (isset($routeA[$common + 1]) && $routeB[$common] === $routeA[$common + 1]->getParentCategory()) {
                return 1;
            }

            dd($a->getName().' '.$b->getName());

            return 0;
        });
        $queryData = $this->articleCategoryManager->getQueryData($data);
        $formBatch = $this->createForm(ArticleCategoryBatchType::class, null, [
            'action' => $this->generateUrl('back_article_category_search', $queryData),
            'article_categories' => $articleCategories,
        ]);
        $formBatch->handleRequest($request);
        if ($formBatch->isSubmitted() && $formBatch->isValid()) {
            $url = $this->articleCategoryManager->dispatchBatchForm($formBatch);
            if ($url) {
                return $this->redirect($url);
            }
        }

        return $this->render('back/article_category/search/index.html.twig', [
            'article_categories' => $articleCategories,
            'form_filter' => $formFilter->createView(),
            'form_batch' => $formBatch->createView(),
            'form_delete' => $this->createFormBuilder()->getForm()->createView(),
            'query_data' => $queryData,
        ]);
    }

    /**
     * @Route("/create", name="back_article_category_create", methods="GET|POST")
     */
    public function create(Request $request): Response
    {
        $articleCategory = new ArticleCategory();
        $form = $this->createForm(ArticleCategoryType::class, $articleCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $last = false;
            if ($parent = $articleCategory->getParentCategory()) {
                $last = $parent->getSubcategories()->last();
            } else {
                $roots = $this->articleCategoryRepository->findRoots();
                $last = end($roots);
            }
            if ($last) {
                $articleCategory->setPosition($last->getPosition() + 1);
            }
            foreach ($form->get('articles')->getData() as $article) {
                $articlePositionCategory = new ArticlePositionCategory();
                $articleCategory->addPositionArticle($articlePositionCategory);
                $article->addPositionCategory($articlePositionCategory);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($articleCategory);
            $em->flush();
            $msg = $this->translator->trans('article_category.create.flash.success', ['%identifier%' => $articleCategory], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_article_category_search');
        }

        return $this->render('back/article_category/create.html.twig', [
            'article_category' => $articleCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/update/{id}", name="back_article_category_update", methods="GET|POST")
     */
    public function update(Request $request, ArticleCategory $articleCategory): Response
    {
        $oldParent = null;
        if ($articleCategory->getParentCategory()) {
            $oldParent = clone $articleCategory->getParentCategory();
        }
        $oldArticles = $articleCategory->getArticles();
        $form = $this->createForm(ArticleCategoryType::class, $articleCategory, ['articles' => $oldArticles]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($oldParent !== $articleCategory->getParentCategory()) {
                $last = false;
                if ($parent = $articleCategory->getParentCategory()) {
                    $last = $parent->getSubcategories()->last();
                } else {
                    $roots = $this->articleCategoryRepository->findRoots();
                    $last = end($roots);
                }
                if ($last) {
                    $articleCategory->setPosition($last->getPosition() + 1);
                }
            }
            $articles = $form->get('articles')->getData();
            foreach ($oldArticles as $article) {
                if (false === array_search($article, $articles, true)) {
                    $articleCategory->removeArticle($article);
                }
            }
            foreach ($articles as $article) {
                if (false === array_search($article, $oldArticles, true)) {
                    $articlePositionCategory = new ArticlePositionCategory();
                    $articleCategory->addPositionArticle($articlePositionCategory);
                    $article->addPositionCategory($articlePositionCategory);
                }
            }
            $this->getDoctrine()->getManager()->flush();
            $msg = $this->translator->trans('article_category.update.flash.success', [], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_article_category_search');
        }

        return $this->render('back/article_category/update.html.twig', [
            'article_category' => $articleCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete", name="back_article_category_delete", methods="GET|POST")
     */
    public function delete(Request $request): Response
    {
        $articleCategories = $this->articleCategoryManager->getArticleCategories();
        $formBuilder = $this->createFormBuilder();
        $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($articleCategories) {
            $result = $this->articleCategoryManager->validationDelete($articleCategories);
            if (true !== $result) {
                $event->getForm()->addError(new FormError($result));
            }
        });
        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($articleCategories as $articleCategory) {
                $articleCategory->setParentCategory(null);
                if ($articleCategory->getSubcategories()->count()) {
                    $roots = $this->articleCategoryRepository->findRoots();
                    $last = end($roots);
                    $positionLast = -1;
                    if ($last) {
                        $positionLast = $last->getPosition();
                    }
                    foreach ($articleCategory->getSubcategories() as $subcategory) {
                        $subcategory->setPosition(++$positionLast);
                        $articleCategory->removeSubcategory($subcategory);
                    }
                }
                $em->remove($articleCategory);
            }
            try {
                $em->flush();
                $this->addFlash('success', $this->translator->trans('article_category.delete.flash.success', [], 'back_messages'));
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->addFlash('warning', $e->getMessage());
            }

            return $this->redirectToRoute('back_article_category_search');
        }

        return $this->render('back/article_category/delete.html.twig', [
            'article_categories' => $articleCategories,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/order/{id}", defaults={"id"=null}, name="back_article_category_order", methods="GET|POST")
     */
    public function order(Request $request, ArticleCategory $articleCategory = null): Response
    {
        $categories = [];
        if ($articleCategory) {
            $categories = $articleCategory->getSubcategories();
        } else {
            $categories = $this->articleCategoryRepository->findRoots();
        }
        if (count($categories) < 2) {
            $msg = $this->translator->trans('article_category.order.flash.less_than_two_subcategory', [], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_article_category_search');
        }
        $form = $this->createForm(ArticleCategoryOrderCollectionType::class, null, ['categories' => $categories]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $msg = $this->translator->trans('article_category.order.flash.success', [], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_article_category_search');
        }

        return $this->render('back/article_category/order.html.twig', [
            'article_category' => $articleCategory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/articles/order/{id}", name="back_article_category_order_articles", methods="GET|POST")
     */
    public function orderArticles(Request $request, ArticleCategory $articleCategory): Response
    {
        if ($articleCategory->getPositionArticles()->count() < 2) {
            $msg = $this->translator->trans('article_category.article_order.flash.less_than_two_subcategory', [], 'back_messages');
            $this->addFlash('warning', $msg);

            return $this->redirectToRoute('back_article_category_search');
        }
        $form = $this->createForm(ArticlePositionCategoryOrderCollectionType::class, null, [
                'article_position_categories' => $articleCategory->getPositionArticles(),
                'position' => 'positionArticle',
            ])
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $msg = $this->translator->trans('article_category.article_order.flash.success', [], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_article_category_search');
        }

        return $this->render('back/article_category/article_order.html.twig', [
            'article_category' => $articleCategory,
            'form' => $form->createView(),
        ]);
    }
}
