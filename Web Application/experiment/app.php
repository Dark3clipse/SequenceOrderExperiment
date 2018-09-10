<?php 
//IMPORTANT: chrome://flags/#autoplay-policy    set to no gestures required!!

//force https connection
if($_SERVER["HTTPS"] != "on"){
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

//require '../vendor/autoload.php';
include("php/websdk/SpotifyWebAPI.php");
include("php/websdk/SpotifyWebAPIException.php");
include("php/websdk/Session.php");
include("php/websdk/Request.php");
include 'create_session.php';
include 'mysql_connect.php';
include 'functions.php';
include ("globals.php");
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(120);//max execution time


//get the current page
if (empty($_GET['p'])){
    $page = 0;
}else{
    $page = $_GET['p'];
}

//clear the session if starting at page 0
if ($page == 0){
    session_unset();
}

//get whether a relog is required
if (empty($_GET['relog'])){
    $relog = 0;
}else{
    $relog = $_GET['relog'];
    $_SESSION['return_page'] = $page;
}

//if the user requests a relog, do it
if ($GLOBALS['S_RELOG'] || $relog){
    unset($_SESSION['access_token']);
}

//check if login is required, then call for login
if (empty($_SESSION['access_token']) && $page >= 2){
    
    //check if this is an AJAX call for the login status
    if ($page == 3){
        $status = 0;
        if (!empty($_GET['status'])){
            $status = $_GET['status'];
        }
        if ($status == 1){
            echo "Logged out";
            die();
        }
    }
    
    //redirect to login
    $_SESSION['require_spotify_user_account'] = 1;
    header('Location: auth.php');
}

//check if user is already logged in
if (!empty($_SESSION['access_token'])){
    //user is succesfully logged in!
    
    //if this is the main page and the relog popup is finished in p=3, return to that page after a spotify callback.
    /*if (!empty($_SESSION['return_page'])){
        $pp = $_SESSION['return_page'];
        unset($_SESSION['return_page']);
        header("location: app.php?p=$pp");
    }*/
    
    //refresh token if session is expired
    if ($session->getTokenExpiration() == 0){
        $r = $session->refreshAccessToken($_SESSION['refresh_token']);
        $_SESSION['access_token'] = $session->getAccessToken();
        $query = "UPDATE participants SET access_token = '".$_SESSION['access_token']."' WHERE participant_id = ".$_SESSION['participant_id'];
        $sql->query($query);
    }

    //initialize the API
    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($_SESSION['access_token']);
    
    //get the user details
    $me = $api->me();
    
    //check if the previous participant is still logged in
    /*if ($page < 3){
        foreach ($GLOBALS['S_PREMIUM_USERNAME'] as $account) {
            if (strpos($me->email, $account) !== FALSE) {
                //found a playback account, redirect to relog
                header("location: app.php?p=$page&relog=1");
            }
        }
    }*/
    
    //Special page for fetching unconstrained recommendations
    include("php/computation/fetch_distribution_and_die.php");
}

//execute page-specific logic
switch($page){
    default:
        break;
        
    case 0:
        $_SESSION['switching_account'] = false;
        
        //force a relog
        if (empty($_SESSION['access_token'])){
            unset($_SESSION['access_token']);
        }
        $_SESSION['require_spotify_user_account'] = 0;
        
        $query = "SELECT * FROM spotify_premium";
        $result = $sql->query($query);
        if ($result->num_rows > 0){
            $_SESSION['premium_accounts'] = [];
            while($row = $result->fetch_assoc()) {
                array_push($_SESSION['premium_accounts'], [
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => $row['password'],
                    'spotify_id' => $row['spotify_id'],
                    'access_token' => $row['access_token'],
                    'refresh_token' => $row['refresh_token'],
                    'timestamp' => new DateTime($row['timestamp']),
                ]);
            }
        }
        //echo "<pre>";print_r($_SESSION['premium_accounts']);echo "</pre>";
        break;
        
    case 1:
        $_SESSION['switching_account'] = false;
        
        $acc_nr = 0;
        if (!empty($_GET['s'])){
            $acc_nr = $_GET['s'];
        }
        $_SESSION['spotify_account_index'] = $acc_nr;
        break;
        
    case 2:
        $_SESSION['switching_account'] = false;
        
        //unset return page variable if this is a new session
        if (!empty($_SESSION['return_page'])){
            unset($_SESSION['return_page']);
        }
        
        include("php/computation/fetch_top_10.php");
        break;
        
    //instructions for switching account.
    case 3:
        //check if this is an AJAX call for the status
        $status = 0;
        if (empty($_GET['status'])){
            $status = 0;
        }else{
            $status = $_GET['status'];
        }
        if ($status == 1){
            echo $me->display_name;
            echo ";;";
            echo $me->email;
            die();
        }
        
        //$_SESSION['switching_account'] = true;
        
        //store the chosen top 3, create a list of the 10 track id's.
        include("php/computation/store_top3_selection.php");
        
        //fetch recommendations based on the track id's
        include("php/computation/fetch_recommendations.php");
        
        //pause execution
        sleep(2);
        
        //Code for creating the local playlist
        include("php/computation/create_playlist_local.php");
        
        //create the spotify playlist
        include("php/computation/create_playlist_spotify.php");
        
        header("location: app.php?p=8");
        break;
        
    case 4:
        //get the track info
        $track = $_SESSION['tracks'];
        $playlist = $_SESSION['playlist'];
        
        //get the call type: 0 = initial page load, 1 = switch spotify connect device, 3 = load metadata, 4 store survey answers
        $playback_call = 0;
        if (!empty($_GET['playback_call'])){
            $playback_call = $_GET['playback_call'];
        }
        
        if (!empty($_GET['curpos']) && $_GET['curpos'] > 0){
            $_SESSION['jsvar_curpos'] = $_GET['curpos'];
        }else{
            $_SESSION['jsvar_curpos'] = 0;
        }
        
        //switch between the various requests coming from this page
        switch($playback_call){
            
            //initial page load
            default:
            case 0:
                $_SESSION['refresh_iter'] = 1;
                if (!empty($_GET['iter'])){
                    $_SESSION['refresh_iter'] = $_GET['iter'];
                }
                
                //fix for problem of not finding the premium accounts
                if (empty($_SESSION['premium_accounts'])){
                    switch($_SERVER['REMOTE_ADDR']){
                        default:
                            $_SESSION['spotify_account_index'] = 0;
                            break;
                        case "131.155.231.6":
                            $_SESSION['spotify_account_index'] = 1;
                            break;
                        case "131.155.238.221":
                            $_SESSION['spotify_account_index'] = 2;
                            break;
                        case "131.155.231.103":
                            $_SESSION['spotify_account_index'] = 3;
                            break;
                        case "131.155.161.184":
                            $_SESSION['spotify_account_index'] = 4;
                            break;
                    }
                }
                
                //update access token anyways
                $query = "SELECT * FROM spotify_premium";
                $result = $sql->query($query);
                if ($result->num_rows > 0){
                    $_SESSION['premium_accounts'] = [];
                    while($row = $result->fetch_assoc()) {
                        array_push($_SESSION['premium_accounts'], [
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'password' => $row['password'],
                            'spotify_id' => $row['spotify_id'],
                            'access_token' => $row['access_token'],
                            'refresh_token' => $row['refresh_token'],
                            'timestamp' => new DateTime($row['timestamp']),
                        ]);
                    }
                }
                break;
            
            //switch to the web device. Called after the Spotify Connect webplayer is initialized.
            case 1:
                
                //Store the position of the player. Set the initial position
                if ($_SESSION['trial_completed']==false){
                    $_SESSION['playlist_position'] = 0;
                }else{
                    $_SESSION['playlist_position'] = $GLOBALS['S_TRACKS_TRIAL'];
                }
                if (!empty($_GET['curpos']) && $_GET['curpos'] > 0){
                    $_SESSION['playlist_position'] = $_GET['curpos'];
                }
                
                //switch the active playback device to the Spotify web player.
                include("php/computation/switch_playback_device.php");
                
                die();
            
            case 2:
                //continue to next track in playlist. deprecated
                die();
                
            case 3:
                //echo data to display new cover and text
                include("php/computation/output_track_info.php");
                
                //increment playlist position
                $_SESSION['playlist_position'] += 1;
                die();
                
            case 4:
                //store survey answers in database
                include("php/computation/store_dyn_survey_result.php");
                die();
        }
        break;
        
    //instruction page after the trial
    case 5:
        $_SESSION['trial_completed'] = true;
        break;
        
    //forward user back to page 4, to do the experiment
    case 6:
        header('Location: app.php?p=4');
        die();
    
    //instructions after the playlist is completed
    case 7:
        
        //delete the spotify playlist
        include("php/computation/delete_playlist_spotify.php");
        break;
        
    //instructions for starting the trial. Actually between 3 and 4
    case 8:
        //set session variable indicating the trial has not yet been completed
        $_SESSION['trial_completed'] = false;
        $_SESSION['switching_account'] = false;
        break;
        
    //questionnaire
    case 9:
        //obtain the subpage
        if (empty($_GET['sp'])){
            $subpage = 0;
        }else{
            $subpage = $_GET['sp'];
        }
        
        //get data if ajax call for storing results
        if (empty($_GET['data'])){
            $data = "";
        }else{
            $data = $_GET['data'];
        }
        
        //if ajax call: store data
        if (strlen($data) > 0){
            $arr=array();
            $arr=json_decode($data);
            
            $query="SELECT * FROM questionnaire WHERE participant_id=".$_SESSION['participant_id'];
            $result = $sql->query($query);
            
            //choose between updating or inserting
            if ($result->num_rows > 0){
                $query="UPDATE questionnaire SET ";
                switch($subpage){
                    case 0:
                    default:
                        for($i=0;$i<18;$i++){
                            $query .= "msi$i = '$arr[$i]'";
                            if ($i+1<18){
                                $query.=", ";
                            }
                        }
                        break;
                        
                    case 1:
                        for($i=0;$i<15;$i++){
                            $query .= "persona$i = '$arr[$i]'";
                            if ($i+1<15){
                                $query.=", ";
                            }
                        }
                        break;
                        
                    case 2:
                        for($i=0;$i<10;$i++){
                            $query .= "bfi$i = '$arr[$i]'";
                            if ($i+1<10){
                                $query.=", ";
                            }
                        }
                        break;
                        
                    case 3:
                        $query .= "gender = '".$arr[0]."', ";
                        $query .= "age = '".$arr[1]."', ";
                        $query .= "spotifyhours = '".$arr[2]."', ";
                        $query .= "perceive_personalized = '".$arr[3]."'";
                        break;
                }
                $query.=" WHERE participant_id=".$_SESSION['participant_id'];
                echo $query;
                $result = $sql->query($query);
            }else{
                $query="INSERT INTO questionnaire (participant_id";
                switch($subpage){
                    case 0:
                    default:
                        for($i=0;$i<18;$i++){
                            $query .= ", msi$i ";
                        }
                        break;
                        
                    case 1:
                        for($i=0;$i<15;$i++){
                            $query .= ", persona$i ";
                        }
                        break;
                        
                    case 2:
                        for($i=0;$i<10;$i++){
                            $query .= ", bfi$i ";
                        }
                        break;
                    case 3:
                        $query .= "gender, age, spotifyhours, perceive_personalized ";
                        break;
                }
                $query.=") VALUES ('".$_SESSION['participant_id']."'";
                for($i=0;$i<18;$i++){
                    $query .= ", '".$arr[$i]."' ";
                }
                $query.=")";
                $result = $sql->query($query);
            }
            echo "success";
            die();
        }
        break;
        
    //end of experiment
    case 10:
        
        break;
}
?>

<!DOCTYPE html>
<html>

	<!-- document header -->
	<head>
		<title>Experiment</title>
		<link rel="stylesheet" type="text/css" href="css/style.css">
		<link rel="icon" type="image/png" href="png/favicon.png">
	</head>
	
	<!-- document body -->
	<body>
	
	<!-- Include javascript dependencies and sdk's -->
	<script src="js/jquery-3.3.1.min.js"></script>
	<script src="js/observable-slim.js"></script>
	<script src="js/functions.js"></script>
	<?php 
	
	//print the session vaiables
	echo "<script>console.log(\"SESSION VARIABLES\\n";
	ob_start();
	foreach ($_SESSION as $key=>$val){
	    if (is_array($val)){
	        echo $key. ": "."array"."\\n";
	    }else if (is_object($val)){
	        echo $key. ": "."object"."\\n";
	    }else{
	        echo $key. ": ".substr((string)$val, 0, 10)."\\n";
	    }
	    
	}
	//print_r($_SESSION);
	$result = ob_get_clean();
	echo $result;
	echo "\")</script>";
	
	//include javascript for each page
	switch($page){
	    case 0:
	        echo "    <script src=\"js/session_start.js\"></script>\n";
	        break;
	        
	    case 1:
	        echo "    <script src=\"js/test_headphone.js\"></script>\n";
	        break;
	    
	    case 2:
	        echo "    <script src=\"js/select_top3.js\"></script>\n";
	        break;
	        
	    case 3:
	        echo "    <script src=\"js/relog.js.php\"></script>\n";
	        break;
	    
	    case 4:
	       
	        //spotify sdk
	        echo "<script src=\"https://sdk.scdn.co/spotify-player.js\"></script>\n";
	       
	        //implementation of the web player
	        echo "    <script src=\"js/web_player.js\"></script>\n";
	       
	        //initialize chart
	        if ($GLOBALS['S_SHOW_TRACK_METADATA']){
	           
	            //plotly sdk
	            echo "    <script src=\"https://cdn.plot.ly/plotly-latest.min.js\"></script>\n";
	           
	            //track metadata charts
	            echo "    <script src=\"js/init_condition_charts.js.php\"></script>\n";
	        }
	       
	        //dynamic survey functionality
	        echo "    <script src=\"js/dynamic_survey.js\"></script>\n";
	        break;
	        
	    case 9:
	        echo "    <script src=\"js/questionnaire.js\"></script>\n";
	        break;
	        
	    
	}
	?>
    
    
    <!-- place for warning messages -->
    <script src="js/warning.js"></script>
	<div class='warning_js' id="war_pl" style="visibility: hidden;"><a class='warning' id='warning_text'></a></div>
	
	<!-- Start of content -->
        <?php 
        switch($page){
            //session setup
            default:
            case 0:
                echo "<h1>Experiment Session Setup</h1><br/>";
                echo "<form>";
                echo "<div id='textbox'>";
                echo "<p>Please select the Spotify Premium account used in this session. Make sure there is a valid token with sufficient time left. Each token is valid for 1 hour. The password for each account is: <b>hidden</b>.</p>";
                
                echo "<table>";
                echo "  <tr class='premium_header'>";
                echo "    <td class='premium_cell'><a>Name</a></td>";
                echo "    <td class='premium_cell'><a>Email</a></td>";
                echo "    <td class='premium_cell'><a>Status</a></td>";
                echo "    <td class='premium_cell'><a>Actions</a></td>";
                echo "  </tr>";
                $curtime = new DateTime(date("Y-m-d H:i:s"), new DateTimeZone('UTC'));
                
                function dateIntervalToSeconds($dateInterval){
                    $reference = new DateTimeImmutable;
                    $endTime = $reference->add($dateInterval);
                    return $endTime->getTimestamp() - $reference->getTimestamp();
                }
                
                for($i=0;$i<count($_SESSION['premium_accounts']);$i++){
                    echo "  <tr class='premium_row'>";
                    echo "    <td class='premium_cell'><a>".$_SESSION['premium_accounts'][$i]['name']."</a></td>";
                    echo "    <td class='premium_cell'><a>".$_SESSION['premium_accounts'][$i]['email']."</a></td>";
                    
                    //status
                    echo "    <td class='premium_cell'>";
                    $interval = $curtime->diff($_SESSION['premium_accounts'][$i]['timestamp']);
                    $hours = $interval->h + ($interval->days*24);
                    if (abs($hours) >= 1){
                        echo "<a style='color:red'>Token expired</a>";
                    }else{
                        echo "<a style='color:green' id='ctdn_$i'>Token valid ".$interval->format('(since: %i m %s s)')."</a>";
                    }
                    echo "</td>";
                    
                    //actions
                    echo "    <td class='premium_cell'>";
                    echo "<form>";
                    echo "<input type='button' class='but_action' value='refresh token' onclick='refreshPremiumToken()' id='acc".$i."_refresh' />";
                    if (abs($hours) >= 1){
                        //echo "<input type='button' class='but_action' value='select' onclick=\"onSubmit($i)\" disabled />";
                    }else{
                        echo "<input type='button' class='but_action' value='select' onclick=\"onSubmit($i)\" id='ctdn_b_$i'/>";
                        $seconds = -dateIntervalToSeconds($interval);
                        echo "<script>startCountdown('ctdn_$i', 'ctdn_b_$i', $seconds);</script>";
                    }
                    echo "</td>";
                    echo "  </tr>";
                }
                echo "</table>";
                
                /*echo "<select id='spotify_username' style='margin-left:15px;'>";
                for ($i=0;$i<count($GLOBALS['S_PREMIUM_USERNAME']);$i++){
                    echo "<option value='$i'>".$GLOBALS['S_PREMIUM_NAME'][$i]."</option>";
                }
                echo "</select>";*/
                echo "</div>";
                
                echo "<table class='btn_display'><tr>";
                echo "<th class='btn_right'>";
                echo "</th><th class='btn_left'>";
                echo "<div></div>";
                echo "</th>";
                echo "</tr></table>";
                echo "</form>";
                break;
            
            //instructions
            case 1:
                echo "<h1>Experiment instructions</h1><br/>";
                echo "<div id='textbox'>";
                echo "<p>";
                    echo "Welcome to our experiment. ";
                echo "</p><p>";
                    echo "Please follow the instructions provided on the screen carefully. ";
                    echo "If the instructions are unclear to you, please ask the researcher for help. ";
                    echo "Please do not talk, exclaim, or try to communicate with other participants during the study.";
                echo "</p><p>";
                    echo "When you are finished with the experiment, please remove your headphone and return to the researcher. ";
                echo "</p><p>";
                    echo "Please click the button below to listen to an audio file to make sure your headphone works as intended. If you cannot hear the audio, please ask the researcher for help. ";
                    echo "After you have verified that you can hear the sound of the headphone, please press 'Continue'.";
                echo "</p>";
                echo "</div>";
                
                echo "<br/>";
                
                echo "<form>";
                echo "<table class='btn_display'><tr>";
                echo "<th class='btn_right'>";
                echo "<div><input type='button' class='but_action' value='Test Headphone' onclick='playTestAudio();' /></div>";
                echo "</th><th class='btn_left'>";
                echo "<div id='btn_continue'><input type='button' class='but_action' value='Continue' onclick=\"location.href='?p=2'\" /></div>";
                echo "</th>";
                echo "</tr></table>";
                echo "</form>";
                break;
            
            //choose track page
            case 2:
                echo "<h1>Choose your favorite three songs:</h1><br/>";
                
                //print top tracks
                echo "<table id='track_select'><tr id='track_row'>";
                for ($i = 0; $i < $Ntop_received; $i++){
                    if ($i%5 == 0){
                        echo "</tr><tr id='track_row'>";
                    }
                    //get the item
                    $item = $top10->items[$i];
                    
                    //compose artists string
                    $Nart=count($item->artists);
                    $art_str = "";
                    for ($j = 0; $j<$Nart; $j++){
                        $art_str = $art_str.$item->artists[$j]->name;
                        if ($j+1 < $Nart){
                            $art_str = $art_str." & ";
                        }
                    }
                    
                    
                    //display track
                    echo "<th id='track_col'>";
                    echo "<div class='track_wrapper' id='track_wrapper_$i' onclick=\"selectTrack($i);\">";
                    echo "<img id='track_cover' src='".$item->album->images[1]->url."' /><br/>";
                    echo "<a class='track_title'>$item->name</a><br/>";
                    echo "<a class='track_artist'>$art_str</a>";
                    echo "</div>";
                    echo "</th>";
                }
                echo "</tr></table>";
                
                //Continue button
                echo "<form>";
                echo "<table class='btn_display'><tr>";
                echo "<th class='btn_right'>";
                echo "<div><input type='button' id='but_top3reset' value='Reset selection' class='but_action' onclick='resetSelection();' /></div>";
                echo "</th><th class='btn_left'>";
                echo "<div id='btn_sel_tracks'><input id='but_top3cont' type='button' class='but_action' value='Continue' onclick=\"finishSelection();\" /></div>";
                echo "</th>";
                echo "</tr></table>";
                echo "</form>";
                
                //echo "<pre>";print_r($top10);echo "</pre>";
                break;
                
            case 8:
                echo "<h1>Instructions</h1><br/>";
                
                echo "<div id='textbox'>";
                echo "<p>";
                echo "In the next part of the experiment, you will listen to several songs. ";
                echo "The songs will be played automatically. ";
                echo "</p><p>";
                echo "While the songs are playing, questions will appear regularly. ";
                echo "Please answer the questions as good as you can, but do not wait too long before answering them. A progress bar will show you the time you have for answering the questions.";
                echo "</p><p>";
                echo "Please press 'continue' to proceed to an example. ";
                echo "</p>";
                echo "</div>";
                
                echo "<form>";
                echo "<table class='btn_display'><tr>";
                echo "<th class='btn_right'>";
                echo "</th><th class='btn_left'>";
                echo "<div><input type='button' value='Continue' class='but_action' onclick=\"location.href='?p=4'\" /></div>";
                echo "</th>";
                echo "</tr></table>";
                echo "</form>";
                break;
                
            case 4:
                $p = $_SESSION['trial_completed']?$GLOBALS['S_TRACKS_TRIAL']:0;
                echo "<h1>".($_SESSION['trial_completed']?"Experiment":"Trial")."</h1><br/>";
                echo "<table id='player'>";
                echo "  <tr>";
                echo "    <th id='player_trackinfo'>";
                echo "      <div id='track_wrapper_play' >";
                echo "        <p id='current'>Currently playing</p>";
                echo "        <a class='current_sub'>track </a><a class='current_sub' id='track_counter'>1</a><a class='current_sub'> of ".$GLOBALS['S_TRACKS_EXP']."</a>";
                echo "        <img id='track_cover_big' src='".$track[$p]['cover_url']."' /><br/>";
                echo "        <a id='track_title' class='track_title'>".$track[$p]['name']."</a><br/>";
                echo "        <a id='track_artist' class='track_artist'>".$track[$p]['artists']."</a>";
                echo "      </div>";
                echo "    </th>";
                echo "    <th id='player_survey'>";
                echo "      <div id='survey_wrapper'>\n";
                echo "        <form>\n";
                echo "          <table>\n";
                
                include("php/surveys/dynamic.php");
                for($i=0;$i<count($question);$i++){
                    outputQuestion($question[$i], $i, true);
                }
                
                echo "            <tr>\n";
                echo "              <td colspan='7' style='text-align: right;padding: 10px;'>\n";
                echo "                <table style='width: 100%;'><tr><td>";
                echo "                <div id='survey_timer'>\n";
                echo "                  <div id='survey_timer_bar'></div>\n";
                echo "                </div>\n";
                echo "                </td></tr><tr><td style='padding-top: 10px;'>";
                echo "                <a id='nonvalid'>Please answer all questions before submitting.</a>\n";
                echo "                <input type='button' onclick='onSurveySubmit();' class='but_action' id='but_dynsubmit' value='submit' style='margin-right: 10px;'/>\n";
                echo "                </td></tr></table>";
                echo "              </td>\n";
                echo "            </tr>\n";
                echo "          </table>\n";
                echo "        </form>\n";
                echo "      </div>\n";
                echo "    </th>";
                echo "  </tr>";
                echo "  <tr>";
                echo "    <th colspan='2' id='player_survey'>";
                if ($GLOBALS['S_SHOW_TRACK_METADATA']){
                    echo "      <div id='track_wrapper_meta'>\n";
                    echo "        <p>Transition information:</p>\n";
                    echo "        <table>\n";
                    echo "          <tr>\n";
                    echo "            <td><div id='metaChart'></div></td>\n";
                    echo "            <td rowspan='2'><div id='metaChart3'></div></td>\n";
                    echo "          </tr><tr>\n";
                    echo "            <td><div id='metaChart2'></div></td>\n";
                    echo "          </tr>\n";
                    echo "        </table>\n";
                    echo "      </div>\n";
                }
                echo "    </th>";
                echo "  </tr>";
                echo "</table>";
                //if playing previews, create the audioplayer, otherwise we use Spotify's player
                if ($GLOBALS['S_PLAY_PREVIEWS_ONLY']==true){
                    echo "<audio id='audio_playback'>";
                    echo "<source id='audio_source' src='".$track[$p]['preview_url']." type='audio/mp3' />";
                    echo "</audio>";
                }
                
                
                break;
                
            case 5:
                echo "<h1>Instructions</h1><br/>";
                
                echo "<div id='textbox'>";
                echo "<p>";
                echo "You have just completed a trial that demonstrates how the experiment will look like. ";
                echo "The remainder of this experiment will continue in a similar fashion. ";
                echo "</p><p>";
                echo "Please keep in mind that the focus of the questions is on the 'flow' of the songs, ";
                echo "and not on the individual songs themselves. ";
                echo "</p><p>";
                echo "If the instructions or questions were unclear to you, please raise your hand and wait for an experimenter to come to you. ";
                echo "Please do not talk, exclaim, or try to communicate with other participants during the study.";
                echo "</p><p>";
                echo "Please press 'continue' to proceed with the experiment. ";
                echo "</p>";
                echo "</div>";
                
                echo "<form>";
                echo "<table class='btn_display'><tr>";
                echo "<th class='btn_right'>";
                echo "</th><th class='btn_left'>";
                echo "<div><input type='button' value='Continue' class='but_action' onclick=\"location.href='?p=6'\" /></div>";
                echo "</th>";
                echo "</tr></table>";
                echo "</form>";
                break;
                
            case 7:
                echo "<h1>Instructions</h1><br/>";
                echo "<div id='textbox'>";
                echo "<p>";
                echo "The playlist has now been completed.  ";
                echo "The remainder of the experiment consists of a number of questionnaires. ";
                echo "Please take your time to answer the questions carefully and as correctly as possible. ";
                echo "</p><p>";
                echo "Press 'continue' to proceed to the questionnaires. ";
                echo "</p>";
                echo "</div>";
                
                echo "<form>";
                echo "<table class='btn_display'><tr>";
                echo "<th class='btn_right'>";
                echo "</th><th class='btn_left'>";
                echo "<div><input type='button' class='but_action' value='Continue' onclick=\"location.href='?p=9'\" /></div>";
                echo "</th>";
                echo "</tr></table>";
                echo "</form>";
                break;
                
            //page used for switching Spotify account
            case 3:
                echo "<h1>Change Spotify account</h1><br/>";
                echo "<form>";
                echo "<table>";
                echo "<tr>";
                echo "<td style='width: 40%;'>";
                echo "<div id='textbox_left'>";
                echo "<p>";
                echo "You are now required to logout with you Spotify account and continue with one of the experimenter's accounts. ";
                echo "To do this, please follow the instructions on this page. ";
                echo "</p>";
                echo "<p class='instruction_heading'>Step 1</p>";
                echo "<a class='instruction'>Press the switch user button. A pop-up will appear prompting for Spotify login.</a>";
                echo "<div><input id='but_switch' type='button' value='Switch user' onclick=\"forceRelog()\" /></div>";
                
                echo "<p class='instruction_heading'>Step 2</p>";
                echo "<a class='instruction'>Click on the \"Jij niet\" area.</a><br/>";
                echo "<img class='instruction' src='png/instruction_1.png' />";
                
                echo "<p class='instruction_heading'>Step 3</p>";
                echo "<a class='instruction'>Click on the \"Bestaand Spotify account\" button.</a><br/>";
                echo "<img class='instruction' src='png/instruction_2.png' />";
                
                echo "<p class='instruction_heading'>Step 4</p>";
                echo "<a class='instruction'>Fill in the following username and password and click the login button:</a><br/><br/>";
                echo "<table><tr>";
                echo "<td><a class='instruction'>Username: </a></td><td><a class='credential' style='margin-left:5px;'>".$GLOBALS['S_PREMIUM_USERNAME'][$_SESSION['spotify_account_index']]."</a></td>";
                echo "</tr><tr>";
                echo "<td><a class='instruction'>Password: </a></td><td><a class='credential' style='margin-left:5px;'>".$GLOBALS['S_PREMIUM_PASSWORD']."</a></td>";
                echo "</tr></table>";
                echo "<img class='instruction' src='png/instruction_3.png' />";
                
                echo "<p class='instruction_heading'>Step 4</p>";
                echo "<a class='instruction'>Check whether you are logged in as 'participant'. Then click the 'ok' button.</a><br/>";
                echo "<img class='instruction' src='png/instruction_4.png' />";
                
                echo "</div>";
                echo "</form>";
                
                echo "</td>";
                
                echo "<td style='width: 20%; vertical-align:top;'>";
                echo "<div id='textbox_center'>";
                echo "<p>Currently logged in as: </p>";
                echo "<a class='spotify_status' id='login_status_name'>".(empty($me)?"-":$me->display_name)."<a><br/>";
                echo "<a class='spotify_status_mail' id='login_status_mail'>".(empty($me)?"-":$me->email)."<a><br/>";
                echo "<p>Current status: </p>";
                if ($me->email == $GLOBALS['S_PREMIUM_USERNAME'][$_SESSION['spotify_account_index']]){
                    echo "<a class='spotify_status' id='login_status' style='color: green;'>Finished<a><br/>";
                }else{
                    echo "<a class='spotify_status' id='login_status' style='color: red;'>Wrong user<a><br/>";
                }
                echo "</div>";
                echo "<div id='textbox_center' style='margin-top:25px'>";
                echo "<p>Credentials: </p>";
                echo "<table><tr>";
                echo "<td><a class='instruction'>Username: </a></td><td><a class='credential' style='margin-left:5px;'>".$GLOBALS['S_PREMIUM_USERNAME'][$_SESSION['spotify_account_index']]."</a></td>";
                echo "</tr><tr>";
                echo "<td><a class='instruction'>Password: </a></td><td><a class='credential' style='margin-left:5px;'>".$GLOBALS['S_PREMIUM_PASSWORD']."</a></td>";
                echo "</tr></table>";
                echo "</div>";
                echo "</td>";
                echo "<td style='width: 40%;'></td></tr></table>";
                break;
                
            case 9:
                echo "<h1>Questionnaire</h1><br/>";
                echo "<div id='textbox'>";
                echo "      <div id='questionnaire_wrapper'>\n";
                echo "        <form>\n";
                
                //obtain the subpage
                if (empty($_GET['sp'])){
                    $subpage = 0;
                }else{
                    $subpage = $_GET['sp'];
                }
                
                switch($subpage){
                    default:
                    case 0:
                        include("php/surveys/msi.php");
                        break;
                        
                    case 1:
                        include("php/surveys/persona.php");
                        break;
                        
                    case 2:
                        include("php/surveys/personality.php");
                        break;
                        
                    case 3:
                        include("php/surveys/demographics.php");
                        break;
                }
                for($i=0;$i<count($question);$i++){
                    echo "          <table class='questionnaire_table'>\n";
                    outputQuestion($question[$i], $i, false);
                    echo "          </table>\n";
                }
                echo "          <table class='questionnaire_table'>\n";
                echo "            <tr>\n";
                echo "              <td colspan='7' style='text-align: right;padding: 10px;'>\n";
                echo "                <table style='width: 100%;'>";
                echo "                  <tr><td style='padding-top: 10px;'>";
                echo "                      <a id='nonvalid'>Please answer all questions before submitting.</a>\n";
                echo "                      <input type='button' onclick='onSurveySubmit(".count($question).", $subpage);' value='Continue' id='but_continue'/>\n";
                echo "                  </td></tr></table>";
                echo "              </td>\n";
                echo "            </tr>\n";
                echo "          </table>\n";
                echo "        </form>\n";
                echo "      </div>\n";
                echo "</div>";
                break;
                
            case 10:
                echo "<h1>End of experiment</h1><br/>";
                echo "<div id='textbox'>";
                echo "<p>";
                echo "This concludes the experiment. Thank you for your participation.  ";
                echo "You may now leave the room and report to the experimenter. ";
                echo "</p>";
                echo "</div>";
                break;
        }
        
        ?>
    </body>
</html>