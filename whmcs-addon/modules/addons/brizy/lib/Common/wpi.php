<?php
class WpInstaller
{
    private $login = 'admin';
    private $password = '{wpPassword}';
    private $email = '{wpEmail}';

    private $remoteArchiveFile = 'https://wordpress.org/latest.zip';
    private $localArchiveFile = "wp-install.zip";

    private $remoteBrizyArchiveFile = 'https://downloads.wordpress.org/plugin/brizy.latest-stable.zip';
    private $localBrizyArchiveFile = 'brizy.zip';

    private $remoteBrizyArchiveFilePro = 'https://www.brizy.cloud/api/licences_download';
    private $localBrizyProArchiveFile = 'brizy-pro.zip';

    private $lockFile = "installation.lock";


    private $dbName = '{dbName}';
    private $dbUser = '{dbUser}';
    private $dbPass = '{dbPass}';
    private $hostname = '{hostname}';



    private $wordpress = '{installWordpress}';
    private $brizy = '{installBrizy}';
    private $brizyPro = '{installBrizyPro}';

    private $brizyTheme = '{brizyTheme}';

    private $logEntries = [];

    public function __construct()
    {
        $this->wordpress = $this->wordpress === '1' ? true : false;
        $this->brizy = $this->brizy === '1' ? true : false;
        $this->brizyPro = $this->brizyPro === '1' ? true : false;

        $this->remoteBrizyArchiveFilePro .= '?token=' . '{bDownloadToken}';

        $this->brizyTheme = intval($this->brizyTheme);
    }

    private function generateHash()
    {
        return md5(uniqid(rand(), true));
    }

    private function createLockFile()
    {
        $fp = fopen($this->lockFile, "a+");
        fclose($fp);

        if (file_exists($this->lockFile)) {
            return true;
        }

        return false;
    }

    private function deleteLockFile()
    {
        $this->deleteFile("installation.lock");
    }

    private function checkIfInProgress()
    {
        return file_exists("installation.lock");
    }

    private function checkIfICanInstall()
    {
        return !$this->checkIfInProgress();
    }

    private function downloadLatestArchive()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->remoteArchiveFile);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        curl_close($ch);

        $fp = fopen($this->localArchiveFile, 'w');
        fwrite($fp, $result);
        fclose($fp);

        if (file_exists($this->localArchiveFile)) {
            return true;
        }

        return false;
    }

    private function unzipArchive()
    {
        $zip = new ZipArchive();
        $res = $zip->open("wp-install.zip");

        if ($res === true) {
            $zip->extractTo('./');
            $zip->close();
            $this->moveFolder('wordpress', __DIR__);
            copy('wp-config-sample.php', 'wp-config.php');

            return true;
        }
 
        return false;
    }

    private function setConfigFile()
    {
        if ($this->checkDatabaseConnection()){
            $this->log(' - Incorrect database access data');
            return false;
        }

        $configFile = "wp-config.php";

        if (!file_exists($configFile)) {
            return false;
        }

        $config = file_get_contents($configFile);
        $config = str_replace("database_name_here", $this->dbName, $config);
        $config = str_replace("username_here", $this->dbUser, $config);
        $config = str_replace("password_here", $this->dbPass, $config);
        file_put_contents($configFile, $config);
        return true;
    }

    private function checkDatabaseConnection() {

        // Create connection
        $conn = new mysqli('localhost', $this->dbUser, $this->dbPass, $this->dbName);
        // Check connection
        if ($conn->connect_error) {
            return true;
        }

        return false;
    }

    private function installWp()
    {

        $_SERVER['HTTP_HOST'] = $this->hostname;
        $_SERVER['SCRIPT_FILENAME'] = $this->hostname;

        define("WP_INSTALLING", true);
        define("ABSPATH", './');

        require_once("wp-load.php");

        require_once(ABSPATH . "/wp-admin/includes/upgrade.php");
        require_once(ABSPATH . "/wp-admin/includes/translation-install.php");
        require_once(ABSPATH . WPINC . "/wp-db.php");
        require_once(ABSPATH . "/wp-admin/includes/translation-install.php");
        require_once(ABSPATH . "wp-admin/includes/upgrade.php");

        if (!is_blog_installed()) {

            $res = wp_install("Wordpress", $this->login, $this->email, true, "", $this->password, "en");
            if (!isset($res['user_id'])) {
                return false;
            }
        } else {
            $user = get_user_by('login', $this->login);
            if ($user) {
                wp_set_password($this->password, $user->ID);;
            }
        }


        return true;
    }

    private function activateBrizy()
    {

        require_once("wp-load.php");
        require_once(ABSPATH . '/wp-admin/includes/admin.php');
        require_once(ABSPATH . '/wp-admin/includes/plugin.php');

        $pluginPath = 'brizy/brizy.php';
        activate_plugin($pluginPath);

        if (!$this->brizyPro) {
            $this->downloadTheme();
        }

        return is_plugin_active($pluginPath);
    }


    private function downloadTheme() {

        if (!$this->brizyTheme) {
            return;
        }

        require_once(ABSPATH . '/wp-content/plugins/brizy/brizy.php');

        if (class_exists('Brizy_Import_Import')) {
            $import = new Brizy_Import_Import($this->brizyTheme);

            try {
                $import->import();
            } catch ( Exception $e ) {

            }
        }
    }

    private function activateBrizyPro()
    {
        $this->activateBrizy();

        require_once("wp-load.php");
        require_once(ABSPATH . 'wp-content/plugins/brizy/brizy.php');
        $pluginPath = 'brizy-pro/brizy-pro.php';
        activate_plugin($pluginPath);

        $input = [
            'license' => '{bLicense}',
            'brizy' => '{bPluginName}',
            'description' => '{bDescription}',
            'brizy-prefix' => '{bPrefix}',
            'support-url' => '{bSupportUrl}',
            'about-url' => '{bAboutUrl}',
            'brizy-logo' => '{bLogo}' //(20px x 20px) .svg
        ];


        require_once(ABSPATH . '/wp-content/plugins/brizy-pro/brizy-pro.php');
        if (!class_exists('BrizyPro_Admin_WhiteLabel') || !BrizyPro_Admin_WhiteLabel::_init()->installWhiteLabel($input)) {
            return false;
        }

        if ($this->brizy) {
            $this->downloadTheme();
        }

        return is_plugin_active($pluginPath);
    }


    private function downloadBrizy()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->remoteBrizyArchiveFile);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        curl_close($ch);

        $fp = fopen($this->localBrizyArchiveFile, 'w');
        fwrite($fp, $result);
        fclose($fp);

        if (file_exists($this->localBrizyArchiveFile)) {
            return true;
        }

        return false;
    }

    public function unzipBrizy()
    {
        $zip = new ZipArchive();
        $res = $zip->open($this->localBrizyArchiveFile);
        if ($res === true) {
            $zip->extractTo('.' . DIRECTORY_SEPARATOR . 'wp-content'  . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR);
            $zip->close();

            $this->deleteFile($this->localBrizyArchiveFile);
            return true;
        }

        return false;
    }

    private function downloadBrizyPro()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->remoteBrizyArchiveFilePro);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        curl_close($ch);

        $fp = fopen($this->localBrizyProArchiveFile, 'w');
        fwrite($fp, $result);
        fclose($fp);

        if (file_exists($this->localBrizyProArchiveFile)) {
            return true;
        }

        return false;
    }

    private function  cleanAfterInstallation()
    {
        $this->deleteFile('wpi.log');
        $this->deleteFile('installation.lock');
        $this->deleteFile($this->localArchiveFile);
        $this->deleteFile($this->localBrizyArchiveFile);
        $this->deleteFile($this->localBrizyProArchiveFile);
        $this->deleteDir('tmp');
        $this->deleteDir('wordpress');
        file_put_contents('wpi.php', '<?php echo "OK"?>');

        return true;
    }

    private function getInstallationLog4mail() {
        return implode("\r\n", $this->logEntries);
    }

    private function getInstalledPackages4mail() {
        $packages = "{LANG.wpi.installer.packages.header}
";

        if ($this->wordpress) {
            $packages .=
"- Wordpress:
    {LANG.wpi.installer.packages.user} " . $this->login . "
    {LANG.wpi.installer.packages.pass} " . $this->password . "
";
        }

        if ($this->brizy) {
            $packages .=
"- {bPluginName}
";
        }

        if ($this->brizyPro) {
            $packages .=
"- {bPluginName} Pro
";
        }

        return $packages;
    }

    private function sendErrorMail() {
        $message = "
{LANG.wpi.installer.packages.installationFailed},
{LANG.wpi.installer.packages.url} http://" . $this->hostname . "

" . $this->getInstalledPackages4mail() . "

{LANG.wpi.installer.packages.url}
" . $this->getInstallationLog4mail(). "

{LANG.wpi.installer.packages.thanks}
{bPluginName} {LANG.wpi.installer.packages.team}
        ";
                mail($this->email, "{bPluginName} {LANG.wpi.installer.packages.mailTitleFailed}", $message);
    }
    private function sendEmail() {


        $message = "
{LANG.wpi.installer.packages.installationSuccess},
{LANG.wpi.installer.packages.url} http://" . $this->hostname . "

" . $this->getInstalledPackages4mail() . "

{LANG.wpi.installer.packages.thanks}
{bPluginName} {LANG.wpi.installer.packages.team}
";
        mail($this->email, "{bPluginName} {LANG.wpi.installer.packages.mailTitleSuccess}", $message);
    }
    public function unzipBrizyPro()
    {
        $zip = new ZipArchive();
        $res = $zip->open($this->localBrizyProArchiveFile);
        if ($res === true) {
            $zip->extractTo('.' . DIRECTORY_SEPARATOR . 'wp-content'  . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR);
            $zip->close();
            unlink($this->localBrizyProArchiveFile);
            return true;
        }

        return false;
    }

    public function deleteFile($file) {
        if (!file_exists($file)){
            return;
        }

        unlink($file);
    }

    public static function deleteDir($dirPath)
    {
        if (!file_exists($dirPath)){
            return;
        }

        $dir = $dirPath;
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator(
            $it,
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
    public function moveFolder($from, $to) {

        if (is_dir($from)) {
            if ($dh = opendir($from)) {
                while (($file = readdir($dh)) !== false) {

                    if ($file==".") continue;
                    if ($file=="..")continue;            

                    rename($from . DIRECTORY_SEPARATOR . $file, $to.DIRECTORY_SEPARATOR.$file);
                    
                }
                closedir($dh);
            }
        }
    }

    public function log($data)
    {

        $data = date('Y-m-d H:i:s') . ': ' . $data;
        $this->logEntries[] = $data;
        echo '<br/>' . $data;
        ob_flush();
        flush();
        $fp = fopen('wpi.log', 'a');

        fwrite($fp, $data . "\r\n");
        fclose($fp);
    }

    public function install()
    {
        $errors = false;
        foreach ($this->getInstallSteps() as $method => $description) {
            $this->log($description);

            try {
                $status = $this->$method();
            } catch (Exception $e) {
                $this->log($e->getMessage());
                $status = false;
            }

            if ($status) {
                $this->log('OK');
            } else {
                $this->log('Error');
                $errors = true;

                $this->cleanAfterInstallation();
                if (count($this->logEntries) > 2) {
                    $this->sendErrorMail();
                }

                break;
            }
        }

        if (!$errors) {
            $this->log('Installation finished');
            $this->sendEmail();
        }

    }

    private function setWpDebug()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        define('WP_DEBUG', true);
        define('WP_DEBUG_LOG', true);
        define('WP_DEBUG_DISPLAY', true);
        define('SCRIPT_DEBUG', true);
    }

    private function defineVars()
    {
        define("ABSPATH", './');
        define('WP_ADMIN', true);
        define('WP_NETWORK_ADMIN', true);
        define('WP_USER_ADMIN', true);
    }

    public function getInstallSteps()
    {
        $steps = [
            'checkIfICanInstall' => '{LANG.wpi.installer.log.checkIfICanInstall}',
            'createLockFile' => '{LANG.wpi.installer.log.createLockFile}',
        ];

        if ($this->wordpress) {
            $steps['downloadLatestArchive'] = '{LANG.wpi.installer.log.downloadLatestArchive}';
            $steps['unzipArchive'] = '{LANG.wpi.installer.log.unzipArchive}';
            $steps['setConfigFile'] = '{LANG.wpi.installer.log.setConfigFile}';
            $steps['installWp'] = '{LANG.wpi.installer.log.installWp}';
        }

        if ($this->brizy) {
            $steps['downloadBrizy'] = '{LANG.wpi.installer.log.downloadBrizy}';
            $steps['unzipBrizy'] = '{LANG.wpi.installer.log.unzipBrizy}';
        }

        if ($this->brizyPro) {
            $steps['downloadBrizyPro'] = '{LANG.wpi.installer.log.downloadBrizyPro}';
            $steps['unzipBrizyPro'] = '{LANG.wpi.installer.log.unzipBrizyPro}';
        }

        if ($this->brizy) {
            $steps['activateBrizy'] = '{LANG.wpi.installer.log.activateBrizy}';
        }

        if ($this->brizyPro) {
            $steps['activateBrizyPro'] = '{LANG.wpi.installer.log.activateBrizyPro}';
        }

        $steps['cleanAfterInstallation'] = '{LANG.wpi.installer.log.cleanAfterInstallation}';

        return $steps;
    }
}

$wpi = new WpInstaller();

$wpi->install();
?>