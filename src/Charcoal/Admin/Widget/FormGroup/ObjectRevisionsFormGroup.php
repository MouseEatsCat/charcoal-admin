<?php

namespace Charcoal\Admin\Widget\FormGroup;

use \Pimple\Container;

use \Charcoal\Loader\CollectionLoader;

use \Charcoal\Ui\FormGroup\AbstractFormGroup;

use \Charcoal\Admin\Widget\TableWidget;

/**
 *
 */
class ObjectRevisionsFormGroup extends AbstractFormGroup
{
    /**
     * Store the factory instance for the current class.
     *
     * @var FactoryInterface
     */
    private $modelFatory;

    /**
     * Inject dependencies from a DI Container.
     *
     * @param  Container $container A dependencies container instance.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);
        $this->modelFactory = $container['model/factory'];
    }

    /**
     * @return boolean
     */
    public function active()
    {
        return parent::active() && $this->objType() && $this->objId();
    }

    /**
     * Retrieve the current object type from the GET parameters.
     *
     * @return string
     */
    public function objType()
    {
        return (isset($_GET['obj_type']) ? $_GET['obj_type'] : null);
    }

    /**
     * Retrieve the current object ID from the GET parameters.
     *
     * @return string
     */
    public function objId()
    {
        return (isset($_GET['obj_id']) ? $_GET['obj_id'] : null);
    }

    /**
     * @return array
     */
    public function objectRevisions()
    {
        if (!$this->objType() || !$this->objId()) {
            return [];
        }

        $target = $this->modelFactory->create($this->objType());
        $target->setId($this->objId());

        $lastRevision = $target->latestRevision();

        $callback = function(&$obj) use ($lastRevision, $target) {
            $dataDiff = $obj->dataDiff();
            $obj->revTsDisplay = $obj->revTs()->format('Y-m-d H:i:s');
            $obj->numDiff = count($dataDiff);
            if (isset($dataDiff[0])) {
                $props = array_keys($dataDiff[0]);
                $props = array_diff($props, ['last_modified', 'last_modified_by']);
                $propNames = [];
                foreach ($props as $p) {
                    $propNames[] = $target->p($p)->label();
                }
                $obj->changedProperties = implode(', ', $propNames);
            } else {
                $obj->changedProperties = '';
            }
            $obj->allowRevert = ($lastRevision->revNum() != $obj->revNum());
        };

        return $target->allRevisions($callback);
    }
}