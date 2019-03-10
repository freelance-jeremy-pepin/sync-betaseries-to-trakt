<?php
/**
 * Created by PhpStorm.
 * User: PC-FIXE
 * Date: 09/03/2019
 * Time: 10:29
 */

require __DIR__ . '/vendor/autoload.php';

use Wubs\Trakt\Auth;
use Wubs\Trakt\Trakt;

$config = parse_ini_file('config.ini', true);

try {
    $provider = new Auth\TraktProvider($config['trakt_tv']['clientId'], $config['trakt_tv']['clientSecret'], $config['trakt_tv']['redirectUrl']);
    $auth = new Auth\Auth($provider);
    $trakt = new Trakt($auth);

    $token = $trakt->auth->token($_GET['code']);
} catch (\Exception $e) {
    echo $e->getMessage();
}



config_set($config, 'trakt_tv', 'accessToken', $token->accessToken);
config_set($config, 'trakt_tv', 'expires', $token->expires);
config_set($config, 'trakt_tv', 'refreshToken', $token->refreshToken);
config_write($config, 'config.ini');

header('Location: config.php');

// Update a setting in loaded inifile data
function config_set(&$config_data, $section, $key, $value) {
    $config_data[$section][$key] = $value;
}

// Serializes inifile config data back to disk.
function config_write($config_data, $config_file) {
    $new_content = '';
    foreach ($config_data as $section => $section_content) {
        $section_content = array_map(function($value, $key) {
            return "$key=$value";
        }, array_values($section_content), array_keys($section_content));
        $section_content = implode("\n", $section_content);
        $new_content .= "[$section]\n$section_content\n";
    }
    file_put_contents($config_file, $new_content);
}