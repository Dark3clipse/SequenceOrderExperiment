<?php 
session_start();
include ("../globals.php");
header('Content-Type: text/javascript');
?>

//Script parameters
var trackDuration = 30000;//duration for each track.
var startupDelay = 3000 //delay before starting playback.
var totms = 2000;//total ms for single fade section
var dms = 100; //ms change per iteration
var nextTrackTimeout = 100;//time given to play next track before timeout.
var requestDelay = 250;//delay in ms before requesting something from Spotify

//When the sdk is ready: initialize the player.
window.onSpotifyWebPlaybackSDKReady = () => {
	websdk_device_id = "";
	const token = '<?php echo trim($_SESSION['premium_accounts'][$_SESSION['spotify_account_index']]['access_token']); ?>'; // use researcher account
	//const token = '<?php echo trim($_SESSION['access_token']); ?>'; // use personal account
	const player = new Spotify.Player({
			name: '<?php echo $GLOBALS['WEB_PLAYER_NAME']; ?>',
			getOAuthToken: cb => { cb(token); }
	});

    var playing = false;
    var curTrack = "";
    var refreshIter = <?php echo $_SESSION['refresh_iter']; ?>;
    

    // Error handling
    player.addListener('initialization_error', ({ message }) => { 
        console.error(message); 
    });
    player.addListener('authentication_error', ({ message }) => { 
        console.error(message);
        setTimeout(function() {
            window.location.assign('app.php?p=4&playback_call=0&curpos='+curPos+'&iter='+(refreshIter+1));
        }, 1000 * refreshIter^2);
    });
    player.addListener('account_error', ({ message }) => { 
        console.error(message); 
    });
    player.addListener('playback_error', ({ message }) => { 
    	console.error(message); 
    	if (refreshIter < 3){
	    	setTimeout(function() {
	            window.location.assign('app.php?p=4&playback_call=0&curpos='+curPos+'&iter='+(refreshIter+1));
	        }, 1000 * refreshIter^2);
        }else{
        	//after refreshing doesn't work, skip this track
        	refreshIter = 1;
        	setTimeout(function() {
	            window.location.assign('app.php?p=4&playback_call=0&curpos='+(curPos+1)+'&iter='+(refreshIter+1));
	        }, 1000 * refreshIter^2);
        }
    });

    //array containing the track starting positions. 
    if (<?php echo $_SESSION['jsvar_curpos']>0?"true":"false"; ?>){
    	curPos = <?php echo $_SESSION['jsvar_curpos']; ?>;
    	transitionUserPreview(curPos);
    }else{
    	curPos = <?php echo ($_SESSION['trial_completed']?$GLOBALS['S_TRACKS_TRIAL']:0); ?>;
    }
    
    //curPos = 0;
    var trace7pos = 0;
<?php
echo"    var start_position = [";
for ($i=0;$i<count($_SESSION['tracks']);$i++){
    echo intval($_SESSION['tracks'][$i]['start']*1000);
    if ($i+1 < count($_SESSION['tracks'])){
        echo ", ";
    }
}
echo "];\n";
echo "    var tracks = [";
for ($i=0;$i<count($_SESSION['tracks']);$i++){
    echo "'".$_SESSION['tracks'][$i]['track_id']."'";
    if ($i+1 < count($_SESSION['tracks'])){
        echo ", ";
    }
}
echo "];\n";
?>

    // Called when player status changes (e.g. new track starts to play.)
	var stateHandleTimeout = null;
    player.addListener('player_state_changed', state => {
    	if (stateHandleTimeout != null){
    		clearTimeout(stateHandleTimeout);
    		stateHandleTimeout = null;
    	}
    	stateHandleTimeout = setTimeout(function() {
    		handleState(state);
        }, requestDelay);
    });
        
    function handleState(state){
    	if (state!=null) {
            console.log(state);

            //if a context uri is available
            if (state.context != null && state.context.uri != null){
            	if (state.context.uri != "<?php echo $_SESSION['playlist']->uri; ?>"){
            		//playing = false;
                	setDeviceAndSwitchContext(websdk_device_id);
                	console.log('STATE RESPONSE: switch context, REASON: Incorrect context.');
            		return;
            	}
            }
            
            var trackIndex = tracks.indexOf(state.track_window.current_track.id);
            if (trackIndex > -1) {
                if (trackIndex == curPos){
                	if (playing == false){
	                    if (state.position != start_position[curPos]){
	                    	player.seek(start_position[curPos]).then(() => { });
	                    	console.log('STATE RESPONSE: seek to position '+start_position[curPos]+', REASON: correct track, incorrect position.');
	                    }else{
	                        console.log('STATE RESPONSE: start playing the track, REASON: Correct track and correct position');
	                        player.resume().then(() => {
	                            console.log('Track resumed.');
	
	                            //Start the fade-in of the track.
	                            fadeIn();
	
	                            //Set the timer for the fade-out of the track
	                            setTimeout(function(){
	                                fadeOut();
	                                transitionUserPreview(curPos+1);
	                            }, trackDuration);
	                            
	                            //update timer progress bar
	                            startTimerBar();
	
	                            //The player is now playing. 
	                            playing = true;
	                            
	                            //reset refresh iteration
	                            refreshIter = 1;
	                            
	                            //update the visual position counter
	                            document.getElementById("track_counter").innerHTML = curPos+1;
	                        });
	                    }
                	}else{
                		console.log("STATE RESPONSE: no response, REASON: Player is already playing, waiting till the track is finished.");
                	}
                }else{
                	
                	if (trackIndex < curPos){
                    	requestNextTrack(false);
                    	console.log('STATE RESPONSE: call next track, REASON: Track id in playlist, but before the correct position. (track index: '+trackIndex+', required: '+curPos+')');
                	}else{
                    	setDeviceAndSwitchContext(websdk_device_id);
                    	console.log('STATE RESPONSE: reset context, REASON: Track id in playlist, but further than the correct position. (track index: '+trackIndex+', required: '+curPos+')');
                	}
                }
            }else{
                console.log('STATE RESPONSE: no response, REASON: Track id not found in playlist.');
            }
        }else{
        	console.log("STATE RESPONSE: no response, REASON: state is null.");
        }
    }

    player.addListener('ready', ({ device_id }) => {
        console.log('Ready with Device ID', device_id);
        websdk_device_id = device_id;
        
        //start to play the playlist
        setDeviceAndSwitchContext(device_id);
        setVolume(0);
    });

    player.addListener('not_ready', ({ device_id }) => {
        console.log('Device ID has gone offline', device_id);
    });

    // Connect to the player!
    player.connect();

    //define function to access volume control in the Spotify sdk.
    var playerVolume = 0;
    function setVolume(vol){
        /*e_fake = {
            type: \"SP_MESSAGE\",
            body: {
                topic: \"SET_VOLUME\",
                data: vol ? JSON.parse(JSON.stringify(vol)) : null
            }
        };
        Reflect.getPrototypeOf(player)._sendMessageWhenLoaded.call(player, e_fake);*/
        player.setVolume(vol).then(() => { /*console.log('volume set to: '+vol);*/});
    };

	function setDeviceAndSwitchContext(device_id){
	    console.log("AJAX Call: Setting context.");
	    var xhttp = new XMLHttpRequest();
	    xhttp.onreadystatechange = function() {
	        if (this.readyState == 4 && this.status == 200) {
	            if (this.responseText.length>0){
	                var code = Number(this.responseText.charAt(0));
	                showWarning(this.responseText.substring(2));
	                if (code == 2){
	                    setTimeout(function() {
	                        window.location.assign('app.php?p=4&playback_call=0');
	                    }, 1000);
	                    return;
	                }
	            }
	
	            //call to play the playlist context. 
	            console.log('Call to Spotify API to set the Playlist Context');
	            const play = ({
	                spotify_uri,
	                playerInstance: {
	                    _options: {
	                        getOAuthToken,
	                        id
	                    }
	                }
	            }) => {
	            	getOAuthToken(access_token => {
	            		fetch(`https://api.spotify.com/v1/me/player/play?device_id=${device_id}`, {
	            			method: 'PUT',
	            			body: JSON.stringify({ 'context_uri': spotify_uri}),//, 'offset': { 'position': curPos}
	            			headers: {
	            				'Content-Type': 'application/json',
	            				'Authorization': `Bearer ${token}`
	            			},
	            		});
	            	});
	           };
	           play({
	               playerInstance: player,
	               spotify_uri: '<?php echo $_SESSION['playlist']->uri; ?>',
	           });
	        }
	    };
	    xhttp.open('GET', 'app.php?p=4&playback_call=1', true);
	    xhttp.send();
	}

    //define function to be called when fade-in must start.
    function fadeIn() {
        //Start the fade-in of the volume.
        var volfadein = setInterval(function() {
            if (playerVolume < 1){
                playerVolume = Math.min(playerVolume + dms/totms, 1); 
                setVolume(playerVolume);
            }
            if (playerVolume >= 1){
                clearInterval(volfadein);
            }
        }, dms);
    }

    //define function to be called when fade-out must start
    function fadeOut() {
        //start the fade-out of the volume.
        var volfadeout = setInterval(function() {
            if (playerVolume > 0){
                playerVolume = Math.max(playerVolume - dms/totms, 0); 
                setVolume(playerVolume);
            }
            if (playerVolume <= 0){
                playing = false;
                clearInterval(volfadeout);
                
                //check if the playlist ended
                if (curPos +1 >= <?php echo ($_SESSION['trial_completed']?$GLOBALS['S_TRACKS_EXP']:$GLOBALS['S_TRACKS_TRIAL']); ?>){
                	console.log('Playlist ended.');
                	window.location.assign('?p=<?php echo ($_SESSION['trial_completed']?7:5)?>');
                }else{
                	requestNextTrack(true);
                	
                	//also pop-up the survey question on the transition
                	showSurvey();
            	}
           }
       }, dms);
    }

    //Send a request for the next track. 
    function requestNextTrack(increase_position){
        player.nextTrack().then(() => {console.log('Skipped to next track.');});
        if (increase_position){
        	curPos++;
        }

        //Request the next track. 
        /*var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                if (this.responseText.includes(\"Playlist Ended\")){
                    window.location.assign('?p=".($_SESSION['trial_completed']?6:5)."');
                }else{
                    if(this.responseText.length > 0){
                        showWarning(this.responseText);
                    }else{
                        clearWarning();
                    }
 
                    //Set an interval to check if keep checking if the request was successful. 
                    var nextTrackCheck = setTimeout( function(){ 
                        if (!playing){
                            console.log(\"Next track timed-out, resent request!\");
                            requestNextTrack(1, iter+1);
                        }
                    }, nextTrackTimeout*Math.pow(2, iter-1));
                }
            }
        };
        xhttp.open('GET', 'app.php?p=4&playback_call=2&repeat='+again, true);
        xhttp.send();*/
    }



    //define function to be called when the user preview metadata must change.
    function transitionUserPreview(coverpos){
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                if (this.responseText.length>0){
                     //when fade-out result is received, start fade-to animations
                     var track = this.responseText.split(';;');
                     
                     $('#track_cover_big').fadeTo(totms, 0, function() {
                         $('#track_cover_big').attr('src', track[0]);
                     }).fadeTo(totms,1);
                     
                     $('#track_title').fadeTo(totms, 0, function() {
                         $('#track_title').text(track[1]);
                     }).fadeTo(totms,1);
                     
                     $('#track_artist').fadeTo(totms, 0, function() {
                         $('#track_artist').text(track[2]);
                     }).fadeTo(totms,1);

                     if (<?php echo ($GLOBALS['S_SHOW_TRACK_METADATA']?"true":"false"); ?>){
    
                         //update chart
                         Plotly.extendTraces('metaChart', {
                        	 y: [[track[3]], 
                        		 [track[4]], 
                        		 [(track[6]=='high_valence'?1:0)]], 
                        	 text: [[''], 
                        		 [''], 
                        		 [track[1]]]
                         }, [0, 1, 2]);
                         Plotly.extendTraces('metaChart2', {
                        	 y: [[track[5]], 
                        		 [(track[7]=='high'?210:0)]], 
                        	 text: [[''], 
                        		 [track[1]]]
                         }, [0, 1]);
    
                         var nx = (track[7]=='high'?Math.min(.9, Math.max(.1, randn_bm(.5, 1))):Math.min(-.1, Math.max(-.9, randn_bm(-.5, 1))));
                         var ny = (track[6]=='high_valence'?Math.min(.9, Math.max(.1, randn_bm(.5, 1))):Math.min(-.1, Math.max(-.9, randn_bm(-.5, 1))));
                         var vpos = [(track[7]=='high'?1:-1), track[6]=='high_valence'?1:-1];
                         var apos = adjustGraphPos(vpos);
                         trace7pos += 1;
                         Plotly.extendTraces('metaChart3', {
                             x: [[trace7.x[trace7pos]]],
                             y: [[trace7.y[trace7pos]]],
                             text: [['track '+(curPos+1)]]
                         }, [1]);
                     }
                }
            }
        };
        xhttp.open('GET', 'app.php?p=4&playback_call=3&coverpos='+coverpos, true);
        xhttp.send();
    }
};