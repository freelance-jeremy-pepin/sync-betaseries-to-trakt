<?php
use Repositories\Config;
use Repositories\Utility;

Config::build();
$isConfigOK = Config::isOK();
$config = Config::get();
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Welcome! | Synchronize Netflix with Trakt.tv</title>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- Compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

</head>

<body style="background-color: #e3f2fd  ">

<h1 class="center-align blue-text text-darken-2">Synchronize Netflix with Trakt.tv</h1>
<br>
<h2 class="center-align orange-text text-darken-2">Welcome!</h2>
<br><br>

<div class="row center-align">
    <div class="col s12 m6 offset-m3">
        <div class="row">
        <?php
            if (!$isConfigOK) {
                echo '<h3>To start, please configure the app <a href="config" target="_blank">here</a>.</h3>';
            } else {
                echo '<a class="waves-effect waves-light btn btn-large blue" href="sync_now" style="margin-bottom: 16px;"><i class="material-icons left">sync</i>Sync now</a>';
                echo '<br>';
                echo '<a class="waves-effect waves-light btn btn-small grey" href="config" target="config" style="margin-top: 16px;"><i class="material-icons left">settings</i>Config</a>';
            }
        ?>
        </div>
    </div>
</div>

</body>
</html>