<?php

namespace  WHMCS\Module\Addon\Brizy\Common;

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Brizy\Common\Helpers;


class CpanelDefault
{
    private $service;
    private $userName;
    private $cpanelAccessData;
    private $cpanel;
    private $userHomeDirectory;
    private $targetFilename = 'bci.php';
    private $templateInstallationFilePath;
    private $placeHoldersReplace = [];
    private $installerTemplateFile = '';
    private $installerTemplatesDirectory = __DIR__.'/InstallerTemplates';

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

        if (!$userName) {
            $userName = 'TMP';
        }

        if (!$password) {
            $password = 'TMP';
        }

        $this->cpanelAccessData = [
            'host'        =>  $service->serverModel->hostname, // required
            'username'    =>  $userName, // required
            'auth_type'   =>  $auth, // optional, default 'hash'
            'password'    =>  $password, // required
        ];

        $this->cpanel = new Cpanel($this->cpanelAccessData);
        $this->cpanel->setTimeout(30);
        $this->cpanel->setConnectionTimeout(5);
        $this->userName = $service->username;
        $this->userHomeDirectory = $this->getUserHomeDirectory();

    }


    public function setInstallerTemplateFile($templateFile) {
        $this->installerTemplateFile = $templateFile;
        return file_exists($this->getInstallerFilePath());
    }

    public function setTargetFileName($filename){
        $this->targetFilename = $filename;
    }

    public function putInstallationScriptOnServer()
    {

        $uploadStatus = $this->uploadInstallationFile();

        if ($uploadStatus) {

            return true;
        }

        $updateStatus  = $this->updateInstallationFile();

        if ($updateStatus ) {

            return true;
        }

        $content = $this->getInstallationScriptContent();
        $fileResponse = $this->cpanel->execute_action(
            3,
            'Fileman',
            'save_file_content',
            $this->userName,
            [
                'file' => 'public_html/'.$this->targetFilename,
                'content' => $content,
            ]
        );

        if (!isset($fileResponse['result']['errors'])) {

            return true;
        }

        return false;
    }




    public function updateInstallationFile()
    {
        $content = $this->getInstallationScriptContent();

        $payload = [
            'file' => $this->targetFilename,
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

    public function uploadInstallationFile()
    {
        $content = $this->getInstallationScriptContent();

        $file = tempnam(sys_get_temp_dir(), 'POST');
        file_put_contents($file, $content);

        if (function_exists('curl_file_create')) {
            $cf = curl_file_create($file, 'text/plain', $this->targetFilename);
        } else {
            $cf = "@/".$file."; filename=".$this->targetFilename;
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
            $ch = curl_init('http://' . $this->service->serverModel->ipaddress . '/'.$this->targetFilename);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false );
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

            $resolve = [
                $this->service->domain . ":80:" . $this->service->serverModel->ipaddress
            ];

            curl_setopt($ch, CURLOPT_RESOLVE, $resolve);

            $result = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($code == 200) {
                return true;
            }

            sleep($waitingBetweenAttempts);
        }

        return false;
    }

    public function setPlaceholderToReplace($placeHoldersReplace) {
        $this->placeHoldersReplace = $placeHoldersReplace;
    }

    public function getInstallationScriptContent()
    {

        $content = file_get_contents($this->getInstallerFilePath());

        $translations = Translations::set();
        $translationsList = $translations::convertToDot();

        if (is_array($translationsList) && count($translationsList) > 0) {
            $placeHoldersReplace = array_merge($translationsList, $this->placeHoldersReplace);
        }

        foreach ($this->placeHoldersReplace as $placeHolder => $value) {
            $content = str_replace($placeHolder, $value, $content);
        }

        return $content;
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



    public function getHost() {
        return $this->service->domain;
    }


    public function autoInstall() {


    }

    private function getInstallerFilePath() {
        return $filePath = $this->installerTemplatesDirectory.DIRECTORY_SEPARATOR.$this->installerTemplateFile;
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

}
