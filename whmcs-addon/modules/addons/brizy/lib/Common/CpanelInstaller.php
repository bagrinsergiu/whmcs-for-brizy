<?php

namespace  WHMCS\Module\Addon\Brizy\Common;

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Brizy\Common\Helpers;


class CpanelInstaller
{
    private $service;
    private $userName;
    private $databaseName;
    private $databasePass;
    private $cpanelAccessData;
    private $cpanel;
    private $tmpInstallationFile;
    private $installationDbData;
    private $wpPassword;
    private $userHomeDirectory;


    private $options = [
        'wordpress' => true,
        'brizy' => true,
        'brizyPro' => false,
    ];

    public function __construct($service)
    {

        $this->service = $service;

        $auth = 'hash';
        $password = $service->serverModel->accesshash;
        $userName = $service->serverModel->username;

        if (!$service->serverModel->accesshash && $service->serverModel->password) {

            $results = \localAPI('DecryptPassword', [
                'password2' => $service->serverModel->password
            ], '');

            if ($results['result'] === 'success') {
                $accessPassword = $results['password'];
            }

            $auth = 'password';
            $password = $accessPassword;
            $userName = $service->serverModel->username;
        }

        $this->cpanelAccessData = [
            'host'        =>  $service->serverModel->hostname, // required
            'username'    =>  $userName, // required
            'auth_type'   =>  $auth, // optional, default 'hash'
            'password'    =>  $password, // required
        ];

        $this->cpanel = new Cpanel($this->cpanelAccessData);
        $this->cpanel->setTimeout(30);

        $this->userName = $service->username;
        $this->databaseName = $this->userName . '_' . 'wp';
        $this->userHomeDirectory = $this->getUserHomeDirectory();


        $this->tmpInstallationFile = dirname(__FILE__) . '/wpi.php';

        $this->installationDbData = Capsule::table('brizy_installations')
            ->where('user_id', $this->service->clientId)
            ->where('service_id', $this->service->id)
            ->first();

        if (!$this->installationDbData) {
            $this->installationDbData = Capsule::table('brizy_installations')
                ->insert(
                    [
                        'user_id' => $this->service->clientId,
                        'service_id' => $this->service->id,
                        'db_name' => '',
                        'db_user' => '',
                        'db_pass' => '',
                        'path' => '',
                        'type' => 1, //normal brizy installation
                        'status' => 0,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
        }

        if ($this->installationDbData->db_pass != '') {
            $results = \localAPI('DecryptPassword', [
                'password2' => $this->installationDbData->db_pass
            ], '');

            if ($results['result'] === 'success') {
                $this->databasePass = $results['password'];
            }
        }

        if (!$this->databasePass){

            $this->databasePass = $this->generatePassword();
            $results = \localAPI('EncryptPassword', [
                'password2' => $this->databasePass
            ], '');

            $this->updateInstallationDbData([
                'db_pass' => $results['password']
            ]);
        }
    }

    public function checkIfDbExists()
    {
        $response = $this->cpanel->execute_action(3, 'Mysql', 'list_databases', $this->userName);

        if (isset($response['result']['data']) && count($response['result']['data'])) {
            return in_array($this->databaseName, array_map(function ($item) {
                return $item['database'];
            }, $response['result']['data']));
        }

        return false;
    }

    public function createDb()
    {

        $exists = $this->checkIfDbExists();

        if ($exists) {
            $this->updateInstallationDbData([
                'db_name' => $this->databaseName
            ]);

            return true;
        } else {
            $response = $this->cpanel->execute_action(3, 'Mysql', 'create_database', $this->userName, ['name' => $this->databaseName]);

            if (is_array($response) && array_key_exists('errors',  $response['result']) && $response['result']['errors'] === null) {
                $this->updateInstallationDbData([
                    'db_name' => $this->databaseName
                ]);
                return true;
            }
        }

        return false;
    }

    public function createDbUser()
    {

        $userResponse = $this->cpanel->execute_action(
            3,
            'Mysql',
            'create_user',
            $this->userName,
            [
                'name' => $this->databaseName,
                'password' => $this->databasePass
            ]
        );


        if (array_key_exists('errors',  $userResponse['result'])
            && (
                $userResponse['result']['errors'] === null
                || (
                    isset($userResponse['result']['errors'][0])
                    && strpos($userResponse['result']['errors'][0], $this->databaseName) !== false
                )

                || (
                    isset($userResponse['result']['errors'][0])
                    && strpos($userResponse['result']['errors'][0], 'already exists') !== false
                )
            )
        )
        {
            $grantResponse = $this->cpanel->execute_action(
                3,
                'Mysql',
                'set_privileges_on_database',
                $this->userName,
                [
                    'user' => $this->databaseName,
                    'database' => $this->databaseName,
                    'privileges' => 'ALL PRIVILEGES'
                ]
            );

            return true;
        }

        return false;
    }


    public function getInstallationScriptContent($forceReplace = [])
    {
        $content = file_get_contents($this->tmpInstallationFile);

        $license = Helpers::assignNewLicenseForService($this->service->id);

        $prefix = strtolower(preg_replace('/\s+/', '', Settings::get('company_name')));

        $this->wpPassword = $this->generatePassword();
        $placeHoldersReplace = [
            '{wpPassword}' => $this->wpPassword,
            '{wpEmail}' => $this->service->client->email,
            '{dbName}' => $this->databaseName,
            '{dbUser}' => $this->databaseName,
            '{dbPass}' => $this->databasePass,
            '{hostname}' =>  $this->service->domain,

            '{installWordpress}' => $this->options['wordpress'],
            '{installBrizy}' => $this->options['brizy'],
            '{installBrizyPro}' => $this->options['brizyPro'],

            '{bLicense}' => $license,
            '{bPluginName}' => Settings::get('company_name'),
            '{bDescription}' => Settings::get('plugin_description'),
            '{bPrefix}' => $prefix,
            '{bSupportUrl}' => Settings::get('support_url'),
            '{bAboutUrl}' => Settings::get('about_url'),
            '{bLogo}' => Settings::get('logo_url'),
            '{bDownloadToken}' => Settings::get('brizy_pro_download_token'),
            '{brizyTheme}' => Helpers::getThemeIdForOrder($this->service->order->id)
        ];


        $translations = Translations::set();
        $translationsList = $translations::convertToDot();

        if (is_array($translationsList) && count($translationsList) > 0) {
            $placeHoldersReplace = array_merge($translationsList, $placeHoldersReplace);
        }

        if ($forceReplace && is_array($forceReplace)) {
            $placeHoldersReplace = array_merge($placeHoldersReplace, $forceReplace);
        }

        foreach ($placeHoldersReplace as $placeHolder => $value) {
            $content = str_replace($placeHolder, $value, $content);
        }

        return $content;
    }

    public function putInstallationScriptOnServer($options = [])
    {
        $htaccessStatus = $this->updateHtaccessFile();

        $uploadStatus = $this->uploadInstallationFile($options);

        if ($uploadStatus) {
            $this->sendEmail();
            return true;
        }

        $updateStatus  = $this->updateInstallationFile($options);

        if ($updateStatus ) {
            $this->sendEmail();
            return true;
        }

        $content = $this->getInstallationScriptContent($options);
        $fileResponse = $this->cpanel->execute_action(
            3,
            'Fileman',
            'save_file_content',
            $this->userName,
            [
                'file' => 'public_html/wpi.php',
                'content' => $content,
            ]
        );

        if (!isset($fileResponse['result']['errors'])) {
            $this->sendEmail();
            return true;
        }

        return false;
    }

    public function sendEmail() {

        if (!$this->options['wordpress']) {
            return false;
        }

        $translations = Translations::set();
        $command = 'SendEmail';

        $msgData = [
            'subject' => Translations::$_['wpi']['installer']['mail']['subject'],
            'content' => Translations::$_['wpi']['installer']['mail']['content'],
        ];

        $msgData = str_replace(
            ['{domain}', '{companyName}', '{wpPassword}'],
            [$this->service->domain, Settings::get('company_name'), '{literal}' . $this->wpPassword . '{/literal}'],
            $msgData
        );

        $postData = [
            'id' => $this->service->clientId,
            'customtype' => 'general',
            'customsubject' => $msgData['subject'],
            'custommessage' => $msgData['content']
        ];

        $results = localAPI($command, $postData, '');

        return true;
    }

    public function updateInstallationFile($options = [])
    {
        $content = $this->getInstallationScriptContent($options);

        $payload = [
            'file' => 'wpi.php',
            'content' => $content,
            'dir' => '/public_html'
        ];

        $cpanelHost = $this->service->serverModel->hostname;
        $requestUri = "https://" . $cpanelHost.":2083/execute/Fileman/save_file_content";
        $rawResponse = $this->webApiRequest($requestUri, $payload);

        $response = json_decode($rawResponse);
        if (empty($response)) {
            return false;
        } elseif (!$response->status) {
            return false;
        }

        return true;
    }

    public function uploadInstallationFile($options = [])
    {
        $content = $this->getInstallationScriptContent($options);
        $file = tempnam(sys_get_temp_dir(), 'POST');
        file_put_contents($file, $content);

        if (function_exists('curl_file_create')) {
            $cf = curl_file_create($file, 'text/plain', 'wpi.php');
        } else {
            $cf = "@/".$file."; filename=wpi.php";
        }

        $payload = [
            'dir'    => '/public_html',
            'file-1' => $cf
        ];

        $cpanelHost = $this->service->serverModel->hostname;
        $requestUri = "https://" . $cpanelHost.":2083/execute/Fileman/upload_files";

        $rawResponse = $this->webApiRequest($requestUri, $payload);

        $response = json_decode($rawResponse);
        if (empty($response)) {
            return false;
        } elseif (!$response->status) {
            return false;
        }

        return true;
    }

    public function runInstallationFile() {

        $header = [
            "Host: " . $this->service->domain,
        ];

        $maxAttempts  = 15;
        $waitingBetweenAttempts = 3;

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $ch = curl_init('http://' . $this->service->serverModel->ipaddress . '/wpi.php');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false );
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);

            $resolve = [
                $this->service->domain . ":80:" . $this->service->serverModel->ipaddress
            ];

            curl_setopt($ch, CURLOPT_RESOLVE, $resolve);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $result = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($code == 200) {
                return true;
            }

            sleep($waitingBetweenAttempts);
        }

        return false;
    }

    public function addCronJob()
    {
        $command = '/usr/bin/php ' . $this->userHomeDirectory . '/public_html/wpi.php';

        $cronResponse = $this->cpanel->execute_action(
            2,
            'Cron',
            'add_line',
            $this->userName,
            [
                'command' => $command,
                'day' => '*',
                'hour' => '*',
                'minute' => '*/1',
                'month' => '*',
                'weekday' => '*'
            ]
        );

        if (
            !isset($cronResponse['cpanelresult']['event']['result'])
            || $cronResponse['cpanelresult']['event']['result'] !== 1) {
            return false;
        }

        return true;
    }

    public function checkIfAnotherSiteInstalled() {
        $fileResponse = $this->cpanel->execute_action(
            3,
            'Fileman',
            'get_file_information',
            $this->userName,
            [
                'path' => $this->userHomeDirectory . '/public_html/index.php'
            ]
        );


        if (isset($fileResponse['result']['data']['file'])) {
            return true;
        }

        return false;
    }

    public function checkIfWpInstalled() {
        $fileResponse = $this->cpanel->execute_action(
            3,
            'Fileman',
            'get_file_information',
            $this->userName,
            [
                'path' => $this->userHomeDirectory . '/public_html/wp-admin/admin.php'
            ]
        );


        if (isset($fileResponse['result']['data']['file'])) {
            return true;
        }

        return false;
    }

    public function checkIfBrizzyInstalled() {
        $fileResponse = $this->cpanel->execute_action(
            3,
            'Fileman',
            'get_file_information',
            $this->userName,
            [
                'path' => $this->userHomeDirectory . '/public_html/wp-content/plugins/brizy/brizy.php'
            ]
        );

        if (isset($fileResponse['result']['data']['file'])) {
            return true;
        }

        return false;
    }

    public function checkIfBrizzyProInstalled() {
        $fileResponse = $this->cpanel->execute_action(
            3,
            'Fileman',
            'get_file_information',
            $this->userName,
            [
                'path' => $this->userHomeDirectory . '/public_html/wp-content/plugins/brizy-pro/brizy-pro.php'
            ]
        );

        if (isset($fileResponse['result']['data']['file'])) {
            return true;
        }

        return false;
    }

    public function getHost() {
        return $this->service->domain;
    }

    public function generatePassword($length = 10)
    {
        $alphaSmall = 'abcdefghijklmnopqrstuvwxyz';
        $alphaCaps  = strtoupper($alphaSmall);
        $numerics   = '1234567890';
        $specialChars = '`~!@#$%^&*()-_=+][;:,/?|';

        $container = $alphaSmall.$alphaCaps.$numerics.$specialChars;
        $password = '';

        for($i = 0; $i < $length; $i++) {
            $rand = rand(0, strlen($container) - 1);
            $password .= substr($container, $rand, 1);
        }

        return $password;
    }

    public function setOptions($param, $value) {
        if (array_key_exists( $param, $this->options)){
            $this->options[$param] = $value;
            return $value;
        }

        return false;
    }

    public function autoInstall() {

        $theme = Helpers::getThemeDataForOrder($this->service->order->id);

        if ($theme) {

            $createDb = $this->createDb();
            $createDbUser =  $this->createDbUser();

            $brizyProOption = $theme->pro && Helpers::checkIfCanInstallBrizyPro($this->service->id) ? 1 : 0;

            $replace = [
                '{installWordpress}' => $this->options['wordpress'],
                '{installBrizy}' => $this->options['brizy'],
                '{installBrizyPro}' => $brizyProOption,
                '{brizyTheme}' => $theme->theme_id,
                '{bDownloadToken}' => Settings::get('brizy_pro_download_token')
            ];

            $script = $this->putInstallationScriptOnServer($replace);

            $runStatus = $this->runInstallationFile();

            if (!$runStatus) {
                $cronJob = $this->addCronJob();
            }
        }
    }



    private function updateInstallationDbData($data) {
         $updatedCount = Capsule::table('brizy_installations')
        ->where('user_id', $this->service->clientId)
        ->where('service_id', $this->service->id)
        ->update($data);

        return $updatedCount;
    }

    private function webApiRequest($url, $payload) {

        $results = \localAPI('DecryptPassword', [
            'password2' => $this->service->password
        ], '');

        if ($results['result'] === 'success') {
            $userPassword = $results['password'];
        }

        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
        curl_setopt( $ch, CURLOPT_USERPWD, $this->service->username . ':' . $userPassword );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $curlResponse = curl_exec( $ch );

        return $curlResponse;
    }

    private function getUserHomeDirectory() {
        $settingsResponse = $this->cpanel->execute_action(
            3,
            'Variables',
            'get_user_information',
            $this->userName,
        );

        if (is_array($settingsResponse)
            && isset($settingsResponse['result']['data']['home'])
            && $settingsResponse['result']['data']['home']
        ) {
            return $settingsResponse['result']['data']['home'];
        }

        $default = '/home/' . $this->userName;
        return $default;
    }

    public function updateHtaccessFile() {

        sleep(1);
        $htaccessTemplate = '

# BEGIN BIZY INSTALLER
<IfModule Litespeed>
    RewriteEngine On
    RewriteRule wpi.php - [E=noabort:1, E=noconntimeout:1]
</IfModule>
# END BIZY INSTALLER
';
        $fileResponse = $this->cpanel->execute_action(
            3,
            'Fileman',
            'get_file_content',
            $this->userName,
            [
                'dir' => $this->userHomeDirectory . '/public_html',
                'file' => '.htaccess'
            ]
        );

        if ($fileResponse && $fileResponse['result']['status']) {
            $data = $fileResponse['result']['data']['content'];

            if ($data && str_contains($data, 'wpi.php')) {
                return true;
            }

            $updatedData = $data . $htaccessTemplate;
            $payload = [
                'file' => '.htaccess',
                'content' => $updatedData,
                'dir' => '/public_html'
            ];

            $fileResponseUpdate = $this->cpanel->execute_action(
                3,
                'Fileman',
                'save_file_content',
                $this->userName,
                [
                    'dir' => $this->userHomeDirectory . '/public_html',
                    'file' => '.htaccess',
                    'content' => $updatedData,
                ]
            );


            if ($fileResponseUpdate && $fileResponseUpdate['result']['status']){
                    return true;
            }

        }
        $file = tempnam(sys_get_temp_dir(), 'POST');
        file_put_contents($file, $htaccessTemplate);

        if (function_exists('curl_file_create')) {
            $cf = curl_file_create($file, 'text/plain', '.htaccess');
        } else {
            $cf = "@/".$file."; filename=.htaccess";
        }

        $payload = [
            'dir'    => '/public_html',
            'file-1' => $cf
        ];

        $cpanelHost = $this->service->serverModel->hostname;
        $requestUri = "https://" . $cpanelHost.":2083/execute/Fileman/upload_files";

        $rawResponse = $this->webApiRequest($requestUri, $payload);

        $response = json_decode($rawResponse);
        if (empty($response)) {
            return false;
        } elseif (!$response->status) {
            return false;
        }

        return true;
    }
}
