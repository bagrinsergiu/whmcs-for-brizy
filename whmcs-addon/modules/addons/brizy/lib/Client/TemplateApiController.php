<?php

namespace WHMCS\Module\Addon\Brizy\Client;

use WHMCS\Module\Addon\brizy\Api\DefaultApiController;
use WHMCS\Module\Addon\Brizy\Common\Session;
use WHMCS\Module\Addon\Brizy\Common\BrizyApi;
use WHMCS\Module\Addon\Brizy\Common\Helpers;
use WHMCS\Module\Addon\Brizy\Common\Translations;
/**
 * Client area API controller
 */
class TemplateApiController extends DefaultApiController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    public function setInstallerTemplate() {
        $themeId = (int)$_GET['themeId'];
        $productId = (int)$_GET['productId'];

        $product = \WHMCS\Product\Product::find($productId);

        if (!$product){
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['themeSelector']['setThemeError']);
        }

        $brizyApi = new BrizyApi();
        $themes = $brizyApi->getDemos();

        $demoExists = false;

        if (!$themes || !isset($themes->demos)) {
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['themeSelector']['setThemeError']);
        }

        foreach($themes->demos as $demo) {

            if ($demo->id == $themeId) {
                Session::set('theme_id', $themeId);
                Session::set('theme_name', $demo->name);
                Session::set('theme_pro', $demo->pro ? 1 : 0);
                $demoExists = true;
                break;
            }
        }

        if ($demoExists) {
            $addonAvailable = Helpers::getBrizyProProductAddon($productId) ? true : false;
            $productIsBrizyPro = Helpers::isProductBrizyPro($productId);

            if ($demo->pro && !$productIsBrizyPro && !$addonAvailable) {
                $this->respondWithError(Translations::$_['client']['api']['repsonse']['themeSelector']['proThemeUnavailable']);
            }

            $this->respond([
                'pro' => $demo->pro,
                'name' => $demo->name,
                'id' => $demo->id,
                'addon_available' => $addonAvailable,
                'product_pro' => $productIsBrizyPro,
            ]);
        }

        $this->respondWithError(Translations::$_['client']['api']['repsonse']['themeSelector']['notPossibleToSet']);
    }


    public function getInstallerTemplate() {
        $data = [
            'themeId' => null,
        ];

        $data['themeId'] = Session::get('theme_id');

        $this->respond($data);
    }
}
