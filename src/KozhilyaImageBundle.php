<?php

namespace Kozhilya\ImageBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KozhilyaImageBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $ormCompilerClass = 'Doctrine\\Bundle\\DoctrineBundle\\DependencyInjection\\Compiler\\DoctrineOrmMappingsPass';

        if (class_exists($ormCompilerClass)) {
            $namespaces = ['Kozhilya\\ImageBundle\\Entity'];
            $directories = [(realpath(__DIR__ . '/Entity'))];
            $managerParameters = [];
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createAttributeMappingDriver(
                    $namespaces,
                    $directories,
                    $managerParameters,
                    aliasMap: ['KozhilyaImageBundle' => 'Kozhilya\\ImageBundle\\Entity']
                )
            );
        }


    }
}