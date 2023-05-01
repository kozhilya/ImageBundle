<?php

namespace Kozhilya\ImageBundle\Processor;

use Kozhilya\ImageBundle\Entity\Image;
use Kozhilya\ImageBundle\ImageService;

/**
 * Простой обработчик изображений
 */
class ImageProcessor extends ImageProcessorAbstract
{
    /**
     * Создание изображений для данного объекта
     */
    protected function initImages(): void
    {
        if (is_null($this->getEntityImage())) {
            $this->setEntityImage(new Image());
        }
    }

    /**
     * Получение изображения сущности
     *
     * @return Image|null
     */
    public function getEntityImage(): ?Image
    {
        return $this->propertyAccessor->getValue(
            $this->entity,
            $this->schema->field
        );
    }

    /**
     * Установка изображения сущности
     *
     * @param Image $image
     */
    public function setEntityImage(Image $image): void
    {
        $this->propertyAccessor->setValue(
            $this->entity,
            $this->schema->field,
            $image
        );
    }

    /**
     * Получение уникального пути сущности
     *
     * @return string
     */
    protected function generateEntityPath(): string
    {
        return implode(ImageService::PATH_SEPARATOR, [
            $this->schema->slug,
            $this->getPathId(),
        ]);
    }
}