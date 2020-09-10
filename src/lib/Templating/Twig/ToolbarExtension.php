<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */

namespace Gie\EzToolbar\Templating\Twig;

use eZ\Publish\API\Repository\Values\Content\Location;
use Gie\EzToolbar\Manager\ToolbarManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ToolbarExtension extends AbstractExtension
{
    /** @var \Twig\Environment  */
    private $templating;

    /** @var \Gie\EzToolbar\Manager\ToolbarManager  */
    private $toolbarManager;

    /**
     * ToolbarExtension constructor.
     *
     * @param \Twig\Environment $templating
     * @param \Gie\EzToolbar\Manager\ToolbarManager $toolbarManager
     */
    public function __construct(Environment $templating, ToolbarManager $toolbarManager)
    {
        $this->templating = $templating;
        $this->toolbarManager = $toolbarManager;
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
     * @param \Gie\EzToolbar\Templating\Twig\Location|null $location
     *
     * @return string|null
     * @throws \EzSystems\EzPlatformAdminUi\Exception\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function displayToolbar(?Location $location)
    {
        $content = $location->getContent();
        $contentType = $content->getContentType();

        if ($this->toolbarManager->canUse()) {

            $params = [
                'currentUser' => $this->toolbarManager->getCurrentUser(),
                'content' => $content,
                'contentType' => $contentType,
                'location' => $location
                ];

            $params = $this->toolbarManager->addContentActionForms($params, $location, $content);

            return $this->templating->render("@GieEzToolbar/toolbar/toolbar.html.twig",$params);
        }
        return null;
    }
}