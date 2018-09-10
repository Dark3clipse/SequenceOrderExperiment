var spareRandom = null;

function normalRandom()
{
	var val, u, v, s, mul;

	if(spareRandom !== null)
	{
		val = spareRandom;
		spareRandom = null;
	}
	else
	{
		do
		{
			u = Math.random()*2-1;
			v = Math.random()*2-1;

			s = u*u+v*v;
		} while(s === 0 || s >= 1);

		mul = Math.sqrt(-2 * Math.log(s) / s);

		val = u * mul;
		spareRandom = v * mul;
	}
	
	return val / 14;	// 7 standard deviations on either side
}

function normalRandomInRange(min, max)
{
	var val;
	do
	{
		val = normalRandom();
	} while(val < min || val > max);
	
	return val;
}

function randn_bm(mean, stddev)
{
	var r = normalRandomInRange(-1, 1);
	r = r * stddev + mean;
	return r;
}

var adjustGraphPosW = .02 + .05*Math.sqrt(2);
function adjustGraphPos(pos){
	var cent = [0, 0];
	var dir = [cent[0]-pos[0], cent[1]-pos[1]];
	dir = dir.map(function(x) { return x / (Math.sqrt(Math.pow(dir[0], 2)+Math.pow(dir[1], 2))); });
	var add = [dir[0]*adjustGraphPosW, dir[1]*adjustGraphPosW];
	var r = [pos[0]+add[0], pos[1]+add[1]];
	adjustGraphPosW+=.05*Math.sqrt(2);
	return r;
}

/*window.onbeforeunload = function() {
    return "Please do not refresh this page. Click 'cancel' to continue or ask the researcher for help.";
}*/

function refreshPremiumToken() {
	var win = window.open('auth.php', '_blank');
  	win.focus();
}