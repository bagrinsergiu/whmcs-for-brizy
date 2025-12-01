<!-- Theme Selector T START PID: {$pid} -->
{assign var=$translations value=(\WHMCS\Module\Addon\Brizy\Common\Translations::set())}
{assign var=showBrizyInstaller value=(\class_exists('\WHMCS\Module\Addon\Brizy\Common\Settings') && (\WHMCS\Module\Addon\Brizy\Common\Helpers::isProductBrizyPro($pid) || \WHMCS\Module\Addon\Brizy\Common\Helpers::isProductBrizyFree($pid))) }

{assign var=showLackOfLicensesMsg value=(
    \class_exists('\WHMCS\Module\Addon\Brizy\Common\Settings')
    && \WHMCS\Module\Addon\Brizy\Common\Helpers::isProductBrizyPro($pid, false)
    && !\WHMCS\Module\Addon\Brizy\Common\Helpers::checkForFreeLicenses(true)
    && \WHMCS\Module\Addon\Brizy\Common\Settings::get('generate_new_licenses_automaticaly') !== 'on')
}

{if $showLackOfLicensesMsg}
    <div style="margin:0 0 10px 0;padding:10px 35px;background-color:#ffffd2;color:#555;font-size:16px;text-align:center;">
    <strong> {\WHMCS\Module\Addon\Brizy\Common\Translations::$_['client']['template']['order']['lackOfLicenses']['header']}</strong>
    {\WHMCS\Module\Addon\Brizy\Common\Translations::$_['client']['template']['order']['lackOfLicenses']['msg']}
    </div>
{/if}

{if $showBrizyInstaller}
    <style>
        .main-header {
            display: none !important;
        }

        .themes {
            max-height: 1320px !important;
        }

        .brz-theme-selector {
            position: relative;
        }

        .brz-theme-selector .theme-browser {
            padding-bottom: 20px;
        }
    </style>

    {assign var=customCss value=\WHMCS\Module\Addon\Brizy\Common\Settings::get('theme_selector_custom_css')}
    {if $customCss}
        <style>
            {$customCss}
        </style>
    {/if}
    <app-brizy-theme-selector pro="{(int)\WHMCS\Module\Addon\Brizy\Common\Helpers::isProductBrizyPro($pid)}" product-id="{$pid}"></app-brizy-theme-selector>

    <link rel="stylesheet" href="{\WHMCS\Module\Addon\Brizy\Common\Settings::getWHMCSDomain()}modules/addons/brizy/apps/brizy-admin/styles.css?h={\WHMCS\Module\Addon\Brizy\Common\Helpers::getHash()}">
    <script src="{\WHMCS\Module\Addon\Brizy\Common\Settings::getWHMCSDomain()}modules/addons/brizy/apps/brizy-admin/runtime.js?h={\WHMCS\Module\Addon\Brizy\Common\Helpers::getHash()}" defer></script>
    <script src="{\WHMCS\Module\Addon\Brizy\Common\Settings::getWHMCSDomain()}modules/addons/brizy/apps/brizy-admin/polyfills.js?h={\WHMCS\Module\Addon\Brizy\Common\Helpers::getHash()}" defer></script>
    <script src="{\WHMCS\Module\Addon\Brizy\Common\Settings::getWHMCSDomain()}modules/addons/brizy/apps/brizy-admin/main.js?h={\WHMCS\Module\Addon\Brizy\Common\Helpers::getHash()}" defer></script>
{/if}
<!-- Theme Selector T END -->