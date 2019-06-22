<?php

namespace Gie\EzToolbarBundle\Controller;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\Values\Content\Location;
use EzSystems\EzPlatformAdminUi\Form\SubmitHandler;
use Gie\EzToolbar\Form\Data\ToolbarData;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;
use Gie\EzToolbar\Form\Type\ToolbarType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     */
    public function __construct(
        EngineInterface $templating,
        LocationService $locationService,
        LanguageService $languageService,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        SubmitHandler $submitHandler,
        FormFactory $factory,
        RouterInterface $router,
        GlobalHelper $globalHelper,
        array $languages
    )
    {
        $this->templating = $templating;
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->languageService = $languageService;
        $this->permissionResolver = $permissionResolver;
        $this->submitHandler = $submitHandler;
        $this->factory = $factory;
        $this->router = $router;
        $this->globalHelper = $globalHelper;
        $this->languages = $languages;
    }


    public function renderAction(Request $request, $pathString = null)
    {
        $response = new Response();

        if ($this->permissionResolver->hasAccess('toolbar', 'use'))
        {
            $currentLocation = $this->getCurrentLocation($pathString);

            $toolbarData = new ToolbarData();
            $toolbarData->setParentLocation($currentLocation);
            $toolbarData->setContent($this->contentService->loadContent($currentLocation->contentId));

            $toolbarData->setLanguage($this->languages[0]);

            $name = StringUtil::fqcnToBlockPrefix(ToolbarType::class);
            $toolbarForm = $this->factory->createNamed($name,ToolbarType::class, $toolbarData);

            $toolbarForm->handleRequest($request);
            if ($toolbarForm->isSubmitted() && $toolbarForm->isValid()) {

                return $this->submitHandler->handle($toolbarForm, function (ToolbarData $data) {

                    $contentType = $data->getContentType();
                    $language = $data->getLanguage();
                    $parentLocation = $data->getParentLocation();

                    return new RedirectResponse($this->router->generate('ez_content_create_no_draft', [
                        'contentTypeIdentifier' => $contentType->identifier,
                        'language' => $language->languageCode,
                        'parentLocationId' => $parentLocation->id,
                    ]));
                });
            }

            $response->setContent( $this->templating->render("@ezdesign/toolbar/toolbar.html.twig",
                ['form' => $toolbarForm->createView(),
                ]));
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
