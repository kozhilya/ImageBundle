<?php


namespace Kozhilya\ImageBundle\TwigExtensions;

use Doctrine\ORM\Exception\NotSupported;
use Kozhilya\ImageBundle\Processor\ImageProcessorTranslatable;
use Kozhilya\ImageBundle\ImageService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Kozhilya\ImageBundle\Entity\Image as ImageEntity;

class ImageTwigExtension extends AbstractExtension
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('image', [$this, 'image'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param ?ImageEntity $image Entity to render
     * @param ?string $size Size to render
     * @param array $settings Setting object
     *
     *  $settings = [
     *      'locale' => (?string) Locale to render (if needed)
     *      'attrs' => (?array) Additional attributes for &lt;img&gt;
     *  ]
     * @throws NotSupported
     */
    public function image(?ImageEntity $image, string $size = null, array $settings = []): string
    {
        $locale = $settings['locale'] ?? $this->imageService->getLocale();
        $attrs = $settings['attrs'] ?? [];
        $size = $size ?? 'original';

        if (is_null($image)) {
            $attrs['src'] = 'https://placehold.jp/200x200.png?text=No image';
            $attrs['alt'] = 'No image';
        }
        else {
            $processor = $this->imageService->resolveImage($image);

            if ($processor instanceof ImageProcessorTranslatable) {
                $processor->translate($locale);
            }

            $attrs['src'] = $processor->getWebPath($size);
            $attrs['alt'] = $image->getAlt();
        }

        $attrsHtml = [];
        foreach ($attrs as $attr => $value) {
            $attrsHtml[] = sprintf('%s="%s"'
                , $attr
                , htmlspecialchars($value)
            );
        }

        return sprintf('<'.'img %s/>'
            , implode(' ', $attrsHtml)
        );
    }
}