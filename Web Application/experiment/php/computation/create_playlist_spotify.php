<?php 
//create the playlist on Spotify ------------------------------------------

//delete old playlist if present
include("php/computation/delete_playlist_spotify.php");

//create a new one
$options = [
    'name' => $PLAYLIST_NAME,
    'public' => false,
    'collaborative' => false,
    'description' => 'Playlist created for the Experiment.',
];
$playlist = $api->createUserPlaylist($me->id, $options);

//add tracks to the playlist----------------------------------------------
$success = $api->addUserPlaylistTracks($me->id, $playlist->id, $_SESSION['track_ids_playlist'], $options = []);
$playlist = $api->getUserPlaylist($me->id, $playlist->id, $options = []);

$_SESSION['playlist'] = $playlist;
//echo "<pre>";print_r($_SESSION['track_ids_playlist']);echo "</pre>";die();
?>