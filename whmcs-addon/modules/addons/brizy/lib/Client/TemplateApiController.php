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
        $domain = (int)$_GET['domain'];

        $product = \WHMCS\Product\Product::find($productId);

        if (!$product){
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['themeSelector']['setThemeError']);
        }

        $brizyApi = new BrizyApi();
        $themes = $brizyApi->getDemos();

        if (!$themes || !isset($themes->demos)) {
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['themeSelector']['setThemeError']);
        }
        
        foreach($themes->demos as $demo) {

            if ($demo->id == $themeId) {

                Session::set('theme_id', $themeId);
                Session::set('theme_name', $demo->name);
                Session::set('theme_pro', $demo->pro ? 1 : 0);
                Session::set('brizy_pro', 0);
                
                $addonAvailable = Helpers::getBrizyProProductAddon($productId) ? true : false;
                $productIsBrizyPro = Helpers::isProductBrizyPro($productId);
    
                if ($productIsBrizyPro) {
                    Session::set('brizy_pro', 1);
                    $theme['brizy_pro'] = 1;
                }

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

                break;
            }
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

    public function getDemos() {
        $brizyApi = new BrizyApi();
        $themes = $brizyApi->getDemos();
        return $this->respond($themes);
    }
}
