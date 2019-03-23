<?php
namespace Repositories;

/**
 * Created by PhpStorm.
 * User: PC-FIXE
 * Date: 12/03/2019
 * Time: 19:39
 */

class Config {
    public static function build() {
        if (!file_exists(config_file())) {
            touch(config_file());

            Config::set($config, 'betaseries', 'apiKeyBetaserie', '');
            Config::set($config, 'betaseries', 'idMembre', '');

            Config::set($config, 'trakt_tv', 'clientId', '');
            Config::set($config, 'trakt_tv', 'clientSecret', '');
            Config::set($config, 'trakt_tv', 'redirectUrl', '');
            Config::set($config, 'trakt_tv', 'accessToken', '');
            Config::set($config, 'trakt_tv', 'expires', '');
            Config::set($config, 'trakt_tv', 'refreshToken', '');

            Config::set($config, 'app', 'synchronizeOnlyNetflix', '1');
            Config::set($config, 'app', 'scheduleSyncNow', '');
            Config::set($config, 'app', 'deleteLogAfterXDays', '');

            Config::set($config, 'mail', 'mailFrom', '');
            Config::set($config, 'mail', 'mailHost', '');
            Config::set($config, 'mail', 'mailPort', '');
            Config::set($config, 'mail', 'mailEncryption', '');
            Config::set($config, 'mail', 'mailAuthentification', '');
            Config::set($config, 'mail', 'mailUsername', '');
            Config::set($config, 'mail', 'mailPassword', '');

            Config::write($config);
        }
    }

    public static function isOK() {
        $config = Config::get();
        if (
            !empty($config['trakt_tv']['clientId']) &&
            !empty($config['trakt_tv']['clientSecret']) &&
            !empty($config['trakt_tv']['redirectUrl']) &&
            !empty($config['trakt_tv']['accessToken']) &&
            !empty($config['trakt_tv']['expires']) &&
            !empty($config['trakt_tv']['refreshToken']) &&
            !empty($config['tmdb']['tmdb_api_key']) &&
            !empty($config['betaseries']['apiKeyBetaserie']) &&
            !empty($config['betaseries']['idMembre']) ) {

            return true;
        } else {
            return false;
        }
    }

    public static function get() {
        return parse_ini_file(config_file(), true);
    }

    // Update a setting in loaded inifile data
    public static function set(&$config_data, $section, $key, $value) {
        $config_data[$section][$key] = $value;
    }

    // Serializes inifile config data back to disk.
    public static function write($config_data) {
        $new_content = '';
        foreach ($config_data as $section => $section_content) {
            $section_content = array_map(function($value, $key) {
                return "$key=\"$value\"";
            }, array_values($section_content), array_keys($section_content));
            $section_content = implode("\n", $section_content);
            $new_content .= "[$section]\n$section_content\n";
        }
        file_put_contents(config_file(), $new_content);
    }
}