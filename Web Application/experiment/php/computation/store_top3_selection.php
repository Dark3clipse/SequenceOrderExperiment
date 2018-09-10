<?php
if (!array_key_exists("t1", $_GET) || !array_key_exists("t2", $_GET) || !array_key_exists("t3", $_GET)){
    
    showWarning("top 3 selection not set.");
}else{

    //get the top tracks
    $track_top = [$_GET['t1'], $_GET['t2'], $_GET['t3']];
    
    //insert top tracks
    $query = "SELECT top_track_1 FROM participants WHERE id=".$_SESSION['participant_id'];
    $result = $sql->query($query);
    if ($result->num_rows > 0){
        while($row = $result->fetch_assoc()) {
            //if ($row['top_track_1'] == null){
                $query = "UPDATE participants SET top_track_1=$track_top[0], top_track_2=$track_top[1], top_track_3=$track_top[2] WHERE id=".$_SESSION['participant_id'];
                $sql->query($query);
            //}
        }
    }
    
    //get top-track id's
    $track_ids = [];
    for ($i=1;$i<=3;$i++){
        $query = "SELECT t.track_id FROM top_tracks t INNER JOIN participants p ON p.id = t.participant_id WHERE t.pos=p.top_track_$i AND t.participant_id=".$_SESSION['participant_id'];
        $result = $sql->query($query);
        if ($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                array_push($track_ids, $row['track_id']);
            }
        }
    }
    
    //get remaining track id's (7 not picked from selection)
    $query = "SELECT t.track_id FROM top_tracks t INNER JOIN participants p ON p.id = t.participant_id WHERE t.pos!=p.top_track_1 AND t.pos!=p.top_track_2 AND t.pos!=p.top_track_3 AND t.participant_id=".$_SESSION['participant_id'];
    $result = $sql->query($query);
    if ($result->num_rows > 0){
        while($row = $result->fetch_assoc()) {
            array_push($track_ids, $row['track_id']);
        }
    }
}
?>