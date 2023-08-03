<?php

namespace  WHMCS\Module\Addon\Brizy\Common;

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Brizy\Common\BrizyApi;

/**
 * Helpers
 */
class Helpers
{
    /**
     * License status - synced and active
     */
    const LICENSE_STATUS_SYNCED_ACTIVE = 1;

    /**
     * License status - synced and none active
     */
    const LICENSE_STATUS_SYNCED_NONE_ACTIVE = 2;

    /**
     * License status - not synced
     */
    const LICENSE_STATUS_NOT_SYNCED = 0;


    /**
     * Returns whether the client has a license assigned to the service.
     *
     * @param integer $serviceId
     * @return boolean
     */
    public static function checkIfBrizyLicenseAssigned($serviceId) {
        $currentUser =  new \WHMCS\Authentication\CurrentUser;
        $userData = $currentUser->user();

        $license = Capsule::table('brizy_licenses')
            ->where('service_id', $serviceId)
            ->where('user_id', $userData->id)
            ->first();

        return $license ? true : false;
    }


    /**
     * Returns whether the customer can install Brizy Pro
     *
     * @param integer $serviceId
     * @return boolean
     */
    public static function checkIfCanInstallBrizyFree($serviceId) {

        $service = \WHMCS\Service\Service::where('id', $serviceId)
            ->first();

        $productBrizyPro = array_map('trim', explode(',', Settings::get('product_name_free')));

        if (in_array($service->product->name, $productBrizyPro)) {

            return true;
        }

        return false;
    }



    /**
     * Returns whether the product can install brizy pro
     *
     * @param integer $productId
     * @return boolean
     */
    public static function isProductBrizyPro($productId) {
        $product = \WHMCS\Product\Product::where('id', $productId)
        ->first();

        if ($product) {

            $productBrizyPro = array_map('trim', explode(',', Settings::get('product_name')));

            if (in_array($product->name, $productBrizyPro)) {

                if (self::checkForFreeLicenses()) {
                    return true;
                }
            }
        }

        return false;
    }


    public static function getBrizyProProductAddon($productId){
        $product = \WHMCS\Product\Product::where('id', $productId)
            ->first();

        if ($product) {

            $productAddonsForBrizyPro = array_map('trim', explode(',', Settings::get('product_addon_name')));

            foreach($productAddonsForBrizyPro as $addonName) {
                $productAddon = Capsule::table('tbladdons')
                    ->where('packages', 'LIKE', '%,'.$productId.',%')
                    ->where('showorder', 1)
                    ->where('name', $addonName)
                    ->first();

                if ($productAddon) {
                    return $productAddon;
                }
            }

        }

        return false;
    }

    /**
     * Checks if addon is brizy pro
     *
     * @param string $addonName
     * @return boolean
     */
    public static function checkIfAddonIsBrizyPro($addonName) {

        $productAddonsForBrizyPro = array_map('trim', explode(',', Settings::get('product_addon_name')));

        return in_array($addonName, $productAddonsForBrizyPro);
    }


    /**
     * checks if any of the addons are for brizy pro
     *
     * @param array $addons addons from template - configureproductdomain.tpl
     * @return boolean
     */
    public static  function checkAllAddonsIfBrizyPro($addons) {

        if (is_array($addons)) {

            foreach($addons as $addon) {
                if (isset($addon['name']) && self::checkIfAddonIsBrizyPro($addon['name'])){
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Returns whether the customer can install Brizy Pro
     *
     * @param integer $serviceId
     * @return boolean
     */
    public static function checkIfCanInstallBrizyPro($serviceId)
    {

        $service = \WHMCS\Service\Service::where('id', $serviceId)
            ->first();

        $productBrizyPro = array_map('trim', explode(',', Settings::get('product_name')));

        if (in_array($service->product->name, $productBrizyPro)) {

            if (self::checkForFreeLicenses()) {
                return true;
            }
        }

        $productAddonsForBrizyPro = array_map('trim', explode(',', Settings::get('product_addon_name')));

        foreach ($service->addons as $addon) {

            $addonData =  Capsule::table('tbladdons')->where('id', $addon->addonid)->first();

            if ($addonData && in_array($addonData->name, $productAddonsForBrizyPro)) {

                if (self::checkForFreeLicenses()) {
                    return true;
                }
            }
        }

        if (self::getLicenseForService($serviceId)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the are free licenses
     */
    public static function checkForFreeLicenses() {
        $freeLicenses = Capsule::table('brizy_licenses')
        ->where('service_id', 0)
        ->where('user_id', 0)
        ->count();

        return $freeLicenses > 0;
    }

    /**
     * Returns whether the customer can install Brizy
     *
     * @param integer $serviceId
     * @return boolean
     */
    public static function checkIfCanInstallBrizy($serviceId)
    {

        $currentUser =  new \WHMCS\Authentication\CurrentUser;
        $userData = $currentUser->user();
        $service = \WHMCS\Service\Service::where('id', $serviceId)
            ->first();


        if ($service->clientId !== $userData->id) {
            return false;
        }

        return false;
    }

    /**
     * Generates a unique hash
     *
     * @param integer $length
     * @return string
     */
    public static function getHash($length = null)
    {
        if (!$length) {
            return $length = rand(4, 22);
        }

        $bytes = random_bytes(ceil($length / 2));

        return  substr(bin2hex($bytes), 0, $length);
    }

    /**
     * Returns the license assigned to the given service
     *
     * @param integer $serviceId
     * @return string
     */
    public static function getLicenseForService($serviceId)
    {

        $service = \WHMCS\Service\Service::where('id', $serviceId)
            ->first();

        $license =  Capsule::table('brizy_licenses')
            ->where('service_id', $service->id)
            ->first();


        return $license->license;;
    }

    /**
     * Assigns new license to service
     *
     * @param integer  $forceUserId
     * @return string|boolean
     */
    public static function assignNewLicenseForService($serviceId)
    {

        if (!self::checkIfCanInstallBrizyPro($serviceId)){
            return null;
        }

        $license = self::getLicenseForService($serviceId);
        if ($license){
            return $license;
        }

        $service = \WHMCS\Service\Service::where('id', $serviceId)
            ->first();

        if (!$service) {
            return false;
        }

        $license = Capsule::table('brizy_licenses')
            ->where('service_id', 0)
            ->where('user_id', 0)
            ->orderby('id', 'ASC')
            ->limit(1)
            ->update([
            'service_id' => $service->id,
            'user_id' => $service->clientId,
            'assigned_at' => date('Y-m-d H:i:s')
        ]);

       return self::getLicenseForService($serviceId);
    }

    /**
     * Synchronize licenses data
     *
     * @param any $licenseApiResponse
     * @return void
     */
    public static function synchronizeLicenses($licenses) {

        if ($licenses && is_array($licenses)) {
            foreach ($licenses as $license) {

                   self::syncSingleLicense($license);
            }
        }
    }

    /**
     * Synchronize license data
     *
     * @param any $licenseApiResponse
     * @return void
     */
    public static function syncSingleLicense($licenseApiResponse) {
        $licenseDb =  Capsule::table('brizy_licenses')
            ->where('license', $licenseApiResponse->license)
            ->first();

            if (!$licenseDb) {
                $licenseDb = Capsule::table('brizy_licenses')
                ->insert(
                    [
                        'license' => $licenseApiResponse->license,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                );

            }

            $status = self::LICENSE_STATUS_NOT_SYNCED;

            if ($licenseApiResponse->status == 'active') {
                $status = self::LICENSE_STATUS_SYNCED_ACTIVE;
            }

            if ($licenseApiResponse->status == 'non-active') {
                $status = self::LICENSE_STATUS_SYNCED_NONE_ACTIVE;
            }

            Capsule::table('brizy_licenses')
            ->where('id', $licenseDb->id)
            ->limit(1)
            ->update([
                'status' => $status,
                'activation_domain' => $licenseApiResponse->domain,
            ]);
    }

    /**
     * Returns saved theme id for order
     *
     * @param integer $orderId
     * @return integer
     */
    public static function getThemeIdForOrder($orderId) {
        $theme = self::getThemeDataForOrder($orderId);

        if ($theme) {
            return $theme->theme_id;
        }
        return null;
    }

    /**
     * Returns saved theme data
     *
     * @param integer $orderId
     * @return WHMCS\Database\Capsule
     */
    public static function getThemeDataForOrder($orderId) {
        $orderTheme =  Capsule::table('brizy_order_themes')
        ->where('order_id', (int)$orderId)
        ->first();

        if ($orderTheme) {
            return $orderTheme;
        }

        return null;
    }

    /**
     * Validates API connection
     *
     * @return boolean
     */
    public static function validateApiConnection() {
        $brizyApi = new BrizyApi();
        $licensesCheck = $brizyApi->getLicenses();

        return $licensesCheck !== false ? true : false;
    }

    /**
     * Validates Brizy Pro download token
     *
     * @return boolean
     */
    public static function validateDownloadToken() {

        $downloadToken = trim(Settings::get('brizy_pro_download_token'));
        $downloadUrl = 'https://www.brizy.cloud/api/licences_download?token=' . $downloadToken;

        $context = stream_context_create(array('http' => ['method' => 'HEAD']));
        $headers = get_headers($downloadUrl, 1, $context);

        if (!$headers) {
            return false;
        }

        if (
            isset($headers[0]) && str_contains($headers[0], '200')
            && isset($headers['Content-Disposition']) && str_contains($headers['Content-Disposition'], 'attachment')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Generates URL to product forcing order template for brizy
     *
     * @param string $productUrl
     * @return string
     */
    public static function forceBrizyTplProdyuctUrl($productUrl) {
        if (strpos($productUrl, '?')) {
            $productUrl .= '&';
        } else {
            $productUrl .= '?';
        }

        return $productUrl . 'carttpl=brizy_standard_cart';
    }
}
