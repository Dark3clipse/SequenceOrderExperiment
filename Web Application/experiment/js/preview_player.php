<?php 
header('Content-Type: text/javascript');
?>

//audio playback script with fade in/out
	$(document).ready(function(){
		if (document.getElementById("audio_playback") != null){

			<?php 
			if ($page == 4){
			    if ($GLOBALS['S_PLAY_PREVIEWS_ONLY']==true){
			        
			        //script that plays previews
        			echo "$('#audio_playback')[0].play();\n";
                    echo "$('#audio_playback')[0].volume = 0;\n";
                    echo "$('#audio_playback').animate({volume: 1}, 2000);\n";
                    
                    echo "setTimeout(function() {\n";
                    echo "    $('#audio_playback').animate({volume: 0}, 2000);\n";
                    echo "}, 30000-2000);\n";
                    
                    echo "setTimeout(function() {\n";
                    //echo "    $('#audio_playback').attr('src', '.$track[1]['preview_url'].');\n";
                    echo "    $('#audio_source').attr('src', 'https://p.scdn.co/mp3-preview/d516cf9159df2722a3f7f8d4cb117d39c2442b2b?cid=978e736fdb1b47e48212695dbbe71692');\n";
                    
                    echo "    $('#audio_playback')[0].pause();\n";
                    echo "    $('#audio_playback')[0].load();\n";
                    echo "    $('#audio_playback')[0].oncanplaythrough = function() {\n";
                    echo "        $('#audio_playback')[0].currentTime = 0;\n";
                    echo "        $('#audio_playback')[0].play();\n";
                    //echo "        alert($('#audio_playback')[0].paused);\n";
                    echo "    };\n";
                    echo "}, 30000);\n";
                    
                    echo "setTimeout(function() {\n";
                        echo "$('#track_cover_big').fadeTo(2000, 0, function() {\n";
                        echo "	$('#track_cover_big').attr('src', '".$track[1]['cover_url']."');\n";
                        echo "}).fadeTo(2000,1);\n";
                        
            			echo "$('#track_title').fadeTo(2000, 0, function() {\n";
            			echo "  	$('#track_title').text('".$track[1]['name']."');\n";
            			echo "}).fadeTo(2000,1);\n";
            			
            			echo "$('#track_artist').fadeTo(2000, 0, function() {\n";
            			echo "  	$('#track_artist').text('".$track[1]['artists']."');\n";
            			echo "}).fadeTo(2000,1);\n";
        			echo "}, 30000-2000);\n";
			    }else{
			         //script that plays segments   
			    }
			}
			?>
		}
	});