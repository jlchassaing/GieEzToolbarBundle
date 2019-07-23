<?php

namespace Gie\EzToolbarBundle\Controller;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\URLAliasService;
use eZ\Publish\API\Repository\Values\Content\Location;
use EzSystems\EzPlatformAdminUi\Form\SubmitHandler;
use EzSystems\EzPlatformAdminUiBundle\Controller\Controller;
use EzSystems\RepositoryForms\Data\Content\CreateContentDraftData;
use EzSystems\RepositoryForms\Form\ActionDispatcher\ActionDispatcherInterface;
use EzSystems\RepositoryForms\Form\Type\Content\ContentDraftCreateType;
use Gie\EzToolbar\Form\Data\ToolbarData;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;
use Gie\EzToolbar\Form\Type\ToolbarType;
use Gie\EzToolbar\Manager\ToolbarManager;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

class ToolbarController extends Controller
{

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var LocationService
     */
    private $locationService;

    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    private $contentService;

    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    private $contentTypeService;

    /**
     * @var \eZ\Publish\API\Repository\LanguageService
     */
    private $languageService;

    /**
     * @var \eZ\Publish\API\Repository\URLAliasService
     */
    private $urlAliasService;

    /**
     * @var PermissionResolver
     */
    private $permissionResolver;

    /**
     * @var SubmitHandler
     */
    private $submitHandler;

    /**
     * @var \Symfony\Component\Form\FormFactory
     */
    private $factory;

    /**
     * @var \Symfony\Component\Routing\Router
     */
    private $router;

    /**
     * @var GlobalHelper
     */
    private $globalHelper;

    /**
     * @var \EzSystems\RepositoryForms\Form\ActionDispatcher\ActionDispatcherInterface
     */
    private $actionDispatcher;

    /**
     * @var \Gie\EzToolbar\Manager\ToolbarManager
     */
    private $toolbarManager;

    /**
     * @var array
     */
    private $languages;


    /**
     * ToolbarController constructor.
     * @param \Symfony\Component\Templating\EngineInterface $templating
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\LanguageService $languageService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\API\Repository\PermissionResolver $permissionResolver
     * @param \EzSystems\EzPlatformAdminUi\Form\SubmitHandler $submitHandler
     * @param \Symfony\Component\Form\FormFactory $factory
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper $globalHelper
     * @param array $languages
     *
     */
    public function __construct(
        EngineInterface $templating,
        LocationService $locationService,
        LanguageService $languageService,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        URLAliasService $urlAliasService,
        PermissionResolver $permissionResolver,
        SubmitHandler $submitHandler,
        FormFactory $factory,
        RouterInterface $router,
        GlobalHelper $globalHelper,
        ActionDispatcherInterface $actionDispatcher,
        ToolbarManager $toolbarManager,
        array $languages
    )
    {
        $this->templating = $templating;
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->languageService = $languageService;
        $this->permissionResolver = $permissionResolver;
        $this->urlAliasService = $urlAliasService;
        $this->submitHandler = $submitHandler;
        $this->factory = $factory;
        $this->router = $router;
        $this->globalHelper = $globalHelper;
        $this->actionDispatcher = $actionDispatcher;
        $this->toolbarManager = $toolbarManager;
        $this->languages = $languages;
    }


    public function renderAction(Request $request, Location $location)
    {
        $response = new Response();

        if ($this->toolbarManager->canUse())
        {
            $toolbarData = new ToolbarData();
            $toolbarForm = $this->toolbarManager
                ->initToolbarForm($toolbarData)
                ->handleRequest($request)
                ->getToolbarForm();

            if ($toolbarForm->isSubmitted() && $toolbarForm->isValid()) {

                $nextAction = $toolbarForm->getClickedButton()->getName();
                $currentLanguage = $this->languageService->loadLanguage($this->languages[0]);
                $data = $this->toolbarManager->getToolbarData();

                    $contentType = $data->getContentType();
                    $parentLocation = $data->getParentLocation();
                    $content = $data->getContent();

                    if ($nextAction === 'create') {
                        return new RedirectResponse($this->router->generate('ez_content_create_no_draft', [
                            'contentTypeIdentifier' => $contentType->identifier,
                            'language' => $currentLanguage->languageCode,
                            'parentLocationId' => $parentLocation->id,
                        ]));
                    }

                    $createContentDraft = new CreateContentDraftData();
                    $contentInfo = $content->contentInfo;
                    $createContentDraft->contentId = $content->id;

                    $createContentDraft->fromVersionNo = $contentInfo->currentVersionNo;
                    $createContentDraft->fromLanguage = $contentInfo->mainLanguageCode;


                    $form = $this->createForm(
                        ContentDraftCreateType::class,
                        $createContentDraft,
                        [
                            'action' => $this->generateUrl('ez_content_draft_create'),
                        ]
                    );

                    $this->actionDispatcher->dispatchFormAction($form, $createContentDraft, 'createDraft');
                    if ($response = $this->actionDispatcher->getResponse()) {
                        return $response;
                    }

            }
            $url = $this->urlAliasService->reverseLookup($location);
            $response = new RedirectResponse($url->path);
        }
        return $response;
    }





    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    private function getRootLocation()
    {
        return $this->globalHelper->getRootLocation();
    }

    /**
     * @param null $locationId
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    private function getCurrentLocation($pathString)
    {
        if ($pathString !== null)
        {
            if ($pathString instanceof Location) {
                return $pathString;
            } elseif (is_string($pathString) and strlen($pathString) > 0) {
                $locationId = array_reverse(explode('/',trim($pathString,'/')))[0];
                return $this->locationService->loadLocation($locationId);
            }
        }
        return $this->getRootLocation();
    }



}
