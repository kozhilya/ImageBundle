<?php

namespace Kozhilya\ImageBundle\EventListener;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Kozhilya\ImageBundle\Processor\ImageProcessorTranslatable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Kozhilya\ImageBundle\ImageService;

class AdvancedImageListener implements EventSubscriberInterface
{
    public function __construct(protected ImageService $imageService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'submit',
        ];
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     * @throws Exception
     */
    public function submit(FormEvent $event): void
    {
        $form = $event->getForm();
        $image = $event->getData();

        $rootForm = $form;
        while (!is_null($rootForm->getParent())) {
            $rootForm = $rootForm->getParent();
        }

        $data = $rootForm->getData();

        $imageProcessor = $this->imageService->get($data);

        if ($imageProcessor instanceof ImageProcessorTranslatable) {
            $imageProcessor->translate($form->getParent()->getName());
        }

        $imageProcessor->setEntityImage($image);

        $uploadedFile = $form->get('src')->getData();

        if (!is_null($uploadedFile)) {
            $image = $imageProcessor->save($uploadedFile);
        }

        $image->setAlt($form->get('alt')->getData());
    }

}