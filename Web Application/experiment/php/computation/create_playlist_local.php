<?php 
//fetch and order the recs
$query = "SELECT r.* FROM recommendations r INNER JOIN participants p ON p.id = r.participant_id WHERE r.mood_group='low_valence' AND r.tempo_group='low' AND p.id=".$_SESSION['participant_id'];
$result = $sql->query($query);
$i=0;
if ($result->num_rows > 0){
    
    $recs00 = [];
    while($row = $result->fetch_assoc()) {
        $recs00 = array_merge($recs00, [$i => $row]);
    }
    $i++;
}
$query = "SELECT r.* FROM recommendations r INNER JOIN participants p ON p.id = r.participant_id WHERE r.mood_group='low_valence' AND r.tempo_group='high' AND p.id=".$_SESSION['participant_id'];
$result = $sql->query($query);
$i=0;
if ($result->num_rows > 0){
    
    $recs01 = [];
    while($row = $result->fetch_assoc()) {
        $recs01 = array_merge($recs01, [$i => $row]);
    }
    $i++;
}
$query = "SELECT r.* FROM recommendations r INNER JOIN participants p ON p.id = r.participant_id WHERE r.mood_group='high_valence' AND r.tempo_group='low' AND p.id=".$_SESSION['participant_id'];
$result = $sql->query($query);
$i=0;
if ($result->num_rows > 0){
    
    $recs10 = [];
    while($row = $result->fetch_assoc()) {
        $recs10 = array_merge($recs10, [$i => $row]);
    }
    $i++;
}
$query = "SELECT r.* FROM recommendations r INNER JOIN participants p ON p.id = r.participant_id WHERE r.mood_group='high_valence' AND r.tempo_group='high' AND p.id=".$_SESSION['participant_id'];
$result = $sql->query($query);
$i=0;
if ($result->num_rows > 0){
    
    $recs11 = [];
    while($row = $result->fetch_assoc()) {
        $recs11 = array_merge($recs11, [$i => $row]);
    }
    $i++;
}
$recs = [
    'mood' => [
        'low_valence' => [
            'tempo' => [
                'low' => $recs00,
                'high' => $recs01,
            ],
        ],
        'high_valence' => [
            'tempo' => [
                'low' => $recs10,
                'high' => $recs11,
            ],
        ],
    ],
];
//echo "<pre>";print_r($recs);echo "</pre>";die();

//create a playlist locally
$track = [];
$track_ids_playlist = [];
$stateCycler = new StateCycler();
$startState = $stateCycler->_state;
for ($i=0;$i<$GLOBALS['S_TRACKS_TRIAL'];$i++){
    switch($i){
        default:
        case 0:
            $dim = 'tempo';
            $stateTrial = switchState($startState, $dim);
            break;
        case 1:
            $stateTrial = switchState($startState, 'tempo');
            $stateTrial = switchState($stateTrial, 'mood');
            break;
        case 2:
            $dim = 'mood';
            $stateTrial = switchState($startState, $dim);
            break;
    }
    
    $r = getNextTrack($recs, $stateTrial);
    if ($r == null){
        break;
    }
    
    $recs = $r['recs'];
    $track = array_merge($track, [$i => $r['track']]);
    $track_ids_playlist = array_merge($track_ids_playlist, [$r['track']['track_id']]);
}
for ($i=0;$i<$GLOBALS['S_TRACKS_EXP']-$GLOBALS['S_TRACKS_TRIAL'];$i++){
    $state = $stateCycler->getNextState();
    
    //echo "<pre>";print_r($state);echo "</pre>";
    
    $r = getNextTrack($recs, $state);
    if ($r == null){
        break;
    }
    
    $recs = $r['recs'];
    $track = array_merge($track, [$i => $r['track']]);
    $track_ids_playlist = array_merge($track_ids_playlist, [$r['track']['track_id']]);
}
//die();
$_SESSION['tracks'] = $track;
$_SESSION['track_ids_playlist'] = $track_ids_playlist;

//echo "<pre>";print_r($_SESSION['track_ids_playlist']);echo "</pre>";die();

$query = "DELETE FROM transitions WHERE participant_id=".$_SESSION['participant_id'];
$sql->query($query);
for($i=$GLOBALS['S_TRACKS_TRIAL'];$i<count($track)-1;$i++){
    $track_from = $track[$i];
    $track_to = $track[$i+1];
    $query = "INSERT INTO transitions (participant_id, rec_from, rec_to, mood_from, mood_to, tempo_from, tempo_to, d_energy, d_valence, d_tempo, d_key) VALUES('".$_SESSION['participant_id']."', '".$track_from['id']."', '".$track_to['id']."', '".$track_from['mood_group']."', '".$track_to['mood_group']."', '".$track_from['tempo_group']."', '".$track_to['tempo_group']."', '".($track_to['energy']-$track_from['energy'])."', '".($track_to['valence']-$track_from['valence'])."', '".($track_to['tempo']-$track_from['tempo'])."', '".($track_to['key_tone']-$track_from['key_tone'])."')";
    $result = $sql->query($query);
    if (!$result){
        showWarning("query failed to execute: $query:   errno: $sql->errno   error: $sql->error");
    }else{
        $_SESSION['tracks'][$i]['transition_id'] =  $sql->insert_id;
    }
}
?>