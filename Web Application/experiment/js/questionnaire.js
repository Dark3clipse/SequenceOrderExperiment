function onTdClick(answer, qid){
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

function onSurveySubmit(n_question, subpage){
	var val = [];
	
	for (var j = 0; j < n_question; j++){
		var radios = document.getElementsByName('survey_'+j);
		for (var i = 0, length = radios.length; i < length; i++){
			if (radios[i].type == "radio"){
				if (radios[i].checked){
					val[j] = i+1;
					break;
				}
			}else if (radios[i].type == "text"){
				 var vtext = radios[i].value.trim();
				 if (vtext.length > 0){
					 if (!isNaN(vtext)){
						 val[j] = Number(vtext);
					 }else{
						 //must be numeric
						 showInputWarningNumeric();
						 return;
					 }
				 }else{
					 showInputWarning();
					 return;
				 }
			}
		}
	}
	if (val.length == n_question && !val.includes(undefined)){
		hideInputWarning();
		sendResults(val, subpage);
	}else{
		showInputWarning();
	}
}

function hideInputWarning(){
	document.getElementById("nonvalid").innerHTML = "Please answer all questions before submitting.";
	$('#nonvalid').fadeTo(25, 0);
}

function showInputWarning(){
	document.getElementById("nonvalid").innerHTML = "Please answer all questions before submitting.";
	$('#nonvalid').fadeTo(25, 1);
}

function showInputWarningNumeric(){
	document.getElementById("nonvalid").innerHTML = "The text input must be a number.";
	 $('#nonvalid').fadeTo(25, 1);
}

function sendResults(r, subpage){
	var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            if (this.responseText.length>0){
            	if (this.responseText.includes("Invalid Call")){
            		showWarning("syntax error while storing result.");
            		return;
            	}if (this.responseText.includes("success")){
            		if (subpage < 3){
            			location.href="app.php?p=9&sp="+(subpage+1);
            		}else{
            			location.href="app.php?p=10";
            		}
            	}else{
            		showWarning(this.responseText);
            	}
            }
        }
    };
    xhttp.open('GET', 'app.php?p=9&sp='+subpage+'&data='+JSON.stringify(r), true);
    xhttp.send();
}