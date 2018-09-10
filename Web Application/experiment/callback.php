<?php 
if($_SERVER["HTTPS"] != "on"){
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

include("php/websdk/SpotifyWebAPI.php");
include("php/websdk/SpotifyWebAPIException.php");
include("php/websdk/Session.php");
include("php/websdk/Request.php");
include 'create_session.php';
include 'mysql_connect.php';
include("globals.php");
session_start();

if ($_GET['error'] == "access_denied"){
    echo "For this experiment you are required to login to your Spotify account. Please try again.";
    echo "<script>";
    echo "setTimeout(function(){location.href='app.php?p=2'}, 4000);";
    echo "</script>";
    die();
}

// Request a access token using the code from Spotify
$session->requestAccessToken($_GET['code']);

$accessToken = $session->getAccessToken();
$refreshToken = $session->getRefreshToken();

// Store the access and refresh tokens somewhere. In a database for example.
$_SESSION['access_token'] = $accessToken;
$_SESSION['refresh_token'] = $refreshToken;
$_SESSION['relog'] = false;

$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($_SESSION['access_token']);
$_SESSION['spotify_userid'] = $api->me()->id;

//check if this is one of our premium accounts
$query = "SELECT * FROM spotify_premium WHERE spotify_id = '".$_SESSION['spotify_userid']."'";
$result = $sql->query($query);
if ($result->num_rows > 0){
    //update access tokens and timestamp
    $query = "UPDATE spotify_premium SET access_token = '$accessToken', refresh_token='$refreshToken', timestamp=UTC_TIMESTAMP() WHERE spotify_id = '".$_SESSION['spotify_userid']."'";
    $result = $sql->query($query);
    
    //check if a user is required to login to his/her own account
    if ($_SESSION['require_spotify_user_account'] == 1){
        echo "You are required to login to your own Spotify account. Please try again.";
        echo "<script>";
        echo "setTimeout(function(){location.href='app.php?p=2'}, 4000);";
        echo "</script>";
        unset($_SESSION['access_token']);
        die();
    }
    
    
    echo "Token refreshed. You can close this window and refresh the window of the experiment.";
    die();
}/*elseif ($_SESSION['require_spotify_user_account'] == 0){
    echo "You are supposed to login using one of the accounts from the table. Please retry.";
    echo "<script>";
    echo "setTimeout(function(){location.href='auth.php'}, 4000);";
    echo "</script>";
    unset($_SESSION['access_token']);
    die();
}*/

// Send the user along and fetch some data!
if (!empty($_SESSION['switching_account']) && $_SESSION['switching_account'] == true){
    echo "You can now close this window, or wait until it automatically closes.";
    die();
}else{
    //check if this user already exists in the database
    $query = "SELECT * FROM participants WHERE spotify_id = '".$_SESSION['spotify_userid']."'";
    $result = $sql->query($query);
    if ($result->num_rows > 0){
        //old participant, fetch his/her participant id and update tokens
        while($row = $result->fetch_assoc()) {
            $_SESSION['participant_id'] = $row['id'];
            $query = "UPDATE participants SET access_token = '$accessToken', refresh_token='$refreshToken', experiment_version=".$GLOBALS['S_EXPERIMENT_VERSION']." WHERE id=".$_SESSION['participant_id'];
            $sql->query($query);
            break;
        }
        
    }else{
        //new participant
        $query = "INSERT INTO participants (spotify_id, access_token, refresh_token, experiment_version) VALUES ('".$_SESSION['spotify_userid']."', '$accessToken', '$refreshToken', ".$GLOBALS['S_EXPERIMENT_VERSION'].")";
        if (!$r = $sql->query($query)){
            echo "Error: Our query failed to execute and here is why: \n";
            echo "Query: " . $query . "\n";
            echo "Errno: " . $sql->errno . "\n";
            echo "Error: " . $sql->error . "\n";
        }else{
            $_SESSION['participant_id'] = $sql->insert_id;
        }
    }
}

header('Location: app.php?p=2');
die();
?>