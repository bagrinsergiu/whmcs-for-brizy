<?php
use WHMCS\Module\Addon\Brizy\Client\ProductDetailsDispatcher;
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\BrizyClient;
use WHMCS\Module\Addon\Brizy\Common\Session;
use WHMCS\Module\Addon\Brizy\Common\CpanelInstaller;
use WHMCS\Module\Addon\Brizy\Common\Helpers;
use WHMCS\Module\Addon\Brizy\Common\Settings;
use WHMCS\Module\Addon\Brizy\Common\Translations;

use WHMCS\Service\Service;
use WHMCS\Order\Order;
use WHMCS\Order\OrderItem;

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
 * Client area - add Brizy Cloud theme selector to the product configuration page in the cart
 */
add_hook('ClientAreaPageCart', 1, function($vars) {


    if ($vars['filename'] === 'cart' && $_GET['a'] === 'confproduct') {

        $cartIndex = (int)$_GET['i'];
        $productInfo = $vars['productinfo'];
        $whmcsDomain = \WHMCS\Module\Addon\Brizy\Common\Settings::getWHMCSDomain();
        $hash = \WHMCS\Module\Addon\Brizy\Common\Helpers::getHash();

        $brizyCloudProduct = Settings::checkBrizyCloudSettingForProduct($productInfo['pid']);

        if (!$brizyCloudProduct) {
            return;
        }

        $selectedTemplateId = (int)$_SESSION[Settings::$sessionCloudTemplateName][$cartIndex] ?? null;

        $productInfo['description'] .= '
            <div>
                <app-brizy-cloud-theme-selector selected-template-id="'.$selectedTemplateId.'" product-id="'.$productInfo['pid'].'" i="'.$cartIndex.'"></app-brizy-cloud-theme-selector>
            </div>

            <link rel="stylesheet" href="'.$whmcsDomain.'modules/addons/brizy/apps/brizy-admin/styles.css?h=">
            <script src="'.$whmcsDomain.'modules/addons/brizy/apps/brizy-admin/runtime.js?h='.$hash.'" defer></script>
            <script src="'.$whmcsDomain.'modules/addons/brizy/apps/brizy-admin/polyfills.js?h='.$hash.'" defer></script>
            <script src="'.$whmcsDomain.'modules/addons/brizy/apps/brizy-admin/main.js?h='.$hash.'" defer></script>
        ';

        return [
            'productinfo' => $productInfo,
        ];
    }

});


/**
 * Upon completion of checkout once the order has been created, invoice generated and all email notifications sent
 */
add_hook('AfterShoppingCartCheckout', 1, function($vars) {

    Translations::set($vars);

    $themeId = Session::get('theme_id');
    $themeName = Session::get('theme_name');
    $themeIsPro = Session::get('theme_pro');
    $brizyPro = Session::get('brizy_pro');

    if ($themeId) {
        Session::clear();

        Capsule::table('brizy_order_themes')->insert(
            [
                'order_id' => $vars['OrderID'],
                'theme_id' => $themeId,
                'name' => $themeName,
                'pro' => ($themeIsPro || $brizyPro),
            ]
        );
    }


    $orderId = (int)$vars['OrderID'] ?? null;
    $templatesIds = $_SESSION[Settings::$sessionCloudTemplateName] ?? [];

    if (!$orderId || !$templatesIds) {
        return;
    }

    $order = Order::find($orderId);
    if (!$order) {
        return;
    }
    $items = Capsule::table('tblhosting')
        ->where('orderid', $orderId)
        ->get();

    foreach ($items as $index =>  $item) {

        $templateId = $templatesIds[$index] ?? null;
        $service = Service::find($item->id);

        if (!$templateId) {
            continue;
        }

        $service->serviceProperties->save([Settings::$servicePropertiesTemplate => $templateId]);
    }


    unset($_SESSION[Settings::$sessionCloudTemplateName]);

});

/**
 * Executes upon successful completion of the module function.
 */
add_hook('AfterModuleCreate', 1, function($vars) {

    Translations::set($vars);

    $themeId = Helpers::getThemeIdForOrder($vars['params']['model']->order->id);
    $serviceId = $vars['params']['serviceid'];
    $productId = $vars['params']['packageid'];

    if ($themeId) {
        $service = Service::where('id', $serviceId)->first();

        if ($service) {
            $cpInstaller = new CpanelInstaller($service);
            $cpInstaller->autoInstall();
        }
    }

    $brizyCloud = Settings::checkBrizyCloudSettingForProduct($productId);

    if ($brizyCloud == 1) {

        BrizyClient::autoCreateWorkspace($serviceId);
    }

});

/**
 * Admin area - add custom product configuration fields
 */
add_hook('AdminProductConfigFields', 1, function($vars) {

    if (!Capsule::schema()->hasTable('brizy_product_settings')) {
        return [];
    }

    $productId = $vars['pid'];
    $brizyCloud = Capsule::table('brizy_product_settings')
        ->where('product_id', $productId)
        ->where('field_name', 'brizy_cloud')
        ->first() ?? 0;

    $fieldsArray = array(
     'Brizy Cloud' => '
        <select class="form-control select-inline"  name="brizy_product_settings[brizy_cloud]">
            <option '.($brizyCloud->field_value == 0 ? 'selected' : '').' value="0">No</option>
            <option '.($brizyCloud->field_value == 1 ? 'selected' : '').' value="1">Yes</option>
        </select>',
    );
    return $fieldsArray;
});

/**
 * Admin area - save custom product configuration fields
 */
add_hook('AdminProductConfigFieldsSave', 1, function($vars) {

    $value = $_POST['brizy_product_settings']['brizy_cloud'] ?? null;
    $productId = (int)$vars['pid'];

    if ($value !== null) {
        Capsule::table('brizy_product_settings')->updateOrInsert([
            'product_id' => $productId,
            'field_name' => 'brizy_cloud',
        ], [
            'field_value' => $value,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }


    Settings::createBrizyCloudCustomFieldsForProduct($productId);

});

/**
 * Admin area - accept order hook
 */
add_hook('AcceptOrder', 1, function($vars) {

    $orderId = $vars['orderid'];

    $results = localAPI('GetOrders',  [
        'id' => $orderId,
    ]);

    if (isset($results['orders']['order'][0])) {
        $orderData = $results['orders']['order'][0];

        $products = $orderData['lineitems']['lineitem'];


        foreach($products as $product) {
            $serviceData = Service::where('id', $product['relid'])->first();

            if ($serviceData) {
                $systemProductId = $serviceData->packageid;

                $brizyCloud = Settings::checkBrizyCloudSettingForProduct($systemProductId);

                if ($brizyCloud == 1) {
                    BrizyClient::autoCreateWorkspace($serviceData->id);
                }
            }

        }
    }

});

/**
 * Admin area - delete order hook
 */
add_hook('DeleteOrder', 1, function($vars) {

    $orderId = $vars['orderid'];

    $results = localAPI('GetOrders',  [
        'id' => $orderId,
    ]);

    if (isset($results['orders']['order'][0])) {
        $orderData = $results['orders']['order'][0];

        $products = $orderData['lineitems']['lineitem'];


        foreach($products as $product) {
            $serviceData = Service::where('id', $product['relid'])->first();

            if ($serviceData) {
                $systemProductId = $serviceData->packageid;

                $brizyCloud = Settings::checkBrizyCloudSettingForProduct($systemProductId);

                if ($brizyCloud > 0) {
                    $workspaceId = $serviceData->serviceProperties->get([Settings::$servicePropertiesWorkspace]);

                    if ($workspaceId) {
                        $status = BrizyClient::deleteWorkspace($workspaceId);

                        if ($status) {
                            $serviceData->serviceProperties->save([Settings::$servicePropertiesWorkspace => '']);
                        }
                    }


                }

            }

        }
    }

});

/**
 * Admin area - add custom service configuration fields
 */
add_hook('AdminClientServicesTabFields', 1, function($params) {

    $serviceId = (int)$params['id'];

    $serviceData = Service::where('id', $serviceId)->first();

    if ($serviceData) {
        $systemProductId = $serviceData->packageid;
        $brizyCloud = Settings::checkBrizyCloudSettingForProduct($systemProductId);

        $workspaceId = $serviceData->serviceProperties->get([Settings::$servicePropertiesWorkspace]);

        if ($workspaceId) {
            $deleteWorkspaceButton = '<button type="submit" class="btn btn-danger" name="bc_delete_workspace" value="'.$serviceId.'" >Delete workspace for this service</button>';
        } else {
            $createWorkspaceButton = '<button type="submit" class="btn btn-primary" name="bc_create_workspace" value="'.$serviceId.'" >Create workspace for this service</button>';
        }


        if ($brizyCloud > 0) {
            $html = '
                <span style="display:flex; gap: 15px;">
                    '.$deleteWorkspaceButton.'
                    '.$createWorkspaceButton.'
                </span>

            ';

            return [
                'Brizy cloud workspace' => $html,
            ];
        }
    }
});

/**
 * Admin area - save custom service configuration fields
 */
add_hook('AdminClientServicesTabFieldsSave', 1, function($vars) {


    if (isset($vars['bc_create_workspace']) && (int)$vars['bc_create_workspace'] > 0) {

        $serviceId = (int)$vars['bc_create_workspace'];
        $serviceData = Service::where('id', $serviceId)->first();

        if ($serviceData) {
            $systemProductId = $serviceData->packageid;

            $brizyCloud = Settings::checkBrizyCloudSettingForProduct($systemProductId);

            if ($brizyCloud > 0) {
                $workspaceId = $serviceData->serviceProperties->get([Settings::$servicePropertiesWorkspace]);


                if (!$workspaceId) {
                    BrizyClient::autoCreateWorkspace($serviceData->id);

                    return [
                        'success' => true,
                    ];

                } else {
                    return [
                        'success' => false,
                        'errorMsg' => 'Failed to create workspace - workspace already exists',
                    ];
                }

                return [
                    'success' => false,
                    'errorMsg' => 'Failed to create workspace - check logs (2)',
                ];
            }
        }

        return [
            'success' => false,
            'errorMsg' => 'Failed to create workspace',
        ];
    }



    if (isset($vars['bc_delete_workspace']) && (int)$vars['bc_delete_workspace'] > 0) {

        $serviceId = (int)$vars['bc_delete_workspace'];
        $serviceData = Service::where('id', $serviceId)->first();

        if ($serviceData) {
            $systemProductId = $serviceData->packageid;

            $brizyCloud = Settings::checkBrizyCloudSettingForProduct($systemProductId);

            if ($brizyCloud > 0) {
                $workspaceId = $serviceData->serviceProperties->get([Settings::$servicePropertiesWorkspace]);

                if ($workspaceId) {
                    $status = BrizyClient::deleteWorkspace($workspaceId);

                    if ($status) {
                        $serviceData->serviceProperties->save([Settings::$servicePropertiesWorkspace => '']);
                        return [
                            'success' => true,
                        ];
                    } else {
                        return [
                            'success' => false,
                            'errorMsg' => 'Failed to delete workspace - check logs (1)',
                        ];
                    }
                }

                return [
                    'success' => false,
                    'errorMsg' => 'Failed to delete workspace - check logs (2)',
                ];
            }
        }

        return [
            'success' => false,
            'errorMsg' => 'Failed to delete workspace',
        ];
    }



});