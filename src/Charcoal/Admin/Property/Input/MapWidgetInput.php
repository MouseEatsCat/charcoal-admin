<?php

namespace Charcoal\Admin\Property\Input;

// Module `charcoal-admin` dependencies
use \Charcoal\Admin\Property\AbstractPropertyInput;

/**
 *
 */
class MapWidgetInput extends AbstractPropertyInput
{
    /**
     * @var array $mapOptions
     */
    private $mapOptions = [];

    /**
     * @param array $mapOptions The map options.
     * @return MapWidgetInput Chainable
     */
    public function setMapOptions(array $mapOptions)
    {
        $this->mapOptions = $mapOptions;
        $this->mapOptions['api_key'] = $this->mapApiKey();
        return $this;
    }

    /**
     * Get the map options as JSON-encoded string.
     *
     * @return string
     */
    public function mapOptions()
    {
        return json_encode($this->mapOptions, true);
    }

    /**
     * Map api key for google maps.
     * @return string Map api key.
     */
    public function mapApiKey()
    {
        $app = \Charcoal\App\App::instance();
        $key = $app->config()->get('apis.google.map.key');
        return $key;
    }
}