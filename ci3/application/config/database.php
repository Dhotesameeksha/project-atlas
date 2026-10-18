<?php defined('BASEPATH') OR exit('No direct script access allowed');
// Credentials come from environment variables only (never committed).
$active_group = 'default';
$query_builder = TRUE;
$db['default'] = array(
    'dsn'      => '',
    'hostname' => getenv('MYSQL_HOST') ?: 'mysql',
    'username' => getenv('MYSQL_APP_USER') ?: 'atlas_app',
    'password' => getenv('MYSQL_APP_PASSWORD') ?: '',
    'database' => 'atlas',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => FALSE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_general_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => FALSE
);
