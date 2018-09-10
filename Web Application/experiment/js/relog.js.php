<?php
session_start();
include ("../globals.php");
header('Content-Type: text/javascript');
?>

var myWindow;
function forceRelog(){
	//document.getElementById("frame_relog").setAttribute("src", "app.php?p=9&relog=1");
	myWindow = window.open("app.php?p=3&relog=1", 
			"_black", 
			"height=800,width=800,location=no,menubar=no,left="+(screen.width-800)+",top="+((screen.height-800)/2));
}

function checkStatus(){
	var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            if (this.responseText.length>0){
            	if (this.responseText.includes("Logged out")){
            		document.getElementById("login_status_name").innerHTML = "";
            		document.getElementById("login_status_mail").innerHTML = "";
            		document.getElementById("login_status").innerHTML = "Logged out";
            		document.getElementById("login_status").style.color = "grey";
            	}else{
            		var user = this.responseText.split(';;');
            		if (user[0].length < 100){
            			document.getElementById("login_status_name").innerHTML = user[0];
            			document.getElementById("login_status_mail").innerHTML = user[1];
            		}else{
            			document.getElementById("login_status_name").innerHTML = '-';
            			document.getElementById("login_status_mail").innerHTML = '-';
            		}
            		if (user[1] == '<?php echo $GLOBALS['S_PREMIUM_USERNAME'][$_SESSION['spotify_account_index']]; ?>'){
            			document.getElementById("login_status").innerHTML = "Finished";
            			document.getElementById("login_status").style.color = "green";
            			clearInterval(checkInterval);
            			myWindow.close();
            			setTimeout(function(){
            				document.location.href = "app.php?p=8";
            			}, 100);
            		}else{
            			document.getElementById("login_status").innerHTML = "Wrong user";
            			document.getElementById("login_status").style.color = "red";
            		}
            	}
            }
        }
    };
    xhttp.open('GET', 'app.php?p=3&status=1', true);
    xhttp.send();
}

var checkInterval = setInterval(function(){ 
	checkStatus();
}, 3000);