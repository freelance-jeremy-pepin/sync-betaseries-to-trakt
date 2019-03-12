<?php

require __DIR__ . '/vendor/autoload.php';
require_once 'Utility.php';

use Wubs\Trakt\Auth;
use Wubs\Trakt\Trakt;

set_time_limit(3600); // 1 hour

$config = parse_ini_file(__DIR__.'/config.ini', true);

// Check config
if (
    empty($config['trakt_tv']['clientId']) ||
    empty($config['trakt_tv']['clientSecret']) ||
    empty($config['trakt_tv']['redirectUrl']) ||
    empty($config['trakt_tv']['accessToken']) ||
    empty($config['trakt_tv']['expires']) ||
    empty($config['trakt_tv']['refreshToken']) ||
    empty($config['betaseries']['apiKeyBetaserie']) ||
    empty($config['betaseries']['idMembre']) ) {

    die('Missing configuration. Please configure the app <a href="config.php">here</a>.');
}

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

    $report = [];
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
        } catch (\Exception $e) {
            $lineReport['error'] = $e->getMessage();
        }
        finally {
            if (!empty($lineReport)) {
                $report[] = $lineReport;
            }
        }
    }

} catch (\Exception $e) {
    echo $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Synchronization report | Synchronize Netflix with Trakt.tv</title>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- Compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

</head>

<body style="background-color: #e3f2fd">

<h1 class="center-align blue-text text-darken-2"">Synchronize Netflix with Trakt.tv</h1>
<br>
<h2 class="center-align orange-text text-darken-2"">Synchronization report</h2>

<div class="row center-align">
    <a class="waves-effect waves-light btn blue" href="index.php"><i class="material-icons left">home</i>Home</a>
</div>
<div class="row center-align">
    <a class="waves-effect waves-light btn blue btn-large" href="sync_now.php"><i class="material-icons left">sync</i>Sync now</a>
</div>

<div class="row center-align">
    <div class="col s12 m6 offset-m3">
        <div class="card">
            <div class="card-content">
                <?php
                if (empty($report)) {
                    echo '<h4>Nothing to synchronize</h4>';

                } else {
                    echo '<table>';
                    echo '    <thead>';
                    echo '        <tr>';
                    echo '            <th>Title</th>';
                    echo '            <th>Type</th>';
                    echo '            <th>Date</th>';
                    echo '            <th class="center-align">Added</th>';
                    echo '            <th></th>';
                    echo '        </tr>';
                    echo '    </thead>';
                    echo '    <tbody>';

                    foreach ($report as $line) {
                        echo '<tr>';
                        echo '    <td>'.(empty($line['title']) ? '' : $line['title']).'</td>';
                        echo '    <td>'.(empty($line['type']) ? '' : $line['type']).'</td>';
                        echo '    <td>'.(empty($line['watched_at']) ? '' : $line['watched_at']).'</td>';
                        echo '    <td class="center-align">'.($line['added'] ? '<i class="material-icons center-align" style="color: green">check_circle</i>' : '<i class="material-icons center-align" style="color: red">cancel</i>').'</td>';
                        echo '    <td class="center-align">'.(isset($line['error']) ? 'Error: '.$line['error'] : '').' '.($line['already_added'] ? 'Already added' : '').'</td>';
                        echo '</tr>';
                    }

                    echo '    </tbody>';
                    echo '</table>';
                }

                ?>

            </div>
        </div>
    </div>
</div>

</body>
</html>
