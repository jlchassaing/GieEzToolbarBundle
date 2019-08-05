<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */

namespace Gie\EzToolbar\Templating\Twig;


use eZ\Publish\API\Repository\Values\Content\Location;
use Gie\EzToolbar\Manager\ToolbarManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ToolbarExtension extends AbstractExtension
{
    /**
     * @var \Symfony\Bundle\TwigBundle\TwigEngine
     */
    private $templating;

    /**
     * @var \Gie\EzToolbar\Manager\ToolbarManager
     */
    private $toolbarManager;
    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    private $session;

    /**
     * ToolbarExtension constructor.
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating
     * @param \Gie\EzToolbar\Manager\ToolbarManager $toolbarManager
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     */
    public function __construct(
        EngineInterface $templating,
        ToolbarManager $toolbarManager,
        Session $session
    )
    {
        $this->templating = $templating;
        $this->toolbarManager = $toolbarManager;
        $this->session = $session;
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
        if ($this->toolbarManager->canUse()) {
            $toolbarForm = $this->toolbarManager
                ->initToolbarForm($location)
                ->getToolbarForm();
            return $this->templating->render("@GieEzToolbar/toolbar/toolbar.html.twig",
                [
                    'form' => $toolbarForm->createView(),
                    'currentUser' => $this->toolbarManager->getCurrentUser(),
                    'location' => $location,
                    'flashBag' => $this->session->getFlashBag()->all(),
                ]);
        }
        return null;


    }

}