<?php

namespace App\Manager\Back;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticleCategoryManager
{
    const NUMBER_BY_PAGE = 15;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        RequestStack $requestStack,
        SessionInterface $session,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }

    /**
     * Configure the filter form.
     *
     *  Set the filter's default fields, save and retrieve the last search in session.
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function configFormFilter(FormInterface $form)
    {
        if (!$form->getData()) {
            $form->setData($this->getDefaultFormSearchData());
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $this->session->set('back_article_category_search', $form->get('search')->getData());
        }

        return $form;
    }

    /**
     * Get the default data from the filter form.
     *
     *  Get saved data in session or default filter form.
     *
     * @return array
     */
    public function getDefaultFormSearchData()
    {
        return [
            'search' => $this->session->get('back_article_category_search', null),
        ];
    }

    /**
     * Get query data.
     *
     *  Transform filter form data into an array compatible with url parameters.
     *  The returned array must be merged with the parameters of the route.
     *
     * @return array
     */
    public function getQueryData(array $data)
    {
        $queryData['filter'] = [];
        foreach ($data as $key => $value) {
            if (null === $value) {
                $queryData['filter'][$key] = '';
            } else {
                $queryData['filter'][$key] = $value;
            }
        }

        return $queryData;
    }

    /**
     * Valid the multiple selection form.
     *
     *  If the result returned is a string the form is not validated and the message is added in the flash bag
     *
     * @throws LogicException
     *
     * @return bool|string
     */
    public function validationBatchForm(FormInterface $form)
    {
        $articleCategories = $form->get('article_categories')->getData();
        if (0 === count($articleCategories)) {
            return $this->translator->trans('error.no_element_selected', [], 'back_messages');
        }
        $action = $form->get('action')->getData();

        switch ($action) {
            case 'delete':
                return $this->validationDelete($articleCategories);
        }

        return true;
    }

    /**
     * Valid the delete action from multiple selection form.
     *
     *  If the result returned is a string the form is not validated and the message is added in the flash bag
     *
     * @return bool|string
     */
    public function validationDelete($articleCategories)
    {
        /*foreach($articleCategories as $articleCategory) {

        }*/
        return true;
    }

    /**
     * Dispatch the multiple selection form.
     *
     *  This method is called after the validation of the multiple selection form.
     *  Different actions can be performed on the list of entities.
     *  If the result returned is a string (url) the controller redirects to this page else if the result returned is false the controller does nothing.
     *
     * @return bool|string
     */
    public function dispatchBatchForm(FormInterface $form)
    {
        $articleCategories = $form->get('article_categories')->getData();
        $action = $form->get('action')->getData();
        switch ($action) {
            case 'delete':
                return $this->urlGenerator->generate('back_article_category_delete', $this->getIds($articleCategories));
        }

        return false;
    }

    /**
     * Get ids.
     *
     *  Transform entities list into an array compatible with url parameters.
     *  The returned array must be merged with the parameters of the route.
     *
     * @return array
     */
    private function getIds($articleCategories)
    {
        $ids = [];
        foreach ($articleCategories as $articleCategory) {
            $ids[] = $articleCategory->getId();
        }

        return ['ids' => $ids];
    }

    /**
     * Get $articleCategories     *
     *  Transform query parameter ids list into an array entities list.
     *
     * @throws InvalidParameterException
     * @throws NotFoundHttpException
     *
     * @return array
     */
    public function getArticleCategories()
    {
        $request = $this->requestStack->getCurrentRequest();
        $ids = $request->query->get('ids', null);
        if (!is_array($ids)) {
            throw new InvalidParameterException();
        }
        $articleCategories = $this->em->getRepository('App\Entity\ArticleCategory')->findById($ids);
        if (count($ids) !== count($articleCategories)) {
            throw new NotFoundHttpException();
        }

        return $articleCategories;
    }
}
