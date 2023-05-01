<?php


namespace Kozhilya\ImageBundle\Processor;

/**
 * Структура настроек для размера изображения
 */
class ImageProcessorFileSchema
{
    /**
     * Идентификатор размера
     *
     * @var string
     */
    public string $size;

    /**
     * Формат изображения
     *
     * @var string
     */
    public string $format;

    /**
     * Требуемая ширина изображения (`null` для сохранения оригинальной ширины)
     *
     * @var int|null
     */
    public ?int $width = null;

    /**
     * Требуемая высота изображения (`null` для сохранения оригинальной высоты)
     *
     * @var int|null
     */
    public ?int $height = null;

    public function __construct(
        public ImageProcessorSchema $parent,
        string $size,
        array $input)
    {
        $this->size = $size;
        $this->format = $input['format'] ?? 'webp';
        $this->width = $input['width'];
        $this->height = $input['height'];
    }
}