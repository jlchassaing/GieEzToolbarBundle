<?php

namespace Gie\EzToolbarBundle\Controller;

use EzSystems\EzPlatformAdminUi\Form\Data\Content\Draft\ContentCreateData;
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
     * @param \eZ\Publish\API\Repository\PermissionResolver $permissionResolver
     * @param \EzSystems\EzPlatformAdminUi\Form\SubmitHandler $submitHandler
     * @param \EzSystems\EzPlatformAdminUi\Form\Factory\FormFactory $formFactory
     * @param \eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper $globalHelper
     * @param array $languages
     */
    public function __construct(
        EngineInterface $templating,
        LocationService $locationService,
        PermissionResolver $permissionResolver,
        SubmitHandler $submitHandler,
        FormFactory $formFactory,
        GlobalHelper $globalHelper,
        array $languages
    )
    {
        $this->templating = $templating;
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
            $contentCreateData = new ContentCreateData(null,$parentLocation,null);

            $contentCreate = new ContentCreateData();
            $contentCreate->setParentLocation($parentLocation);
            $form = $this->formFactory->createContent($contentCreateData);

            $options = $form->get('content_type')->getconfig()->getOptions();
            $options['expanded'] = false;
            $form->add('content_type', ContentTypeChoiceType::class, $options);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                return $this->submitHandler->handle($form, function (ContentCreateData $data) {
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

            $response->setContent( $this->templating->render("@ezdesign/toolbar/toolbar.html.twig",
                ['form' => $form->createView(),
                 'isPublished' => false,
                ]));
        }
        return $response;
    }


        if ($this->permissionResolver->hasAccess('toolbar', 'use'))
        {
            $form = $this->formFactory->createContent();
            $form->handleRequest($request);

            if ( $form->isSubmitted() && $form->isValid() )
            {
                return $this->submitHandler->handle( $form, function ( ContentCreateData $data ) {
                    $contentType    = $data->getContentType();
                    $language       = $data->getLanguage();
                    $parentLocation = $data->getParentLocation();

                    return $this->redirectToRoute( 'ez_content_create_no_draft', [
                        'contentTypeIdentifier' => $contentType->identifier,
                        'language'              => $language->languageCode,
                        'parentLocationId'      => $parentLocation->id,
                    ] );
                } );
            }

            $response->setContent( $this->templating->render("@GieEzToolbar/toolbar/toolbar.html.twig",
                ['form' => $form->createView()]));
        }
        return $response;
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
        return $this->getRootLocation();
    }

}