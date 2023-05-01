<?php

namespace Kozhilya\ImageBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Kozhilya\ImageBundle\Repository\ImageRepository;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

/**
 * ORM-класс для хранения данных об изображении
 */
#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private ?string $id = null;

    #[ORM\Column(type: 'uuid')]
    private ?UuidV4 $uuid = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $alt = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $entityPath = '';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->getId();
    }

    /**
     * Полный UUID изображения
     *
     * @return UuidV4
     */
    public function getUuid(): UuidV4
    {
        if (is_null($this->uuid)) {
            $this->uuid = Uuid::v4();
        }

        return $this->uuid;
    }

    /**
     * ID изображения для сохранения на диске
     *
     * @return string
     */
    public function getId(): string
    {
        if (is_null($this->id)) {
            $this->id = $this->getUuid()->toBase58();
        }

        return $this->id;
    }

    /**
     * Установить ID изображения для сохранения на диске
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Альтернативный текст изображения (содержимого атрибута alt)
     *
     * @return string|null
     */
    public function getAlt(): ?string
    {
        return $this->alt;
    }

    /**
     * Установить альтернативный текст изображения (содержимого атрибута alt)
     *
     * @param string $alt
     *
     * @return $this
     */
    public function setAlt(string $alt): self
    {
        $this->alt = $alt;

        return $this;
    }

    /**
     * Уникальный путь к объекту
     *
     * @return string
     */
    public function getEntityPath(): string
    {
        return $this->entityPath;
    }

    /**
     * Установить уникальный путь к объекту
     *
     * @param string $path
     * @return $this
     *
     * @internal
     */
    public function setEntityPath(string $path): self
    {
        $this->entityPath = $path;
        return $this;
    }

    /**
     * Время создания файла
     *
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Установить время создания файла
     *
     * @param DateTimeImmutable $createdAt
     * @return $this
     *
     * @internal
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Время изменения файла
     *
     * @return DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Установить время изменения файла
     *
     * @param DateTimeImmutable $updatedAt
     * @return $this
     *
     * @internal
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
