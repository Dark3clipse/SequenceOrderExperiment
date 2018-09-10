<?php
function getRecommendationsLocal($api, $seed, $limit, $constraints, $sleepdur = 1){
    $options = array_merge($constraints, [
        'limit' => $limit,
        'seed_tracks' => [
            $seed,
        ],
        'market' => 'NL',
    ]);
    
    try{
        $r = $api->getRecommendations($options);
        return $r;
    } catch(Exception $e){
        if ($e->getCode() == 429){
            sleep($sleepdur);
            $sleepdur*=2;
            return getRecommendationsLocal($api, $seed, $limit, $constraints, $sleepdur);
        }
    }
}

function getAudioAnalysis($api, $track, $sleepdur = 1){
    try{
        $audio = $api->getAudioAnalysis($track);
        return $audio;
    } catch(Exception $e){
        if ($e->getCode() == 429){
            sleep($sleepdur);
            $sleepdur*=2;
            return getAudioAnalysis($api, $track, $sleepdur);
        }
    }
}

function getAudioFeatures($api, $track_ids, $sleepdur = 1){
    try{
        $f = $api->getAudioFeatures($track_ids);
        return $f;
    } catch(Exception $e){
        if ($e->getCode() == 429){
            sleep($sleepdur);
            $sleepdur*=2;
            return getAudioFeatures($api, $track_ids, $sleepdur);
        }
    }
}

function showWarning($str){
    if (!$GLOBALS['S_SHOW_WARNINGS']){
        return;
    }
    static $has_run = False;
    if (!$has_run){
        echo "</br>";
    }
    echo "<div class='warning'><a class='warning'>Warning: $str</a></div><br/>";
    $has_run = True;
}

function getRecommendations($sql, $api, $options){
    $track_id = $options['seed'];
    $limit_per_group = $options['limit_per_group'];
    
    //generate general tags
    $options_new = [];
    $gen = $options['tags_general'];
    $n = count($gen);
    for($i=0;$i<$n;$i++){
        $tag = $gen[$i];
        $name = $tag['tag'];
        if (array_key_exists('min', $tag)){
            $options_new = array_merge($options_new, ['min_'.$name => $tag['min']]);
        }
        if (array_key_exists('max', $tag)){
            $options_new = array_merge($options_new, ['max_'.$name => $tag['max']]);
        }
    }
    
    //loop over the groups
    $gen = $options['groups'];
    $n = count($gen);
    $r = [];
    for($i=0;$i<$n;$i++){
        
        //group metadata
        $tempo_group = $gen[$i]['tempo_group'];
        $mood_group = $gen[$i]['mood_group'];
        
        //generate options for this group
        $tags = $gen[$i]['tags'];
        $m = count($tags);
        for($j=0; $j<$m; $j++){
            $tag = $tags[$j];
            $name = $tag['tag'];
            if (array_key_exists('min', $tag)){
                $options_new = array_merge($options_new, ['min_'.$name => $tag['min']]);
            }
            if (array_key_exists('max', $tag)){
                $options_new = array_merge($options_new, ['max_'.$name => $tag['max']]);
            }
        }
        
        //get recommendations from Spotify
        $recs = getRecommendationsLocal($api, $track_id, $limit_per_group, $options_new);
        $Nrecs = count($recs->tracks);
        
        $track_ids = [];
        $album_covers = [];
        $artists = [];
        $names = [];
        $previews = [];
        for ($p = 0; $p < $Nrecs; $p++){
            $item = $recs->tracks[$p];
            $track_ids = array_merge($track_ids, [$item->id]);
            $names = array_merge($names, [$p => $item->name]);
            $previews = array_merge($previews, [$p => $item->preview_url]);
            if (count($item->album->images) > 0){
                $album_covers = array_merge($album_covers, [$p => $item->album->images[0]->url]);
            }else{
                $album_covers = array_merge($album_covers, ["png/default_album.png"]);
            }
            
            $Nart=count($item->artists);
            $art_str = "";
            for ($j = 0; $j<$Nart; $j++){
                $art_str = $art_str.$item->artists[$j]->name;
                if ($j+1 < $Nart){
                    $art_str = $art_str." & ";
                }
            }
            
            $artists = array_merge($artists, [$art_str]);
        }
        
        //get audio features
        $features = getAudioFeatures($api, $track_ids);
        
        //make an array of indices to be skipped based on having no audio features
        $skip_indices = [];
        for ($p = 0; $p < $Nrecs; $p++){
            $item = $features->audio_features[$p];
            
            //in case a track has no audio features
            if (!is_object($item)){
                $skip_indices = array_merge($skip_indices, [$p]);
            }
        }
        
        //extract track features
        $valence = [];
        $energy = [];
        $key = [];
        $tempo = [];
        $startpos = [];
        for ($p = 0; $p < $Nrecs; $p++){
            if (in_array($p, $skip_indices)){
                $valence = array_merge($valence, [0]);
                $energy = array_merge($energy, [0]);
                $key = array_merge($key, [0]);
                $tempo = array_merge($tempo, [0]);
                $startpos = array_merge($startpos, [0]);
                continue;
            }
            
            $item = $features->audio_features[$p];
            $valence = array_merge($valence, [$item->valence]);
            $energy = array_merge($energy, [$item->energy]);
            $key = array_merge($key, [$item->key]);
            $tempo = array_merge($tempo, [$item->tempo]);
            
            //get audio analysis
            $audio = getAudioAnalysis($api, $track_ids[$p]);
            $audio->bars = [];
            $audio->beats = [];
            $audio->tatums = [];
            $audio->segments = [];
            
            //find the section with maximum duration, within the tempo group
            $max = 0;
            $max_index = -1;
            for ($s = 0; $s < count($audio->sections); $s++){
                if ($audio->sections[$s]->duration > $max && //duration larger than previously found
                    $options_new['min_tempo'] <= $audio->sections[$s]->tempo && $options_new['max_tempo'] >= $audio->sections[$s]->tempo && // within tempo group
                    $audio->sections[$s]->start + $GLOBALS['S_SEGMENT_DURATION'] < $audio->track->start_of_fade_out){ // section has sufficient duration till end of track
                    
                    $max = $audio->sections[$s]->duration;
                    $max_index = $s;
                }
            }
            
            //if we found a suitable section
            $section_found = 0;
            if ($max_index > -1){
                $section_found = 1;
                
                //get the section data
                $section_maxduration = $audio->sections[$max_index];
                
                //override tempo and key if sufficient confidence
                if ($audio->sections[$max_index]->tempo_confidence > $GLOBALS['S_OVERRIDE_FEATURE_WITH_SEGMENT_IF_CONFIDENCE']){
                    $tempo[$p] = $audio->sections[$max_index]->tempo;
                }
                if ($audio->sections[$max_index]->key_confidence > $GLOBALS['S_OVERRIDE_FEATURE_WITH_SEGMENT_IF_CONFIDENCE']){
                    $key[$p] = $audio->sections[$max_index]->key;
                }
                
                //set the starting position
                $startpos = array_merge($startpos, [$section_maxduration->start]);
                
            //if no suitable section is found
            }else{
                //pick a random start position
                $min_start = $audio->track->end_of_fade_in;
                $max_start = $audio->track->start_of_fade_out - $GLOBALS['S_SEGMENT_DURATION'];
                $startpos = array_merge($startpos, [mt_rand($min_start, $max_start)]);
            }
        }
        
        array_push($r, []);
        for ($p = 0; $p < $Nrecs; $p++){
            if (in_array($p, $skip_indices)){
                continue;
            }
            
            array_push($r[$i], [
                    'track_id' => $track_ids[$p],
                    'album_cover' => $album_covers[$p],
                    'artists' => $artists[$p],
                    'name' => $names[$p],
                    'preview' => $previews[$p],
                    'valence' => $valence[$p],
                    'energy' => $energy[$p],
                    'key' => $key[$p],
                    'tempo' => $tempo[$p],
                    'start' => $startpos[$p],
                    'section_used' => $section_found,
            ]);
        }
    }
    
    //return an array containing the number of recommendations fetched per group
    return $r;
}

function getNextTrack($recs, $state){
    $tempo = $state['tempo'];
    $mood = $state['mood'];
    $t = $recs['mood'][$mood]['tempo'][$tempo];
    if (!is_array($t) || count($t) == 0){
        return null;
    }
    $id = rand(0, count($t)-1);
    $track = $t[$id];
    array_splice($recs['mood'][$mood]['tempo'][$tempo], $id, 1);
    
    $r = [
        'track'=> $track,
        'recs'=> $recs,
    ];
    
    return $r;
}

function switchState($state, $dimension){
    $opt_tempo = getStateOptions($dimension);
    if ($state[$dimension] == $opt_tempo[0]){
        $state[$dimension] = $opt_tempo[1];
    }else{
        $state[$dimension] = $opt_tempo[0];
    }
    return $state;
}

function getStateOptions($state){
    switch ($state){
        case 'tempo':
            return ['low', 'high'];
        case 'mood':
            return ['low_valence', 'high_valence'];
    }
}


//the current cycling direction
class StateCycler{
    public $_cycle;
    public $_state;
    public $_counter;
    
    function __construct() {
        $this->_cycle = rand(0, 1);
        $this->_state = $this->genRandomState();
        $this->_counter = -1;
    }
    
    function genRandomState(){
        $states = $this->getStates();
        $state = $states[rand(0,3)];
        return $state;
    }
    
    function switchState($state, $dimension){
        $opt_tempo = $this->getStateOptions($dimension);
        if ($state[$dimension] == $opt_tempo[0]){
            $state[$dimension] = $opt_tempo[1];
        }else{
            $state[$dimension] = $opt_tempo[0];
        }
        return $state;
    }
    
    function getNextState(){
        $i = $this->_counter;
        
        //update state after the first one
        if ($i >= 0){
            
            //update cycle direction after 12 steps
            $this->_cycle = ($this->_cycle + ($i%12==0?1:0))%2;
            
            //follow within-cycle pattern: T S C C M S C C T S M S
            $tempo = ((($i-0)%12==0?1:0) + (($i-8)%12==0?1:0))%2;
            $mood = ((($i-4)%12==0?1:0) + (($i-10)%12==0?1:0))%2;
            $cross = (($i-2)%12==0?1:0) + (($i-3)%12==0?1:0) + (($i-6)%12==0?1:0) + (($i-7)%12==0?1:0);
            $same = (($i-1)%12==0?1:0) + (($i-5)%12==0?1:0) + (($i-9)%12==0?1:0) + (($i-11)%12==0?1:0);
            
            if ($this->_cycle == 1){
                $t = $tempo;
                $tempo = $mood;
                $mood = $t;
            }
            
            //update state according to the position
            if ($tempo==1){
                $this->_state = $this->switchState($this->_state, 'tempo');
            }
            if ($mood==1){
                $this->_state = $this->switchState($this->_state, 'mood');
            }
            if ($cross==1){
                $this->_state = $this->switchState($this->_state, 'tempo');
                $this->_state = $this->switchState($this->_state, 'mood');
            }
            
            //echo "i: $i, cycle: ".$this->_cycle . ", "."tempo: ".$tempo . ", "."mood: ".$mood . ", "."cross: ".$cross . ", "."same: ".$same."<br/>";
        }
        
        $this->_counter++;
        
        return $this->_state;
    }
    
    function getStates(){
        $states = [
            '0' => [
                'tempo' => 'low',
                'mood' => 'low_valence',
            ],
            '1' => [
                'tempo' => 'low',
                'mood' => 'high_valence',
            ],
            '2' => [
                'tempo' => 'high',
                'mood' => 'low_valence',
            ],
            '3' => [
                'tempo' => 'high',
                'mood' => 'high_valence',
            ],
        ];
        return $states;
    }
    
    function getStateOptions($state){
        switch ($state){
            case 'tempo':
                return ['low', 'high'];
            case 'mood':
                return ['low_valence', 'high_valence'];
        }
    }
}


function nrand($mean, $sd){
    $x = mt_rand()/mt_getrandmax();
    $y = mt_rand()/mt_getrandmax();
    return sqrt(-2*log($x))*cos(2*pi()*$y)*$sd + $mean;
}

function sign($n) {
    return ($n > 0) - ($n < 0);
}

function outputQuestion($question, $id, $word_highlighting){
    
    switch($question['response_type']){
        default:
        case 'agreement':
            echo "            <tr>\n";
            echo "              <td colspan='7' class='questionnaire_question'><a class='".($word_highlighting?"survey_question":"questionnaire_question")."'>".$question['question']."</a></td>\n";
            echo "            </tr><tr>\n";
            echo "              <td class='questionnaire_scale_l'><a class='metadata'>Strongly disagree</a></td>\n";
            echo "              <td id='q0_$id' class='questionnaire_input' onclick=\"onTdClick(0, $id)\"><input class='questionnaire_radio' type='radio' name='survey_$id' /></td>\n";
            echo "              <td id='q1_$id' class='questionnaire_input' onclick=\"onTdClick(1, $id)\"><input class='questionnaire_radio' type='radio' name='survey_$id' /></td>\n";
            echo "              <td id='q2_$id' class='questionnaire_input' onclick=\"onTdClick(2, $id)\"><input class='questionnaire_radio' type='radio' name='survey_$id' /></td>\n";
            echo "              <td id='q3_$id' class='questionnaire_input' onclick=\"onTdClick(3, $id)\"><input class='questionnaire_radio' type='radio' name='survey_$id' /></td>\n";
            echo "              <td id='q4_$id' class='questionnaire_input' onclick=\"onTdClick(4, $id)\"><input class='questionnaire_radio' type='radio' name='survey_$id' /></td>\n";
            echo "              <td class='questionnaire_scale_r'><a class='metadata'>Strongly agree</a></td>\n";
            echo "            </tr>\n";
            break;
            
        case 'choice':
            echo "            <tr>\n";
            echo "              <td colspan='".(count($question['choice_options']))."' class='questionnaire_question'><a class='".($word_highlighting?"survey_question":"questionnaire_question")."'>".$question['question']."</a></td>\n";
            echo "            </tr><tr>\n";
            for ($i=0;$i<count($question['choice_options']);$i++){
                echo "              <td style='width: ".(100/count($question['choice_options']))."%; height: 50px;' id='q".$i."_".$id."' class='questionnaire_input' onclick=\"onTdClick($i, $id)\"><a class='metadata'>".$question['choice_options'][$i]."</a><input class='questionnaire_radio' type='radio' name='survey_$id' /></td>\n";
            }
            echo "            </tr>\n";
            break;
            
        case 'open':
            echo "            <tr>\n";
            echo "              <td colspan='3' class='questionnaire_question'><a class='".($word_highlighting?"survey_question":"questionnaire_question")."'>".$question['question']."</a></td>\n";
            echo "            </tr><tr>\n";
            echo "              <td class='questionnaire_scale_l'></td>";
            echo "              <td style='width: 20%; height: 50px;' id='q0_".$id."' class='questionnaire_input_open'><input type='text' name='survey_$id' class='questionnaire_text' /></td>\n";
            echo "              <td class='questionnaire_scale_r'></td>";
            echo "            </tr>\n";
            break;
    }
    
}
?>