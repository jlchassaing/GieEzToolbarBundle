<?php

namespace Gie\EzToolbarBundle\Controller;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\URLAliasService;
use eZ\Publish\API\Repository\Values\Content\Location;
use EzSystems\EzPlatformAdminUi\Form\SubmitHandler;
use EzSystems\RepositoryForms\Data\Content\CreateContentDraftData;
use EzSystems\RepositoryForms\Form\ActionDispatcher\ActionDispatcherInterface;
use EzSystems\RepositoryForms\Form\Type\Content\ContentDraftCreateType;
use Gie\EzToolbar\Form\Data\ToolbarData;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;
use Gie\EzToolbar\Manager\ToolbarManager;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

class ToolbarController
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
     * @param \eZ\Publish\API\Repository\URLAliasService $urlAliasService
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
        $this->urlAliasService = $urlAliasService;
        $this->submitHandler = $submitHandler;
        $this->factory = $factory;
        $this->router = $router;
        $this->globalHelper = $globalHelper;
        $this->actionDispatcher = $actionDispatcher;
        $this->toolbarManager = $toolbarManager;
        $this->languages = $languages;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|void|null
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function renderAction(Request $request, Location $location)
    {
        if ($this->toolbarManager->canUse())
        {
            $toolbarData = new ToolbarData();
            $toolbarForm = $this->toolbarManager
                ->initToolbarForm($location, $toolbarData)
                ->handleRequest($request)
                ->getToolbarForm();

            if ($toolbarForm->isSubmitted() && $toolbarForm->isValid()) {

                $nextAction = $toolbarForm->getClickedButton()->getName();
                $data = $this->toolbarManager->getToolbarData();

                return $this->getActionResponse($nextAction, $data);
            }
        }
        return $this->redirectToLocation($location);
    }

    /**
     * @param $action
     * @param \Gie\EzToolbar\Form\Data\ToolbarData $data
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|void|null
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    private function getActionResponse($action, ToolbarData $data)
    {
        switch ($action)
        {
            case 'create':
                return $this->createAction($data);
            case 'edit':
                return $this->editAction($data);
        }
        return $this->redirectToLocation($data->getParentLocation());
    }

    /**
     * @param \Gie\EzToolbar\Form\Data\ToolbarData $data
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    private function createAction(ToolbarData $data)
    {
        $contentType = $data->getContentType();
        $parentLocation = $data->getParentLocation();
        $currentLanguage = $this->languageService->loadLanguage($this->languages[0]);

        return new RedirectResponse($this->router->generate('ez_content_create_no_draft', [
            'contentTypeIdentifier' => $contentType->identifier,
            'language' => $currentLanguage->languageCode,
            'parentLocationId' => $parentLocation->id,
        ]));
    }

    /**
     * @param \Gie\EzToolbar\Form\Data\ToolbarData $data
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|void|null
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    private function editAction(ToolbarData $data)
    {
        $content = $data->getContent();
        $createContentDraft = new CreateContentDraftData();
        $contentInfo = $content->contentInfo;
        $createContentDraft->contentId = $content->id;

        $createContentDraft->fromVersionNo = $contentInfo->currentVersionNo;
        $createContentDraft->fromLanguage = $contentInfo->mainLanguageCode;



        $form = $this->factory->create(
            ContentDraftCreateType::class,
            $createContentDraft,
            [
                'action' => $this->router->generate('ez_content_draft_create'),
            ]
        );

        $this->actionDispatcher->dispatchFormAction($form, $createContentDraft, 'createDraft');
        if ($response = $this->actionDispatcher->getResponse()) {
            return $response;
        } else {
            return $this->redirectToLocation($data->getParentLocation());
        }
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|void
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    private function redirectToLocation(Location $location)
    {
        $url = $this->urlAliasService->reverseLookup($location);
        return new RedirectResponse($url->path);
    }
}
