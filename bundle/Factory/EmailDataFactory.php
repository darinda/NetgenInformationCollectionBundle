<?php

namespace Netgen\Bundle\InformationCollectionBundle\Factory;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\Core\Helper\FieldHelper;
use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\API\Repository\Values\Content\Content;
use Netgen\Bundle\InformationCollectionBundle\Value\EmailData;

class EmailDataFactory
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var TranslationHelper
     */
    protected $translationHelper;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var ContentTypeService
     */
    protected $contentTypeService;

    /**
     * EmailDataFactory constructor.
     *
     * @param array $config
     * @param TranslationHelper $translationHelper
     * @param FieldHelper $fieldHelper
     * @param ContentTypeService $contentTypeService
     */
    public function __construct(
        array $config,
        TranslationHelper $translationHelper,
        FieldHelper $fieldHelper,
        ContentTypeService $contentTypeService
    ) {
    
        $this->config = $config;
        $this->translationHelper = $translationHelper;
        $this->fieldHelper = $fieldHelper;
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * Factory method
     *
     * @param Content $content
     *
     * @return EmailData
     */
    public function build(Content $content)
    {
        return new EmailData(
            $this->resolve($content, 'recipient', 'email'),
            $this->resolve($content, 'sender', 'email'),
            $this->resolve($content, 'subject'),
            $this->resolveTemplate($content)
        );
    }

    /**
     * Returns resolved parameter
     *
     * @param Content $content
     * @param string $field
     * @param string $property
     *
     * @return mixed
     */
    protected function resolve(Content $content, $field, $property = 'text')
    {
        if (array_key_exists($field, $content->fields) &&
            !$this->fieldHelper->isFieldEmpty($content, $field)
        ) {
            $fieldValue = $this->translationHelper->getTranslatedField($content, $field);

            return $fieldValue->value->$property;
        } else {
            return $this->config['default_variables'][$field];
        }
    }

    /**
     * Returns resolved template name
     *
     * @param Content $content
     *
     * @return string
     */
    protected function resolveTemplate($content)
    {
        $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);

        $contentTypeIdentifier = $contentType->identifier;

        if (array_key_exists($contentTypeIdentifier, $this->config['templates'])) {

            return $this->config['templates'][$contentTypeIdentifier];

        }

        return $this->config['templates']['default'];
    }
}