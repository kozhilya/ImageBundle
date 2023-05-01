<?php

namespace Kozhilya\ImageBundle\Processor;

use Exception;

/**
 * Структура хранения информации о свойствах сохранённого файла
 */
class ImageProcessorSchema
{
    /**
     * Короткое слово, используемое в URL
     *
     * @var string
     */
    public string $slug;

    /**
     * Название класса сущности, к которому относится изображение
     *
     * @var string
     */
    public string $class;

    /**
     * Поле класса, к которому относится изображение
     *
     * @var string
     */
    public string $field;

    /**
     * Должно ли изменяться изображение в зависимости от языка пользователя
     *
     * @var bool
     */
    public bool $translatable;

    /**
     * Использовать ли поле `slug` как уникальное поле сущности (если `false` используется `id`)
     *
     * @var bool
     */
    public bool $useSlug;

    /**
     * Использовать ли Voter для проверки доступа к изображению. Если `true`, система
     * будет проверять право доступа `image`, используя Voter для класса-сущности.
     *
     * @var bool
     */
    public bool $useVoter;

    /**
     * Список структур настроек для размеров изображения
     *
     * @var array<string, ImageProcessorFileSchema>
     */
    public array $files;

    /**
     * Создание структура хранения информации о свойствах сохранённого файла на основе
     * массива настроек Symfony
     *
     * @param array $input
     *
     * @internal
     */
    public function __construct(array $input)
    {
        $this->class = (string)$input['class'];
        $this->slug = (string)$input['slug'];
        $this->field = (string)$input['field'];
        $this->translatable = (bool)$input['translatable'];
        $this->useSlug = (bool)$input['use_slug'];
        $this->useVoter = (bool)$input['use_voter'];

        foreach ($input['files'] as $size => $fileInput) {
            $this->files[$size] = new ImageProcessorFileSchema($this, $size, $fileInput);
        }
    }

    /**
     * Проверка, что указанная сущность является объектом настроенного класса
     *
     * @param mixed $entity
     * @return bool
     */
    public function testEntity(mixed $entity): bool
    {
        return (get_class($entity) === $this->class) or is_a($entity, $this->class);
    }

    /**
     * Получение структуры настроек для размера изображения
     *
     * @param string $size
     * @return ImageProcessorFileSchema
     *
     * @throws Exception
     */
    public function getFileSchema(string $size): ImageProcessorFileSchema
    {
        foreach ($this->files as $fileSize => $schemaFile) {
            if ($fileSize === $size) {
                return $schemaFile;
            }
        }

        throw new Exception(
            sprintf(
                "Can't resolve file size \"%s\" for schema \"%s\".",
                $size,
                $this->slug
            )
        );
    }
}