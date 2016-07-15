<?php

namespace Charcoal\Admin\Widget;

use \InvalidArgumentException;

use \Charcoal\Translation\TranslationString;
use \Charcoal\Translation\TranslationConfig;
use \Charcoal\Admin\AdminWidget;

/**
 *
 */
class FormSidebarWidget extends AdminWidget
{
    /**
     * In-memory copy of the parent form widget.
     * @var FormWidget $form
     */
    private $form;

    /**
     * @var string
     */
    private $widget_type = 'properties';

    /**
     * @var Object $actions
     */
    private $actions;

    /**
     * @var array $sidebarProperties
     */
    protected $sidebarProperties = [];

    /**
     * Priority, or sorting index.
     * @var integer $priority
     */
    protected $priority;

    /**
     * @var TranslationString $title
     */
    protected $title;


    /**
     * @param FormWidget $form The sidebar form widget.
     * @return FormSidebarWidget Chainable
     */
    public function setForm(FormWidget $form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return FormWidget
     */
    public function form()
    {
        return $this->form;
    }


    /**
     * @param mixed $subtitle The sidebar subtitle.
     * @return FormSidebarWidget Chainable
     */
    public function setSubtitle($subtitle)
    {
        if ($subtitle === null) {
            $this->title = null;
        } else {
            $this->title = new TranslationString($subtitle);
        }
        return $this;
    }

    /**
     * @param mixed $properties The sidebar properties.
     * @return FormSidebarWidget Chainable
     */
    public function setSidebarProperties($properties)
    {
        $this->sidebarProperties = $properties;
        return $this;
    }

    /**
     * @return mixed
     */
    public function sidebarProperties()
    {
        return $this->sidebarProperties;
    }

    /**
     * @return mixed This method is a generator.
     */
    public function formProperties()
    {
        $sidebarProperties = $this->sidebarProperties();
        $formProperties = $this->form()->formProperties($sidebarProperties);
        $ret = [];
        foreach ($formProperties as $property_ident => $property) {
            if (in_array($property_ident, $sidebarProperties)) {
                if (is_callable([$this->form(), 'obj'])) {
                    $val = $this->form()->obj()->p($property_ident)->val();
                    $property->setProperty_val($val);
                }
                yield $property_ident => $property;
            }
        }
    }

    /**
     * Defined the form actions.
     * @param object $actions The sidebar actions.
     * @return FormGroupWidget Chainable
     */
    public function setActions($actions)
    {
        if (!$actions) {
            return $this;
        }
        $this->actions = [];

        foreach ($actions as $ident => $action) {
            if (!isset($action['url']) || !isset($action['label'])) {
                continue;
            }
            $label = new TranslationString($action['label']);
            $obj = $this->form()->obj();
            // Shame: Make sure the view is set before attempt rendering
            if ($obj->view()) {
                $url = $obj->render($action['url']);
            } else {
                // Shame part 2: force '{{id}}' to use obj_id GET parameter...
                if (isset($_GET['obj_id'])) {
                    $url = str_replace('{{id}}', $_GET['obj_id'], $action['url']);
                } else {
                    $url = $action['url'];
                }
            }


            // Info = default
            // Possible: danger, info
            $btn = isset($action['type']) ? $action['type'] : 'info';
            $this->actions[] = [ 'label' => $label, 'url' => $url, 'btn' => $btn ];
        }

        return $this;
    }

    /**
     * Returns the actions as an ArrayIterator
     * [ ['label' => $label, 'url' => $url] ]
     * @see $this->set_actions()
     * @return object actions
     */
    public function actions()
    {
        return $this->actions;
    }

    /**
     * @param integer $priority The priority, or sorting index.
     * @throws InvalidArgumentException If the priority is not a number.
     * @return FormGroupWidget Chainable
     */
    public function setPriority($priority)
    {
        if (!is_numeric($priority)) {
            throw new InvalidArgumentException(
                'Priority must be an integer'
            );
        }
        $priority = (int)$priority;
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return integer
     */
    public function priority()
    {
        return $this->priority;
    }

    /**
     * @param mixed $title The sidebar title.
     * @return FormSidebarWidget Chainable
     */
    public function setTitle($title)
    {
        if ($title === null) {
            $this->title = null;
        } else {
            $this->title = new TranslationString($title);
        }
        return $this;
    }

    /**
     * @return TranslationString
     */
    public function title()
    {
        if ($this->title === null) {
            $this->setTitle('Actions');
        }
        return $this->title;
    }

    /**
     * @return boolean
     */
    public function showLanguageSwitch()
    {
        foreach ($this->form()->formProperties() as $prop) {
            if ($prop->prop()->l10n()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Active languages generator, formatted for the sidebar language-switcher.
     *
     * @return array This is a generator.
     */
    public function languages()
    {
        $curLang = TranslationConfig::instance()->currentLanguage();
        $langs = TranslationConfig::instance()->languages();
        foreach ($langs as $lang) {
            yield [
                'ident' => $lang->ident(),
                'name'  => $lang->name(),
                'current' => ($lang->ident() == $curLang)
            ];
        }
    }
}