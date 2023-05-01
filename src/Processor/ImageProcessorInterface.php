<?php

namespace Kozhilya\ImageBundle\Processor;

use Kozhilya\ImageBundle\Entity\Image;
use Kozhilya\ImageBundle\ImageService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ImageProcessorInterface
{
    /**
     * Создание нового обработчика для данной сущности
     *
     * @internal
     *
     * @see ImageService::get()
     * @see ImageService::resolve()
     * @see ImageService::resolveImage()
     *
     * @param ImageService $imageService
     * @param ImageProcessorSchema $schema
     * @param object|null $entity
     */
    public function __construct(ImageService $imageService, ImageProcessorSchema $schema, ?object $entity);

    /**
     * Получение изображения сущности
     *
     * @return Image|null
     */
    public function getEntityImage(): ?Image;

    /**
     * Установка изображения сущности
     *
     * @param Image $image
     */
    public function setEntityImage(Image $image): void;

    /**
     * Проверка, может ли пользователь получить доступ к изображению,
     * если в настройках для изображений задана проверка на Voter.
     *
     * @return bool
     */
    public function canAccess(): bool;

    /**
     * Создание ID для нового изображения (используется в имени файла на диске)
     *
     * @return string
     * @internal
     */
    public function generateId(): string;

    /**
     * Получение ID изображения (используется в имени файла на диске)
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Получение пути к файлу на сайте
     *
     * @param string $size Размер изображения
     * @return string
     */
    public function getWebPath(string $size = 'original'): string;

    /**
     * Получение полного пути к файлу
     *
     * @param string $size Размер изображения
     * @param bool $blur Размыть изображение (работает только если установлен Voter
     *                   в настройках изображения, см. {@see ImageProcessorInterface::canAccess()})
     * @return string
     * @internal
     */
    public function getAbsolutePath(string $size = 'original', bool $blur = false): string;

    /**
     * Получение полного пути к файлу с автоматической проверкой доступа
     *
     * @param string $size Размер изображения
     * @return string
     */
    public function resolveAbsolutePath(string $size = 'original'): string;

    /**
     * Сохранение загруженного изображения
     *
     * @param UploadedFile $file
     * @param string $alt
     * @return Image
     */
    public function save(UploadedFile $file, string $alt = ''): Image;
}