<?php

use Repositories\Config;
use Repositories\App;

$config = Config::get();

// Check config
if (!Config::isOK()) {
    die('Missing configuration. Please configure the app <a href="config.php">here</a>.');
}

$report = App::synchronize();
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
    <a class="waves-effect waves-light btn blue" href="/"><i class="material-icons left">home</i>Home</a>
</div>
<div class="row center-align">
    <a class="waves-effect waves-light btn blue btn-large" href="sync_now"><i class="material-icons left">sync</i>Sync now</a>
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
                    echo '            <th></th>';
                    echo '            <th>Title</th>';
                    echo '            <th>Type</th>';
                    echo '            <th>Watched at</th>';
                    echo '            <th class="center-align">Added</th>';
                    echo '            <th></th>';
                    echo '        </tr>';
                    echo '    </thead>';
                    echo '    <tbody>';

                    foreach ($report as $line) {
                        echo '<tr>';
                        echo '    <td>'.(empty($line['poster']) ? '' : '<img style="max-width: 100px;" src="'.$line['poster'].'">').'</td>';
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
