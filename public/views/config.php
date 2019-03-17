<?php

use Wubs\Trakt\Auth;
use Wubs\Trakt\Trakt;

use Repositories\Config;
use Repositories\Utility;

Config::build();
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
        $config = Config::get();

        // Save config
        if (isset($_GET['bs_api_key']) && isset($_GET['bs_member_id']) && isset($_GET['tr_client_id']) && isset($_GET['tr_client_secret']) && isset($_GET['tr_redirect_url'])) {
            Config::set($config, 'betaseries', 'apiKeyBetaserie', $_GET['bs_api_key']);
            Config::set($config, 'betaseries', 'idMembre', $_GET['bs_member_id']);

            Config::set($config, 'trakt_tv', 'clientId', $_GET['tr_client_id']);
            Config::set($config, 'trakt_tv', 'clientSecret', $_GET['tr_client_secret']);
            Config::set($config, 'trakt_tv', 'redirectUrl', $_GET['tr_redirect_url']);

            if (isset($_GET['synchronizeOnlyNetflix'])) {
                Config::set($config, 'app', 'synchronizeOnlyNetflix', '1');
            } else {
                Config::set($config, 'app', 'synchronizeOnlyNetflix', '0');
            }
            
            Config::set($config, 'app', 'scheduleSyncNow', $_GET['scheduleSyncNow']);
            Config::set($config, 'app', 'emailsReceiveLogs', $_GET['emailsReceiveLogs']);
            Config::set($config, 'app', 'deleteLogAfterXDays', $_GET['deleteLogAfterXDays']);
            
            Config::set($config, 'mail', 'mailFrom', $_GET['mailFrom']);
            Config::set($config, 'mail', 'mailHost', $_GET['mailHost']);
            Config::set($config, 'mail', 'mailPort', $_GET['mailPort']);
            Config::set($config, 'mail', 'mailEncryption', $_GET['mailEncryption']);
            Config::set($config, 'mail', 'mailAuthentification', $_GET['mailAuthentification']);

            if (!empty($_GET['mailAuthentification']) && $_GET['mailAuthentification'] == 'yes') {
                var_dump('expression');
                Config::set($config, 'mail', 'mailUsername', $_GET['mailUsername']);
                Config::set($config, 'mail', 'mailPassword', Utility::decryptEncrypt('encrypt', $_GET['mailPassword']));
            } else {
                Config::set($config, 'mail', 'mailUsername', '');
                Config::set($config, 'mail', 'mailPassword', '');
            }

            Config::write($config);

            // Trakt.tv authorize
            $config = Config::get();
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
        
        $scheduleSyncNow = $config['app']['scheduleSyncNow'];
        $emailsReceiveLogs = empty($config['app']['emailsReceiveLogs']) ? '' : $config['app']['emailsReceiveLogs'];
        $deleteLogAfterXDays = empty($config['app']['deleteLogAfterXDays']) ? '' : $config['app']['deleteLogAfterXDays'];

        $mailFrom = empty($config['mail']['mailFrom']) ? '' : $config['mail']['mailFrom'];
        $mailHost = empty($config['mail']['mailHost']) ? '' : $config['mail']['mailHost'];
        $mailPort = empty($config['mail']['mailPort']) ? '' : $config['mail']['mailPort'];
        $mailEncryption = empty($config['mail']['mailEncryption']) ? '' : $config['mail']['mailEncryption'];
        $mailAuthentification = empty($config['mail']['mailAuthentification']) ? '' : $config['mail']['mailAuthentification'];
        $mailUsername = empty($config['mail']['mailUsername']) ? '' : $config['mail']['mailUsername'];
        $mailPassword = empty($config['mail']['mailPassword']) ? '' : Utility::decryptEncrypt('decrypt', $config['mail']['mailPassword']);
    ?>

    <h1 class="center-align blue-text text-darken-2"">Synchronize Netflix with Trakt.tv</h1>
    <br>
    <h2 class="center-align orange-text text-darken-2"">Configuration</h2>

    <div class="row center-align">
        <a class="waves-effect waves-light btn blue" href="/"><i class="material-icons left">home</i>Home</a>
    </div>

    <div class="row">
        <form method="get" action="config">
            <div class="col s12 m6 offset-m3">

                <div class="card">
                    <div class="card-content">
                        <h4 class="center-align">BetaSeries</h4>
                        <div class="center-align"><a href="https://www.betaseries.com/api/" target="_blank">Ask API key</a></div>

                        <div class="row">
                            <div class="input-field col s12">
                                <input id="bs_api_key" name="bs_api_key" type="text" value="<?php echo $bsApiKeyBetaserie?>" required>
                                <label for="bs_api_key">API Key*</label>
                            </div>

                            <div class="input-field col s12">
                                <input id="bs_member_id" name="bs_member_id" type="text" value="<?php echo $bsIdMembre?>"  required>
                                <label for="bs_member_id">Member ID*<a href="search_member_betaseries" target="_blank">   (Search here)</a></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content">
                        <h4 class="center-align">Trakt.tv</h4>
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
                    </div>
                </div>

                <div class="card">
                    <div class="card-content">
                        <h4 class="center-align">General settings</h4>

                        <div class="row">
                            <div class="input-field col s12">
                                <label>
                                    <input type="checkbox" id="synchronizeOnlyNetflix"  name="synchronizeOnlyNetflix"  <?php echo $config['app']['synchronizeOnlyNetflix'] == '1' ? 'checked' : '' ?> />
                                    <span>Only synchronize Netflix</span>
                                </label>
                            </div>

                            <br><br><br>

                            <div class="input-field col s12">
                                <input id="scheduleSyncNow" name="scheduleSyncNow" type="text" value="<?php echo $scheduleSyncNow?>">
                                <label for="scheduleSyncNow">Schedule Sync Now Task (cron task format : m h  dom mon dow)</label>
                            </div> 

                            <div class="input-field col s12">
                                <input id="emailsReceiveLogs" name="emailsReceiveLogs" type="text" value="<?php echo $emailsReceiveLogs?>" class="tooltipped" data-tooltip="You must to fill Mail settings section if you choose to receive logs. Otherwise let this field blank.">
                                <label for="emailsReceiveLogs">Send daily report to this emails (separate with ;)</label>
                            </div>

                            <div class="input-field col s12">
                                <input id="deleteLogAfterXDays" name="deleteLogAfterXDays" type="number" value="<?php echo $deleteLogAfterXDays?>" required class="tooltipped" data-tooltip="Log are save here: <?php echo log_path(); ?>" >
                                <label for="deleteLogAfterXDays">Delete logs files after X days</label>
                            </div> 
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content">
                        <h4 class="center-align">Mail settings</h4>
                        <div class="center-align"><a href="send_mail_test" target="test_mail">Test mail (You must to save config before)</a></div>

                        <div class="row">
                            <div class="input-field col s12">
                                <input id="mailFrom" name="mailFrom" type="text" value="<?php echo $mailFrom?>">
                                <label for="mailFrom">From email</label>
                            </div>

                            <div class="input-field col s12">
                                <input id="mailHost" name="mailHost" type="text" value="<?php echo $mailHost?>">
                                <label for="mailHost">SMTP Host</label>
                            </div>

                            <div class="input-field col s12">
                                <input id="mailPort" name="mailPort" type="text" value="<?php echo $mailPort?>">
                                <label for="mailPort">SMTP Port</label>
                            </div>

                            <div class="input-field col s12">
                                <select name="mailEncryption">
                                    <option value="" disabled <?php echo $mailEncryption=='' ? 'selected' : ''?> >Choose your option</option>
                                    <option value="no" <?php echo $mailEncryption=='no' ? 'selected' : ''?> >No encryption</option>
                                    <option value="ssl" <?php echo $mailEncryption=='ssl' ? 'selected' : ''?> >Use SSL encryption</option>
                                    <option value="tls" <?php echo $mailEncryption=='tls' ? 'selected' : ''?> >Use TLS encryption</option>
                                </select>
                                <label>Encryption</label>
                            </div>

                            <div class="input-field col s12">
                                <select name="mailAuthentification">
                                    <option value="" disabled <?php echo $mailAuthentification=='' ? 'selected' : ''?> >Choose your option</option>
                                    <option value="no" <?php echo $mailAuthentification=='no' ? 'selected' : ''?> >No: Do not use SMTP authentification (values below will be ignored)</option>
                                    <option value="yes" <?php echo $mailAuthentification=='yes' ? 'selected' : ''?> >Yes: Use SMTP authentification</option>
                                </select>
                                <label>Authentification</label>
                            </div>

                            <div class="input-field col s12">
                                <input id="mailUsername" name="mailUsername" type="text" value="<?php echo $mailUsername?>">
                                <label for="mailUsername">Username</label>
                            </div>

                            <div class="input-field col s12">
                                <input id="mailPassword" name="mailPassword" type="password" value="<?php echo $mailPassword?>">
                                <label for="mailPassword">Password</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row right-align">
                    <button class="btn waves-effect waves-light right-align blue" type="submit">Save
                        <i class="material-icons right">save</i>
                    </button>
                </div>

            </div>
        </form>
    </div>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var elems = document.querySelectorAll('.tooltipped');
            var instances = M.Tooltip.init(elems);
        });

          document.addEventListener('DOMContentLoaded', function() {
            var elems = document.querySelectorAll('select');
            var instances = M.FormSelect.init(elems);
          });
    </script>

</body>
</html>