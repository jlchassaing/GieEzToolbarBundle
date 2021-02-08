<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */

namespace Gie\EzToolbar\Manager;


use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Language;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;
use eZ\Publish\API\Repository\PermissionResolver;
use EzSystems\EzPlatformAdminUi\Form\Data\Content\ContentVisibilityUpdateData;
use EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentCreateData;
use EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentEditData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationCopyData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationCopySubtreeData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationMoveData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationTrashContainerData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationTrashData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationTrashWithAssetData;
use EzSystems\EzPlatformAdminUi\Form\Type\ChoiceList\Loader\ContentEditTranslationChoiceLoader;
use EzSystems\EzPlatformAdminUi\Form\Type\Content\ContentVisibilityUpdateType;
use EzSystems\EzPlatformAdminUi\Permission\LookupLimitationsTransformer;
use EzSystems\EzPlatformAdminUi\Specification\Content\ContentHaveAssetRelation;
use EzSystems\EzPlatformAdminUi\Specification\Content\ContentHaveUniqueRelation;
use EzSystems\EzPlatformAdminUi\Specification\Location\HasChildren;
use EzSystems\EzPlatformAdminUi\Specification\Location\IsContainer;
use Gie\EzToolbar\Form\Data\ToolbarData;
use Gie\EzToolbar\Form\Type\ToolbarType;
use EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpFoundation\Request;

class ToolbarManager
{
    /** @var \eZ\Publish\API\Repository\PermissionResolver  */
    private $permissionResolver;

    /** @var \eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper  */
    private $globalHelper;

    /** @var \eZ\Publish\API\Repository\ContentService  */
    private $contentService;

   /** @var \eZ\Publish\API\Repository\LocationService  */
    private $locationService;

    /** @var \eZ\Publish\API\Repository\LanguageService  */
    private $languageService;

    /** @var \EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory  */
    private $formFactory;

    /** @var \Symfony\Component\Form\FormFactoryInterface  */
    private $sfFormFactory;

    /** @var \eZ\Publish\API\Repository\UserService */
    private $userService;

    /** @var \EzSystems\EzPlatformAdminUi\Permission\LookupLimitationsTransformer  */
    private $lookupLimitationsTransformer;

    /**
     * ToolbarManager constructor.
     *
     * @param \eZ\Publish\API\Repository\PermissionResolver $permissionResolver
     * @param \eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper $globalHelper
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\LanguageService $languageService
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory $formFactory
     * @param \Symfony\Component\Form\FormFactoryInterface $sfFormFactory
     * @param \EzSystems\EzPlatformAdminUi\Permission\LookupLimitationsTransformer $lookupLimitationsTransformer
     */
    public function __construct(
        PermissionResolver $permissionResolver,
        GlobalHelper $globalHelper,
        ContentService $contentService,
        LocationService $locationService,
        LanguageService $languageService,
        UserService $userService,
        FormFactory $formFactory,
        FormFactoryInterface $sfFormFactory,
        LookupLimitationsTransformer $lookupLimitationsTransformer
    ) {
        $this->permissionResolver = $permissionResolver;
        $this->globalHelper = $globalHelper;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->languageService = $languageService;
        $this->userService = $userService;
        $this->formFactory = $formFactory;
        $this->sfFormFactory = $sfFormFactory;
        $this->lookupLimitationsTransformer = $lookupLimitationsTransformer;
    }

    /**
     * @return array|bool
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function canUse()
    {
        $res = $this->permissionResolver->hasAccess('toolbar', 'use') !== false;
        return $res;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\User
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function getCurrentUser()
    {
        return $this->userService->loadUser(
            $this->permissionResolver->getCurrentUserReference()->getUserId()
        );
    }

    /**
     * @param array $params
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     *
     * @return array
     * @throws \EzSystems\EzPlatformAdminUi\Exception\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function addContentActionForms(array $params, Location $location, Content $content):array
    {
        $versionInfo = $content->versionInfo;

        $locationCopyType = $this->formFactory->copyLocation(
            new LocationCopyData($location)
        );

        $locationMoveType = $this->formFactory->moveLocation(
            new LocationMoveData($location)
        );

        $subitemsContentEdit = $this->formFactory->contentEdit(
            null,
            'form_subitems_content_edit'
        );

        $contentCreateType = $this->formFactory->createContent(
            $this->getContentCreateData($location)
        );

        $locationCopySubtreeType = $this->formFactory->copyLocationSubtree(
            new LocationCopySubtreeData($location)
        );

        $contentVisibilityUpdateForm = $this->sfFormFactory->create(
            ContentVisibilityUpdateType::class,
            new ContentVisibilityUpdateData(
                $location->getContentInfo(),
                $location,
                $location->getContentInfo()->isHidden
            )
        );

        $locationTrashType = $this->formFactory->trashLocation(
            new LocationTrashData($location)
        );

        $contentEditType = $this->createContentEditForm(
            $content->contentInfo,
            $versionInfo,
            null,
            $location
        );

        $params +=[
//            'form_location_copy' => $locationCopyType->createView(),
//            'form_location_move' => $locationMoveType->createView(),
            'form_content_create' => $contentCreateType->createView(),
//            'form_content_visibility_update' => $contentVisibilityUpdateForm->createView(),
            'form_subitems_content_edit' => $subitemsContentEdit->createView(),
//            'form_location_copy_subtree' => $locationCopySubtreeType->createView(),
            'form_location_trash' => $locationTrashType->createView(),
            'form_content_edit' => $contentEditType->createView(),
        ];

        $contentHaveAssetRelation = new ContentHaveAssetRelation($this->contentService);

        if ($contentHaveAssetRelation
            ->and(new ContentHaveUniqueRelation($this->contentService))
            ->isSatisfiedBy($content)
        ) {
            $trashWithAssetType = $this->formFactory->trashLocationWithAsset(
                new LocationTrashWithAssetData($location)
            );

            $params += [
                /** @deprecated since 2.5, to be removed in 3.0 */
                'form_location_trash_with_single_asset' => $trashWithAssetType->createView(),
            ];
        } elseif ($contentHaveAssetRelation->isSatisfiedBy($content)) {
            $locationTrashType = $this->formFactory->trashLocation(
                new LocationTrashData($location)
            );

            $parmas += [
                /** @deprecated since 2.5, to be removed in 3.0 */
                'form_location_trash_with_asset' => $locationTrashType->createView(),
            ];
        }

        $isContainer = new IsContainer();
        $hasChildren = new HasChildren($this->locationService);

        if ($isContainer->and($hasChildren)->isSatisfiedBy($location)) {
            $trashLocationContainerForm = $this->formFactory->trashLocation(
                new LocationTrashData($location)
            );
            $params +=[
                /** @deprecated since 2.5, to be removed in 3.0 */
                'form_location_trash_container' => $trashLocationContainerForm->createView(),
            ];
        }
        return $params;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location|null $location
     *
     * @return \EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentCreateData
     */
    private function getContentCreateData(?Location $location): ContentCreateData
    {
        $languages = $this->languageService->loadLanguages();
        $language = 1 === \count($languages)
            ? array_shift($languages)
            : null;

        return new ContentCreateData(null, $location, $language);
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\ContentInfo|null $contentInfo
     * @param \eZ\Publish\API\Repository\Values\Content\VersionInfo|null $versionInfo
     * @param \eZ\Publish\API\Repository\Values\Content\Language|null $language
     * @param \eZ\Publish\API\Repository\Values\Content\Location|null $location
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createContentEditForm(
        ?ContentInfo $contentInfo = null,
        ?VersionInfo $versionInfo = null,
        ?Language $language = null,
        ?Location $location = null
    ): FormInterface {
        $languageCodes = $versionInfo->languageCodes ?? [];

        return $this->formFactory->contentEdit(
            new ContentEditData($contentInfo, null, $language, $location),
            null,
            [
                'choice_loader' => new ContentEditTranslationChoiceLoader(
                    $this->languageService,
                    $this->permissionResolver,
                    $contentInfo,
                    $this->lookupLimitationsTransformer,
                    $languageCodes
                ),
            ]
        );
    }



}