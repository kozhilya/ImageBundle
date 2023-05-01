<?php

namespace Kozhilya\ImageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Kozhilya\ImageBundle\EventListener\AdvancedImageListener;
use Kozhilya\ImageBundle\ImageService;

/**
 * Публичный тип поля формы для загрузки и редактирования изображений
 */
class AdvancedImageType extends AbstractType
{
    public function __construct(
        protected ImageService $imageService,
        protected AdvancedImageListener $advancedImageListener
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this->advancedImageListener);

        $builder
            ->add('src', FileType::class, [
                'mapped' => false,
            ])
            ->add('alt', TextType::class, [
                'empty_data' => '',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'default_alt' => '',
            'preview_src' => null,
        ]);

        $resolver->setAllowedTypes('default_alt', 'string');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['local_id'] = 'advanced-image-loader-' . uniqid();
    }
}