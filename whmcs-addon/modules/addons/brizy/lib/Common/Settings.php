<?php
namespace  WHMCS\Module\Addon\Brizy\Common;
use WHMCS\Database\Capsule;

/**
 * Helper class for addon settings
 */
class Settings
{

    public static $servicePropertiesWorkspace = 'Brizy cloud workspace ID';
    public static $servicePropertiesTemplate = 'Brizy cloud template ID';
    public static $servicePropertiesWorkspaceCoreTeamMember = 'Brizy workspace core team member';
    public static $sessionCloudTemplateName = 'bc_template_id';

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
        $settingsDomain = \WHMCS\Config\Setting::getValue('SystemURL');
        if (!$settingsDomain) {
            return '';
        }
        return rtrim($settingsDomain, "/").'/'; ;
    }

    /**
     * Returns config data from file
     *
     * @return mixed
     */
    public static function getFromFile($param){
        $file = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'custom_settings.json';

        if (file_exists($file)) {
            $data = file_get_contents($file);

            $settingsData = json_decode($data, true);

            $jsonError = json_last_error();

            if (is_null($settingsData) && $jsonError == JSON_ERROR_NONE) {
                return null;
            }

            if (isset($settingsData[$param])) {
                return $settingsData[$param];
            }
        }

        return null;
    }
    /**
     * Checks brizy settings for product
     *
     * @param int $productId
     * @return void
     */
    public static function checkBrizyCloudSettingForProduct($productId) {

        if (!Capsule::schema()->hasTable('brizy_product_settings')) {
            return null;
        }

        $brizyCloudSettings = Capsule::table('brizy_product_settings')->where([
            'product_id' => $productId,
            'field_name' => 'brizy_cloud',
        ])->first();

        if ($brizyCloudSettings) {
            return $brizyCloudSettings->field_value;
        }

        return null;
    }

    /**
     * Creates custom fields for product
     *
     * @param int $productId
     * @param string $fieldName
     * @param array $fieldData
     * @return void
     */
    public function createCustomFieldForProduct($productId, $fieldName, $fieldData = []) {
        $defaults = [
            'type' => 'product',
            'relid' => $productId,
            'fieldname' => $fieldName,
            'fieldtype' => 'text',
            'description' => '',
        ];

        $fieldData = array_merge($defaults, $fieldData);

        $exists = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $productId)
            ->where('fieldname', $fieldName)
            ->count();


        if ($exists === 0) {
            Capsule::table('tblcustomfields')->insert($fieldData);

            return true;
        }

        return false;
    }

    /**
     * Creates the necessary custom fields for the product
     *
     * @param int $productId
     * @return void
     */
    public static function createBrizyCloudCustomFieldsForProduct($productId) {

        $fields = [
            [
                'name' => 'Website builder',
                'data' => [
                    'description' => 'You cannot change this value',
                    'showorder' => 'on',
                    'fieldtype' => 'dropdown',
                    'fieldoptions' => 'Yes',
                    'required' => 'on',
                ],
            ],

        ];

        foreach($fields as $field) {
            Settings::createCustomFieldForProduct($productId, $field['name'], $field['data']);
        }
    }

}
