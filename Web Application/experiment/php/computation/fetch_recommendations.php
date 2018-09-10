<?php 
//fetch recommendations based on the chosen tracks
$query = "SELECT r.* FROM recommendations r INNER JOIN participants p ON p.id = r.participant_id WHERE r.participant_id=".$_SESSION['participant_id'];
$result = $sql->query($query);
$limit_group = (($GLOBALS['S_TRACKS_EXP']-$GLOBALS['S_TRACKS_TRIAL']-1) / $GLOBALS['S_GROUPS']) + 1;
$n_invalid = 0;
if ($result->num_rows <= 0 || $GLOBALS['S_REFETCH_RECS']){
    
    //create empty array holding the recs
    $r = [[],[],[],[]];
    
    //empty aray holding track id's to avoid duplicates
    $r_list = [];
    
    //get recommendations for the remaining tracks, skipping groups that are full
    for ($i=0;$i<count($track_ids);$i++){
        $track_id = $track_ids[$i];
        
        //do this for each group separately, only fetching how many we need
        $executed = false;
        for($j=0;$j<4;$j++){
            $limit = $limit_group - count($r[$j]);
            if ($limit == 0){
                continue;
            }
            if ($GLOBALS['S_CONSTRAIN_RECS']){
                include('rec_options.php');
            }else{
                include('rec_options_unconstrained.php');
            }
            
            $options['groups'] = [$options['groups'][$j]];
            //echo "<pre>";print_r($options);echo "</pre>";
            
            $executed = true;
            
            $newrec = getRecommendations($sql, $api, $options)[0];
            //echo "<pre>";print_r($newrec);echo "</pre>";die();
            
            //get the boundaries
            $val_min = $options['groups'][0]['tags'][0]['min'];
            $val_max = $options['groups'][0]['tags'][0]['max'];
            $ene_min = $options['groups'][0]['tags'][1]['min'];
            $ene_max = $options['groups'][0]['tags'][1]['max'];
            $tem_min = $options['groups'][0]['tags'][2]['min'];
            $tem_max = $options['groups'][0]['tags'][2]['max'];
            
            for($k=0;$k<count($newrec);$k++){
                
                //check if we already have this track in our recommendations
                if (in_array($newrec[$k]['track_id'], $r_list)){
                    //if so, continue
                    continue;
                }
                
                //check if spotify listened to what we asked for
                if ($newrec[$k]['valence'] >= $val_min && $newrec[$k]['valence'] <= $val_max &&
                    $newrec[$k]['energy']  >= $ene_min && $newrec[$k]['energy']  <= $ene_max &&
                    $newrec[$k]['tempo']   >= $tem_min && $newrec[$k]['tempo']   <= $tem_max){
                        
                        //if so, add to track list
                        array_push($r[$j], $newrec[$k]);
                        
                        //also add to array meant for checking duplicates
                        array_push($r_list, $newrec[$k]['track_id']);
                }else{
                    //echo "invalid track received. at i=$i, k=$k. <br/>";
                    $n_invalid += 1;
                }
            }
        }
        
        if (!$executed){
            //execution finished at $i-1
            $query = "UPDATE participants SET seeds_needed = $i, invalid_recs=$n_invalid WHERE id=".$_SESSION['participant_id'];
            $result = $sql->query($query);
            //echo "finished at i=".($i-1);
            break;
            
            //if iteration ends and we are not filled up
        }elseif ($i+1 >= count($track_ids)){
            //echo "not yet full at i=$i, fetching 10 random tracks...<br/>";
            
            //select 10 random tracks
            $query = "SELECT trackid FROM top_tracks_prev ORDER BY RAND() LIMIT 10";
            $result = $sql->query($query);
            if ($result->num_rows > 0){
                while($row = $result->fetch_assoc()) {
                    array_push($track_ids, $row['trackid']);
                }
            }
        }
    }
    
    //echo "<pre>";print_r($r_list);echo "</pre>";
    
    //send the recs to the database
    if ($GLOBALS['S_REFETCH_RECS']){
        $query = "DELETE FROM recommendations WHERE participant_id=".$_SESSION['participant_id'];
        $sql->query($query);
    }
    include('rec_options.php');
    for($k=0;$k<count($r);$k++){
        for ($p = 0; $p < count($r[$k]); $p++){
            $mood_group = $options['groups'][$k]['mood_group'];
            $tempo_group = $options['groups'][$k]['tempo_group'];
            $query = "INSERT INTO recommendations VALUES('', '".$_SESSION['participant_id']."', '".$r[$k][$p]['track_id']."', \"".str_replace("\"","",$r[$k][$p]['name'])."\", '".$r[$k][$p]['preview']."', '".$r[$k][$p]['album_cover']."', \"".str_replace("\"","",$r[$k][$p]['artists'])."\", '".$r[$k][$p]['valence']."', '".$r[$k][$p]['energy']."', '".$r[$k][$p]['tempo']."', '".$r[$k][$p]['key']."', '".$r[$k][$p]['start']."', '".$r[$k][$p]['section_used']."', '$mood_group', '$tempo_group')";
            $result = $sql->query($query);
            if (!$result){
                showWarning("query failed to execute: $query:   errno: $sql->errno   error: $sql->error");
            }
        }
    }
}
?>