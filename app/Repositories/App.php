<?php
/**
 * Created by PhpStorm.
 * User: PC-FIXE
 * Date: 12/03/2019
 * Time: 19:39
 */

namespace Repositories;

use Wubs\Trakt\Auth;
use Wubs\Trakt\Trakt;

use Repositories\Log;
use Repositories\Config;
use Repositories\Utility;

class App {
    public static function authentification() {
        $config = Config::get();

        try {
            $provider = new Auth\TraktProvider($config['trakt_tv']['clientId'], $config['trakt_tv']['clientSecret'], $config['trakt_tv']['redirectUrl']);
            $auth = new Auth\Auth($provider);
            $trakt = new Trakt($auth);

            $token = $trakt->auth->token($_GET['code']);

            Config::set($config, 'trakt_tv', 'accessToken', $token->accessToken);
            Config::set($config, 'trakt_tv', 'expires', $token->expires);
            Config::set($config, 'trakt_tv', 'refreshToken', $token->refreshToken);
            Config::write($config);

            header('Location: config');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public static function synchronize() {
        Log::write('___Start sync___');

        if (!Config::isOK()) {
            throw new Exception("Missing configuration elements!");
        }

        set_time_limit(3600); // 1 hour

        $config = Config::get();


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

        // TMDB
        $api_key_tmdb = $config['tmdb']['tmdb_api_key'];
        $tmdb_url_movie = "https://api.themoviedb.org/3/movie/{id}}?api_key=$api_key_tmdb";
        $tmdb_url_show = "https://api.themoviedb.org/3/tv/{id}?api_key=$api_key_tmdb";
        $tmdb_root_path_image = '//image.tmdb.org/t/p/original';

        $report = [];

        try {
            // Init last_id
            if (empty($last_id)) {
                $lastEvent = Utility::getDataJson("https://api.betaseries.com/timeline/member?key=$api_key_betaserie&id=$id_membre&nbpp=1&types=markas,film_add");

                if ( !empty($lastEvent->events) ) {
                    $last_id = $lastEvent->events[0]->id;
                    Config::set($config, 'betaseries', 'lastId', $last_id);
                    Config::write($config);
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
                    Config::set($config, 'betaseries', 'lastId', $last_id);
                    Config::write($config);
                    continue;
                }


                $default_time_zone = date_default_timezone_get();
                $lineReport = [];

                try {
                    switch ($historique->type) {
                        case 'markas':
                            $episodeBS = Utility::getDataJson(str_replace('%id%', $historique->ref_id, $url_episode));

                            $showTrakt = null;
                            $results = $trakt->search->byId('tvdb', $episodeBS->episode->show->thetvdb_id);
                            foreach ($results as $result) {
                                if ($result->type == 'show') {
                                    $showTrakt = $result->show;
                                    break;
                                }
                            }

                            if (App::isNetflix($episodeBS->episode->platform_links) || $config['app']['synchronizeOnlyNetflix'] == '0') {
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

                                if (!empty($showTrakt)) {
                                    // Get Poster from TMDB
                                    $showTMDB = Utility::getDataJson(str_replace('{id}', $showTrakt->ids->tmdb, $tmdb_url_show));
                                    if ( !empty($showTMDB->poster_path) ) {
                                        $lineReport['poster'] = $tmdb_root_path_image.'/'.$showTMDB->poster_path;
                                    }
                                }

                                // Check if episode not already added to history
                                $username = $trakt->users->settings($token)->toArray()[0]->user->username;
                                $watched = $trakt->users->history($username, 'episodes', $episodeTrakt->ids->trakt);
                                $lineReport['already_added'] = false;

                                foreach ($watched as $watch) {
                                    if ( strtotime('-1 day', $time) <= strtotime($watch->watched_at) && strtotime('+1 day', $time) >= strtotime($watch->watched_at) ) {
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
                            if (App::isNetflix($movieBS->movie->platform_links) || $config['app']['synchronizeOnlyNetflix'] == '0') {
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

                                // Get Poster from TMDB
                                $movieTMDB = Utility::getDataJson(str_replace('{id}', $movieTrakt->ids->tmdb, $tmdb_url_movie));
                                if (!empty($movieTMDB->poster_path)) {
                                    $lineReport['poster'] = $tmdb_root_path_image.'/'.$movieTMDB->poster_path;
                                }

                                // Check if movie not already added to history
                                $username = $trakt->users->settings($token)->toArray()[0]->user->username;
                                $watched = $trakt->users->history($username, 'movies', $movieTrakt->ids->trakt);
                                $lineReport['already_added'] = false;
                                foreach ($watched as $watch) {
                                    if ( strtotime('-1 day', $time) <= strtotime($watch->watched_at) && strtotime('+1 day', $time) >= strtotime($watch->watched_at) ) {
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
                    Config::set($config, 'betaseries', 'lastId', $last_id);
                    Config::write($config);

                    // Report
                    $statut = $lineReport['added'] ===true ? 'Added' : 'Not added';
                    if ($lineReport['already_added'] === true) $statut = 'Already added';

                    Log::write([
                                          empty($statut) ? '' : $statut,
                                          empty($lineReport['type']) ? '' : $lineReport['type'],
                                          empty($lineReport['title']) ? '' : $lineReport['title'],
                                          empty($lineReport['watched_at']) ? '' : $lineReport['watched_at'],
                                          empty($lineReport['poster']) ? '' : $lineReport['poster'],
                                      ]);
                } catch (\Exception $e) {
                    $lineReport['error'] = $e->getMessage();

                    Log::write([
                                          'Error',
                                          empty($lineReport['type']) ? '' : $lineReport['type'],
                                          empty($lineReport['title']) ? '' : $lineReport['title'],
                                          empty($lineReport['watched_at']) ? '' : $lineReport['watched_at'],
                                          empty($lineReport['poster']) ? '' : $lineReport['poster'],
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
            Log::write('___End sync___');
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
}