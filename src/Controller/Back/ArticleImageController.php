<?php

namespace App\Controller\Back;

use App\Entity\Article;
use App\Entity\File;
use App\Form\DropzoneImageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Form\CropperType;

/**
 * @Route("/back/article/image")
 */
class ArticleImageController extends AbstractController
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/update/{id}", name="back_article_image_update")
     */
    public function update(Request $request, Article $article): Response
    {
        $article->getImage() ?: $article->setImage(new File());
        $form = $this->createForm(DropzoneImageType::class, $article->getImage());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article
                ->setCroppedImageName(null)
                ->setCroppedImageThumbnailName(null);
            $article
                ->getImage()
                ->setUpdatedAt(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();
            $article->getImage()->setFile();
            $msg = $this->translator->trans('article_image.update.flash.success', [], 'front_messages');
            $this->addFlash('success', $msg);

            return $this->redirectToRoute('back_article_image_crop', ['id' => $article->getId()]);
        }

        return $this->render('back/article_image/update.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/crop/{id}", name="back_article_image_crop")
     */
    public function crop(CropperInterface $cropper, Request $request, Article $article): Response
    {
        if (!$article->getImage() || !file_exists($article->getImage()->getPhpPath())) {
            return $this->redirectToRoute('back_article_image_update', ['id' => $article->getId()]);
        }

        $crop = $cropper->createCrop($article->getImage()->getPhpPath());

        $crop->setCroppedMaxSize(300, 270);

        $form = $this->createFormBuilder(['crop' => $crop])
            ->add('crop', CropperType::class, [
                'public_url' => $article->getImage()->getWebPath(),
                'view_mode' => 1,
                'drag_mode' => 'move',
                'initial_aspect_ratio' => 2000 / 1800,
                'aspect_ratio' => 2000 / 1800,
                'responsive' => true,
                'restore' => true,
                'check_cross_origin' => true,
                'check_orientation' => true,
                'modal' => true,
                'guides' => true,
                'center' => true,
                'highlight' => true,
                'background' => true,
                'auto_crop' => true,
                'auto_crop_area' => 0.1,
                'movable' => true,
                'rotatable' => true,
                'scalable' => true,
                'zoomable' => true,
                'zoom_on_touch' => true,
                'zoom_on_wheel' => true,
                'wheel_zoom_ratio' => 0.2,
                'crop_box_movable' => true,
                'crop_box_resizable' => true,
                'toggle_drag_mode_on_dblclick' => true,
                'min_container_width' => 200,
                'min_container_height' => 100,
                'min_canvas_width' => 0,
                'min_canvas_height' => 0,
                'min_crop_box_width' => 320,
                'min_crop_box_height' => 0,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $croppedImageName = 'cropped_image'.$article->getImage()->getFilename();
            $fp = fopen($article->getImage()->getPhpDir().'/'.$croppedImageName, 'w+');
            fwrite($fp, $crop->getCroppedImage());
            fclose($fp);

            $croppedImageThumbnailName = 'cropped_image_thumbnail'.$article->getImage()->getFilename();
            $fp = fopen($article->getImage()->getPhpDir().'/'.$croppedImageThumbnailName, 'w+');
            fwrite($fp, $crop->getCroppedThumbnail(150, 135));
            fclose($fp);

            $article->setCroppedImageName($croppedImageName);
            $article->setCroppedImageThumbnailName($croppedImageThumbnailName);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('back_article_read', ['id' => $article->getId()]);
        }

        return $this->render('back/article_image/crop.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }
}