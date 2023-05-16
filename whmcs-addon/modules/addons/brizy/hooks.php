<?php
use WHMCS\Module\Addon\Brizy\Client\ProductDetailsDispatcher;
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Brizy\Common\Session;
use WHMCS\Module\Addon\Brizy\Common\CpanelInstaller;
use WHMCS\Module\Addon\Brizy\Common\Helpers;
use WHMCS\Module\Addon\Brizy\Common\Translations;

/**
 * Client area product details hook
 */
add_hook('ClientAreaProductDetailsOutput', 1, function($vars) {

    Translations::set($vars);

    $action = isset($_REQUEST['brizy']) ? $_REQUEST['brizy'] : '';
    $dispatcher = new ProductDetailsDispatcher();
    return $dispatcher->dispatch($action, $vars);
});



/**
 * Upon completion of checkout once the order has been created, invoice generated and all email notifications sent
 */
add_hook('AfterShoppingCartCheckout', 1, function($vars) {

    Translations::set($vars);

    $themeId = Session::get('theme_id');
    $themeName = Session::get('theme_name');
    $themeIsPro = Session::get('theme_pro');

    if ($themeId) {
        Session::clear();

        Capsule::table('brizy_order_themes')->insert(
            [
                'order_id' => $vars['OrderID'],
                'theme_id' => $themeId,
                'name' => $themeName,
                'pro' => $themeIsPro,
            ]
        );
        }
});


/**
 * Executes upon successful completion of the module function.
 */
add_hook('AfterModuleCreate', 1, function($vars) {
    
    Translations::set($vars);

    $themeId = Helpers::getThemeIdForOrder($vars['params']['model']->order->id);
    $serviceId = $vars['params']['serviceid'];

    if ($themeId) {
        $service = \WHMCS\Service\Service::where('id', $serviceId)->first();

        if ($service) {
            $cpInstaller = new CpanelInstaller($service);
            $cpInstaller->autoInstall();
        }
    }

});
