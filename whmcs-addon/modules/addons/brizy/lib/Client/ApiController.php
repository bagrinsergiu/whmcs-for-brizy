<?php

namespace WHMCS\Module\Addon\Brizy\Client;

use WHMCS\Module\Addon\brizy\Api\DefaultApiController;
use WHMCS\Module\Addon\Brizy\Common\CpanelInstaller;
use WHMCS\Module\Addon\Brizy\Common\Helpers;
use WHMCS\Module\Addon\Brizy\Common\Settings;
use WHMCS\Module\Addon\Brizy\Common\Translations;
use \GuzzleHttp\Client;


/**
 * Client area API controller
 */
class ApiController extends DefaultApiController
{
    /**
     * CpanelInstaller - helper
     *
     * @var WHMCS\Module\Addon\Brizy\Common\CpanelInstaller
     */
    private $cpanelInstaller;

    /**
     * Service ID
     *
     * @var integer
     */
    private $serviceId;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->serviceId = (int)$_GET['serviceId'];

        $currentUser =  new \WHMCS\Authentication\CurrentUser;

        if ($this->serviceId) {
            $service = \WHMCS\Service\Service::where('id', $this->serviceId)
            ->first();
           
            if (!$service || !Helpers::checkIfCanInstallBrizy($this->serviceId)) {
                $this->respondWithError(Translations::$_['client']['api']['repsonse']['accessRestricted']);
            }

            if (!$currentUser->user()) {
                $this->respondWithError(Translations::$_['client']['api']['repsonse']['accessRestricted']);
            }

            $this->cpanelInstaller = new CpanelInstaller($service);
        } else {
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['accessRestricted']);
        }     
    }

    /**
     * Creates a database
     *
     * @return void
     */
    public function createDb()
    {
        $status = $this->cpanelInstaller->createDb();
        if ($status) {
            $this->respond();
        }

        $this->respondWithError(Translations::$_['client']['api']['repsonse']['accessRestricted'], 403);
    }

    /**
     * Creates a user and grants database permissions
     *
     * @return void
     */
    public function createDbUser()
    {
        $status = $this->cpanelInstaller->createDbUser();
        if ($status) {
            $this->respond();
        }

        $this->respondWithError(Translations::$_['client']['api']['repsonse']['taskFailed'], 403);
    }

    /**
     * Uploads the installation file to the server
     *
     * @return void
     */
    public function uploadInstallationScript()
    {
        $this->cpanelInstaller->setOptions('wordpress', $this->input['wordpress']  ? 1 : 0);
        $this->cpanelInstaller->setOptions('brizy', $this->input['brizy']  ? 1 : 0);
        $brizyProOption = $this->input['brizyPro'] &&  Helpers::checkIfCanInstallBrizyPro($this->serviceId) ? 1 : 0;
        $this->cpanelInstaller->setOptions('brizyPro', $brizyProOption);

        $status = $this->cpanelInstaller->putInstallationScriptOnServer([]);

        if ($status) {
            $this->respond();
        }

        $this->respondWithError(Translations::$_['client']['api']['repsonse']['taskFailed'], 403);
    }

    /**
     * Add a task to cron that will run the installation script
     *
     * @return void
     */
    public function addCronJob()
    {
        $runStatus = $this->cpanelInstaller->runInstallationFile();

        if ($runStatus) {
            $this->respond();
        }

        $status = $this->cpanelInstaller->addCronJob();

        if ($status) {
            $this->respond();
        }

        $this->respondWithError(Translations::$_['client']['api']['repsonse']['taskFailed'], 403);
    }

    /**
     * Tests the FTP connection
     *
     * @return void
     */
    public function testFtpConnection()
    {
        $ftp = $this->input['ftp'];

        try {
            $con = \ftp_connect($ftp['host'], $ftp['port']);

            if (false === $con) {
                $this->respondWithError(Translations::$_['client']['api']['repsonse']['invalidHost'], 403);
            }

            $loggedIn = \ftp_login($con,  $ftp['username'],  $ftp['password']);

            if ($loggedIn !== true) {
                $this->respondWithError(Translations::$_['client']['api']['repsonse']['wrongCredentials'], 403);
            }

            \ftp_close($con);

            $this->respond();
        } catch (Exception $e) {
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['taskFailed'], 403);
        }
    }

    /**
     * Uploads the installation file to the server via FTP
     *
     * @return void
     */
    public function uploadInstallationScriptFtp()
    {
        $ftp = $this->input['ftp'];

        try {
            $con = \ftp_connect($ftp['host'], $ftp['port']);
            if (false === $con) {
                $this->respondWithError(Translations::$_['client']['api']['repsonse']['taskFailed'], 403);
            }

            $loggedIn = \ftp_login($con,  $ftp['username'],  $ftp['password']);
            if ($loggedIn !== true) {
                $this->respondWithError(Translations::$_['client']['api']['repsonse']['taskFailed'], 403);
            }

            ftp_pasv($con, true);

            $replace = [
                '{dbName}' => $ftp['dbName'],
                '{dbUser}' => $ftp['dbUser'],
                '{dbPass}' => $ftp['dbPassword'],
                '{installWordpress}' => $this->input['wordpress']  ? 1 : 0,
                '{installBrizy}' => $this->input['brizy'] ? 1 : 0,
                '{installBrizyPro}' => $this->input['brizyPro']  ? 1 : 0,
            ];

            $path = $ftp['path'];
            if (trim($path) != '' && !preg_match('%/$%si', $path)) {
                $path .= '/';
            }
            $file = $path.'wpi.php';
            $lockFile = $path.'installation.lock';
            $del = \ftp_delete($con, $lockFile);
            $del = \ftp_delete($con, $file);

            $stream = \fopen('php://memory', 'r+');
            \fwrite($stream, $this->cpanelInstaller->getInstallationScriptContent($replace));
            \rewind($stream);

            for ($i=0; $i < 10; $i++) {
                $this->deleteInstallationScriptFtp($ftp);
                \ftp_fput($con, $file, $stream);
                $h = fopen('php://temp', 'r+');
                ftp_fget($con, $h, $file);
                $fstats = fstat($h);
                fseek($h, 0);
                $contents = fread($h, $fstats['size']);
                sleep(1);
                if ($contents) {
                    $this->respond();
                }
            }

            \fclose($stream);
            \ftp_close($con);

            $this->respondWithError(Translations::$_['client']['api']['repsonse']['uploadError'], 403);
        } catch (Exception $e) {
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['taskFailed'], 403);
        }
    }

    /**
     * Delete remote file via FTP
     *
     * @param FTP\Connection $ftp Ftp connection
     * @return void
     */
    private function deleteInstallationScriptFtp($ftp)
    {
        try {
            $con = \ftp_connect($ftp['host'], $ftp['port']);
            if (false === $con) {
                return false;
            }

            $loggedIn = \ftp_login($con,  $ftp['username'],  $ftp['password']);
            if ($loggedIn !== true) {
                return false;
            }

            $file = 'wpi.php';

            $del = \ftp_delete($con, $file);

            return $del;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Runs the installation file on a remote server
     *
     * @return void
     */
    public function runInstallationScriptFtp(){
        try {
            $client = new Client();
            $url = 'http://'. $this->cpanelInstaller->getHost() . '/wpi.php';
            $response = $client->get($url);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['ftpScriptRunError']['part1'] . $this->cpanelInstaller->getHost() . Translations::$_['client']['api']['repsonse']['ftpScriptRunError']['part2'], 403);
        }

        if ($response->getStatusCode() !== 200) {
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['taskFailed'], 403);
        }
        $body = $response->getBody()->getContents();
        $logUrl = 'http://'. $this->cpanelInstaller->getHost() . '/wpi.log';

        if (preg_match('%Incorrect database access data%si', $body)){
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['ftpDataBaseError'], 403);
        }

        if (preg_match('%Error%si', $body)){
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['ftpScriptError'] . $logUrl . ' ', 403);
        }

        $this->respond();
    }

    /**
     * Returns initial data for Brizy Installer
     *
     * @return void
     */
    public function initData()
    {
        $pluginName = Settings::get('company_name');
        $pluginLogo = Settings::get('logo_url');
        $data = [
            'installed' => [
                'wordpress' =>  $this->cpanelInstaller->checkIfWpInstalled(),
                'brizy' => $this->cpanelInstaller->checkIfBrizzyInstalled(),
                'brizyPro' => $this->cpanelInstaller->checkIfBrizzyProInstalled(),
            ],
            'host' => $this->cpanelInstaller->getHost(),
            'wl' => [
                'bPluginName' => $pluginName,
                'bLogo' => $pluginLogo,
            ]
        ];

        $this->respond($data);
    }
}
