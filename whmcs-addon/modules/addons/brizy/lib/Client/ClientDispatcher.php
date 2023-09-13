<?php

namespace WHMCS\Module\Addon\Brizy\Client;
use WHMCS\Module\Addon\Brizy\Client\Controller;
use WHMCS\Module\Addon\Brizy\Common\Translations;


/**
 * Sample Client Area Dispatch Handler
 */
class ClientDispatcher {

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
        }
        else if ($action === 'template') {
            $controller = new TemplateApiController();
            $action = isset($_REQUEST['execute']) ? $_REQUEST['execute'] : '';
        } else {
            $controller = new Controller();
        }

        if (is_callable(array($controller, $action))) {
            return $controller->$action($parameters);
        }

        return  Translations::$_['client']['invalidAction'];
    }
}
