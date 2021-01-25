<?php

/**
 * Class UploadFtp
 */
class UploadFtp
{
    private $ftp_resource;
    private $host;
    private $user;
    private $password;
    private $port = 21;
    private $timeout = 300;
    private $root_dir = '';
    private $structure = [];

    public function __construct($host, $user, $password, $port = 21, $timeout = 300)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    public function getRootDir()
    {
        return $this->root_dir;
    }

    public function setRootDir($root_dir)
    {
        $this->root_dir = __DIR__ . DIRECTORY_SEPARATOR . $root_dir;
    }

    /**
     * @throws ErrorException
     */
    public function upload()
    {
        try {
            $this->login();
        } catch (ErrorException $e) {
            throw $e;
        }

        if (!$this->getCache()) {
            $this->structure = $this->factoryStructure($this->getRootDir());
            $this->setCache();
        }

        $this->createDir();
        $this->uploadFile();
    }

    /**
     * @throws ErrorException
     */
    private function login()
    {
        if (!($this->ftp_resource = ftp_connect($this->host, $this->port, $this->timeout))) {
            throw new \ErrorException("Could not connect to " . $this->host);
        }

        if (!ftp_login($this->ftp_resource, $this->user, $this->password)) {
            throw new \ErrorException($this->user . " not logged in!");
        }

        ftp_pasv($this->ftp_resource, true);
    }

    private function factoryStructure($dir)
    {
        $cdir = scandir($dir);
        $structure = [];
        foreach ($cdir as $key => $value) {
            if (!in_array($value, [".", ".."])) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $structure[$value] = $this->factoryStructure($dir . DIRECTORY_SEPARATOR . $value);
                } else {
                    $structure[] = $value;
                }
            }
        }

        return $structure;
    }

    private function createDir($structure = null, $parent_dir = '')
    {
        $items = is_null($structure) ? $this->structure : $structure;

        foreach ($items as $type => $item) {
            if (is_string($type)) {
                $folder = $parent_dir . '/' . $type;
                @ftp_mkdir($this->ftp_resource, $folder);
                $this->createDir($item, $folder);

                printf("Folder %s is created\n", $folder);
            }
        }
    }

    private function uploadFile($structure = null, $parent_dir = '')
    {
        $dir = $this->getRootDir();
        $items = is_null($structure) ? $this->structure : $structure;

        foreach ($items as $type => $item) {
            if (is_string($type)) {
                $folder = $parent_dir . '/' . $type;
                $this->uploadFile($item, $folder);
            }

            if (is_numeric($type)) {
                $dir_local = $dir . $parent_dir . DIRECTORY_SEPARATOR . $item;
                $dir_remote = $parent_dir . '/' . $item;
                ftp_put($this->ftp_resource, $dir_remote, $dir_local, FTP_ASCII);
                printf("File %s is uploaded\n", $dir_remote);
            }
        }
    }

    private function getCache()
    {
        if ($cache = @file_get_contents('structure.cache')) {
            $this->structure = unserialize($cache);
            return $this->structure;
        }

        return false;
    }

    private function setCache()
    {
        file_put_contents('structure.cache', serialize($this->structure));
    }

    public function __destruct()
    {
        ftp_close($this->ftp_resource);
        printf("Done\n");
    }
}