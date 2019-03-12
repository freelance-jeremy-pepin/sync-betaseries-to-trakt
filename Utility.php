<?php
/**
 * Created by PhpStorm.
 * User: PC-FIXE
 * Date: 12/03/2019
 * Time: 19:39
 */

class Utility {
    public static function buildConfig() {
        if (!file_exists('config.ini')) {
            touch('config.ini');

            Utility::config_set($config, 'betaseries', 'apiKeyBetaserie', '');
            Utility::config_set($config, 'betaseries', 'idMembre', '');

            Utility::config_set($config, 'trakt_tv', 'clientId', '');
            Utility::config_set($config, 'trakt_tv', 'clientSecret', '');
            Utility::config_set($config, 'trakt_tv', 'redirectUrl', '');
            Utility::config_set($config, 'trakt_tv', 'accessToken', '');
            Utility::config_set($config, 'trakt_tv', 'expires', '');
            Utility::config_set($config, 'trakt_tv', 'refreshToken', '');

            Utility::config_set($config, 'app', 'synchronizeOnlyNetflix', '1');

            Utility::config_write($config, 'config.ini');
        }
    }

    public static function isNetflix($platform_links) {
        foreach ($platform_links as $platform_link) {
            if ($platform_link->platform == 'Netflix') {
                return true;
            }
        }

        return false;
    }

    public static function getDataJson($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        return json_decode($data);
    }

    // Update a setting in loaded inifile data
    public static function config_set(&$config_data, $section, $key, $value) {
        $config_data[$section][$key] = $value;
    }

    // Serializes inifile config data back to disk.
    public static function config_write($config_data, $config_file) {
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
}