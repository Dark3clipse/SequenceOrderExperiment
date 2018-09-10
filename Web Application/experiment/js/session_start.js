function onSubmit(index){
	location.href='?p=1&s='+index;
}

function startCountdown(element_id, button_id, seconds){
	var e = document.getElementById(element_id);
	var b = document.getElementById(button_id);
	var m = Math.floor(parseFloat(seconds)/parseFloat(60));
	var s = seconds - 60*m;
	var interval = setInterval(function(){
		s+=1;
		while(s >= 60){
			m+=1;
			s-=60;
		}
		if(m >= 60){
			e.innerHTML = "Token expired";
			e.style.color = "red";
			b.disabled = true;
			b.parentNode.removeChild(b);
			clearInterval(interval);
			return;
		}else{
			e.innerHTML = "Token valid (since: "+m+" m "+s+" s)";
		}
	}, 1000);
}