<?php 
if ($GLOBALS['S_FETCH_DISTRIBUTION_AND_DIE']){
    set_time_limit(5*60);
    $query = "SELECT trackid FROM top_tracks_prev";
    if ($result = $sql->query($query)){
        if ($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $track_id = $row['trackid'];
                include('rec_options_unconstrained.php');
                getRecommendations($sql, $api, $options);
            }
        }
    }
    die();
}
?>