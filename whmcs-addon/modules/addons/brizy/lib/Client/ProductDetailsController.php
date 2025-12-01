<?php

namespace WHMCS\Module\Addon\Brizy\Client;

use WHMCS\Module\Addon\Brizy\Common\Settings;
use WHMCS\Module\Addon\Brizy\Common\Helpers;
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Brizy\Common\Translations;


use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\BrizyClient;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\CreateWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\CreateProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\CreateUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\CreateTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\UpdateTeamMemberRequest;



/**
 * Product Details controller
 */
class ProductDetailsController
{

    private $wp = false;
    private $cloud = false;

    /**
     * List of addons for services/products that allow  to install Brizy Pro
     *
     * @var array
     */
    private $productAddons;

    public function __construct()
    {
        $this->smarty = new \Smarty;
        $this->smarty->template_dir = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'templates');

        $this->productAddons = array_map('trim', explode(',', Settings::get('product_addon_name')));
    }

    /**
     * Index action.
     *
     * @param array $vars Product/Service configuration parameters
     *
     * @return array
     */
    public function index($vars)
    {

        $service = $vars['service'];

        if ($service->product->servertype == 'cpanel') {
            $this->wp = true;
        }

        $productName = $service->product->name;
        $orderId = $service->orderId;

        $addonsAvailableToBuy =   Capsule::table('tbladdons')->where('packages', 'LIKE', '%,' . $service->packageid . ',%')->get();
        $brizyAddonOptions = [];

        foreach ($addonsAvailableToBuy as $addon) {

            if (in_array($addon->name, $this->productAddons)) {
                $brizyAddonOptions[] = $addon;
            }
        }

        $freeLicenses = Capsule::table('brizy_licenses')
            ->where('service_id', 0)
            ->where('user_id', 0)
            ->count();

        $this->smarty->assign('wp', $this->wp);

        $this->smarty->assign('hasAssignedBrizyLicense', Helpers::checkIfBrizyLicenseAssigned($service->id));
        $this->smarty->assign('canInstallFree', Helpers::checkIfCanInstallBrizyFree($service->id));
        $this->smarty->assign('canInstallPro', Helpers::checkIfCanInstallBrizyPro($service->id));
        $this->smarty->assign('orderId', $orderId);
        $this->smarty->assign('brizyAddonOptions', $brizyAddonOptions);
        $this->smarty->assign('hash', Helpers::getHash());
        $this->smarty->assign('serviceId', $service->id);
        $this->smarty->assign('freeLicenses', $freeLicenses);

        $this->smarty->assign('bLogo', Settings::get('logo_url'));
        $this->smarty->assign('bPluginName', Settings::get('company_name'));

        $this->smarty->assign('LANG', Translations::$_);

        //brizy cloud
        $this->cloud = false;
        $cloudDeploy = false;

        $workspaceCheck =  $service->serviceProperties->get([Settings::$servicePropertiesWorkspace]);

        if ($workspaceCheck) {
            $cloudApiValid = Helpers::validateBrizyCloudApi();
            if ($cloudApiValid) {
                $this->cloud = true;
                $cloudDeploy = $this->checkIfCpanelActiveService($service);

                $this->smarty->assign('cloud_website_builder_name', Settings::get('cloud_website_builder_name'));
                $this->smarty->assign('cloud_website_builder_logo_path', Settings::get('cloud_website_builder_logo_path'));
            }
        }


        $this->smarty->assign('cloud', $this->cloud);
        $this->smarty->assign('cloudDeploy', $cloudDeploy);

        $brizyServiceMenu = $this->smarty->fetch('mainMenu.tpl');

        return $brizyServiceMenu;
    }

    private function checkIfCpanelActiveService($service) {
        if (!$service) {
            return false;
        }

        if ($service->status !== 'Active') {
            return false;
        }

        if ($service->product->module !== 'cpanel') {
            return false;
        }

        return true;
    }



}
