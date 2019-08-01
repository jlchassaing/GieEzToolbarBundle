<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Gie\EzToolbar\Form\Type;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\User\Limitation;
use eZ\Publish\Core\Repository\Permission\PermissionResolver;
use eZ\Publish\SPI\Limitation\Target\Builder\VersionBuilder;
use EzSystems\EzPlatformAdminUi\Form\Type\ChoiceList\Loader\ContentCreateContentTypeChoiceLoader;
use EzSystems\EzPlatformAdminUi\Form\Type\Content\ContentType;
use EzSystems\EzPlatformAdminUi\Form\Type\Content\LocationType;
use EzSystems\EzPlatformAdminUi\Form\Type\ContentType\ContentTypeChoiceType;
use EzSystems\EzPlatformAdminUi\Permission\LookupLimitationsTransformer;
use EzSystems\EzPlatformAdminUi\Permission\PermissionCheckerInterface;
use Gie\EzToolbar\Form\Data\ToolbarData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToolbarType extends AbstractType
{
    /** @var \Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface */
    private $contentTypeChoiceLoader;

    /** @var \EzSystems\EzPlatformAdminUi\Permission\PermissionCheckerInterface */
    private $permissionChecker;

    /** @var \EzSystems\EzPlatformAdminUi\Permission\LookupLimitationsTransformer */
    private $lookupLimitationsTransformer;

    /** @var \eZ\Publish\Core\Repository\Permission\PermissionResolver */
    private $permissionResolver;

    /**
     * ToolbarType constructor.
     * @param \Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface $contentTypeChoiceLoader
     * @param \EzSystems\EzPlatformAdminUi\Permission\PermissionCheckerInterface $permissionChecker
     * @param \eZ\Publish\Core\Repository\Permission\PermissionResolver $permissionResolver
     * @param \EzSystems\EzPlatformAdminUi\Permission\LookupLimitationsTransformer $lookupLimitationsTransformer
     */
    public function __construct(

        ChoiceLoaderInterface $contentTypeChoiceLoader,
        PermissionCheckerInterface $permissionChecker,
        PermissionResolver $permissionResolver,
        LookupLimitationsTransformer $lookupLimitationsTransformer
    ) {

        $this->contentTypeChoiceLoader = $contentTypeChoiceLoader;
        $this->permissionChecker = $permissionChecker;
        $this->permissionResolver = $permissionResolver;
        $this->lookupLimitationsTransformer = $lookupLimitationsTransformer;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     * @throws \EzSystems\EzPlatformAdminUi\Exception\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $restrictedContentTypesIds = [];
        $canEdit = true;
        $canDelete = true;
        $isContainer = false;

        /** @var ToolbarData $toolbarData */
        $toolbarData = $options['data'];
        if ($location = $toolbarData->getParentLocation()) {
            $content = $toolbarData->getContent();
            $limitationsValues = $this->getLimitationValuesForLocation($location);
            $restrictedContentTypesIds = $limitationsValues[Limitation::CONTENTTYPE];
            $canEdit = $this->getCanEdit($location, $content);
            $isContainer = $content->getContentType()->isContainer;

            $canDelete = $this->permissionResolver->canUser(
                'content',
                'remove',
                $content
            );
            $canTrashLocation = $this->permissionResolver->canUser(
                'content',
                'remove',
                $location->getContentInfo(),
                [$location]
            );
        }

        $builder
            ->add(
                'content_type',
                ContentTypeChoiceType::class,
                [
                    'label' => false,
                    'multiple' => false,
                    'expanded' => false,
                    'disabled' => !$isContainer,
                    'choice_loader' => new ContentCreateContentTypeChoiceLoader($this->contentTypeChoiceLoader, $restrictedContentTypesIds),
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
                    'disabled' => !$isContainer,
                ]
            );
        if ($canEdit) {
            $builder->add(
                'edit',
                SubmitType::class,
                [
                    'label' => 'eztoolbar.edit'
                ]
            );
        }
        if ($canDelete) {
            $builder->add(
                'trash',
                SubmitType::class,
                [
                    'label' => 'eztoolbar.trash'
                ]
            );
        }

    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => ToolbarData::class,
                'translation_domain' => 'forms',
            ]);
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     *
     * @return array
     *
     * @throws \EzSystems\EzPlatformAdminUi\Exception\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    private function getLimitationValuesForLocation(Location $location): array
    {
        $lookupLimitationsResult = $this->permissionChecker->getContentCreateLimitations($location);

        return $this->lookupLimitationsTransformer->getGroupedLimitationValues(
            $lookupLimitationsResult,
            [Limitation::CONTENTTYPE, Limitation::LANGUAGE]
        );
    }

    private function getCanEdit(Location $location, Content $content) {
        $canEdit = $this->permissionResolver->canUser(
            'content',
            'edit',
            $location->getContentInfo(),
            [
                (new VersionBuilder())
                    ->translateToAnyLanguageOf($content->getVersionInfo()->languageCodes)
                    ->build(),
            ]
        );
        return $canEdit;
    }
}
