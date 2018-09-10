function showWarning(str){
	document.getElementById("war_pl").style.visibility = 'visible';
	document.getElementById("warning_text").innerHTML = "Warning: "+str;
}
function clearWarning(){
	document.getElementById("war_pl").style.visibility = 'hidden';
	document.getElementById("warning_text").innerHTML = "";
}