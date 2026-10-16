<?php
$documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$projectRootFS = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

$basePath = str_replace($documentRoot, '', $projectRootFS);
$basePath = str_replace('\\', '/', $basePath);

if ($basePath === '') {
    $basePath = '/';
} elseif (substr($basePath, -1) !== '/') {
    $basePath .= '/';
}

define('BASE_URL', $basePath);

if (!function_exists('generateRandomPassword')) {
    function generateRandomPassword($length = 10) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }
}
?>
