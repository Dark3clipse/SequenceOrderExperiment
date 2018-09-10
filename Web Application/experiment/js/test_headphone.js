test_audio = new Audio('mp3/test.mp3');
function playTestAudio(){
	if (test_audio.currentTime <= 0 || test_audio.paused || test_audio.ended || test_audio.readyState <= 2){
		test_audio.play();
		document.getElementById('btn_continue').style.visibility = 'visible';
	}
}