<?php

namespace App\Form;

use Symfony\Component\Form\SubmitButtonTypeInterface;
use App\Service\Recaptcha3Service;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class Recaptcha3SubmitType extends AbstractType implements SubmitButtonTypeInterface
{
    private $requestStack;
    private $parameterBag;
    private $recaptcha3Service;
    
    public function __construct(RequestStack $requestStack, ParameterBagInterface $parameterBag, Recaptcha3Service $recaptcha3Service)
    {
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
        $this->recaptcha3Service = $recaptcha3Service;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setAttribute('label', $options['label'])
            ->setAttribute('label_html', $options['label_html'])
            ->setAttribute('mapped', $options['mapped'])
            ->setAttribute('attr', $options['attr']);
        
        $parentBuilder = $options['parent_builder'];
            
        $parentBuilder
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $request = $this->requestStack->getCurrentRequest();
                $token = $request->request->get('g-recaptcha-response');
                $response = $this->recaptcha3Service->verifyResponse($token);
                if ($response) {
                    $success = $response->success ?? false;
                    $score = $response->score ?? 0.0;
                    if (!$success || $score < 0.5) {
                        $form->addError(new FormError("Captcha erreur"));
                    }
                } else {
                    $form->addError(new FormError("Recaptcha erreur de configuration."));
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => '<i class="far fa-paper-plane"></i> Envoyer',
            'label_html' => true,
            'attr' => [
                'class' => "btn btn-primary btn-block g-recaptcha",
                'data-sitekey' => $this->parameterBag->get('app.recaptcha3.site_key'),
                'data-callback' => "onSubmit",
                'data-action' => "submit",
            ],
            'mapped' => false,
            'parent_builder' => null,
        ]);
    }

    public function getParent(): string
    {
        return SubmitType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'recaptcha3';
    }
}
