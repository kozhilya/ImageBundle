<?php

namespace Kozhilya\ImageBundle\Processor;

use Kozhilya\ImageBundle\Entity\Image;
use Kozhilya\ImageBundle\ImageService;

class ImageProcessorTranslatable extends ImageProcessorAbstract
{
    protected ?string $locale = null;

    /**
     * Создание изображений для данного объекта
     */
    protected function initImages(): void
    {
        foreach ($this->entity->getTranslations() as $locale => $translation) {
            $this->locale = $locale;

            if (is_null($this->getEntityImage())) {
                $this->setEntityImage(new Image());
            }
        }

        $this->locale = null;
    }

    /**
     * Установить язык
     *
     * @param string|null $locale
     * @return ImageProcessorTranslatable
     */
    public function translate(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Получить текущий язык
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale ?? $this->imageService->getLocale();
    }

    /**
     * Получение изображения сущности
     *
     * @return Image|null
     */
    public function getEntityImage(): ?Image
    {
        $translation = $this->entity->translate($this->getLocale());

        return $this->propertyAccessor->getValue(
            $translation,
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
        $translation = $this->entity->translate($this->getLocale());

        $this->propertyAccessor->setValue(
            $translation,
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
            $this->locale,
        ]);
    }

}