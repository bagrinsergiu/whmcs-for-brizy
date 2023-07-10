<?php
namespace  WHMCS\Module\Addon\Brizy\Common;
use WHMCS\Database\Capsule;

/**
 * Helper class for addon settings
 */
class Settings
{
    /**
     * Returns settings for a given parameter
     *
     * @param string $param
     * @return string
     */
    public static function get($param) {
        $data =  Capsule::table('tbladdonmodules')
        ->where('module', 'brizy')
        ->where('setting', $param)
        ->first();

        $defaults = [
            'company_name' => 'Brizy',
            'logo_url' => 'modules/addons/brizy/logo.svg',
        ];

        $returnValue = trim($data->value);

        if (!$returnValue && isset($defaults[$param])) {
            $returnValue = $defaults[$param];
        }

        return $returnValue;
    }

    /**
     * Returns the domain under which whmcs is installed
     *
     * @return string
     */
    public static function getWHMCSDomain() {
        $settingsDomain = \WHMCS\Config\Setting::getValue('Domain');
        if (!$settingsDomain) {
            return '';
        }
        return rtrim($settingsDomain, "/").'/'; ;
    }
}
