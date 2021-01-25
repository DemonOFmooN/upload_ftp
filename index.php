<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'upload_ftp.php';

$host = '';
$user = '';
$password = '';

$ftp = new UploadFtp($host, $user, $password);
$ftp->setRootDir('upload');

try {
    $ftp->upload();
} catch (ErrorException $e) {
    print $e->getMessage();
}