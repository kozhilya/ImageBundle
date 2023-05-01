<?php


namespace Kozhilya\ImageBundle\Controller;


use DateTime;
use Doctrine\ORM\Exception\NotSupported;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Kozhilya\ImageBundle\ImageService;
use Kozhilya\ImageBundle\Processor\ImageProcessorTranslatable;

/**
 * Контроллер работы с изображениями
 */
class ImageController extends AbstractController
{
    /**
     * Максимальное время хранения файла в кеше пользователя
     */
    protected const MAX_AGE = 60 * 60 * 24 * 31; // 31 days

    public function __construct(
        protected readonly ImageService $imageService,
        protected readonly RequestStack $requestStack
    )
    {
    }

    /**
     * Создание ответа с информацией для кеширования изображения на стороне пользователя
     * @param string $path
     * @return Response
     */
    protected function cacheImageResponse(string $path): Response
    {
        $request = $this->requestStack->getMainRequest();

        $response = new BinaryFileResponse($path);
        $lastModifiedTimestamp = filemtime($path);
        $lastModified = (new DateTime)->setTimestamp($lastModifiedTimestamp);

        $response->setPublic();
        $response->setMaxAge(self::MAX_AGE);
        $response->setLastModified($lastModified);

        if ($response->isNotModified($request)) {
            $response->send();
        }

        return $response;
    }

    /**
     * Создание ответа с изображением
     * @param string $slug
     * @param string $id
     * @param string $size
     * @param string|null $locale
     * @return Response
     * @throws NotSupported
     */
    protected function buildResponse(string $slug, string $id, string $size, ?string $locale = null): Response
    {
        $request = $this->requestStack->getMainRequest();

        if (strpos($request->server->get('HTTP_ACCEPT'), 'image/webp')) {
            $imageProcessor = $this->imageService->resolve($slug, $id);

            if ($imageProcessor instanceof ImageProcessorTranslatable) {
                $imageProcessor = $imageProcessor->translate($locale);
            }

            $path = $imageProcessor->resolveAbsolutePath($size);

            if (!file_exists($path)) {
                return new Response('Not found', 404);
            }

            return $this->cacheImageResponse($path);
        } else {
            return new Response('Not Acceptable', 406);
        }
    }

    /**
     * Получение изображения оригинального размера
     * @param string $id
     * @param string $slug
     * @return Response
     * @throws NotSupported
     */
    public function getImage(string $id, string $slug): Response
    {
        return $this->buildResponse($slug, $id, 'original');
    }

    /**
     * Получение изображения определённого размера
     * @param string $id
     * @param string $size
     * @param string $slug
     * @return Response
     * @throws NotSupported
     */
    public function getImageSized(string $id, string $size, string $slug): Response
    {
        return $this->buildResponse($slug, $id, $size);
    }

    /**
     * Получение изображения оригинального размера на указанном языка
     * @param string $id
     * @param string $locale
     * @param string $slug
     * @return Response
     * @throws NotSupported
     */
    public function getImageTranslated(string $id, string $locale, string $slug): Response
    {
        return $this->buildResponse($slug, $id, 'original', $locale);
    }

    /**
     * Получение изображения определённого размера на указанном языка
     * @param string $id
     * @param string $locale
     * @param string $size
     * @param string $slug
     * @return Response
     * @throws NotSupported
     */
    public function getImageTranslatedSized(string $id, string $locale, string $size, string $slug): Response
    {
        return $this->buildResponse($slug, $id, $size, $locale);
    }

}