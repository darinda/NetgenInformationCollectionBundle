<?php

namespace Netgen\Bundle\InformationCollectionBundle;

use eZ\Publish\Core\MVC\Symfony\View\ContentValueView;
use eZ\Publish\Core\MVC\Symfony\View\LocationValueView;
use Netgen\Bundle\InformationCollectionBundle\Event\InformationCollected;
use Netgen\Bundle\InformationCollectionBundle\Form\Captcha\CaptchaValueInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

trait InformationCollectionTrait
{
    /**
     * Builds Form, checks if Form is valid and dispatches InformationCollected event.
     *
     * @param \eZ\Publish\Core\MVC\Symfony\View\ContentValueView $view
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    protected function collectInformation(ContentValueView $view, Request $request)
    {
        $isValid = false;

        if (!$view instanceof LocationValueView) {
            throw new \BadMethodCallException('eZ view needs to implement LocationValueView interface');
        }

        /** @var \Netgen\Bundle\InformationCollectionBundle\Form\Builder\FormBuilder $formBuilder */
        $formBuilder = $this->container
            ->get('netgen_information_collection.form.builder');

        $form = $formBuilder->createFormForLocation($view->getLocation())
            ->getForm();

        /** @var CaptchaValueInterface $captcha */
        $captcha = $this->container
            ->get('netgen_information_collection.factory.captcha')
            ->getCaptcha($view->getLocation());

        $form->handleRequest($request);
        $validCaptcha = $captcha->isValid($request);
        $formSubmitted = $form->isSubmitted();

        if ($formSubmitted && $form->isValid() && $validCaptcha) {
            $isValid = true;
            $event = new InformationCollected($form->getData());

            /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
            $dispatcher = $this->container
                ->get('event_dispatcher');

            $dispatcher->dispatch(Events::INFORMATION_COLLECTED, $event);
        }

        if (true === $formSubmitted && false === $validCaptcha) {
            $form->addError(new FormError($this->container->get('translator')->trans('form.errors.captcha_failed', array(), 'netgen_information_collection_form_messages')));
        }

        return array(
            'is_valid' => $isValid,
            'form' => $form->createView(),
            'collected_fields' => $form->getData()->payload->getCollectedFields(),
        );
    }
}
