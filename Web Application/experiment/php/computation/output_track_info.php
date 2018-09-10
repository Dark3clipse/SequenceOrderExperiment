<?php 
//$next = $_SESSION['playlist_position']+1;
$next = $_GET['coverpos'];
echo $_SESSION['tracks'][$next]['cover_url'];
echo ";;";
echo $_SESSION['tracks'][$next]['name'];
echo ";;";
echo $_SESSION['tracks'][$next]['artists'];
echo ";;";
echo $_SESSION['tracks'][$next]['valence'];
echo ";;";
echo $_SESSION['tracks'][$next]['energy'];
echo ";;";
echo $_SESSION['tracks'][$next]['tempo'];
echo ";;";
echo $_SESSION['tracks'][$next]['mood_group'];
echo ";;";
echo $_SESSION['tracks'][$next]['tempo_group'];
if (($_SESSION['playlist_position'] >= $GLOBALS['S_TRACKS_TRIAL'] && $_SESSION['trial_completed']==false) ||
    ($_SESSION['playlist_position'] >= $GLOBALS['S_TRACKS_EXP']   && $_SESSION['trial_completed']==true)){
        echo ";;";
        echo "-";
        echo ";;";
        echo "-";
        echo ";;";
        echo "-";
        echo ";;";
        echo "-";
        echo ";;";
        echo "-";
}else{
    echo ";;";
    echo $_SESSION['tracks'][$next+1]['valence'];
    echo ";;";
    echo $_SESSION['tracks'][$next+1]['energy'];
    echo ";;";
    echo $_SESSION['tracks'][$next+1]['tempo'];
    echo ";;";
    echo $_SESSION['tracks'][$next+1]['mood_group'];
    echo ";;";
    echo $_SESSION['tracks'][$next+1]['tempo_group'];
}
?>