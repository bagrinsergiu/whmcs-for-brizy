<?php

namespace WHMCS\Module\Addon\Brizy\Client;
use  WHMCS\Module\Addon\Brizy\Client\ProductDetailsController;

/**
 * Sample Client Area Dispatch Handler
 */
class ProductDetailsDispatcher {

    /**
     * Dispatch request.
     *
     * @param string $action
     * @param array $parameters
     *
     * @return array
     */
    public function dispatch($action, $parameters)
    {

        if (!$action) {
            // Default to index if no action specified
            $action = 'index';
        }

        $controller = new ProductDetailsController();

        // Verify requested action is valid and callable
        if (is_callable(array($controller, $action))) {
            return $controller->$action($parameters);
        }
    }
}
