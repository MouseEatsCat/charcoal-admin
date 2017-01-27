<?php

namespace Charcoal\Admin\Script\Notification;

// PSR-7 (http messaging) dependencies
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// Pimple (DI container) dependencies
use Pimple\Container;

// Module `charcoal-core` dependencies
use Charcoal\Loader\CollectionLoader;

// Module `charcoal-object` dependencies
use Charcoal\Object\ObjectRevision;

// Module `charcoal-factory` dependencies
use Charcoal\Factory\FactoryInterface;

// Module `charcoal-app` dependencies
use Charcoal\App\Script\CronScriptInterface;
use Charcoal\App\Script\CronScriptTrait;

// Intra-module (`charcoal-admin`) dependencies
use Charcoal\Admin\AdminScript;
use Charcoal\Admin\Object\Notification;

/**
 * Base class for all the notification script
 */
abstract class AbstractNotificationScript extends AdminScript implements CronScriptInterface
{
    use CronScriptTrait;

    /**
     * @var FactoryInterface
     */
    private $notificationFactory;

    /**
     * @param Container $container Pimple DI container.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);
        $this->setNotificationFactory($container['model/factory']);
        $this->setRevisionFactory($container['model/factory']);
    }

    /**
     * @return array
     */
    public function defaultArguments()
    {
        $arguments = [
            'now' => [
                'longPrefix'    => 'now',
                'description'   => 'The "relative" time this script should run at. If nothing is provided, default "now" is used.',
                'defaultValue'  => 'now'
            ]
        ];

        $arguments = array_merge(parent::defaultArguments(), $arguments);
        return $arguments;
    }

    /**
     * @param RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        unset($request);

        $this->startLock();

        $climate = $this->climate();

        $frequency = $this->frequency();

        $notifications = $this->loadNotifications($frequency);

        if (!$notifications) {
            return $response;
        }

        foreach ($notifications as $notification) {
            $this->handleNotification($notification);
        }

        $this->stopLock();

        return $response;
    }

    /**
     * @param string $frequency The frequency type to load.
     * @return Charcoal\Model\CollectionInterface
     */
    protected function loadNotifications($frequency)
    {
        $loader = new CollectionLoader([
            'logger' => $this->logger,
            'factory' => $this->notificationFactory()
        ]);
        $loader->setModel(Notification::class);
        $loader->addFilter([
            'property'  => 'frequency',
            'val'     => $frequency
        ]);
        $notifications = $loader->load();
        return $notifications;
    }

    /**
     * Handle a notification request
     *
     * @param Notification $notification The notification object to handle.
     * @return void
     */
    private function handleNotification(Notification $notification)
    {
        if (empty($notification->targetTypes())) {
            return;
        }
        foreach ($notification->targetTypes() as $objType) {
            $objects = $this->updatedObjects($objType);
            foreach ($objects as $obj) {
                var_dump($obj->data());
            }
        }
    }

    /**
     * @param string $objType The object (target) type to process.
     * @return Charcoal\Model\CollectionInterface
     */
    private function updatedObjects($objType)
    {
        $loader = new CollectionLoader([
            'logger' => $this->logger,
            'factory' => $this->revisionFactory()
        ]);
        $loader->setModel(ObjectRevision::class);
        $loader->addFilter([
            'property'  => 'target_type',
            'val'       => $objType
        ]);
        $loader->addFilter([
            'property'  => 'rev_ts',
            'val'       => $this->startDate(),
            'operator'  => '>'
        ]);
        $loader->addFilter([
            'property'  => 'rev_ts',
            'val'       => $this->endDate(),
            'operator'  => '<'
        ]);
        $objects = $loader->load();
        return $objects;
    }

    /**
     * @param FactoryInterface $factory The factory used to create queue items.
     * @return void
     */
    private function setNotificationFactory(FactoryInterface $factory)
    {
        $this->notificationFactory = $factory;
    }

    /**
     * @return FactoryInterface
     */
    private function notificationFactory()
    {
        return $this->notificationFactory;
    }

    /**
     * @param FactoryInterface $factory The factory used to create queue items.
     * @return void
     */
    private function setRevisionFactory(FactoryInterface $factory)
    {
        $this->revisionFactory = $factory;
    }

    /**
     * @return FactoryInterface
     */
    private function revisionFactory()
    {
        return $this->revisionFactory;
    }


    /**
     * Get the frequency type of this script.
     *
     * @return string
     */
    abstract protected function frequency();

    /**
     * Retrieve the "minimal" date that the revisions should have been made for this script.
     * @return string
     */
    abstract protected function startDate();

    /**
     * Retrieve the "maximal" date that the revisions should have been made for this script.
     * @return string
     */
    abstract protected function endDate();
}
