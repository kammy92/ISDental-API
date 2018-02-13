<?php
/**
 * Database configuration
 */
//echo $_SERVER['HTTP_HOST'];
if (strcmp($_SERVER['HTTP_HOST'], 'project-isdental-cammy92.c9users.io')  == 0 || strcmp($_SERVER['HTTP_HOST'], 'www.project-isdental-cammy92.c9users.io')  == 0){
    define('DB_USERNAME', 'cammy92');
    define('DB_PASSWORD', '');
    define('DB_HOST', '0.0.0.0');
    define('DB_NAME', 'isdental');
} else {
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', 'root');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'isdental');
}
?>