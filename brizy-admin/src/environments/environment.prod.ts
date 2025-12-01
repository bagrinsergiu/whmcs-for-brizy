declare var whmcsBaseUrl: string;

let whmcsPath =  window.location.href.substring(0, window.location.href.indexOf("/admin/"))
|| window.location.href.substring(0, window.location.href.indexOf("/clientarea.php"))
|| window.location.href.substring(0, window.location.href.indexOf("/index.php"));

if (!whmcsPath && typeof whmcsBaseUrl !== 'undefined') {
    whmcsPath = window.location.protocol + '//' + window.location.host + whmcsBaseUrl;
}

export const environment = {
    production: true,
    whmcsPath: whmcsPath,
    i18nPath: whmcsPath + '/modules/addons/brizy/lang/js',
    apiUrl: 'https://domapi.mserwis.pl/whmcs/',
};
