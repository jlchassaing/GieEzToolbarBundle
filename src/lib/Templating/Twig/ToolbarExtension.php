<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */

namespace Gie\EzToolbar\Templating\Twig;


use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Language;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use EzSystems\EzPlatformAdminUi\Form\Data\Content\ContentVisibilityUpdateData;
use EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentCreateData;
use EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentEditData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationCopyData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationCopySubtreeData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationMoveData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationTrashContainerData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationTrashData;
use EzSystems\EzPlatformAdminUi\Form\Data\Location\LocationTrashWithAssetData;
use EzSystems\EzPlatformAdminUi\Form\Data\User\UserDeleteData;
use EzSystems\EzPlatformAdminUi\Form\Data\User\UserEditData;
use EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory;
use EzSystems\EzPlatformAdminUi\Form\Type\ChoiceList\Loader\ContentEditTranslationChoiceLoader;
use EzSystems\EzPlatformAdminUi\Form\Type\Content\ContentVisibilityUpdateType;
use EzSystems\EzPlatformAdminUi\Permission\LookupLimitationsTransformer;
use EzSystems\EzPlatformAdminUi\Specification\Content\ContentHaveAssetRelation;
use EzSystems\EzPlatformAdminUi\Specification\Content\ContentHaveUniqueRelation;
use EzSystems\EzPlatformAdminUi\Specification\ContentIsUser;
use EzSystems\EzPlatformAdminUi\Specification\Location\HasChildren;
use EzSystems\EzPlatformAdminUi\Specification\Location\IsContainer;
use Gie\EzToolbar\Manager\ToolbarManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ToolbarExtension extends AbstractExtension
{
    /** @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface  */
    private $templating;

    /** @var \Gie\EzToolbar\Manager\ToolbarManager  */
    private $toolbarManager;

    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    private $session;

    /** @var \eZ\Publish\API\Repository\LanguageService  */
    private $languageService;

    /** @var \eZ\Publish\API\Repository\ContentService  */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\LocationService  */
    private $locationService;

    /** @var \EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory   */
    private $formFactory;

    /** @var \Symfony\Component\Form\FormFactoryInterface  */
    private $sfFormFactory;

    /** @var \eZ\Publish\API\Repository\PermissionResolver  */
    private $permissionResolver;

    /** @var \EzSystems\EzPlatformAdminUi\Permission\LookupLimitationsTransformer  */
    private $lookupLimitationsTransformer;


    /**
     * ToolbarExtension constructor.
     *
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating
     * @param \Gie\EzToolbar\Manager\ToolbarManager $toolbarManager
     * @param \EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory $formFactory
     * @param \Symfony\Component\Form\FormFactoryInterface $sfFormFactory
     * @param \eZ\Publish\API\Repository\LanguageService $languageService
     * @param \eZ\Publish\API\Repository\PermissionResolver $permissionResolver
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     */
    public function __construct(
        EngineInterface $templating,
        ToolbarManager $toolbarManager,
        FormFactory $formFactory,
        FormFactoryInterface $sfFormFactory,
        LanguageService $languageService,
        ContentService $contentService,
        LocationService $locationService,
        PermissionResolver $permissionResolver,
        LookupLimitationsTransformer $lookupLimitationsTransformer,
        Session $session
    )
    {
        $this->templating = $templating;
        $this->toolbarManager = $toolbarManager;
        $this->session = $session;
        $this->formFactory = $formFactory;
        $this->sfFormFactory = $sfFormFactory;
        $this->languageService = $languageService;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->permissionResolver = $permissionResolver;
        $this->lookupLimitationsTransformer = $lookupLimitationsTransformer;
    }

    /**
     * @return array|\Twig\TwigFunction[]
     */
    public function getFunctions() {
        return [
            new TwigFunction('ezToolbar', [$this,'displayToolbar'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location|null $location
     * @return false|string|null
     * @throws \Twig\Error\Error
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function displayToolbar(?Location $location)
    {
        $content = $location->getContent();
        $contentType = $content->getContentType();
        if ($this->toolbarManager->canUse()) {
            $toolbarForm = $this->toolbarManager
                ->initToolbarForm($location)
                ->getToolbarForm();


            $params = [
                'form' => $toolbarForm->createView(),
                'currentUser' => $this->toolbarManager->getCurrentUser(),
                'content' => $content,
                'contentType' => $contentType,
                'location' => $location,
                'flashBag' => $this->session->getFlashBag()->all(),
                ];

            $params = $this->addContentActionForms($params, $location, $content);

            return $this->templating->render("@GieEzToolbar/toolbar/toolbar.html.twig",$params);
        }
        return null;


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
    private function addContentActionForms(array $params, Location $location, Content $content):array
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
            'form_location_copy' => $locationCopyType->createView(),
            'form_location_move' => $locationMoveType->createView(),
            'form_content_create' => $contentCreateType->createView(),
            'form_content_visibility_update' => $contentVisibilityUpdateForm->createView(),
            'form_subitems_content_edit' => $subitemsContentEdit->createView(),
            'form_location_copy_subtree' => $locationCopySubtreeType->createView(),
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
            $trashLocationContainerForm = $this->formFactory->trashContainerLocation(
                new LocationTrashContainerData($location)
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