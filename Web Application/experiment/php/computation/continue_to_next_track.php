<?php 
//WARNING: code is deprecated.

//if Spotify control is disabled, don't instruct Spotify to take over control
if (!$GLOBALS['S_CONTROL_SPOTIFY']){
    echo "Spotify connectivity is turned off in the settings.";
    die();
}

//check if this call is because of a time-out on Spotify's end.
$again = false;
if (isset($_GET['repeat']) && $_GET['repeat'] == 1){
    $again = true;
}else{
    $_SESSION['playlist_position']+=1;
}

//get information about the current playback ----------------------------
$options = [
    'market' => 'NL',
];
$playback = $api->getMyCurrentPlaybackInfo($options);
echo "9 Call: playback info. <br/>";

//transfer the playback device to the web player if it is not the current player
if ($playback != null && $playback->device->id != $_SESSION['device']->id){
    $options = [
        'device_ids' => [
            $_SESSION['device']->id,
        ],
        'play' => true,
    ];
    $result = $api->changeMyDevice($options);
    echo "9 Call: change device. Reason: wrong device was selected. <br/>";
}
if ($playback == null){
    echo "9 No playback info available. <br/>";
}


//if you reached the last track
if (($_SESSION['playlist_position'] >= $GLOBALS['S_TRACKS_TRIAL'] && $_SESSION['trial_completed']==false) ||
    ($_SESSION['playlist_position'] >= $GLOBALS['S_TRACKS_EXP']   && $_SESSION['trial_completed']==true)){
        
        $result = $api->pause($_SESSION['device']->id);
        if ($result == 403){
            echo "playback requires a Spotify Premium account.";
            die();
        }
        echo "Playlist Ended";
        
}else{//otherwise, continue with the next track
    
    if ($again){
        $options = [
            'context_uri' => $_SESSION['playlist']->uri,
            'offset' => [
                'position' => $_SESSION['playlist_position'],
            ],
        ];
        $result = $api->play($_SESSION['device']->id, $options);
        echo "9 Call: play track at position ".$_SESSION['playlist_position'].".<br/>";
    }else{
        $result = $api->next($_SESSION['device']->id);
        echo "9 Call: skip track. <br/>";
    }
    
    if ($result == 403){
        echo "playback requires a Spotify Premium account.";
        die();
    }
    
    //jump to start position
    $options = [
        'position_ms' => intval($_SESSION['tracks'][$_SESSION['playlist_position']]['start']*1000),
        'device_id' => $_SESSION['device']->id,
    ];
    $result = $api->seek($options);
    echo "9 Call: seek to position<br/>";
    switch($result){
        case 403:
            echo "playback requires a Spotify Premium account.";
            die();
        case 404:
            echo "device not found while requesting position jump.";
            die();
    }
}

//echo "playing track: ".$_SESSION['playlist_position'];
?>