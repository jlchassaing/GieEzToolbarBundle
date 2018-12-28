<?php

namespace Gie\EzToolbarBundle\Controller;

use EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentCreateData;
use EzSystems\EzPlatformAdminUi\Form\SubmitHandler;
use EzSystems\EzPlatformFormBuilder\Form\Type\Field\HiddenFieldType;
use \Symfony\Bundle\FrameworkBundle\Controller\Controller;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;
use Gie\EzToolbarBundle\Toolbar\Action;
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
     * @var ContentTypeService
     */
    private $contentTypeService;

    /**
     * @var ContentService
     */
    private $contentService;

    /**
     * @var LocationService
     */
    private $locationService;

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
     *
     * @param EngineInterface $templating
     * @param ContentTypeService $contentTypeService
     * @param ContentService $contentService
     * @param LocationService $locationService
     * @param PermissionResolver $permissionResolver
     * @param FormFactory $formFactory
     * @param GlobalHelper $globalHelper
     * @param array $languages
     */
    public function __construct(
        EngineInterface $templating,
        ContentTypeService $contentTypeService,
        ContentService $contentService,
        LocationService $locationService,
        PermissionResolver $permissionResolver,
        SubmitHandler $submitHandler,
        FormFactory $formFactory,
        GlobalHelper $globalHelper,
        array $languages
    )
    {
        $this->templating = $templating;
        $this->contentTypeService = $contentTypeService;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->permissionResolver = $permissionResolver;
        $this->submitHandler = $submitHandler;
        $this->formFactory = $formFactory;
        $this->globalHelper = $globalHelper;
        $this->languages = $languages;
    }


    public function renderAction(Request $request, $locationId = null)
    {
        $response = new Response();
        $parentLocation = $this->getCurrentLocation($locationId);

        if ($this->permissionResolver->hasAccess('toolbar', 'use'))
        {
            $contentTypes = $this->getCanPublishContentTypes($parentLocation);

            $contentCreate = new ContentCreateData();
            $contentCreate->setParentLocation($parentLocation);

            $form = $this->formFactory->createContent($contentCreate);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $result = $this->submitHandler->handle($form, function (ContentCreateData $data) {
                    $contentType = $data->getContentType();
                    $language = $data->getLanguage();
                    $parentLocation = $data->getParentLocation();


                    return $this->redirectToRoute('ez_content_create_no_draft', [
                        'contentTypeIdentifier' => $contentType->identifier,
                        'language' => $language->languageCode,
                        'parentLocationId' => $parentLocation->id,
                    ]);
                });

                if ($result instanceof Response) {
                    return $result;
                }
            }

            $response->setContent( $this->templating->render("@GieEzToolbar/toolbar/toolbar.html.twig",
                ['form' => $form->createView()]));
        }
        return $response;
    }



    /**
     * @param Location $parentLocaiton
     *
     * @return array
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    protected function getCanPublishContentTypes(Location $parentLocation)
    {
        $canPublishContentType = [];
        $contentTypeGroups = $this->contentTypeService->loadContentTypeGroups();
        foreach ( $contentTypeGroups as $contentTypeGroup )
        {
            $contentTypes = $this->contentTypeService->loadContentTypes($contentTypeGroup);

            foreach ( $contentTypes as $contentType )
            {
                if( $this->canPublish($contentType, $parentLocation, $this->languages[0]))
                {
                    $canPublishContentType[$contentType->getName()] = $contentType->id;
                }
            }
        }
        return $canPublishContentType;
    }

    /**
     * @param ContentType $contentType
     * @param Location $location
     * @param $language
     *
     * @return bool
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    protected function canPublish(ContentType $contentType,Location $location, $language)
    {
        $contentCreateStruct = $this->contentService->newContentCreateStruct($contentType, $language);
        $locationCreateStruct = $this->locationService->newLocationCreateStruct($location->id);

        return $this->permissionResolver->canUser('content', 'publish', $contentCreateStruct, [$locationCreateStruct]);

    }


    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    protected function getRootLocation()
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
    protected function getCurrentLocation($locationId = null)
    {
        if ($locationId !== null && is_integer($locationId))
        {
            return $this->locationService->loadLocation($locationId);
        }
        else{
            return $this->getRootLocation();
        }
    }

}
