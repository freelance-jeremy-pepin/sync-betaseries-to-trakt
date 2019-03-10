<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Search member BetaSeries | Synchronize Netflix with Trakt.tv</title>

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
<h2 class="center-align orange-text text-darken-2">Search member BetaSeries</h2>

<div class="row center-align">
    <div class="col s12 m6 offset-m3">
        <div class="card">
            <div class="card-content">
                <span class="card-title">BetaSeries</span>

                <form method="get" action="/search_member_betaseries.php">
                    <div class="row">
                        <a href="https://www.betaseries.com/api/">Ask dev key</a>

                        <div class="input-field col s12">
                            <input id="api_key" name="api_key" type="text" value="<?php echo isset($_GET['api_key']) ? $_GET['api_key'] : '' ?>">
                            <label for="api_key">API Key</label>
                        </div>

                        <div class="input-field col s12">
                            <input id="member_login" name="member_login" type="text" value="<?php echo isset($_GET['member_login']) ? $_GET['member_login'] : '' ?>">
                            <label for="member_login">Member login (use % as wildcard)</label>
                        </div>


                    </div>

                    <button class="btn waves-effect waves-light blue" type="submit">Search
                        <i class="material-icons right">search</i>
                    </button>
                </form>

                <br>

                <?php

                if (isset($_GET['api_key']) && isset($_GET['member_login'])) {
                    $api_key = $_GET['api_key'];
                    $member_login = $_GET['member_login'];
                    $url = "https://api.betaseries.com/members/search?key=$api_key&login=$member_login";

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $data = curl_exec($ch);
                    curl_close($ch);
                    $results = json_decode($data);

                    if (isset($results->errors) && !empty($results->errors)) {
                        echo '<h4 class="red-text" class="center-align">'.$results->errors[0]->text.'</h4>';

                    } else if (isset($results->users) && !empty($results->users)) {
                        echo '<table>';
                        echo '    <thead>';
                        echo '        <tr>';
                        echo '            <th>ID</th>';
                        echo '            <th>Login</th>';
                        echo '            <th>XP</th>';
                        echo '        </tr>';
                        echo '    </thead>';
                        echo '    <tbody>';

                        foreach ($results->users as $user) {
                            echo '<tr>';
                            echo '    <td>'.$user->id.'</td>';
                            echo '    <td>'.$user->login.'</td>';
                            echo '    <td>'.$user->xp.'</td>';
                            echo '</tr>';
                        }

                        echo '    </tbody>';
                        echo '</table>';
                    }
                }

                ?>

            </div>
        </div>
    </div>
</div>

</body>
</html>