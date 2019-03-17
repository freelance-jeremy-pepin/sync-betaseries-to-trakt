<?php
use Repositories\Config;
use Repositories\Utility;

$config = Config::get();

$mailFrom = empty($config['mail']['mailFrom']) ? '' : $config['mail']['mailFrom'];
$mailHost = empty($config['mail']['mailHost']) ? '' : $config['mail']['mailHost'];
$mailPort = empty($config['mail']['mailPort']) ? '' : $config['mail']['mailPort'];
$mailEncryption = empty($config['mail']['mailEncryption']) ? '' : $config['mail']['mailEncryption'];
$mailAuthentification = empty($config['mail']['mailAuthentification']) ? '' : $config['mail']['mailAuthentification'];
$mailUsername = empty($config['mail']['mailUsername']) ? '' : $config['mail']['mailUsername'];

$error = '';
$success = '';
if (!empty($_GET['send_email']) && $_GET['send_email'] == 'true') {
    unset($_GET['send_email']);
    try {
        Utility::sendMail('Test successful!', 'You will now receive emails here.', explode(';', $_GET['email']));
        $success = 'Mail successfully sended!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Send mail test | Synchronize Netflix with Trakt.tv</title>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- Compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

</head>

<body style="background-color: #e3f2fd">

<h1 class="center-align blue-text text-darken-2">Synchronize Netflix with Trakt.tv</h1>
<br>
<h2 class="center-align orange-text text-darken-2">Send mail test</h2>

<div class="row center-align">
    <a class="waves-effect waves-light btn blue" href="/"><i class="material-icons left">home</i>Home</a>
</div>

<div class="row center-align">
    <div class="col s12 m6 offset-m3">
        <div class="card">
            <div class="card-content">
                <h4 class="card-title">Email test</h4>

                <form method="get" action="/send_mail_test">
                    <div class="row">
                        <div class="input-field col s12">
                            <input id="email" name="email" type="text" value="<?php echo isset($_GET['email']) ? $_GET['email'] : '' ?>">
                            <label for="email">Send test to this email</label>
                        </div>

                        <div>
                            <?php 
                            if (!empty($error)) {
                                echo '<h6 class="red-text" class="center-align">'.$error.'</h6>';
                            } 

                            if (!empty($success)) {
                                echo '<h5 class="green-text" class="center-align">'.$success.'</h5>';
                            }
                            ?>
                        </div>
                    </div>

                    <input name="send_email" value="true" hidden="hidden">

                    <button class="btn waves-effect waves-light blue" type="submit">Send
                        <i class="material-icons right">send</i>
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-content">
                <h4 class="center-align">Mail settings</h4>
                <div class="center-align"><a href="config" target="config">Edit mail settings (after edit reload this page)</a></div>

                <div class="row">
                    <div class="input-field col s12">
                        <input type="text" value="<?php echo $mailFrom?>" disabled>
                        <label>From email</label>
                    </div>

                    <div class="input-field col s12">
                        <input type="text" value="<?php echo $mailHost?>" disabled>
                        <label>SMTP Host</label>
                    </div>

                    <div class="input-field col s12">
                        <input type="text" value="<?php echo $mailPort?>" disabled>
                        <label>SMTP Port</label>
                    </div>

                    <div class="input-field col s12">
                        <input type="text" value="<?php echo $mailEncryption?>" disabled>
                        <label>Encryption</label>
                    </div>

                    <div class="input-field col s12">
                        <input type="text" value="<?php echo $mailAuthentification?>" disabled>
                        <label>Authentification</label>
                    </div>

                    <div class="input-field col s12">
                        <input type="text" value="<?php echo $mailUsername?>" disabled>
                        <label>Username</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>