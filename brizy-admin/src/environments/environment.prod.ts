const whmcsPath = window.location.href.substring(0, window.location.href.indexOf("/admin/")) || window.location.href.substring(0, window.location.href.indexOf("/clientarea.php"));
export const environment = {
    production: true,
    i18nPath: whmcsPath + '/modules/addons/brizy/lang/js'
};
