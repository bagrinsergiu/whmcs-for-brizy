<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Brizy\Admin\AdminDispatcher;
use WHMCS\Module\Addon\Brizy\Client\ClientDispatcher;
use WHMCS\Module\Addon\Brizy\Common\Settings;
use WHMCS\Module\Addon\Brizy\Common\Helpers;

/**
 * Brizy addon module config array.
 *
 * @return array
 */
function brizy_config()
{

    $apiTokenDescription = '<span style="color:#ff6000"><a style="text-decoration: underline; color:#ff6000" target="_blank" href="https://www.brizy.io/solutions-for-hosts-and-resellers#lets-talk">Contact us</a> to get the API token in order to provide upgrade to PRO for your costumers, and activate White Label options.</span> <br/>';

    $apiToken = trim(Settings::get('api_token'));

    if ($apiToken) {

        $apiTokenDescription = '<strong style="color:red">API token is invalid</strong>';


        if (Helpers::validateApiConnection()) {
            $apiTokenDescription = '<strong style="color:green">API token is valid</strong>';

        }
    }

    $downloadTokenDescription = '<span style="color:#ff6000"><a style="text-decoration: underline; color:#ff6000" target="_blank" href="https://www.brizy.io/solutions-for-hosts-and-resellers#lets-talk">Contact us</a> to get the download token in order to provide upgrade to PRO for your costumers, and activate White Label options.</span><br/>';
    $downloadToken = trim(Settings::get('brizy_pro_download_token'));

    if ($downloadToken) {

        $downloadTokenDescription = '<strong style="color:red">Download token is invalid</strong></br>';

        if (Helpers::validateDownloadToken()) {
            $downloadTokenDescription = '<strong style="color:green">Download token is valid</strong></br>';

        }
    }

    return [

        'name' => 'Brizy',
        'description' => 'Brizy for WHMCS extends the capabilities of WHMCS to remotely install WordPress, Brizy or the Brizy PRO version on shared hosting accounts. Both when creating new orders and using a specially prepared installer for existing customers.',
        'author' => 'DOTINUM.COM',
        'language' => 'english',
        'version' => '1.03',
        'fields' => [
            'product_name_free' => [
                'FriendlyName' => 'Product names for the FREE version',
                'Type' => 'text',
                'Size' => '250',
                'Default' => '',
                'Description' => '<br/>Your builder product  name, required for Brizy FREE installation (each product addon name should be separated with ",", for example: "Brizy Pro Hosting,Brizy Pro Hosting #2") ',
            ],
            'product_name' => [
                'FriendlyName' => 'Product names for the PRO version',
                'Type' => 'text',
                'Size' => '250',
                'Default' => '',
                'Description' => '<br/>Your builder product  name, required for Brizy Pro installation (each product addon name should be separated with ",", for example: "Brizy Pro Hosting,Brizy Pro Hosting #2") ',
            ],
            'product_addon_name' => [
                'FriendlyName' => 'Product addons names',
                'Type' => 'text',
                'Size' => '250',
                'Default' => '',
                'Description' => '<br/>Your builder product addon name, required for Brizy Pro installation (each product addon name should be separated with ",", for example: "Brizy Pro Upgrade,Brizy Pro Upgrade - free #2") ',
            ],
            'brizy_pro_download_token' => [
                'FriendlyName' => 'Brizy Pro download token',
                'Type' => 'text',
                'Size' => '250',
                'Description' => '</br>'.$downloadTokenDescription.'The token is needed to download the Brizy Pro archive',
            ],
            'api_token' => [
                'FriendlyName' => 'API Token',
                'Type' => 'text',
                'Size' => '150',
                'Default' => '',
                'Description' => '</br>'.$apiTokenDescription,
            ],
            'company_name' => [
                'FriendlyName' => 'Company name [WHITE LABEL]',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Description' => '<br/>This will basically change the word Brizzy with your company name everywhere in the builder from Edit with Brizy, to label link and WordPress menu items',
            ],
            'logo_url' => [
                'FriendlyName' => 'Logo [WHITE LABEL]',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Description' => '<br/>Will change the logo in the WordPress sidebar menu and on the "Edit with ..." button (path to svg file)',
            ],
            'plugin_description' => [
                'FriendlyName' => 'Description [WHITE LABEL]',
                'Type' => 'textarea',
                'Rows' => '3',
                'Cols' => '60',
                'Default' => 'A drag & drop front-end page builder to help you create Wordpress pages lightening fast',
                'Description' => 'Brizy description',
            ],
            'about_url' => [
                'FriendlyName' => 'About URL [WHITE LABEL]',
                'Type' => 'text',
                'Size' => '150',
                'Default' => '',
                'Description' => '',
            ],
            'support_url' => [
                'FriendlyName' => 'Support URL [WHITE LABEL]',
                'Type' => 'text',
                'Size' => '150',
                'Default' => '',
                'Description' => '',
            ],
            'theme_selector_custom_css' => [
                'FriendlyName' => 'Theme selector - custom css',
                'Type' => 'textarea',
                'Size' => '9000',
                'Default' => '.brz-theme-selector .theme-count {
    background: #57a10a !important;
}
',
                'Description' => 'Your css styles, to personalize theme selector',
            ],
        ]
    ];
}

/**
 * Config validation
 *
 * @param array $params
 * @return void
 */
function brizy_config_validate($params) {
    $valid = false;

    if (!$valid) {
        throw new InvalidConfiguration('API Key is invalid.');
    }
}


/**
 * Activate.
 *
 * Called upon activation of the module for the first time.
 *
 * @return array Optional success/failure message
 */
function brizy_activate()
{
    // Create custom tables and schema required by your module
    try {

        if (!Capsule::schema()->hasTable('brizy_licenses')) {

            Capsule::schema()
                ->create(
                    'brizy_licenses',
                    function ($table) {
                        $table->increments('id');
                        $table->string('license');
                        $table->integer('user_id');
                        $table->integer('service_id');
                        $table->dateTime('assigned_at');
                        $table->string('activation_domain');
                        $table->integer('status');
                        $table->dateTime('created_at')->useCurrent();
                        $table->dateTime('updated_at');
                    }
                );
        }


        if (!Capsule::schema()->hasTable('brizy_installations')) {
            Capsule::schema()
                ->create(
                    'brizy_installations',
                    function ($table) {
                        $table->increments('id');
                        $table->integer('user_id');
                        $table->integer('service_id');
                        $table->string('db_name');
                        $table->string('db_user');
                        $table->string('db_pass');
                        $table->integer('path');
                        $table->integer('type');
                        $table->integer('status');
                        $table->dateTime('created_at')->useCurrent();
                        $table->dateTime('updated_at');
                    }
                );
        }

        if (!Capsule::schema()->hasTable('brizy_order_themes')) {
            Capsule::schema()
                ->create(
                    'brizy_order_themes',
                    function ($table) {
                        $table->increments('id');
                        $table->integer('order_id');
                        $table->integer('theme_id');
                        $table->string('name');
                        $table->integer('pro');
                        $table->dateTime('created_at')->useCurrent();
                    }
                );
        }


        return [
            'status' => 'success',
            'description' => 'Brizy addon successfully installed',
        ];
    } catch (\Exception $e) {
        return [
            'status' => "error",
            'description' => 'Unable to create brizy_licenses table: ' . $e->getMessage(),
        ];
    }
}

/**
 * Deactivate.
 *
 * Called upon deactivation of the module.
 *
 * @return array Optional success/failure message
 */
function brizy_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Brizy addon successfully disabled',
    ];
}

/**
 * Admin Area Output.
 *
 * Called when the addon module is accessed via the admin area.
 *
 * @return string
 */
function brizy_output($vars)
{

    $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $dispatcher = new AdminDispatcher();
    $response = $dispatcher->dispatch($action, $vars);
    echo $response;
}

/**
 * Admin Area Sidebar Output.
 *
 * Used to render output in the admin area sidebar.
 *
 * @param array $vars
 *
 * @return string
 */
function brizy_sidebar($vars)
{
    $modulelink = $vars['modulelink'];

    $sidebar =
        '
    <ul class="menu">
         <li><a href="' . $modulelink . '">Licenses management</a></li>

    </ul>
    ';

    return $sidebar;
}

/**
 * Client Area Output.
 *
 * Called when the addon module is accessed via the client area.
 *
 * @return array
 */
function brizy_clientarea($vars)
{

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $dispatcher = new ClientDispatcher();
    return $dispatcher->dispatch($action, $vars);
}
