<?php 
if (empty($_GET['v1']) || empty($_GET['v2']) || empty($_GET['v3']) || empty($_GET['curpos'])){
    echo "Invalid Call";
    die();
}
$val = [$_GET['v1']-3, $_GET['v2']-3, $_GET['v3']-3];

//get position and transition_id
//$p = $_SESSION['playlist_position']-1;
$p = $_GET['curpos']-1;
if (!array_key_exists('transition_id', $_SESSION['tracks'][$p])){
    if ($_SESSION['trial_completed'] == true){
        echo "Current position for survey submit is invalid.";
    }
    die();
}
$tid = $_SESSION['tracks'][$p]['transition_id'];

$query = "UPDATE transitions SET survey_1=".$val[0].", survey_2=".$val[1].", survey_3=".$val[2]." WHERE id=$tid";
$sql->query($query);
?>