<?php 
//find if one already exists, then unfollow that one
$playlists = $api->getMyPlaylists();
for ($i=0;$i<count($playlists->items);$i++){
    if ($playlists->items[$i]->name == $PLAYLIST_NAME){
        $api->unfollowPlaylist($me->id, $playlists->items[$i]->id);
        break;
    }
}
?>