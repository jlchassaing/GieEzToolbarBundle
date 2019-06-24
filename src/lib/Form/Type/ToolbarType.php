<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Gie\EzToolbar\Form\Type;

use eZ\Publish\API\Repository\LanguageService;
use EzSystems\EzPlatformAdminUi\Form\Type\Content\ContentType;
use EzSystems\EzPlatformAdminUi\Form\Type\Content\LocationType;
use EzSystems\EzPlatformAdminUi\Form\Type\ContentType\ContentTypeChoiceType;
use Gie\EzToolbar\Form\Data\ToolbarData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToolbarType extends AbstractType
{
    /** @var \Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface */
    private $contentTypeChoiceLoader;

    /**
     * ToolbarType constructor.
     * @param \Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface $contentTypeChoiceLoader
     */
    public function __construct(
        ChoiceLoaderInterface $contentTypeChoiceLoader

    ) {
        $this->contentTypeChoiceLoader = $contentTypeChoiceLoader;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add(
                'content_type',
                ContentTypeChoiceType::class,
                [
                    'label' => false,
                    'multiple' => false,
                    'expanded' => false,
                    'choice_loader' => $this->contentTypeChoiceLoader,
                ]
            )
            ->add(
                'parent_location',
                LocationType::class,
                ['label' => false]
            )
            ->add(
                'content',
                ContentType::class,
                ['label' => false]
            )
            ->add(
                'create',
                SubmitType::class,
                [
                    'label' => /** @Desc("Create") */
                        'eztoolbar.create',
                ]
            )
            ->add(
                'edit',
                SubmitType::class,
                [
                    'label' => 'eztoolbar.edit'
                ]
            );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => ToolbarData::class,
                'translation_domain' => 'forms',
            ]);
    }
}
