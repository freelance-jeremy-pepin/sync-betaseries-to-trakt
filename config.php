<?php

require __DIR__ . '/vendor/autoload.php';
require_once 'Utility.php';

use Wubs\Trakt\Auth;
use Wubs\Trakt\Trakt;


Utility::buildConfig();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Configuration | Synchronize Netflix with Trakt.tv</title>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- Compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

</head>

<body style="background-color: #e3f2fd  ">

    <?php
        $config = parse_ini_file('config.ini', true);

        // Save config
        if (isset($_GET['bs_api_key']) && isset($_GET['bs_member_id']) && isset($_GET['tr_client_id']) && isset($_GET['tr_client_secret']) && isset($_GET['tr_redirect_url'])) {
            Utility::config_set($config, 'betaseries', 'apiKeyBetaserie', $_GET['bs_api_key']);
            Utility::config_set($config, 'betaseries', 'idMembre', $_GET['bs_member_id']);

            Utility::config_set($config, 'trakt_tv', 'clientId', $_GET['tr_client_id']);
            Utility::config_set($config, 'trakt_tv', 'clientSecret', $_GET['tr_client_secret']);
            Utility::config_set($config, 'trakt_tv', 'redirectUrl', $_GET['tr_redirect_url']);

            if (isset($_GET['synchronizeOnlyNetflix'])) {
                Utility::config_set($config, 'app', 'synchronizeOnlyNetflix', '1');
            } else {
                Utility::config_set($config, 'app', 'synchronizeOnlyNetflix', '0');
            }

            Utility::config_write($config, 'config.ini');

            // Trakt.tv authorize
            $config = parse_ini_file('config.ini', true);
            $provider = new Auth\TraktProvider($config['trakt_tv']['clientId'], $config['trakt_tv']['clientSecret'], $config['trakt_tv']['redirectUrl']);
            $auth = new Auth\Auth($provider);
            $trakt = new Trakt($auth);
            $trakt->auth->authorize();

            header('Location: config.php');
        }

        $bsApiKeyBetaserie = $config['betaseries']['apiKeyBetaserie'];
        $bsIdMembre = $config['betaseries']['idMembre'];

        $trClientId = $config['trakt_tv']['clientId'];
        $trClientSecret = $config['trakt_tv']['clientSecret'];
        $trRedirectUrl = $config['trakt_tv']['redirectUrl'];

        $trAccessToken = $config['trakt_tv']['accessToken'];
        $trExpires = $config['trakt_tv']['expires'];
        $trRefreshToken = $config['trakt_tv']['refreshToken'];
    ?>

    <h1 class="center-align blue-text text-darken-2"">Synchronize Netflix with Trakt.tv</h1>
    <br>
    <h2 class="center-align orange-text text-darken-2"">Configuration</h2>

    <div class="row center-align">
        <a class="waves-effect waves-light btn blue" href="index.php"><i class="material-icons left">home</i>Home</a>
    </div>

    <div class="row">
        <div class="col s12 m6 offset-m3">
            <div class="card">
                <div class="card-content">
                    <form method="get" action="config.php">
                        <h5 class="center-align">BetaSeries</h5>
                        <div class="center-align"><a href="https://www.betaseries.com/api/" target="_blank">Ask API key</a></div>

                        <div class="row">
                            <div class="input-field col s12">
                                <input id="bs_api_key" name="bs_api_key" type="text" value="<?php echo $bsApiKeyBetaserie?>" required>
                                <label for="bs_api_key">API Key*</label>
                            </div>

                            <div class="input-field col s12">
                                <input id="bs_member_id" name="bs_member_id" type="text" value="<?php echo $bsIdMembre?>"  required>
                                <label for="bs_member_id">Member ID*<a href="search_member_betaseries.php" target="_blank">   (Search here)</a></label>
                            </div>
                        </div>

                        <br><hr style="height: 1px; color: lightsalmon; background-color: lightsalmon; border: none;"><br>

                        <h5 class="center-align">Trakt.tv</h5>
                        <div class="center-align"><a href="https://www.betaseries.com/api/" target="_blank">Create an API app</a></div>

                        <div class="row">
                            <div class="input-field col s12">
                                <input id="tr_client_id" name="tr_client_id" type="text" value="<?php echo $trClientId?>"  required>
                                <label for="tr_client_id">Client ID*</label>
                            </div>

                            <div class="input-field col s12">
                                <input id="tr_client_secret" name="tr_client_secret" type="text" value="<?php echo $trClientSecret?>"  required>
                                <label for="tr_client_secret">Client Secret*</label>
                            </div>

                            <div class="input-field col s12">
                                <input id="tr_redirect_url" name="tr_redirect_url" type="text" value="<?php echo $trRedirectUrl?>"  required>
                                <label for="tr_redirect_url">Redirect URL*</label>
                            </div><div class="input-field col s12">
                                <input id="ddd" name="ddd" type="text" value="<?php echo $trRedirectUrl?>"  required>
                                <label for="ddd">Redirect URL*</label>
                            </div>
                        </div>

                        <?php

                            if (empty($trAccessToken) || empty($trExpires) || empty($trRefreshToken)) {
                                echo '<h5 class="red-text center-align">This app is not authorized yet. Save configuration to authorize app.</h5>';
                            } else {
                                echo '<h5 class="green-text center-align">Your token expires at '.date('m/d/Y H:i:s', $trExpires).'</h5>';
                                echo '<h6 class="green-text center-align">(Save config to refresh token)</h6>';
                            }

                        ?>

                        <br><hr style="height: 1px; color: lightsalmon; background-color: lightsalmon; border: none;"><br>

                        <h5 class="center-align">General settings</h5>

                        <div class="row">
                            <div class="input-field col s12">
                                <label>
                                    <input type="checkbox" id="synchronizeOnlyNetflix"  name="synchronizeOnlyNetflix"  <?php echo $config['app']['synchronizeOnlyNetflix'] == '1' ? 'checked' : '' ?> />
                                    <span>Only synchronize Netflix</span>
                                </label>
                            </div>
                        </div>


                        <div class="row right-align">
                            <button class="btn waves-effect waves-light right-align blue" type="submit">Save
                                <i class="material-icons right">save</i>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>



</body>
</html>