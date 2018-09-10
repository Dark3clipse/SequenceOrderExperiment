<?php 
session_start();
include ("../globals.php");
header('Content-Type: text/javascript');

//set fade parameters
echo "//Script parameters\n";
echo "var trackDuration = 30000;//duration for each track. \n";
echo "var startupDelay = 3000 //delay before starting playback. \n";
echo "var totms = 2000;//total ms for single fade section\n";
echo "var dms = 500; //ms change per iteration \n";
echo "var nextTrackTimeout = 100;//time given to play next track before timeout. \n\n";

echo "//When the sdk is ready: initialize the player. \n";
echo "window.onSpotifyWebPlaybackSDKReady = () => {\n";
echo "    const token = '".trim($_SESSION['access_token'])."';\n";
echo "    const player = new Spotify.Player({\n";
echo "        name: '".$GLOBALS['WEB_PLAYER_NAME']."',\n";
echo "        getOAuthToken: cb => { cb(token); }\n";
echo "    });\n\n";

echo "    var playing = false;\n";
echo "    var curTrack = \"\";\n\n";

echo "    // Error handling\n";
echo "    player.addListener('initialization_error', ({ message }) => { \n";
echo "        console.error(message); \n";
echo "    });\n";
echo "    player.addListener('authentication_error', ({ message }) => { \n";
echo "        console.error(message); \n";
echo "    });\n";
echo "    player.addListener('account_error', ({ message }) => { \n";
echo "        console.error(message); \n";
echo "    });\n";
echo "    player.addListener('playback_error', ({ message }) => { \n";
echo "        setTimeout(function() {\n";
echo "            window.location.assign('app.php?p=4&playback_call=0');\n";
echo "        }, 1000);\n";
echo "        console.error(message); \n";
echo "    });\n\n";

echo "    //array containing the track starting positions. \n";
echo "    var curPos = ".($_SESSION['trial_completed']?$GLOBALS['S_TRACKS_TRIAL']:0).";\n";
//echo "    curPos = 0;\n";
echo "    var trace7pos = 0;\n";
echo "    var start_position = [";
for ($i=0;$i<count($_SESSION['tracks']);$i++){
    echo intval($_SESSION['tracks'][$i]['start']*1000);
    if ($i+1 < count($_SESSION['tracks'])){
        echo ", ";
    }
}
echo "];\n\n";
echo "    var tracks = [";
for ($i=0;$i<count($_SESSION['tracks']);$i++){
    echo "'".$_SESSION['tracks'][$i]['track_id']."'";
    if ($i+1 < count($_SESSION['tracks'])){
        echo ", ";
    }
}
echo "];\n\n";

echo "    // Called when player status changes (e.g. new track starts to play.)\n";
echo "    player.addListener('player_state_changed', state => {\n";
echo "        if (state!=null) {\n";
echo "            console.log(state);\n";

echo "            var trackIndex = tracks.indexOf(state.track_window.current_track.id);\n";
echo "            if (trackIndex > -1) {\n";
echo "                if (trackIndex == curPos && playing == false){\n";
echo "                    if (state.position != start_position[curPos]){\n";
echo "                        player.seek(start_position[curPos]).then(() => { });\n";
echo "                        console.log('Seek to position: '+start_position[curPos]);\n";
echo "                    }else{\n";
echo "                        console.log('Position correctly set.');\n";
echo "                        player.resume().then(() => {\n";
echo "                            console.log('Track resumed.');\n";

echo "                            //Start the fade-in of the track.\n";
echo "                            fadeIn();\n\n";

echo "                            //Set the timer for the fade-out of the track\n";
echo "                            setTimeout(function(){\n";
echo "                                fadeOut();\n";
echo "                                transitionUserPreview();\n";
echo "                            }, trackDuration);\n\n";

echo "                            //The player is now playing. \n";
echo "                            playing = true;\n";
echo "                        });\n";
echo "                    }\n";
echo "                }\n";
echo "            }else{\n";
echo "                console.log('Track id not found in playlist.');\n";
echo "            }\n";
echo "        }\n";
echo "    });\n\n";

echo "    player.addListener('ready', ({ device_id }) => {\n";
echo "        console.log('Ready with Device ID', device_id);\n";

echo "        //start to play the playlist\n";
echo "        setDeviceAndSwitchContext();\n";
echo "        setVolume(0);\n";
echo "    });\n\n";

echo "    player.addListener('not_ready', ({ device_id }) => {\n";
echo "        console.log('Device ID has gone offline', device_id);\n";
echo "    });\n\n";

echo "    // Connect to the player!\n";
echo "    player.connect();\n\n";

echo "    //define function to access volume control in the Spotify sdk.\n";
echo "    var playerVolume = 0;\n";
echo "    function setVolume(vol){\n";
/*echo "        e_fake = {\n";
 echo "            type: \"SP_MESSAGE\",\n";
 echo "            body: {\n";
 echo "                topic: \"SET_VOLUME\",\n";
 echo "                data: vol ? JSON.parse(JSON.stringify(vol)) : null\n";
 echo "            }\n";
 echo "        };\n";
 echo "        Reflect.getPrototypeOf(player)._sendMessageWhenLoaded.call(player, e_fake);\n";*/
echo "        player.setVolume(vol).then(() => { console.log('volume set to: '+vol)});\n";
echo "    };\n\n";

echo "function setDeviceAndSwitchContext(){";
echo "    console.log(\"AJAX Call: Setting context.\");\n";
echo "    var xhttp = new XMLHttpRequest();\n";
echo "    xhttp.onreadystatechange = function() {\n";
echo "        if (this.readyState == 4 && this.status == 200) {\n";
echo "            if (this.responseText.length>0){\n";
echo "                var code = Number(this.responseText.charAt(0));\n";
echo "                showWarning(this.responseText.substring(2));\n";
echo "                if (code == 2){\n";
echo "                    setTimeout(function() {\n";
echo "                        window.location.assign('app.php?p=4&playback_call=0');\n";
echo "                    }, 1000);\n";
echo "                }\n";
echo "            }\n\n";

echo "            //call to play the playlist context. \n";
echo "            console.log('Call to Spotify API to play the Playlist Context at position '+curPos);\n";
echo "            const play = ({\n";
echo "                spotify_uri,\n";
echo "                playerInstance: {\n";
echo "                    _options: {\n";
echo "                        getOAuthToken,\n";
echo "                        id\n";
echo "                    }\n";
echo "                }\n";
echo "            }) => {\n";
echo "            getOAuthToken(access_token => {\n";
echo "                  fetch(`https://api.spotify.com/v1/me/player/play?device_id=\${id}`, {\n";
echo "                      method: 'PUT',\n";
echo "                      body: JSON.stringify({ 'context_uri': spotify_uri, 'offset': { 'position': curPos}}),\n";
echo "                     headers: {\n";
echo "                          'Content-Type': 'application/json',\n";
echo "                          'Authorization': `Bearer \${access_token}`\n";
echo "                      },\n";
echo "                  });\n";
echo "              });\n";
echo "           };\n";
echo "           play({\n";
echo "               playerInstance: player,\n";
echo "               spotify_uri: '".$_SESSION['playlist']->uri."',\n";
echo "           });\n";

echo "        }\n";
echo "    };\n";
echo "    xhttp.open('GET', 'app.php?p=4&playback_call=1', true);\n";
echo "    xhttp.send();\n";
echo "}\n\n";

echo "    //define function to be called when fade-in must start.\n";
echo "    function fadeIn() {\n";
echo "        //Start the fade-in of the volume.\n";
echo "        var volfadein = setInterval(function() {\n";
echo "            if (playerVolume < 1){\n";
echo "                playerVolume = Math.min(playerVolume + dms/totms, 1); \n";
echo "                setVolume(playerVolume);\n";
echo "            }\n";
echo "            if (playerVolume >= 1){\n";
echo "                clearInterval(volfadein);\n";
echo "            }\n";
echo "        }, dms);\n\n";
echo "    }\n\n";

echo "    //define function to be called when fade-out must start\n";
echo "    function fadeOut() {\n";
echo "        //start the fade-out of the volume.\n";
echo "        var volfadeout = setInterval(function() {\n";
echo "            if (playerVolume > 0){\n";
echo "                playerVolume = Math.max(playerVolume - dms/totms, 0); \n";
echo "                setVolume(playerVolume);\n";
echo "            }\n";
echo "            if (playerVolume <= 0){\n";
echo "                playing = false;\n";
echo "                clearInterval(volfadeout);\n";
echo "                requestNextTrack(0, 1);\n";
echo "           }\n";
echo "       }, dms);\n";
echo "    }\n\n";

echo "    //Send a request for the next track. \n";
echo "    function requestNextTrack(again, iter){\n";
echo "        player.nextTrack().then(() => {console.log('Skipped to next track.');});\n";
echo "        curPos++;\n";

/*echo "        //Request the next track. \n";
 echo "        var xhttp = new XMLHttpRequest();\n";
 echo "        xhttp.onreadystatechange = function() {\n";
 echo "            if (this.readyState == 4 && this.status == 200) {\n";
 echo "                if (this.responseText.includes(\"Playlist Ended\")){\n";
 echo "                    window.location.assign('?p=".($_SESSION['trial_completed']?6:5)."');\n";
 echo "                }else{\n";
 echo "                    if(this.responseText.length > 0){\n";
 echo "                        showWarning(this.responseText);\n";
 echo "                    }else{\n";
 echo "                        clearWarning();\n";
 echo "                    }\n\n";
 
 echo "                    //Set an interval to check if keep checking if the request was successful. \n";
 echo "                    var nextTrackCheck = setTimeout( function(){ \n";
 echo "                        if (!playing){\n";
 echo "                            console.log(\"Next track timed-out, resent request!\");\n";
 echo "                            requestNextTrack(1, iter+1);\n";
 echo "                        }\n";
 echo "                    }, nextTrackTimeout*Math.pow(2, iter-1));\n";
 echo "                }\n";
 echo "            }\n";
 echo "        };\n";
 echo "        xhttp.open('GET', 'app.php?p=4&playback_call=2&repeat='+again, true);\n";
 echo "        xhttp.send();\n";*/
echo "    }\n\n";



echo "    //define function to be called when the user preview metadata must change.\n";
echo "    function transitionUserPreview(){\n";
echo "        var xhttp = new XMLHttpRequest();\n";
echo "        xhttp.onreadystatechange = function() {\n";
echo "            if (this.readyState == 4 && this.status == 200) {\n";
echo "                if (this.responseText.length>0){\n\n";
echo "                     //when fade-out result is received, start fade-to animations\n";
echo "                     var track = this.responseText.split(';;');\n\n";
echo "                     $('#track_cover_big').fadeTo(totms, 0, function() {\n";
echo "                         $('#track_cover_big').attr('src', track[0]);\n";
echo "                     }).fadeTo(totms,1);\n\n";
echo "                     $('#track_title').fadeTo(totms, 0, function() {\n";
echo "                         $('#track_title').text(track[1]);\n";
echo "                     }).fadeTo(totms,1);\n\n";
echo "                     $('#track_artist').fadeTo(totms, 0, function() {\n";
echo "                         $('#track_artist').text(track[2]);\n";
echo "                     }).fadeTo(totms,1);\n\n";
if ($GLOBALS['S_SHOW_TRACK_METADATA']){
    /*    echo "                     $('#container_valence').fadeTo(totms, 0, function() {\n";
     echo "                         $('#container_valence').text(track[6]+' ('+track[3]+')');\n";
     echo "                     }).fadeTo(totms,1);\n\n";
     echo "                     $('#container_energy').fadeTo(totms, 0, function() {\n";
     echo "                         $('#container_energy').text(track[6]+' ('+track[4]+')');\n";
     echo "                     }).fadeTo(totms,1);\n\n";
     echo "                     $('#container_tempo').fadeTo(totms, 0, function() {\n";
     echo "                         $('#container_tempo').text(track[7]+' ('+track[5]+')');\n";
     echo "                     }).fadeTo(totms,1);\n\n";
     
     echo "                     $('#container_valence_n').fadeTo(totms, 0, function() {\n";
     echo "                         $('#container_valence_n').text(track[11]+' ('+track[8]+')');\n";
     echo "                     }).fadeTo(totms,1);\n\n";
     echo "                     $('#container_energy_n').fadeTo(totms, 0, function() {\n";
     echo "                         $('#container_energy_n').text(track[11]+' ('+track[9]+')');\n";
     echo "                     }).fadeTo(totms,1);\n\n";
     echo "                     $('#container_tempo_n').fadeTo(totms, 0, function() {\n";
     echo "                         $('#container_tempo_n').text(track[12]+' ('+track[10]+')');\n";
     echo "                     }).fadeTo(totms,1);\n\n";*/
    
    echo "                     //update chart\n";
    echo "                     Plotly.extendTraces('metaChart', {y: [[track[3]], [track[4]], [(track[6]=='high_valence'?1:0)]]}, [0, 1, 2]);\n";
    echo "                     Plotly.extendTraces('metaChart2', {y: [[track[5]], [(track[7]=='high'?210:0)]]}, [0, 1]);\n\n";
    
    echo "                     var nx = (track[7]=='high'?Math.min(.9, Math.max(.1, randn_bm(.5, 1))):Math.min(-.1, Math.max(-.9, randn_bm(-.5, 1))));\n";
    echo "                     var ny = (track[6]=='high_valence'?Math.min(.9, Math.max(.1, randn_bm(.5, 1))):Math.min(-.1, Math.max(-.9, randn_bm(-.5, 1))));\n";
    echo "                     var vpos = [(track[7]=='high'?1:-1), track[6]=='high_valence'?1:-1];\n";
    echo "                     var apos = adjustGraphPos(vpos);\n";
    echo "                     trace7pos += 1;\n";
    echo "                     Plotly.extendTraces('metaChart3', {\n";
    echo "                         x: [[trace7.x[trace7pos]]],\n";
    echo "                         y: [[trace7.y[trace7pos]]],\n";
    echo "                         text: [['track '+curPos]]\n";
    echo "                     }, [1]);\n";
}
echo "                }\n";
echo "            }\n";
echo "        };\n";
echo "        xhttp.open('GET', 'app.php?p=4&playback_call=3', true);\n";
echo "        xhttp.send();\n";
echo "    }\n\n";

echo "};\n";
?>