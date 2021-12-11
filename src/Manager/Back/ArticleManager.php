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

class ArticleManager
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
        $page = $this->requestStack->getCurrentRequest()->get('page');
        $page ?: $page = $this->session->get('back_article_page', 1);
        $this->session->set('back_article_page', $page);
        if (!$form->getData()) {
            $form->setData($this->getDefaultFormSearchData());
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $this->session->set('back_article_search', $form->get('search')->getData());
            $this->session->set('back_article_number_by_page', $form->get('number_by_page')->getData());
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
            'search' => $this->session->get('back_article_search', null),
            'number_by_page' => $this->session->get('back_article_number_by_page', self::NUMBER_BY_PAGE),
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
        $articles = $form->get('articles')->getData();
        if (0 === count($articles)) {
            return $this->translator->trans('error.no_element_selected', [], 'back_messages');
        }
        $action = $form->get('action')->getData();

        switch ($action) {
            case 'delete':
                return $this->validationDelete($articles);
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
    public function validationDelete($articles)
    {
        /*foreach($articles as $article) {

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
        $articles = $form->get('articles')->getData();
        $action = $form->get('action')->getData();
        switch ($action) {
            case 'delete':
                return $this->urlGenerator->generate('back_article_delete', $this->getIds($articles));
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
    private function getIds($articles)
    {
        $ids = [];
        foreach ($articles as $article) {
            $ids[] = $article->getId();
        }

        return ['ids' => $ids];
    }

    /**
     * Get $articles     *
     *  Transform query parameter ids list into an array entities list.
     *
     * @throws InvalidParameterException
     * @throws NotFoundHttpException
     *
     * @return array
     */
    public function getArticles()
    {
        $request = $this->requestStack->getCurrentRequest();
        $ids = $request->query->get('ids', null);
        if (!is_array($ids)) {
            throw new InvalidParameterException();
        }
        $articles = $this->em->getRepository('App\Entity\Article')->findById($ids);
        if (count($ids) !== count($articles)) {
            throw new NotFoundHttpException();
        }

        return $articles;
    }
}
