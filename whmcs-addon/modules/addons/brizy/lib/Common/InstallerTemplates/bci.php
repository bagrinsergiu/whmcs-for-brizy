<?php

ignore_user_abort(true);
set_time_limit(0);

class BcInstaller
{
    private $login = 'admin';

    private $remoteArchiveFile = '{bc_project_download_url}';
    private $localArchiveFile;
    private $lockFile = "installation.lock";
    private $logEntries = [];
    private $logFile;

    public function __construct()
    {
        $this->localArchiveFile = 'install_'.$this->generateHash().'.zip';
        $this->logFile = 'log'.$this->generateHash().'.log';
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
        if ($this->checkIfInProgress()) {
            echo 'In progress...';
            die();
        }

        return true;
    }

    private function downloadProject()
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

    private function unzipProject()
    {
        $this->deleteDir('config');
        $this->deleteDir('var/cache');
        $zip = new ZipArchive();
        $res = $zip->open($this->localArchiveFile);

        if ($res === true) {
            $zip->extractTo('./');
            $zip->close();
            return true;
        }

        return false;
    }


    private function  cleanAfterInstallation()
    {
        $this->deleteFile($this->logFile);
        $this->deleteFile('installation.lock');
        $this->deleteFile($this->localArchiveFile);
        file_put_contents(__FILE__, '<?php echo "OK"?>');

        return true;
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
        $fp = fopen($this->logFile, 'a');

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

                }

                break;
            }
        }

        if (!$errors) {
            $this->log('Installation finished');
        }

    }


    public function getInstallSteps()
    {
        $steps = [
            'checkIfICanInstall' => 'Checking if in progress...',
            'createLockFile' => 'Creating lock file...',
            'downloadProject' => 'Download project...',
            'unzipProject' => 'Unzip project...'
        ];


        $steps['cleanAfterInstallation'] = 'OK';

        return $steps;
    }
}

$wpi = new BcInstaller();

$wpi->install();
?>