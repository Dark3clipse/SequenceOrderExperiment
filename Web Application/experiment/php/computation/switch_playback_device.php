<?php 
//if Spotify control is disabled, don't instruct Spotify to take over control
if (!$GLOBALS['S_CONTROL_SPOTIFY']){
    echo "1 Spotify connectivity is turned off in the settings.";
    die();
}

//get api for the premium account
$apip = new SpotifyWebAPI\SpotifyWebAPI();
$apip->setAccessToken($_SESSION['premium_accounts'][$_SESSION['spotify_account_index']]['access_token']);

//code for finding the Spotify Web Player
$devices = $apip->getMyDevices()->devices;
$device = null;
for ($i=0;$i<count($devices);$i++){
    if ($devices[$i]->name == $WEB_PLAYER_NAME){
        $device = $devices[$i];
        break;
    }
}
if ($device == null){
    echo "2 Spotify Web Player could not be found.";
    die();
}
$_SESSION['device'] = $device;
//echo "9 Web player found: device id = $device->name<br/>";


//transfer the playback device to the web player
$options = [
    'device_ids' => [
        $device->id,
    ],
    'play' => true,
];
$result = $apip->changeMyDevice($options);
//echo "9 Call: change device to $device->name<br/>";


//echo "9 Start position set to ".$_SESSION['playlist_position']."<br/>";

//play the first track (@start position)
/*$options = [
 'context_uri' => $_SESSION['playlist']->uri,
 'offset' => [
 'position' => $_SESSION['playlist_position'],
 ],
 ];
 $result = $api->play($device->id, $options);//$device->id
 echo "9 Call: play track. Result: $result<br/>";
 if ($result == 403){
 echo "3 playback requires a Spotify Premium account.";
 die();
 }*/

//jump to start position
/*$options = [
 'position_ms' => intval($_SESSION['tracks'][$_SESSION['playlist_position']]['start']*1000),
 'device_id' => $device->id,
 ];
 $result = $api->seek($options);
 echo "9 Call: seek to position. Result: $result<br/>";
 switch($result){
 case 403:
 echo "3 playback requires a Spotify Premium account.";
 die();
 case 404:
 echo "4 device not found while requesting position jump.";
 die();
 }*/


//get information about the current playback ----------------------------
/*$options = [
 'market' => 'NL',
 ];
 $playback = $api->getMyCurrentPlaybackInfo($options);
 echo "9 Call: get playback info<br/>";
 
 if ($playback != null){
 if ($playback->device->id == $_SESSION['device']->id){
 echo "9 Current player is changed successfully to $device->name<br/>";
 }
 if ($playback->is_playing == 1){
 echo "9 is_playing = true<br/>";
 }
 }else{
 echo "9 Playback info = null<br/>";
 }*/
?>