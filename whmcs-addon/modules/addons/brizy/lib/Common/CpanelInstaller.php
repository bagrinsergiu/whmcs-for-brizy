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

    private $options = [
        'wordpress' => true,
        'brizy' => true,
        'brizyPro' => false,
    ];


    public function __construct($service)
    {

        $this->service = $service;
        $this->cpanelAccessData = [
            'host'        =>  $service->serverModel->hostname, // required
            'username'    =>   $service->serverModel->username, // required
            'auth_type'   =>  'hash', // optional, default 'hash'
            'password'    =>  $service->serverModel->accesshash, // required
        ];

        $this->cpanel = new Cpanel($this->cpanelAccessData);
        $this->userName = $service->username;
        $this->databaseName = $this->userName . '_' . 'wp';

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

            if (array_key_exists('errors',  $response['result']) && $response['result']['errors'] === null) {
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
        $placeHoldersReplace = [
            '{wpPassword}' => $this->generatePassword(),
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
        $uploadStatus = $this->uploadInstallationFile($options);

        if ($uploadStatus) {
            return true;
        }

        $content = $this->getInstallationScriptContent($options);
        $fileResponse = $this->cpanel->execute_action(
            3,
            'Fileman',
            'save_file_content',
            $this->userName,
            [
                'file' => 'wpi.php',
                'content' => $content,
                'dir' => '/home/' . $this->userName . '/public_html'
            ]
        );

        if (!isset($fileResponse['result']['errors'])) {
            return true;
        }

        return false;
    }

    public function uploadInstallationFile($options = [])
    {
        $content = $this->getInstallationScriptContent($options);
       
        $results = \localAPI('DecryptPassword', [
            'password2' => $this->service->password
        ], '');

        if ($results['result'] === 'success') {
            $userPassword = $results['password'];
        }

        $file = tempnam(sys_get_temp_dir(), 'POST');
        file_put_contents($file, $content);
        if( function_exists( 'curl_file_create' ) ) {
            $cf = curl_file_create( $file, 'text/plain', 'wpi.php' );
        } else {
            $cf = "@/".$file."; filename=wpi.php";
        }

        $payload = array(
            'dir'    => '/home/' . $this->userName . '/public_html',
            'file-1' => $cf
        );

        $cpanelHost = $this->service->serverModel->hostname;
        $requestUri = "https://" . $cpanelHost.":2083/execute/Fileman/upload_files";
        $ch = curl_init( $requestUri );
        curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
        curl_setopt( $ch, CURLOPT_USERPWD, $this->service->username . ':' . $userPassword );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $curlResponse = curl_exec( $ch );

        $response = json_decode( $curlResponse );
        if( empty( $response ) ) {
            return false;
        } elseif ( !$response->status ) {
            return false;
        }

        return true;
    }

    public function checkIfWpInstalled() {
        $fileResponse = $this->cpanel->execute_action(
            3,
            'Fileman',
            'get_file_information',
            $this->userName,
            [
                'path' => '/home/' . $this->userName . '/public_html/wp-admin/admin.php'
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
                'path' => '/home/' . $this->userName . '/public_html/wp-content/plugins/brizy/brizy.php'
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
                'path' => '/home/' . $this->userName . '/public_html/wp-content/plugins/brizy-pro/brizy-pro.php'
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
        $specialChars = '`~!@#$%^&*()-_=+]}[{;:,/?|';

        $container = $alphaSmall.$alphaCaps.$numerics.$specialChars;
        $password = '';

        for($i = 0; $i < $length; $i++) {
            $rand = rand(0, strlen($container) - 1);
            $password .= substr($container, $rand, 1);
        }

        return $password;
    }


    /**
     * Options setter
     *
     * @param string $param
     * @param string $value
     * @return void
     */
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

            $replace = [
                '{installWordpress}' => $this->options['wordpress'],
                '{installBrizy}' => $this->options['brizy'],
                '{installBrizyPro}' => $theme->pro,
                '{brizyTheme}' => $theme->theme_id,
                '{bDownloadToken}' => Settings::get('brizy_pro_download_token')
            ];
    
            $script = $this->putInstallationScriptOnServer($replace);
    
            $cronJob = $this->addCronJob();
        }
      
    }

    private function updateInstallationDbData($data) {
         $updatedCount = Capsule::table('brizy_installations')
        ->where('user_id', $this->service->clientId)
        ->where('service_id', $this->service->id)
        ->update($data);

        return $updatedCount;
    }


}
