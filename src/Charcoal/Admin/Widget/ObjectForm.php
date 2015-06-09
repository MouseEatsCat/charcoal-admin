<?php

namespace Charcoal\Admin\Widget;

use \InvalidArgumentException as InvalidArgumentException;

use \Charcoal\Admin\Widget\Form as Form;

use \Charcoal\Admin\Ui\ObjectContainerInterface as ObjectContainerInterface;
use \Charcoal\Admin\Ui\ObjectContainerTrait as ObjectContainerTrait;

/**
*
*/
class ObjectForm extends Form implements ObjectContainerInterface
{
    use ObjectContainerTrait;

    /**
    * @var string
    */
    protected $_form_ident;

    /**
    * @param array $data
    * @throws InvalidArgumentException
    * @return ObjectForm Chainable
    */
    public function set_data($data)
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data must be an array');
        }

        $this->set_obj_data($data);

        if (isset($data['form_ident']) && $data['form_ident'] !== null) {
            $this->set_form_ident($data['form_ident']);
        }

        $obj_data = $this->data_from_object();
        $data = array_merge_recursive($obj_data, $data);

        parent::set_data($data);
        
        return $this;
    }

    /**
    * @param string $form_ident
    * @throws InvalidArgumentException
    * @return ObjectForm Chainable
    */
    public function set_form_ident($form_ident)
    {
        if (!is_string($form_ident)) {
            throw new InvalidArgumentException('Form ident must be a string');
        }
        $this->_form_ident = $form_ident;
        return $this;
    }

    /**
    * @return string
    */
    public function form_ident()
    {
        return $this->_form_ident;
    }

    public function data_from_object()
    {
        $obj = $this->obj();
        $metadata = $obj->metadata();
        $admin_metadata = isset($metadata['admin']) ? $metadata['admin'] : null;
        $form_ident = $this->form_ident();
        if (!$form_ident) {
            $form_ident = isset($admin_metadata['default_form']) ? $admin_metadata['default_form'] : '';
;
        }

        $obj_form_data = isset($admin_metadata['forms'][$form_ident]) ? $admin_metadata['forms'][$form_ident] : [];
        return $obj_form_data;
    }

    /**
    * FormProperty Generator
    *
    * @todo Merge with property_options
    */
    public function form_properties()
    {
       $obj = $this->obj();
       //var_dump($obj);
       $props = $obj->metadata()->properties();
       foreach ($props as $property_ident => $property) {
            $p = new FormProperty($property);
            $p->set_property_ident($property_ident);
            $p->set_data($property);
            yield $property_ident => $p;
       }
    }

    /**
    * @return array
    */
    public function form_data()
    {
        $obj = $this->obj();
        $form_data = $obj->data();
        return $form_data;
    }
}
