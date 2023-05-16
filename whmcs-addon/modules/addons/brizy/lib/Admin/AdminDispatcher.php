<?php

namespace WHMCS\Module\Addon\Brizy\Admin;

/**
 * Admin area api dispatcher
 */
class AdminDispatcher {

    /**
     * Dispatch request.
     *
     * @param string $action
     * @param array $parameters
     *
     * @return string
     */
    public function dispatch($action, $parameters)
    {
        if (!$action) {
            $action = 'index';
        }

        if ($action === 'api') {
            $controller = new ApiController();
            $action = isset($_REQUEST['execute']) ? $_REQUEST['execute'] : '';
        } else {
            $controller = new Controller();
        }

        if (is_callable(array($controller, $action))) {
            return $controller->$action($parameters);
        }

        return '<p>Invalid action requested. Please go back and try again.</p>';
    }
}
