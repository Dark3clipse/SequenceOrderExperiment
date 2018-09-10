function onTdClick(answer, qid){
	if (survey_visible){
		var radios = document.getElementsByName('survey_'+qid);
		for (var i = 0, length = radios.length; i < length; i++){
			radios[i].checked = false;
			document.getElementById("q"+i+"_"+qid).className = "questionnaire_input";
			
			if (answer == i){
				radios[i].checked = true;
				document.getElementById("q"+i+"_"+qid).className = "questionnaire_input_s";
			}
		}
	}
}

function onSurveySubmit(){
	var val = [null, null, null];
	
	for (var j = 0; j < 3; j++){
		var radios = document.getElementsByName('survey_'+j);
		for (var i = 0, length = radios.length; i < length; i++){
			document.getElementById("q"+i+"_"+j).className = "questionnaire_input";
			if (radios[i].checked){
				val[j] = i+1;
				//radios[i].checked = false;
				break;
			}
		}
	}
	if (val[0] != null && val[1] != null && val[2] != null){
		//alert('chosen '+val);
		hideSurvey();
		sendResults(val);
	}else{
		$('#nonvalid').fadeTo(25, 1);
	}
}

var survey_visible = false;
function hideSurvey(){
	//$('#survey_wrapper').fadeTo(400, 0);
	$('#nonvalid').fadeTo(25, 0);
	survey_visible = false;
	
	var qs = document.getElementsByClassName("survey_question");
	for (i = 0; i < qs.length; i++) {
		qs[i].style.color = "grey";
	}
	
	for (var j = 0; j < 3; j++){
		var radios = document.getElementsByName('survey_'+j);
		for (var i = 0, length = radios.length; i < length; i++){
			document.getElementById("q"+i+"_"+j).className = "questionnaire_input_d";
			if (radios[i].checked){
				radios[i].checked = false;
			}
			radios[i].disabled = true;
		}
	}
	
	document.getElementById("but_dynsubmit").disabled = true;
}

function showSurvey(){
	$('#survey_wrapper').fadeTo(400, 1);
	document.getElementById("survey_timer_bar").style.width = 0;
	survey_visible = true;
	
	var qs = document.getElementsByClassName("survey_question");
	for (i = 0; i < qs.length; i++) {
		qs[i].style.color = "navy";
	}
	
	for (var j = 0; j < 3; j++){
		var radios = document.getElementsByName('survey_'+j);
		for (var i = 0, length = radios.length; i < length; i++){
			document.getElementById("q"+i+"_"+j).className = "questionnaire_input";
			radios[i].checked = false;
			radios[i].disabled = false;
		}
	}
	
	document.getElementById("but_dynsubmit").disabled = false;
}

function startTimerBar(){
	if (survey_visible){
		var elem = document.getElementById("survey_timer_bar"); 
	    var width = 0;
	    var interval = 10;
	    var timer_bar_int = setInterval(frame, interval);
	    var r = 0;
	    var g = 0;
	    var b = 128;
	    function frame() {
	        if (width >= 100) {
	            clearInterval(timer_bar_int);
	        } else {
	            width += (interval / trackDuration)*100;
	            width = Math.min(width, 100);
	            
	            if (width > 65 && width < 80){
	            	r = Math.round(    ((width-65)/15)*255   );
	            	b = Math.round(128 - ((width-65)/15)*128  );
	            }
	            
	            elem.style.width = width + '%'; 
	            if (survey_visible){
	            	elem.style.backgroundColor = rgbToHex(r, g, b);
	            }else{
	            	elem.style.backgroundColor = "#505050";
	            }
	        }
	    }
	}
	
	//reset the form
	for (var j = 0; j < 3; j++){
		var radios = document.getElementsByName('survey_'+j);
		for (var i = 0, length = radios.length; i < length; i++){
			document.getElementById("q"+i+"_"+j).className = "questionnaire_input";
			if (radios[i].checked){
				radios[i].checked = false;
				break;
			}
		}
	}
	$('#nonvalid').fadeTo(25, 0);
}

function componentToHex(c) {
    var hex = c.toString(16);
    return hex.length == 1 ? "0" + hex : hex;
}

function rgbToHex(r, g, b) {
    return "#" + componentToHex(r) + componentToHex(g) + componentToHex(b);
}

function sendResults(r){
	var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            if (this.responseText.length>0){
            	if (this.responseText.includes("Invalid Call")){
            		showWarning("syntax error while storing result.");
            		return;
            	}else{
            		showWarning(this.responseText);
            	}
            }
        }
    };
    xhttp.open('GET', 'app.php?p=4&playback_call=4&v1='+r[0]+'&v2='+r[1]+'&v3='+r[2]+'&curpos='+curPos, true);
    xhttp.send();
}