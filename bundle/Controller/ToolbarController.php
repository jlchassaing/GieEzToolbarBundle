<?php

namespace Gie\EzToolbarBundle\Controller;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\Values\Content\Location;
use EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentCreateData;
use EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentEditData;
use EzSystems\EzPlatformAdminUi\Form\SubmitHandler;
use EzSystems\EzPlatformAdminUi\Form\Type\ContentType\ContentTypeChoiceType;
use \Symfony\Bundle\FrameworkBundle\Controller\Controller;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;
use EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @var PermissionResolver
     */
    private $permissionResolver;

    /**
     * @var SubmitHandler
     */
    private $submitHandler;

    /**
     * @var FormFactory
     */
    private $formFactory;

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
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\PermissionResolver $permissionResolver
     * @param \EzSystems\EzPlatformAdminUi\Form\SubmitHandler $submitHandler
     * @param \EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory $formFactory
     * @param \eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper $globalHelper
     * @param array $languages
     */
    public function __construct(
        EngineInterface $templating,
        LocationService $locationService,
        ContentService $contentService,
        PermissionResolver $permissionResolver,
        SubmitHandler $submitHandler,
        FormFactory $formFactory,
        GlobalHelper $globalHelper,
        array $languages
    )
    {
        $this->templating = $templating;
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->permissionResolver = $permissionResolver;
        $this->submitHandler = $submitHandler;
        $this->formFactory = $formFactory;
        $this->globalHelper = $globalHelper;
        $this->languages = $languages;
    }


    public function renderAction(Request $request, $pathString = null)
    {
        $response = new Response();

        if ($this->permissionResolver->hasAccess('toolbar', 'use'))
        {
            $currentLocation = $this->getCurrentLocation($pathString);
            $contentCreateData = new ContentCreateData();
            $contentCreateData->setParentLocation($currentLocation);
            $formCreateContent = $this->formFactory->createContent($contentCreateData);
            $formEditContent = $this->formFactory->contentEdit(
                $this->getContentEditData($currentLocation)
            );

            $formCreateContent->handleRequest($request);
            if ($formCreateContent->isSubmitted() && $formCreateContent->isValid()) {
                return $this->submitHandler->handle($formCreateContent, function (ContentCreateData $data) {
                    $contentType = $data->getContentType();
                    $language = $data->getLanguage();
                    $parentLocation = $data->getParentLocation();

                    return $this->redirectToRoute('ez_content_create_no_draft', [
                        'contentTypeIdentifier' => $contentType->identifier,
                        'language' => $language->languageCode,
                        'parentLocationId' => $parentLocation->id,
                    ]);
                });
            }

            $options = $formCreateContent->get('content_type')->getconfig()->getOptions();
            $options['expanded'] = false;
            $formCreateContent->add('content_type', ContentTypeChoiceType::class, $options);

            $response->setContent( $this->templating->render("@ezdesign/toolbar/toolbar.html.twig",
                ['formCreateContent' => $formCreateContent->createView(),
                 'formContentEdit' => $formEditContent->createView(),
                 'isPublished' => false,
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
            }
            $locationId = array_reverse(explode('/',trim($pathString,'/')))[0];
            return $this->locationService->loadLocation($locationId);
        }
        return $this->getRootLocation();
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @return \EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentEditData
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    private function getContentEditData(Location $location)
    {
        $content = $this->contentService->loadContent($location->contentId);
        return new ContentEditData($content->contentInfo,$content->getVersionInfo(),null,$location);
    }



}
