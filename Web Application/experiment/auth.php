<?php
include("php/websdk/SpotifyWebAPI.php");
include("php/websdk/Session.php");
include("php/websdk/Request.php");
include 'create_session.php';

$options = [
    'scope' => [
        'user-read-private',
        'user-top-read',
        'user-read-playback-state',
        'user-read-currently-playing',
        'user-read-birthdate',
        'user-read-email',
        'user-read-private',
        'user-modify-playback-state',
        'user-library-read',
        'playlist-modify-private',
        'playlist-modify-public',
        'streaming',
    ],
    'show_dialog' => 'true',
];

header('Location: ' . $session->getAuthorizeUrl($options));
die();
?>