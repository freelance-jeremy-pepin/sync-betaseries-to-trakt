<?php
/**
 * Created by PhpStorm.
 * User: PC-FIXE
 * Date: 12/03/2019
 * Time: 19:39
 */

use Wubs\Trakt\Auth;
use Wubs\Trakt\Trakt;

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

    public static function isConfigOk() {
        $config = Utility::getConfig();
        if (
            !empty($config['trakt_tv']['clientId']) &&
            !empty($config['trakt_tv']['clientSecret']) &&
            !empty($config['trakt_tv']['redirectUrl']) &&
            !empty($config['trakt_tv']['accessToken']) &&
            !empty($config['trakt_tv']['expires']) &&
            !empty($config['trakt_tv']['refreshToken']) &&
            !empty($config['betaseries']['apiKeyBetaserie']) &&
            !empty($config['betaseries']['idMembre']) ) {

            return true;
        } else {
            return false;
        }
    }

    public static function getConfig() {
        return parse_ini_file(__DIR__.'/config.ini', true);
    }

    public static function writeLog($log) {
        try {
            $dir = __DIR__.'/log';
            if (!is_dir($dir)) {
                mkdir($dir, 0755);
                chown($dir, 'www-data');
            }

            $filename = $dir.'/'.date('Y-m-d').'.log';
            if (!file_exists($filename)) {
                touch($filename);
                chown($filename, 'www-data');
            }

            if (is_array($log)) {
                $log = implode("\t", $log);
            }

            $log = '['.date('Y-m-d H:i:s').']'."\t".$log;

            file_put_contents($filename, $log.PHP_EOL , FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {

        }
    }

    public static function synchronize() {
        Utility::writeLog('___Start sync___');

        if (!Utility::isConfigOk()) {
            throw new Exception("Missing configuration elements!");
        }

        set_time_limit(3600); // 1 hour

        $config = Utility::getConfig();


        // TRAKT TV
        $provider = new Auth\TraktProvider($config['trakt_tv']['clientId'], $config['trakt_tv']['clientSecret'], $config['trakt_tv']['redirectUrl']);
        $auth = new Auth\Auth($provider);
        $trakt = new Trakt($auth);

        $token = $trakt->auth->createToken($config['trakt_tv']['accessToken'], "", $config['trakt_tv']['expires'], $config['trakt_tv']['refreshToken'], "");

        // BETASERIES
        $api_key_betaserie = $config['betaseries']['apiKeyBetaserie'];
        $id_membre = $config['betaseries']['idMembre'];
        $last_id = $config['betaseries']['lastId'];

        $url_episode = "https://api.betaseries.com/episodes/display?key=$api_key_betaserie&id=%id%";
        $url_movie = "https://api.betaseries.com/movies/movie?key=$api_key_betaserie&id=%id%";

        $report = [];

        try {
            // Init last_id
            if (empty($last_id)) {
                $lastEvent = Utility::getDataJson("https://api.betaseries.com/timeline/member?key=$api_key_betaserie&id=$id_membre&nbpp=1&types=markas,film_add");

                if ( !empty($lastEvent->events) ) {
                    $last_id = $lastEvent->events[0]->id;
                    Utility::config_set($config, 'betaseries', 'lastId', $last_id);
                    Utility::config_write($config, __DIR__.'/config.ini');
                }

                if (isset($lastEvent->errors) && !empty($lastEvent->errors)) {
                    throw new Exception($lastEvent->errors[0]->text);
                }
            }

            $url_historique = "https://api.betaseries.com/timeline/member?key=$api_key_betaserie&id=$id_membre&last_id=$last_id&nbpp=100&types=markas,film_add";
            $historiques = Utility::getDataJson($url_historique);

            if (isset($historiques->errors) && !empty($historiques->errors)) {
                throw new Exception($lastEvent->errors[0]->text);
            }

            $historiques->events = array_reverse($historiques->events);
            foreach ($historiques->events as $historique) {
                if ( substr($historique->html, 0, strlen('a vu')) !== 'a vu' && substr($historique->html, 0, strlen('vient de regarder')) !== 'vient de regarder' ) {
                    // Update last id
                    $last_id = $historique->id;
                    Utility::config_set($config, 'betaseries', 'lastId', $last_id);
                    Utility::config_write($config, __DIR__.'/config.ini');
                    continue;
                }


                $default_time_zone = date_default_timezone_get();
                $lineReport = [];

                try {
                    switch ($historique->type) {
                        case 'markas':
                            $episodeBS = Utility::getDataJson(str_replace('%id%', $historique->ref_id, $url_episode));
                            if (Utility::isNetflix($episodeBS->episode->platform_links) || $config['app']['synchronizeOnlyNetflix'] == '0') {
                                $lineReport = [];

                                date_default_timezone_set('Europe/Paris');
                                $time = strtotime($historique->date);
                                date_default_timezone_set('UTC');
                                $episodeTrakt = null;
                                $results = $trakt->search->byId('tvdb', $episodeBS->episode->thetvdb_id);

                                foreach ($results as $result) {
                                    if ($result->type == 'episode') {
                                        $episodeTrakt = $result->episode;
                                        break;
                                    }
                                }

                                if ($episodeTrakt == null) {
                                    throw new Exception('Episode not found in Trakt with THE TVDB ID: '. $episodeBS->episode->thetvdb_id);
                                }

                                // Check if episode not already added to history
                                $username = $trakt->users->settings($token)->toArray()[0]->user->username;
                                $watched = $trakt->users->history($username, 'episodes', $episodeTrakt->ids->trakt);
                                $lineReport['already_added'] = false;

                                foreach ($watched as $watch) {
                                    if ( strtotime('-10 minutes', $time) <= strtotime($watch->watched_at) && strtotime('+10 minutes', $time) >= strtotime($watch->watched_at) ) {
                                        $lineReport['already_added'] = true;
                                        break;
                                    }
                                }

                                $episodeTrakt->watched_at = date('Y-m-d H:i:s', $time);
                                $episodesTrakt = ['episodes' => [$episodeTrakt]];

                                $resultSync = null;
                                if (!$lineReport['already_added']) {
                                    $resultSync = $trakt->sync->history->add($token, $episodesTrakt);
                                }

                                date_default_timezone_set($default_time_zone);
                                $lineReport['title'] = $episodeBS->episode->show->title.' • '.$episodeTrakt->season.'x'.$episodeTrakt->number.' - '.$episodeTrakt->title;
                                $lineReport['watched_at'] = date('m/d/Y H:i:s', $time);
                                $lineReport['type'] = 'Episode';

                                if (isset($resultSync) && !empty($resultSync) && is_array($resultSync->toArray()) && $resultSync[0]->added->episodes == 1) {
                                    $lineReport['added'] = true;
                                } else {
                                    $lineReport['added'] = false;
                                }



                            }
                            break;

                        case 'film_add':
                            $movieBS = Utility::getDataJson(str_replace('%id%', $historique->ref_id, $url_movie));
                            if (Utility::isNetflix($movieBS->movie->platform_links) || $config['app']['synchronizeOnlyNetflix'] == '0') {
                                $lineReport = [];

                                date_default_timezone_set('Europe/Paris');
                                $time = strtotime($historique->date);
                                date_default_timezone_set('UTC');
                                $movieTrakt = null;
                                $results = $trakt->search->byId('tmdb', $movieBS->movie->tmdb_id);
                                foreach ($results as $result) {
                                    if ($result->type == 'movie') {
                                        $movieTrakt = $result->movie;
                                        break;
                                    }
                                }

                                if ($movieTrakt == null) {
                                    throw new Exception('Movie not found in Trakt with TMDB ID: '. $movieBS->movie->tmdb_id);
                                }

                                // Check if movie not already added to history
                                $username = $trakt->users->settings($token)->toArray()[0]->user->username;
                                $watched = $trakt->users->history($username, 'movies', $movieTrakt->ids->trakt);
                                $lineReport['already_added'] = false;
                                foreach ($watched as $watch) {
                                    if ( strtotime('-10 minutes', $time) <= strtotime($watch->watched_at) && strtotime('+10 minutes', $time) >= strtotime($watch->watched_at) ) {
                                        $lineReport['already_added'] = true;
                                        break;
                                    }

                                }

                                $movieTrakt->watched_at = date("Y-m-d H:i:s", $time);
                                $moviesTrakt = ['movies' => [$movieTrakt]];

                                $resultSync = null;
                                if (!$lineReport['already_added']) {
                                    $resultSync = $trakt->sync->history->add($token, $moviesTrakt);
                                }

                                date_default_timezone_set($default_time_zone);
                                $lineReport['title'] = $movieBS->movie->title;
                                $lineReport['watched_at'] = date('m/d/Y H:i:s', $time);
                                $lineReport['type'] = 'Movie';

                                if (isset($resultSync) && !empty($resultSync) && is_array($resultSync->toArray()) && $resultSync[0]->added->movies == 1) {
                                    $lineReport['added'] = true;
                                } else {
                                    $lineReport['added'] = false;
                                }
                            }
                            break;
                    }

                    // Update last id
                    $last_id = $historique->id;
                    Utility::config_set($config, 'betaseries', 'lastId', $last_id);
                    Utility::config_write($config, __DIR__.'/config.ini');

                    // Report
                    $statut = $lineReport['added'] ===true ? 'Added' : 'Not added';
                    if ($lineReport['already_added'] === true) $statut = 'Already added';

                    Utility::writeLog([
                                          empty($statut) ? '' : $statut,
                                          empty($lineReport['type']) ? '' : $lineReport['type'],
                                          empty($lineReport['title']) ? '' : $lineReport['title'],
                                          empty($lineReport['watched_at']) ? '' : $lineReport['watched_at'],
                                      ]);
                } catch (\Exception $e) {
                    $lineReport['error'] = $e->getMessage();

                    Utility::writeLog([
                                          'Error',
                                          empty($lineReport['type']) ? '' : $lineReport['type'],
                                          empty($lineReport['title']) ? '' : $lineReport['title'],
                                          empty($lineReport['watched_at']) ? '' : $lineReport['watched_at'],
                                          empty($lineReport['error']) ? '' : $lineReport['error'],
                                      ]);
                }
                finally {
                    if (!empty($lineReport)) {
                        $report[] = $lineReport;
                    }
                }
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        } finally {
            Utility::writeLog('___End sync___');
        }

        return $report;
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