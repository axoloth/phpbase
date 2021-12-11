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

/**
 *
 */
class ContactMessageManager
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

    /**
     * @param RequestStack $requestStack
     * @param SessionInterface $session
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param TranslatorInterface $translator
     */
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
     * Configure the filter form
     *
     *  Set the filter's default fields, save and retrieve the last search in session.
     *
     * @param FormInterface $form
     * @return \Symfony\Component\Form\FormInterface
     */
    public function configFormFilter(FormInterface $form)
    {
        $request = $this->requestStack->getCurrentRequest();
        $page = $request->get('page');
        if (!$page) {
            $page = $this->session->get('back_contact_message_page', 1);
        }

        $this->session->set('back_contact_message_page', $page);
        if ($request->isMethod('POST') && $request->query->get('back_contact_message_search')) {
            $form->submit($request->query->get('back_contact_message_search'));
        } elseif (!$form->getData()) {
            $form->setData($this->getDefaultFormSearchData());
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $this->session->set('back_contact_message_search', $form->get('search')->getData());
            $this->session->set('back_contact_message_number_by_page', $form->get('number_by_page')->getData());
        }
        return $form;
    }

    /**
     * Get the default data from the filter form
     *
     *  Get saved data in session or default filter form.
     *
     * @return array
     */
    public function getDefaultFormSearchData()
    {
        return [
            'search' => $this->session->get('back_contact_message_search', null),
            'number_by_page' => $this->session->get('back_contact_message_number_by_page', self::NUMBER_BY_PAGE),
        ];
    }

    /**
     * Get query data
     *
     *  Transform filter form data into an array compatible with url parameters.
     *  The returned array must be merged with the parameters of the route.
     * @param array $data
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
     * Valid the multiple selection form
     *
     *  If the result returned is a string the form is not validated and the message is added in the flash bag
     *
     * @param FormInterface $form
     * @throws LogicException
     * @return boolean|string
     */
    public function validationBatchForm(FormInterface $form)
    {
        $contactMessages = $form->get('contact_messages')->getData();
        if (0 === count($contactMessages)) {
            return $this->translator->trans('error.no_element_selected', [], 'back_messages');
        }
        $action = $form->get('action')->getData();

        switch ($action) {
            case 'delete':
                return $this->validationDelete($contactMessages);
        }
        return true;
    }

    /**
     * Valid the delete action from multiple selection form
     *
     *  If the result returned is a string the form is not validated and the message is added in the flash bag
     *
     * @param array $contactMessages     * @return boolean|string
     */
    public function validationDelete($contactMessages)
    {
        /*foreach($contactMessages as $contactMessage) {

        }*/
        return true;
    }

    /**
     * Dispatch the multiple selection form
     *
     *  This method is called after the validation of the multiple selection form.
     *  Different actions can be performed on the list of entities.
     *  If the result returned is a string (url) the controller redirects to this page else if the result returned is false the controller does nothing.
     * @param FormInterface $form
     * @return boolean|string
     */
    public function dispatchBatchForm(FormInterface $form)
    {
        $contactMessages = $form->get('contact_messages')->getData();
        $action = $form->get('action')->getData();
        switch ($action) {
            case 'delete':
                return $this->urlGenerator->generate('back_contact_message_delete', $this->getIds($contactMessages));
        }
        return false;
    }

    /**
     * Get ids
     *
     *  Transform entities list into an array compatible with url parameters.
     *  The returned array must be merged with the parameters of the route.
     *
     * @param array $contactMessages     * @return array
     */
    private function getIds($contactMessages)
    {
        $ids = [];
        foreach ($contactMessages as $contactMessage) {
            $ids[] = $contactMessage->getId();
        }
        return ['ids' => $ids];
    }

    /**
     * Get $contactMessages     *
     *  Transform query parameter ids list into an array entities list.
     *
     * @throws InvalidParameterException
     * @throws NotFoundHttpException
     * @return array
     */
    public function getContactMessages()
    {
        $request = $this->requestStack->getCurrentRequest();
        $ids = $request->query->get('ids', null);
        if (!is_array($ids)) {
            throw new InvalidParameterException();
        }
        $contactMessages = $this->em->getRepository('App\Entity\ContactMessage')->findById($ids);
        if (count($ids) !== count($contactMessages)) {
            throw new NotFoundHttpException();
        }
        return $contactMessages;
    }
}
