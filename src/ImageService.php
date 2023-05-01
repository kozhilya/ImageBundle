<?php


namespace Kozhilya\ImageBundle;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Exception;
use Imagick;
use ImagickException;
use Kozhilya\ImageBundle\Controller\ImageController;
use Kozhilya\ImageBundle\Entity\Image;
use Kozhilya\ImageBundle\Processor\ImageProcessor;
use Kozhilya\ImageBundle\Processor\ImageProcessorInterface;
use Kozhilya\ImageBundle\Processor\ImageProcessorTranslatable;
use Kozhilya\ImageBundle\Processor\ImageProcessorFileSchema;
use Kozhilya\ImageBundle\Processor\ImageProcessorSchema;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ImageService implements RouteLoaderInterface
{
    /**
     * Разделитель уникального пути сущности
     */
    const PATH_SEPARATOR = ':';

    /**
     * Флаг загрузки списка путей
     *
     * @var bool
     */
    protected bool $is_loaded = false;

    /**
     * Список всех зарегистрированных схем
     *
     * @var ImageProcessorSchema[]
     */
    protected array $schemas = [];

    /**
     * Путь папке для загрузки изображений
     *
     * @var string
     */
    protected string $upload_path;

    /**
     * Файловая система
     *
     * @var Filesystem
     */
    private Filesystem $filesystem;

    public function __construct(
        array $config,
        protected UrlGeneratorInterface $router,
        protected RequestStack $requestStack,
        protected ParameterBagInterface $parameterBag,
        protected EntityManager $entityManager,
        protected AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->filesystem = new Filesystem();
        $this->upload_path = $config['path'];

        foreach ($config['rules'] as $rule) {
            $this->schemas[] = new ImageProcessorSchema($rule);
        }
    }

    /**
     * Получения текущего языка сайта
     *
     * @return string
     */
    public function getLocale(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request->get('_locale');
    }

    /**
     * Получение обработчика изображений на основе URL-запроса
     *
     * @param string $slug
     * @param string $id
     *
     * @return ImageProcessorInterface
     * @throws NotSupported
     * @throws Exception
     */
    public function resolve(string $slug, string $id): ImageProcessorInterface
    {
        foreach ($this->schemas as $schema) {
            if ($schema->slug === $slug) {
                $repository = $this->entityManager->getRepository($schema->class);

                if ($schema->useSlug) {
                    $entity = $repository->findOneBy(['slug' => $id]);
                }
                else {
                    $entity = $repository->find($id);
                }

                return $this->get($entity);
            }
        }

        throw new Exception(sprintf("Can't resolve slug \"%s\" for image.", $slug));
    }

    /**
     * Получение обработчика изображений для сущности
     *
     * @param $entity
     *
     * @return ImageProcessorInterface
     * @throws Exception
     */
    public function get($entity): ImageProcessorInterface
    {
        foreach ($this->schemas as $schema) {
            if ($schema->testEntity($entity)) {
                return $schema->translatable
                    ? new ImageProcessorTranslatable($this, $schema, $entity)
                    : new ImageProcessor($this, $schema, $entity);
            }
        }

        throw new Exception(
            sprintf(
                "Can't resolve image processor for object of class \"%s\".",
                get_class($entity)
            )
        );
    }

    /**
     * Получение обработчика для изображения
     *
     * @param Image $image
     * @return ImageProcessorInterface
     * @throws NotSupported
     */
    public function resolveImage(Image $image): ImageProcessorInterface
    {
        [$slug, $id, $locale] = array_pad(explode(ImageService::PATH_SEPARATOR, $image->getEntityPath()), 3, null);

        $processor = $this->resolve($slug, $id);

        if ($processor instanceof ImageProcessorTranslatable) {
            $processor = $processor->translate($locale);
        }

        return $processor;
    }

    /**
     * Получение пути к папке для загрузки изображений
     *
     * @return string
     */
    public function getUploadPath(): string
    {
        return '/'.$this->upload_path;
    }

    /**
     * Построение абсолютного пути
     *
     * @param string ...$parts
     * @return string
     */
    public function generateAbsolutePath(string ...$parts): string
    {
        $parts = array_merge([$this->parameterBag->get('kernel.project_dir'), 'public'], $parts);

        return self::formatPathString(implode(DIRECTORY_SEPARATOR, $parts));
    }

    /**
     * Построение веб пути
     *
     * @param string ...$parts
     * @return string
     */
    public function generateWebPath(string ...$parts): string
    {
        $parts = array_merge([$this->upload_path], $parts);

        return self::formatPathString(implode(DIRECTORY_SEPARATOR, $parts), '/');
    }

    /**
     * Построение корректного пути файла
     *
     * @param string $path
     * @param string $sep
     *
     * @return string
     */
    private static function formatPathString(string $path, string $sep = DIRECTORY_SEPARATOR): string
    {
        $path = str_replace(['/', '\\'], $sep, $path);
        $is_root = str_starts_with($path, $sep);

        $parts = array_filter(explode($sep, $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            }
            else {
                $absolutes[] = $part;
            }
        }

        return ($is_root ? $sep : '').implode($sep, $absolutes);
    }

    /**
     * Создание размытого изображения
     *
     * @param UploadedFile $input
     * @return UploadedFile
     * @throws ImagickException
     */
    public function createBlurred(UploadedFile $input): UploadedFile
    {
        $result = tempnam("/tmp", "IM_SER_");
        $image = new Imagick($input->getRealPath());

        $image->blurImage(50, 20);

        $image->writeImageFile(fopen($result, 'wb'));

        return new UploadedFile(
            $result,
            basename($result),
            mime_content_type($result),
            null,
            true
        );
    }

    /**
     * Изменение размера изображения
     *
     * @param UploadedFile $source
     * @param string $filename
     * @param ImageProcessorFileSchema $fileSchema
     * @return void
     * @throws ImagickException
     */
    public function resize(
        UploadedFile $source,
        string $filename,
        ImageProcessorFileSchema $fileSchema
    ): void {
        [$image_width, $image_height] = getimagesize($source->getRealPath());
        $ratio = $image_width / $image_height;

        $width = $fileSchema->width ?? $image_width;
        $height = $fileSchema->height ?? $image_height;

        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        }
        else {
            $height = $width / $ratio;
        }

        $image = new Imagick($source->getRealPath());

        $image->resizeImage($width, $height, Imagick::FILTER_GAUSSIAN, 1);
        $image->setFormat($fileSchema->format);

        $image->writeImageFile(fopen($filename, 'wb'));
    }

    /**
     * Удаление изображения
     *
     * @param string $path
     * @return void
     */
    public function remove(string $path): void
    {
        $this->filesystem->remove($path);
    }

    /**
     * Получение загрузчика
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Список всех зарегистрированных схем
     *
     * @return array|ImageProcessorSchema[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Загрузка списка путей
     *
     * @return RouteCollection
     */
    public function loadRoutes(): RouteCollection
    {
        if (true === $this->is_loaded) {
            throw new RuntimeException('Do not add the "kozhilya_image" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->getSchemas() as $schema) {
            $prefix = sprintf("%s/%s", $this->getUploadPath(), $schema->slug);
            $defaults = [
                'slug' => $schema->slug,
                '_controller' => ImageController::class.'::getImage',
            ];
            $requirements = [
                'id' => '[a-zA-Z0-9\-_]+',
            ];

            if ($schema->translatable) {
                $prefix .= '/{locale}';
                $defaults['_controller'] .= 'Translated';
            }

            $prefix .= '/{id}';
            $sizes = array_keys($schema->files);

            $defaultsSized = $defaults;
            $defaultsSized['_controller'] .= 'Sized';
            $requirements['size'] = implode('|', $sizes);

            $routeOriginal = new Route(sprintf("%s.{_format}", $prefix), $defaults, $requirements);
            $routeSized = new Route(sprintf("%s-{size}.{_format}", $prefix), $defaultsSized, $requirements);

            $routes->add(sprintf("load_sized_%s", $schema->slug), $routeSized);
            $routes->add(sprintf("load_%s", $schema->slug), $routeOriginal);
        }

        return $routes;
    }

    /**
     * Построение пути к Web-изображения
     *
     * @param ImageProcessorInterface $imageProcessor
     * @param string $size
     * @param array $options
     *
     * @return string
     */
    public function buildPath(
        ImageProcessorInterface $imageProcessor,
        string $size = 'original',
        array $options = []
    ): string {
        $route_name = 'load_';
        $route_options = [];

        if ($size !== 'original') {
            $route_name .= 'sized_';
            $route_options['size'] = $size;
        }

        if ($imageProcessor instanceof ImageProcessorTranslatable) {
            $route_options['locale'] = $imageProcessor->getLocale();
        }

        $route_name .= $imageProcessor->schema->slug;
        $route_options['_format'] = $imageProcessor->schema->files[$size]->format;
        $route_options['id'] = $imageProcessor->getPathId();

        return $this->router->generate($route_name, array_merge($route_options, $options));
    }

    /**
     * Проверка доступа к изображению
     *
     * @param $entity
     * @return bool
     */
    public function isGranted($entity): bool
    {
        return $this->authorizationChecker->isGranted('image', $entity);
    }
}