<?php 
//Fetch the top tracks
$Ntop = 10;
$top10 = $api->getMyTop("tracks", array("limit"=>$Ntop));
$Ntop_received = count($top10->items);

//Store in database if not yet stored
$query = "SELECT t.* FROM top_tracks t INNER JOIN participants p ON p.id = t.participant_id WHERE t.participant_id=".$_SESSION['participant_id'];
if (!$result = $sql->query($query)){
    echo "Error: Our query failed to execute and here is why: \n";
    echo "Query: " . $query . "\n";
    echo "Errno: " . $sql->errno . "\n";
    echo "Error: " . $sql->error . "\n";
}else{
    if ($result->num_rows > 0){
        while($row = $result->fetch_assoc()) {
            //echo "pos: " . $row["pos"]. ", track id: " . $row["track_id"]."<br>";
        }
    }else{
        //db is empty, store results
        for ($i = 0; $i < $Ntop_received; $i++){
            $item = $top10->items[$i];
            //echo "<pre>";print_r($item->id);echo "</pre>";
            $query = "INSERT INTO top_tracks VALUES('', '".$_SESSION['participant_id']."', '$i', '".$item->id."')";
            $sql->query($query);
        }
    }
}
?>