<?php

namespace App\Controller\Back;

use App\Entity\Config;
use App\Form\Back\ConfigBatchType;
use App\Form\Back\ConfigFilterType;
use App\Form\Back\ConfigType;
use App\Manager\Back\ConfigManager;
use App\Repository\ConfigRepository;
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
 * @Route("/back/config")
 */
class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository     */
    private $configRepository;

    /**
     * @var ConfigManager     */
    private $configManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ConfigRepository $configRepository, ConfigManager $configManager, TranslatorInterface $translator)
    {
        $this->configRepository = $configRepository;
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * @Route("/search/{page}", name="back_config_search", methods="GET|POST")
     */
    public function search(Request $request, Session $session, $page = null)
    {
        if (!$page) {
            $page = $session->get('back_config_page', 1);
        }
        $formFilter = $this->createForm(ConfigFilterType::class, null, ['action' => $this->generateUrl('back_config_search', ['page' => 1])]);
        $formFilter->handleRequest($request);
        $data = $this->configManager->configFormFilter($formFilter)->getData();
        $configs = $this->configRepository->searchBack($request, $session, $data, $page);
        $queryData = $this->configManager->getQueryData($data);
        $formBatch = $this->createForm(ConfigBatchType::class, null, [
            'action' => $this->generateUrl('back_config_search', array_merge(['page' => $page], $queryData)),
            'configs' => $configs,
        ]);
        $formBatch->handleRequest($request);
        if ($formBatch->isSubmitted() && $formBatch->isValid()) {
            $url = $this->configManager->dispatchBatchForm($formBatch);
            if ($url) {
                return $this->redirect($url);
            }
        }

        return $this->render('back/config/search/index.html.twig', [
            'configs' => $configs,
            'form_filter' => $formFilter->createView(),
            'form_batch' => $formBatch->createView(),
            'form_delete' => $this->createFormBuilder()->getForm()->createView(),
            'number_page' => ceil(count($configs) / $formFilter->get('number_by_page')->getData()) ?: 1,
            'page' => $page,
            'query_data' => $queryData,
        ]);
    }

    /**
     * @Route("/create", name="back_config_create", methods="GET|POST")
     */
    public function create(Request $request): Response
    {
        $config = new Config();
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $this->configManager->setValue($config, $form);
            $em->persist($config);
            $em->flush();
            $msg = $this->translator->trans('config.create.flash.success', ['%identifier%' => $config], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_config_search');
        }

        return $this->render('back/config/create.html.twig', [
            'config' => $config,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/read/{id}", name="back_config_read", methods="GET")
     */
    public function read(Config $config): Response
    {
        return $this->render('back/config/read.html.twig', [
            'config' => $config,
        ]);
    }

    /**
     * @Route("/update/{id}", name="back_config_update", methods="GET|POST")
     */
    public function update(Request $request, Config $config): Response
    {
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->configManager->setValue($config, $form);
            $this->getDoctrine()->getManager()->flush();
            $msg = $this->translator->trans('config.update.flash.success', [], 'back_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_config_search');
        }

        return $this->render('back/config/update.html.twig', [
            'config' => $config,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete", name="back_config_delete", methods="GET|POST")
     */
    public function delete(Request $request): Response
    {
        $configs = $this->configManager->getConfigs();
        $formBuilder = $this->createFormBuilder();
        $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($configs) {
            $result = $this->configManager->validationDelete($configs);
            if (true !== $result) {
                $event->getForm()->addError(new FormError($result));
            }
        });
        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($configs as $config) {
                $em->remove($config);
            }
            try {
                $em->flush();
                $this->addFlash('success', $this->translator->trans('config.delete.flash.success', [], 'back_messages'));
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->addFlash('warning', $e->getMessage());
            }

            return $this->redirectToRoute('back_config_search');
        }

        return $this->render('back/config/delete.html.twig', [
            'configs' => $configs,
            'form' => $form->createView(),
        ]);
    }
}
