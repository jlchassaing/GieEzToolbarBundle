<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */

namespace Gie\EzToolbar\Form\Data;


use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Language;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;

class ToolbarData
{
    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Location|null
     */
    private $parentLocation;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Content|null
     */
    private $content;

    /**
     * @var \eZ\Publish\API\Repository\Values\ContentType\ContentType|null
     */
    private $contentType;

    /**
     * @var string
     */
    private $language;

    private $create;

    private $edit;

    /**
     * ToolbarData constructor.
     * @param \eZ\Publish\API\Repository\Values\Content\Location|null $parentLocation
     * @param \eZ\Publish\API\Repository\Values\Content\Content|null $content
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType|null $contentType
     * @param \eZ\Publish\API\Repository\Values\Content\Language|null $language
     */
    public function __construct(
        ?Location $parentLocation = null,
        ?Content $content = null,
        ?ContentType $contentType = null,
        $language = null
    ) {
        $this->parentLocation = $parentLocation;
        $this->content = $content;
        $this->contentType = $contentType;
        $this->language = $language;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Location|null
     */
    public function getParentLocation(): ?Location {
        return $this->parentLocation;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location|null $parentLocation
     */
    public function setParentLocation(?Location $parentLocation): void {
        $this->parentLocation = $parentLocation;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Content|null
     */
    public function getContent(): ?Content {
        return $this->content;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Content|null $content
     */
    public function setContent(?Content $content): void {
        $this->content = $content;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentType|null
     */
    public function getContentType(): ?ContentType {
        return $this->contentType;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType|null $contentType
     */
    public function setContentType(?ContentType $contentType): void {
        $this->contentType = $contentType;
    }

    /**
     * @return string|null
     */
    public function getLanguage(): ?string  {
        return $this->language;
    }

    /**
     * @param string|null $language
     */
    public function setLanguage(?string $language): void {
        $this->language = $language;
    }

    /**
     * @return mixed
     */
    public function getCreate() {
        return $this->create;
    }

    /**
     * @param mixed $create
     */
    public function setCreate($create): void {
        $this->create = $create;
    }

    /**
     * @return mixed
     */
    public function getEdit() {
        return $this->edit;
    }

    /**
     * @param mixed $edit
     */
    public function setEdit($edit): void {
        $this->edit = $edit;
    }





}