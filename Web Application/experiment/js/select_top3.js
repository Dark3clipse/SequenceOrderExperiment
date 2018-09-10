var top3 = [];
function selectTrack(pos){
	for (i=0;i<top3.length;i++){
		if (top3[i] == pos){
			return;
		}
	}
	if (top3.length < 3){
		top3.push(pos);
		document.getElementById('track_wrapper_'+pos).className = "track_wrapper_s";
	}
	if (top3.length == 3){
		document.getElementById('btn_sel_tracks').disabled = false;
	}
}
function resetSelection(){
	for (i=0;i<top3.length;i++){
		document.getElementById('track_wrapper_'+top3[i]).className = "track_wrapper";
	}
	document.getElementById('btn_sel_tracks').disabled = true;
	top3 = [];
}
function finishSelection(){
	if (top3.length == 3){
		location.href='?p=3&t1='+top3[0]+'&t2='+top3[1]+'&t3='+top3[2];
		document.getElementById('but_top3cont').disabled = true;
		document.getElementById('but_top3reset').disabled = true;
	}
}