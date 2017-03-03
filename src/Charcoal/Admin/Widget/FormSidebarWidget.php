<?php

namespace Charcoal\Admin\Widget;

use \InvalidArgumentException;

// From Pimple
use \Pimple\Container;

// From 'charcoal-ui'
use \Charcoal\Ui\Form\FormInterface;

// From 'charcoal-admin'
use \Charcoal\Admin\AdminWidget;
use \Charcoal\Admin\Ui\ActionContainerTrait;
use \Charcoal\Admin\Ui\FormSidebarInterface;

/**
 * Form Sidebar Widget
 */
class FormSidebarWidget extends AdminWidget implements
    FormSidebarInterface
{
    use ActionContainerTrait;

    /**
     * Default sorting priority for an action.
     *
     * @const integer
     */
    const DEFAULT_ACTION_PRIORITY = 10;

    /**
     * Store a reference to the parent form widget.
     *
     * @var FormInterface
     */
    private $form;

    /**
     * @var string
     */
    private $widgetType = 'properties';

    /**
     * Store the sidebar actions.
     *
     * @var array|null
     */
    private $sidebarActions;

    /**
     * Store the default list actions.
     *
     * @var array|null
     */
    private $defaultSidebarActions;

    /**
     * Keep track if sidebar actions are finalized.
     *
     * @var boolean
     */
    protected $parsedSidebarActions = false;

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
     * The title is displayed by default.
     *
     * @var boolean
     */
    private $showTitle = true;

    /**
     * The sidebar's title.
     *
     * @var \Charcoal\Translator\Translation|string|null
     */
    protected $title;

    /**
     * The subtitle is displayed by default.
     *
     * @var boolean
     */
    private $showSubtitle = true;

    /**
     * The sidebar's subtitle.
     *
     * @var \Charcoal\Translator\Translation|string|null
     */
    private $subtitle;

    /**
     * @var boolean
     */
    protected $showFooter = true;

    /**
     * The required Acl permissions for the whole sidebar.
     *
     * @var string[] $requiredGlobalAclPermissions
     */
    private $requiredGlobalAclPermissions = [];

    /**
     * @param array|ArrayInterface $data Class data.
     * @return FormSidebarWidget Chainable
     */
    public function setData(array $data)
    {
        parent::setData($data);

        if (isset($data['properties'])) {
            $this->setSidebarProperties($data['properties']);
        }

        if (isset($data['actions'])) {
            $this->setSidebarActions($data['actions']);
        }

        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
            unset($data['permissions']);
            $isAssoc = $this->isAssoc($permissions);
            if ($isAssoc) {
                $this->setRequiredAclPermissions($permissions);
            } else {
                $this->setRequiredGlobalAclPermissions($permissions);
            }
        }

        return $this;
    }

    /**
     * Set the form widget the sidebar belongs to.
     *
     * @param FormInterface $form The related form widget.
     * @return FormSidebarWidget Chainable
     */
    public function setForm(FormInterface $form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Retrieve the form widget the sidebar belongs to.
     *
     * @return FormInterface
     */
    public function form()
    {
        return $this->form;
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
     * Determine if the form has any groups.
     *
     * @return boolean
     */
    public function hasSidebarProperties()
    {
        return ($this->numSidebarProperties() > 0);
    }

    /**
     * Count the number of form groups.
     *
     * @return integer
     */
    public function numSidebarProperties()
    {
        return count($this->sidebarProperties());
    }

    /**
     * Retrieve the object's properties from the form.
     *
     * @return mixed|Generator
     */
    public function formProperties()
    {
        $obj = $this->form()->obj();

        $availableProperties = $obj->properties();
        $sidebarProperties   = $this->sidebarProperties();

        foreach ($sidebarProperties as $propertyIdent) {
            if (!$obj->hasProperty($propertyIdent)) {
                continue;
            }

            $property = $obj->property($propertyIdent);
            $value    = $obj->propertyValue($propertyIdent);

            yield $propertyIdent => [
                'prop'       => $property,
                'displayVal' => $property->displayVal($value)
            ];
        }
    }

    /**
     * Determine if the sidebar's actions should be shown.
     *
     * @return boolean
     */
    public function showSidebarActions()
    {
        $actions = $this->sidebarActions();

        return count($actions);
    }

    /**
     * Retrieve the sidebar's actions.
     *
     * @return array
     */
    public function sidebarActions()
    {
        if ($this->sidebarActions === null) {
            $this->setSidebarActions([]);
        }

        if ($this->parsedSidebarActions === false) {
            $this->parsedSidebarActions = true;
            $this->sidebarActions = $this->createSidebarActions($this->sidebarActions);
        }

        return $this->sidebarActions;
    }

    /**
     * Set the sidebar's actions.
     *
     * @param  array $actions One or more actions.
     * @return FormSidebarWidget Chainable.
     */
    protected function setSidebarActions(array $actions)
    {
        $this->parsedSidebarActions = false;

        $this->sidebarActions = $this->mergeActions($this->defaultSidebarActions(), $actions);

        return $this;
    }

    /**
     * Build the sidebar's actions.
     *
     * Sidebar actions should come from the form settings defined by the "sidebars".
     * It is still possible to completly override those externally by setting the "actions"
     * with the {@see self::setSidebarActions()} method.
     *
     * @param  array $actions Actions to resolve.
     * @return array Sidebar actions.
     */
    protected function createSidebarActions(array $actions)
    {
        $this->actionsPriority = $this->defaultActionPriority();

        $sidebarActions = $this->parseAsSidebarActions($actions);

        return $sidebarActions;
    }

    /**
     * Parse the given actions as object actions.
     *
     * @param  array $actions Actions to resolve.
     * @return array
     */
    protected function parseAsSidebarActions(array $actions)
    {
        $sidebarActions = [];
        foreach ($actions as $ident => $action) {
            $ident  = $this->parseActionIdent($ident, $action);
            $action = $this->parseActionItem($action, $ident, true);

            if (!isset($action['priority'])) {
                $action['priority'] = $this->actionsPriority++;
            }

            if ($action['ident'] === 'view' && !$this->isObjViewable()) {
                $action['active'] = false;
            } elseif ($action['ident'] === 'save' && !$this->isObjSavable()) {
                $action['active'] = false;
            } elseif ($action['ident'] === 'reset' && !$this->isObjResettable()) {
                $action['active'] = false;
            } elseif ($action['ident'] === 'delete' && !$this->isObjDeletable()) {
                $action['active'] = false;
            }

            if ($action['isSubmittable'] && !$this->isObjSavable()) {
                $action['active'] = false;
            }

            if ($action['actions']) {
                $action['actions']    = $this->parseAsSidebarActions($action['actions']);
                $action['hasActions'] = !!array_filter($action['actions'], function ($action) {
                    return $action['active'];
                });
            }

            if (isset($sidebarActions[$ident])) {
                $hasPriority = ($action['priority'] > $sidebarActions[$ident]['priority']);
                if ($hasPriority || $action['isSubmittable']) {
                    $sidebarActions[$ident] = array_replace($sidebarActions[$ident], $action);
                } else {
                    $sidebarActions[$ident] = array_replace($action, $sidebarActions[$ident]);
                }
            } else {
                $sidebarActions[$ident] = $action;
            }
        }

        usort($sidebarActions, [ $this, 'sortActionsByPriority' ]);

        while (($first = reset($sidebarActions)) && $first['isSeparator']) {
            array_shift($sidebarActions);
        }

        while (($last = end($sidebarActions)) && $last['isSeparator']) {
            array_pop($sidebarActions);
        }

        return $sidebarActions;
    }

    /**
     * Retrieve the sidebar's default actions.
     *
     * @return array
     */
    protected function defaultSidebarActions()
    {
        if ($this->defaultSidebarActions === null) {
            $save = [
                'label'      => $this->form()->submitLabel(),
                'ident'      => 'save',
                'buttonType' => 'submit',
                'priority'   => 90
            ];
            $this->defaultSidebarActions = [ $save ];
        }

        return $this->defaultSidebarActions;
    }

    /**
     * @return string
     */
    public function jsActionPrefix()
    {
        return 'js-sidebar';
    }

    /**
     * Determine if the object can be deleted.
     *
     * If TRUE, the "Delete" button is shown. The object can still be
     * deleted programmatically or via direct action on the database.
     *
     * @return boolean
     */
    public function isObjDeletable()
    {
        // Overridden by permissions
        if (!$this->checkPermission('delete')) {
            return false;
        }

        $obj    = $this->form()->obj();
        $method = [ $obj, 'isDeletable' ];

        if (is_callable($method)) {
            return call_user_func($method);
        }

        return !!$obj->id();
    }

    /**
     * Determine if the object can be reset.
     *
     * If TRUE, the "Reset" button is shown. The object can still be
     * reset to its default values programmatically or emptied via direct
     * action on the database.
     *
     * @return boolean
     */
    public function isObjResettable()
    {
        // Overridden by permissions
        if (!$this->checkPermission('reset')) {
            return false;
        }

        $obj    = $this->form()->obj();
        $method = [ $obj, 'isResettable' ];

        if (is_callable($method)) {
            return call_user_func($method);
        }

        return true;
    }

    /**
     * Determine if the object can be saved.
     *
     * If TRUE, the "Save" button is shown. The object can still be
     * saved programmatically.
     *
     * @return boolean
     */
    public function isObjSavable()
    {
        // Overridden by permissions
        if (!$this->checkPermission('save')) {
            return false;
        }

        $obj    = $this->form()->obj();
        $method = [ $obj, 'isSavable' ];

        if (is_callable($method)) {
            return call_user_func($method);
        }

        return true;
    }

    /**
     * Determine if the object can be viewed (on the front-end).
     *
     * If TRUE, any "View" button is shown. The object can still be
     * saved programmatically.
     *
     * @return boolean
     */
    public function isObjViewable()
    {
        // Overridden by permissions
        if (!$this->checkPermission('view')) {
            return false;
        }

        $obj = $this->form()->obj();
        if (!$obj->id()) {
            return false;
        }

        $method = [ $obj, 'isViewable' ];
        if (is_callable($method)) {
            return call_user_func($method);
        }

        return true;
    }

    /**
     * Set the widget's priority or sorting index.
     *
     * @param integer $priority An index, for sorting.
     * @throws InvalidArgumentException If the priority is not a number.
     * @return FormSidebarWidget Chainable
     */
    public function setPriority($priority)
    {
        if (!is_numeric($priority)) {
            throw new InvalidArgumentException(
                'Priority must be an integer'
            );
        }

        $this->priority = (int)$priority;

        return $this;
    }

    /**
     * Retrieve the widget's priority or sorting index.
     *
     * @return integer
     */
    public function priority()
    {
        return $this->priority;
    }

    /**
     * Show/hide the widget's title.
     *
     * @param boolean $show Show (TRUE) or hide (FALSE) the title.
     * @return UiItemInterface Chainable
     */
    public function setShowTitle($show)
    {
        $this->showTitle = !!$show;

        return $this;
    }

    /**
     * Determine if the title is to be displayed.
     *
     * @return boolean If TRUE or unset, check if there is a title.
     */
    public function showTitle()
    {
        if ($this->showTitle === false) {
            return false;
        } else {
            return !!$this->title();
        }
    }

    /**
     * @param mixed $title The sidebar title.
     * @return FormSidebarWidget Chainable
     */
    public function setTitle($title)
    {
        $this->title = $this->translator()->translation($title);

        return $this;
    }

    /**
     * @return \Charcoal\Translator\Translation|string|null
     */
    public function title()
    {
        if ($this->title === null) {
            $this->setTitle($this->translator()->translation('Actions'));
        }

        return $this->title;
    }

    /**
     * @param boolean $show The show subtitle flag.
     * @return FormSidebarWidget Chainable
     */
    public function setShowSubtitle($show)
    {
        $this->showSubtitle = !!$show;
        return $this;
    }

    /**
     * @return boolean
     */
    public function showSubtitle()
    {
        if ($this->showSubtitle === false) {
            return false;
        } else {
            return !!$this->subtitle();
        }
    }

    /**
     * @param mixed $subtitle The sidebar widget subtitle.
     * @return FormSidebarWidget Chainable
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $this->translator()->translation($subtitle);

        return $this;
    }

    /**
     * @return \Charcoal\Translator\Translation|string|null
     */
    public function subtitle()
    {
        return $this->subtitle;
    }

    /**
     * @return boolean
     */
    public function showFooter()
    {
        // Overridden by permissions
        if (!$this->checkPermission('footer')) {
            return false;
        }

        return $this->showFooter;
    }

    /**
     * @param mixed $showFooter The show footer flag.
     * @return FormSidebarWidget
     */
    public function setShowFooter($showFooter)
    {
        $this->showFooter = !!$showFooter;

        return $this;
    }

    /**
     * @see    FormPropertyWidget::showActiveLanguage()
     * @return boolean
     */
    public function showLanguageSwitch()
    {
        $locales = count($this->translator()->availableLocales());
        if ($locales > 1) {
            foreach ($this->form()->formProperties() as $prop) {
                if ($prop->prop()->l10n()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retrieve the available languages, formatted for the sidebar language-switcher.
     *
     * @see    FormGroupWidget::languages()
     * @return array
     */
    public function languages()
    {
        $currentLocale = $this->translator()->getLocale();
        $languages = [];
        foreach ($this->translator()->locales() as $locale => $localeConfig) {
            if (isset($localeConfig['name'])) {
                $label = $localeConfig['name'];
            } else {
                $label = 'locale.'.$locale;
            }

            $languages[] = [
                'ident'   => $locale,
                'name'    => $this->translator()->translation($label),
                'current' => ($locale === $currentLocale)
            ];
        }

        return $languages;
    }



    // ACL Permissions
    // =========================================================================

    /**
     * Return true if the user as required permissions.
     *
     * @param string $permissionName The permission name to check against the user's permissions.
     * @return boolean
     */
    protected function checkPermission($permissionName)
    {
        if (!isset($this->requiredAclPermissions[$permissionName])) {
            return true;
        }

        $permissions = $this->requiredAclPermissions[$permissionName];

        // Test sidebar vs. ACL roles
        $authUser = $this->authenticator()->authenticate();
        if (!$this->authorizer()->userAllowed($authUser, $permissions)) {
            header('HTTP/1.0 403 Forbidden');
            header('Location: '.$this->adminUrl().'login');

            return false;
        }

        return true;
    }

    /**
     * @return string[]
     */
    public function requiredGlobalAclPermissions()
    {
        return $this->requiredGlobalAclPermissions;
    }

    /**
     * @param array $permissions The GlobalAcl permissions required pby the form group.
     * @return self
     */
    public function setRequiredGlobalAclPermissions(array $permissions)
    {
        $this->requiredGlobalAclPermissions = $permissions;

        return $this;
    }



    // Utilities
    // =========================================================================

    /**
     * @param array $array Detect if $array is assoc or not.
     * @return boolean
     */
    protected function isAssoc(array $array)
    {
        if ($array === []) {
            return false;
        }

        return !!array_filter($array, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
