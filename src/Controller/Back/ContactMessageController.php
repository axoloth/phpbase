<?php

namespace App\Controller\Back;

use App\Entity\ContactMessage;
use App\Form\Back\ContactMessageBatchType;
use App\Form\Back\ContactMessageFilterType;
use App\Manager\Back\ContactMessageManager;
use App\Repository\ContactMessageRepository;
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
 * @Route("/back/contact/message")
 */
class ContactMessageController extends AbstractController
{
    /**
     *
     * @var ContactMessageRepository     */
    private $contactMessageRepository;

    /**
     *
     * @var ContactMessageManager     */
    private $contactMessageManager;

    /**
     *
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ContactMessageRepository $contactMessageRepository, ContactMessageManager $contactMessageManager, TranslatorInterface $translator)
    {
        $this->contactMessageRepository = $contactMessageRepository;
        $this->contactMessageManager = $contactMessageManager;
        $this->translator = $translator;
    }

    /**
     * @Route("/search/{page}", name="back_contact_message_search", methods="GET|POST")
     */
    public function search(Request $request, Session $session, $page = null)
    {
        if (!$page) {
            $page = $session->get('back_contact_message_page', 1);
        }
        $formFilter = $this->createForm(ContactMessageFilterType::class, null, ['action' => $this->generateUrl('back_contact_message_search', ['page' => 1]), ]);
        $formFilter->handleRequest($request);
        $data = $this->contactMessageManager->configFormFilter($formFilter)->getData();
        $contactMessages = $this->contactMessageRepository->searchBack($request, $session, $data, $page);
        $queryData = $this->contactMessageManager->getQueryData($data);
        $formBatch = $this->createForm(ContactMessageBatchType::class, null, [
            'action' => $this->generateUrl('back_contact_message_search', array_merge(['page' => $page], $queryData)),
            'contact_messages' => $contactMessages,
        ]);
        $formBatch->handleRequest($request);
        if ($formBatch->isSubmitted() && $formBatch->isValid()) {
            $url = $this->contactMessageManager->dispatchBatchForm($formBatch);
            if ($url) {
                return $this->redirect($url);
            }
        }
        return $this->render('back/contact_message/search/index.html.twig', [
            'contact_messages' => $contactMessages,
            'form_filter' => $formFilter->createView(),
            'form_batch' => $formBatch->createView(),
            'form_delete' => $this->createFormBuilder()->getForm()->createView(),
            'number_page' => ceil(count($contactMessages) / $formFilter->get('number_by_page')->getData()) ?: 1,
            'page' => $page,
            'query_data' => $queryData,
        ]);
    }

    /**
     * @Route("/read/{id}", name="back_contact_message_read", methods="GET")
     */
    public function read(ContactMessage $contactMessage): Response
    {
        return $this->render('back/contact_message/read.html.twig', [
            'contact_message' => $contactMessage,
        ]);
    }

    /**
     * @Route("/delete", name="back_contact_message_delete", methods="GET|POST")
     */
    public function delete(Request $request): Response
    {
        $contactMessages = $this->contactMessageManager->getContactMessages();
        $formBuilder = $this->createFormBuilder();
        $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($contactMessages) {
            $result = $this->contactMessageManager->validationDelete($contactMessages);
            if (true !== $result) {
                $event->getForm()->addError(new FormError($result));
            }
        });
        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($contactMessages as $contactMessage) {
                $em->remove($contactMessage);
            }
            try {
                $em->flush();
                $this->addFlash('success', $this->translator->trans('contact_message.delete.flash.success', [], 'back_messages'));
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->addFlash('warning', $e->getMessage());
            }
            return $this->redirectToRoute('back_contact_message_search');
        }
        return $this->render('back/contact_message/delete.html.twig', [
            'contact_messages' => $contactMessages,
            'form' => $form->createView(),
        ]);
    }
}
