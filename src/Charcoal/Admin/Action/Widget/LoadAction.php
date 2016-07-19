<?php

namespace Charcoal\Admin\Action\Widget;

use \Exception;
use \InvalidArgumentException;

// PSR-7 (http messaging) dependencies
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

// From `pimple`
use \Pimple\Container;

// From `charcoal-admin`
use \Charcoal\Admin\AdminAction;

/**
 *
 */
class LoadAction extends AdminAction
{
    /**
     * @var string $widgetId
     */
    protected $widgetId = '';

    /**
     * @var string $widgetHtml
     */
    protected $widgetHtml = '';

    /**
     * @var ViewInterface $widgetView
     */
    protected $widgetView;

    /**
     * @var \Charcoal\Factory\FactoryInterface $widgetFactory
     */
    protected $widgetFactory;

    /**
     * @param Container $dependencies The DI container.
     * @return void
     */
    public function setDependencies(Container $dependencies)
    {
        parent::setdependencies($dependencies);
        $this->widgetFactory = $dependencies['widget/factory'];
        $this->widgetView = $dependencies['view'];
    }

    /**
     * @param ServerRequestInterface $request  PSR7 Request.
     * @param ResponseInterface      $response PSR7 Response.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        $widgetType = $request->getParam('widget_type');
        $widgetOptions = $request->getParam('widget_options');

        if (!$widgetType) {
            $this->setSuccess(false);
            return $response->withStatus(404);
        }

        try {
            $widget = $this->widgetFactory->create($widgetType);

            $widget->setView($this->widgetView);

            if (is_array($widgetOptions)) {
                $widget->setData($widgetOptions);
            }
            $widgetHtml = $widget->renderTemplate($widgetType);
            $widgetId = $widget->widgetId();

            $this->setWidgetHtml($widgetHtml);
            $this->setWidgetId($widgetId);

            $this->setSuccess(true);
            return $response;
        } catch (Exception $e) {
            $this->addFeedback(sprintf('An error occured reloading the widget: "%s"', $e->getMessage()), 'error');
            $this->addFeedback($e->getMessage(), 'error');
            $this->setSuccess(false);
            return $response->withStatus(404);
        }
    }

    /**
     * @param string $id The widget ID.
     * @throws InvalidArgumentException If the widget ID argument is not a string.
     * @return LoadAction Chainable
     */
    public function setWidgetId($id)
    {
        if (!is_string($id)) {
            throw new InvalidArgumentException(
                'Widget ID must be a string'
            );
        }
        $this->widgetId = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function widgetId()
    {
        return $this->widgetId;
    }

    /**
     * @param string $html The widget HTML.
     * @throws InvalidArgumentException If the widget HTML is not a string.
     * @return LoadAction Chainable
     */
    public function setWidgetHtml($html)
    {
        if (!is_string($html)) {
            throw new InvalidArgumentException(
                'Widget HTML must be a string'
            );
        }
        $this->widgetHtml = $html;
        return $this;
    }

    /**
     * @return string
     */
    public function widgetHtml()
    {
        return $this->widgetHtml;
    }

    /**
     * @return string
     */
    public function results()
    {
        return [
            'success'       => $this->success(),
            'widget_html'   => $this->widgetHtml(),
            'widget_id'     => $this->widgetId(),
            'feedbacks'     => $this->feedbacks()
        ];
    }
}
