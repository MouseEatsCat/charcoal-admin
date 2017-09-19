<?php

namespace Charcoal\Admin\Widget;

use InvalidArgumentException;

// From Pimple
use Pimple\Container;

// From 'charcoal-core'
use Charcoal\Charcoal;
use Charcoal\Property\PropertyFactory;
use Charcoal\Property\PropertyInterface;

// From 'charcoal-admin'
use Charcoal\Admin\AdminWidget;
use Charcoal\Admin\Ui\CollectionContainerInterface;
use Charcoal\Admin\Ui\CollectionContainerTrait;

/**
 * The table widget displays a collection in a tabular (table) format.
 */
class SearchWidget extends AdminWidget implements CollectionContainerInterface
{
    use CollectionContainerTrait;

    /**
     * @var array $properties
     */
    protected $properties;

    /**
     * @var $propertiesOptions
     */
    protected $propertiesOptions;

    /**
     * @var array $orders
     */
    protected $orders;

    /**
     * @var array $filters
     */
    protected $filters;

    /**
     * @param array $data The search widget data.
     * @return TableWidget Chainable
     */
    public function setData(array $data)
    {
        if (isset($data['obj_type'])) {
            $this->setObjType($data['obj_type']);
        }

        if (isset($data['collection_ident'])) {
            $this->setCollectionIdent($data['collection_ident']);
        }

        $objData = $this->dataFromObject();
        $data    = array_merge_recursive($objData, $data);

        parent::setData($data);

        return $this;
    }

    /**
     * Fetch metadata from current obj_type
     * @return array List of metadata.
     */
    public function dataFromObject()
    {
        $obj             = $this->proto();
        $metadata        = $obj->metadata();
        $adminMetadata   = isset($metadata['admin']) ? $metadata['admin'] : null;
        $collectionIdent = $this->collectionIdent();
        if (!$collectionIdent) {
            $collectionIdent = isset($adminMetadata['default_list']) ? $adminMetadata['default_list'] : '';
        }

        if (isset($adminMetadata['lists'][$collectionIdent])) {
            return $adminMetadata['lists'][$collectionIdent];
        } else {
            return [];
        }
    }

    /**
     * Sets and returns properties
     *
     * Manages which to display, and their order, as set in object metadata
     *
     * @return array
     */
    public function properties()
    {
        if ($this->properties === null) {
            $obj   = $this->proto();
            $props = $obj->metadata()->properties();

            $collectionIdent = $this->collectionIdent();

            if ($collectionIdent) {
                $metadata      = $obj->metadata();
                $adminMetadata = isset($metadata['admin']) ? $metadata['admin'] : null;

                if (isset($adminMetadata['lists'][$collectionIdent]['properties'])) {
                    // Flipping to have property ident as key
                    $listProperties = array_flip($adminMetadata['lists'][$collectionIdent]['properties']);
                    // Replacing values of listProperties from index to actual property values
                    $props = array_replace($listProperties, $props);
                    // Get only the keys that are in listProperties from props
                    $props = array_intersect_key($props, $listProperties);
                }
            }

            $this->properties = $props;
        }

        return $this->properties;
    }

    /**
     * Properties to display in collection template, and their order, as set in object metadata
     *
     * @return string
     */
    public function jsonPropertiesList()
    {
        $obj             = $this->proto();
        $metadata        = $obj->metadata();
        $adminMetadata   = isset($metadata['admin']) ? $metadata['admin'] : null;
        $collectionIdent = $this->collectionIdent();

        $props = [];

        if (isset($adminMetadata['lists'][$collectionIdent]['properties'])) {
            $props = $adminMetadata['lists'][$collectionIdent]['properties'];
        }

        return json_encode($props);
    }
}
