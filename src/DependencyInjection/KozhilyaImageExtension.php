<?php


namespace Kozhilya\ImageBundle\DependencyInjection;


use Exception;
use Symfony\Bridge\Twig\Extension\LogoutUrlExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class KozhilyaImageExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        $loader->load('services.xml');

        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        if ($container::willBeAvailable('symfony/twig-bridge', LogoutUrlExtension::class, ['symfony/security-bundle'])) {
            $loader->load('templating_twig.php');
        }

        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config['rules'] as $i => $rule) {
            if ($rule['save_original']) {
                $rule['files']['original'] = [
                    'format' => null,
                    'width' => null,
                    'height' => null,
                ];
            }

            $config['rules'][$i] = $rule;
//            $this->imageService->register($config['rules'][$i]);
        }

        $this->setBundleParameters($container, $config);

//        $this->addAnnotatedClassesToCompile([
//            'Kozhilya\\ImageBundle\\Repository\\'
//        ]);
    }

    private function setBundleParameters(ContainerBuilder $container, $config): self
    {
        $container->setParameter('kozhilya_image.config.data', $config);

        return $this;
    }
}