<?php

require __DIR__ . '/../autoload.php';

if (empty($_SERVER['REDIRECT_URL'])) {
    $request = $_SERVER['REQUEST_URI'];
} else {
    $request = $_SERVER['REDIRECT_URL'];
}

switch ($request) {
    case '/' :
        require __DIR__ . '/views/index.php';
        break;
    case '' :
        require __DIR__ . '/views/index.php';
        break;
    case '/config' :
        require __DIR__ . '/views/config.php';
        break;
    case '/sync_now' :
        require __DIR__ . '/views/sync_now.php';
        break;
    case '/search_member_betaseries' :
        require __DIR__ . '/views/search_member_betaseries.php';
        break;
    case '/authentification' :
        require __DIR__ . '/views/authentification.php';
        break;
    case '/send_mail_test' :
        require __DIR__ . '/views/send_mail_test.php';
        break;
    default:
        require __DIR__ . '/views/error/404.php';
        break;
}