<?php

namespace Kozhilya\ImageBundle\Processor;

use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use ImagickException;
use Kozhilya\ImageBundle\Entity\Image;
use Kozhilya\ImageBundle\ImageService;
use Kozhilya\ImageBundle\Repository\ImageRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Общий обработчик изображений
 */
abstract class ImageProcessorAbstract implements ImageProcessorInterface
{
    protected PropertyAccessor $propertyAccessor;
    protected EntityManager $entityManager;
    protected ?string $currentId = null;

    /**
     * Создание нового обработчика
     *
     * @param ImageService $imageService
     * @param ImageProcessorSchema $schema
     * @param object|null $entity
     * @see ImageService::resolveImage()
     *
     * @internal
     * @see ImageService::get()
     * @see ImageService::resolve()
     */
    public function __construct(
        public ImageService $imageService,
        public ImageProcessorSchema $schema,
        public ?object $entity
    ) {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->entityManager = $this->imageService->getEntityManager();

        $this->initImages();
    }

    /**
     * Получение репозитория изображений
     *
     * @throws NotSupported
     */
    protected function getImageRepository(): ImageRepository
    {
        /**
         * @var ImageRepository $imageRepository
         */
        $imageRepository = $this->entityManager->getRepository(Image::class);

        return $imageRepository;
    }

    /**
     * Создание изображений для данного объекта
     */
    protected abstract function initImages(): void;

    /**
     * Проверка, может ли пользователь получить доступ к изображению, если в настройках для изображений задана проверка на Voter.
     *
     * @return bool
     */
    public function canAccess(): bool
    {
        return (!$this->schema->useVoter) || $this->imageService->isGranted($this->entity);
    }

    /**
     * Получение уникальной строки для сохранения файла на диске
     *
     * @return string
     */
    public function getPathId(): string
    {
        $field = $this->schema->useSlug ? 'slug' : 'id';

        return $this->propertyAccessor->getValue($this->entity, $field);
    }

    /**
     * Создание ID для нового изображения
     *
     * @return string
     * @throws NotSupported
     * @internal
     */
    public function generateId(): string
    {
        /**
         * @var ImageRepository $repo
         */
        $repo = $this->entityManager->getRepository(Image::class);

        while (true) {
            $id = hash('md5', mt_rand());
            $entity = $repo->find($id);

            if (is_null($entity)) {
                return $id;
            }
        }
    }

    /**
     * Получение ID изображения (используется в имени файла на диске)
     *
     * @return string
     * @throws NotSupported
     */
    public function getId(): string
    {
        if (!is_null($this->currentId)) {
            return $this->currentId;
        }

        $image = $this->getEntityImage();

        if (!is_null($image)) {
            $this->currentId = $image->getId();
        }
        else {
            $this->currentId = $this->generateId();
        }

        return $this->currentId;
    }

    /**
     * Получение пути к файлу на сайте
     *
     * @param string $size Размер изображения
     * @return string
     */
    public function getWebPath(string $size = 'original'): string
    {
        return $this->imageService->buildPath($this, $size);
    }

    /**
     * Получение полного пути к файлу
     *
     * @param string $size Размер изображения
     * @param bool $blur Размыть изображение (работает только если установлен Voter
     *                   в настройках изображения, см. {@see ImageProcessorInterface::canAccess()})
     * @return string
     * @throws NotSupported
     * @throws Exception
     * @internal
     */
    public function getAbsolutePath(string $size = 'original', bool $blur = false): string
    {
        $fileSchema = $this->schema->getFileSchema($size);

        return $this->imageService->generateAbsolutePath(
            $this->imageService->getUploadPath(),
            sprintf(
                '%s-%s%s.%s',
                $this->getId(),
                $size,
                $blur ? '.blur' : '',
                $fileSchema->format
            )
        );
    }

    /**
     * Получение полного пути к файлу с автоматической проверкой доступа
     *
     * @param string $size Размер изображения
     * @return string
     * @throws NotSupported
     */
    public function resolveAbsolutePath(string $size = 'original'): string
    {
        return $this->getAbsolutePath($size, !$this->canAccess());
    }


    /**
     * Получение уникального пути сущности
     *
     * @return string
     */
    protected abstract function generateEntityPath(): string;

    /**
     * Сохранение загруженного изображения с определённым размером
     *
     * @param UploadedFile $file Загруженный файл
     * @param string $size
     * @param bool $blur
     *
     * @return void
     * @throws NotSupported
     * @throws Exception
     */
    protected function saveSize(UploadedFile $file, string $size, bool $blur = false): void
    {
        $fileSchema = $this->schema->getFileSchema($size);

        $this->imageService->resize(
            $file,
            $this->getAbsolutePath($size, $blur),
            $fileSchema
        );
    }

    /**
     * Заполнить поля изображения
     *
     * @param Image $image
     * @param string $alt
     * @return Image
     *
     * @throws NotSupported
     */
    protected function fillImageFields(Image $image, string $alt): Image
    {
        $originalPath = $this->getAbsolutePath();

        $image->setEntityPath($this->generateEntityPath())
            ->setAlt($alt)
            ->setCreatedAt($this->getDateTimeFromTimestamp(filemtime($originalPath)))
            ->setUpdatedAt($this->getDateTimeFromTimestamp(filectime($originalPath)));

        return $image;
    }

    /**
     * Сохранение загруженного изображения
     *
     * @param UploadedFile $file Загруженный файл
     * @param string $alt Альтернативный текст
     * @param bool $flush_result Произвести запись изменений в базу данных
     *
     * @return Image Сохранённое изображение
     *
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ImagickException
     */
    public function save(UploadedFile $file, string $alt = '', bool $flush_result = true): Image
    {
        $blurredImage = $this->schema->useVoter ? $this->imageService->createBlurred($file) : null;

        foreach (array_keys($this->schema->files) as $fileSize) {
            $this->saveSize($file, $fileSize);

            if ($this->schema->useVoter) {
                $this->saveSize($blurredImage, $fileSize, true);
            }
        }

        $image = $this->fillImageFields(
            $this->getEntityImage() ??
            (new Image())->setId($this->getId()),
            $alt
        );

        $this->entityManager->persist($image);

        $this->setEntityImage($image);
        $this->entityManager->persist($this->entity);

        if ($flush_result) {
            $this->entityManager->flush();
        }

        return $image;
    }


    /**
     * Получить объект DateTimeImmutable из отметки времени UNIX
     *
     * @param int $timestamp
     *
     * @return DateTimeImmutable
     */
    protected function getDateTimeFromTimestamp(int $timestamp): DateTimeImmutable
    {
        $result = new DateTimeImmutable();
        $result->setTimestamp($timestamp);

        return $result;
    }
}