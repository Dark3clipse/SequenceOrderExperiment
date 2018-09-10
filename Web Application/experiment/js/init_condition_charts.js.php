<?php 
session_start();
include ("../globals.php");
include ("../functions.php");
header('Content-Type: text/javascript');

$track = $_SESSION['tracks'];
$p = $_SESSION['trial_completed']?$GLOBALS['S_TRACKS_TRIAL']:0;

echo "//update chart\n";
echo "var trace1 = {\n";
echo "    y: [".$track[$p]['valence']."],\n";
echo "    name: 'valence',\n";
echo "    mode: 'lines+markers',\n";
echo "    type: 'scatter',\n";
echo "    hoverinfo: 'y',\n";
echo "    text: [''],\n";
echo "};\n\n";

echo "var trace2 = {\n";
echo "    y: [".$track[$p]['energy']."],\n";
echo "    name: 'energy',\n";
echo "    mode: 'lines+markers',\n";
echo "    type: 'scatter',\n";
echo "    hoverinfo: 'y',\n";
echo "    text: [''],\n";
echo "};\n\n";

echo "var trace3 = {\n";
echo "    y: [".($track[$p]['tempo'])."],\n";
echo "    name: 'tempo',\n";
echo "    mode: 'lines+markers',\n";
echo "    type: 'scatter',\n";
echo "    hoverinfo: 'y',\n";
echo "    text: [''],\n";
echo "};\n\n";

echo "var trace4 = {\n";
echo "    y: [".($track[$p]['mood_group']=='high_valence'?1:0)."],\n";
echo "    name: 'mood grp',\n";
echo "    mode: 'markers',\n";
echo "    type: 'scatter',\n";
echo "    hoverinfo: 'y+text',\n";
echo "    text: ['".str_replace("'", "\'", $track[$p]['name'])."'],\n";
echo "};\n\n";

echo "var trace5 = {\n";
echo "    y: [".($track[$p]['tempo_group']=='high'?210:0)."],\n";
echo "    name: 'tempo grp',\n";
echo "    mode: 'markers',\n";
echo "    type: 'scatter',\n";
echo "    hoverinfo: 'y+text',\n";
echo "    text: ['".str_replace("'", "\'", $track[$p]['name'])."'],\n";
echo "};\n\n";

//echo "var colarr = Array.apply(null, {length: ".$GLOBALS['S_TRACKS_EXP']."}).map(Number.call, Number).map(function(x) { return x / ".$GLOBALS['S_TRACKS_EXP']."; });\n";
echo "var trace6 = {\n";
echo "    x: [".($track[$p]['tempo_group']=='high'?.98:-.98)."],\n";
echo "    y: [".($track[$p]['mood_group']=='high_valence'?.98:-.98)."],\n";
echo "    name: 'condition',\n";
echo "    mode: 'lines+markers',\n";
echo "    type: 'scatter',\n";
echo "    hoverinfo: 'text',\n";
echo "    marker: {\n";
echo "        size: '8',\n";
echo "        color: 'red',\n";
//echo "        color: colarr,\n"; #set color equal to a variable
//echo "        colorscale: 'Reds',\n";
echo "        showscale: false,\n";
echo "     },\n";
echo "    line: {color: 'orange'},\n";
echo "    text: ['Track $p'],\n";
echo "};\n\n";

echo "var trace7 = {\n";
echo "    x: [";
$jitter_dir = 0;
$jitter_strength = .05;
$limit = 0;
for ($j=$p;$j<count($track);$j++){
    $val = .98 - .03*($j-$p);
    $xval[$j] = ($track[$j]['tempo_group']=='high'?$val:-$val);
    $yval[$j] = ($track[$j]['mood_group']=='high_valence'?$val:-$val);
    $xval_add = $jitter_dir*sign($yval[$j])*$jitter_strength;
    $yval_add = $jitter_dir*sign(-$xval[$j])*$jitter_strength;
    $xval[$j] += $xval_add;
    $yval[$j] += $yval_add;
    if ($j>$p && sign($yval[$j]) != sign($yval[$j-1]) && sign($xval[$j]) != sign($xval[$j-1]) && $limit == 0){
        $xval[$j] = sign($xval[$j]) * .05;
        $yval[$j] = sign($yval[$j]) * .05;
        $limit = 1;
    }else{
        $limit = 0;
    }
    $jitter_dir+=1;
    if ($jitter_dir >= 2){
        $jitter_dir = -1;
    }
    
    echo $xval[$j].", ";
}
echo "],\n";
echo "    y: [";
for ($j=$p;$j<count($track);$j++){
    //$val = .98 - .05*($j-$p);
    echo $yval[$j].", ";
}
echo "],\n";
echo "    name: 'condition',\n";
echo "    mode: 'lines+markers',\n";
echo "    type: 'scatter',\n";
echo "    hoverinfo: 'text',\n";
echo "    marker: {\n";
echo "        size: '8',\n";
echo "        showscale: false,\n";
echo "     },\n";
echo "    line: {color: '#848484'},\n";
echo "    text: [";
for ($j=$p;$j<count($track);$j++){
    echo "'Track $j', ";
}
echo "],\n";
echo "};\n\n";

//get recommendation boundaries
$limit = 5;
$track_id = "";
include("../rec_options.php");
$val_min = $options['groups'][0]['tags'][0]['max'];
$val_max = $options['groups'][2]['tags'][0]['min'];
$ene_min = $options['groups'][0]['tags'][1]['max'];
$ene_max = $options['groups'][2]['tags'][1]['min'];
$tem_min = $options['groups'][1]['tags'][2]['max'];
$tem_max = $options['groups'][0]['tags'][2]['min'];
echo "var layout1 = {\n";
echo "    autosize: false,\n";
echo "    width: 400,\n";
echo "    height: 200,\n";
echo "    margin: {\n";
echo "        l: 25,\n";
echo "        r: 10,\n";
echo "        b: 25,\n";
echo "        t: 10,\n";
echo "        pad: 0\n";
echo "    },\n";
echo "    xaxis: {range: [0, ".($GLOBALS['S_TRACKS_EXP']-$p)."]},\n";
echo "    yaxis: {range: [0, 1]},\n";
echo "    paper_bgcolor: '#b5d1ff',\n";
echo "    plot_bgcolor: '#eff5ff',\n";
echo "    shapes: [\n";
echo "      {";
echo "        type: 'rect',\n";
echo "        xref: 'x',\n";
echo "        x0: 0,\n";
echo "        x1: ".($GLOBALS['S_TRACKS_EXP']-$p).",\n";
echo "        yref: 'y',\n";
echo "        y0: ".max($val_min, $ene_min).",\n";
echo "        y1: ".min($val_max, $ene_max).",\n";
echo "        fillcolor: '#848484',\n";
echo "        opacity: .2,\n";
echo "        line: {\n";
echo "            width: 0\n";
echo "        },\n";
echo "      },\n";
echo "      {";
echo "        type: 'line',\n";
echo "        x0: 0,\n";
echo "        x1: ".($GLOBALS['S_TRACKS_EXP']-$p).",\n";
echo "        y0: $val_min,\n";
echo "        y1: $val_min,\n";
echo "        line: {\n";
echo "            color: 'blue',\n";
echo "            width: 1,\n";
echo "            dash: 'dot',\n";
echo "        },\n";
echo "      },\n";
echo "      {";
echo "        type: 'line',\n";
echo "        x0: 0,\n";
echo "        x1: ".($GLOBALS['S_TRACKS_EXP']-$p).",\n";
echo "        y0: $val_max,\n";
echo "        y1: $val_max,\n";
echo "        line: {\n";
echo "            color: 'blue',\n";
echo "            width: 1,\n";
echo "            dash: 'dot',\n";
echo "        },\n";
echo "      },\n";
echo "      {";
echo "        type: 'line',\n";
echo "        x0: 0,\n";
echo "        x1: ".($GLOBALS['S_TRACKS_EXP']-$p).",\n";
echo "        y0: $ene_min,\n";
echo "        y1: $ene_min,\n";
echo "        line: {\n";
echo "            color: 'orange',\n";
echo "            width: 1,\n";
echo "            dash: 'dot',\n";
echo "        },\n";
echo "      },\n";
echo "      {";
echo "        type: 'line',\n";
echo "        x0: 0,\n";
echo "        x1: ".($GLOBALS['S_TRACKS_EXP']-$p).",\n";
echo "        y0: $ene_max,\n";
echo "        y1: $ene_max,\n";
echo "        line: {\n";
echo "            color: 'orange',\n";
echo "            width: 1,\n";
echo "            dash: 'dot',\n";
echo "        },\n";
echo "      },\n";
echo "    ],\n";
echo "};\n\n";

echo "var layout2 = {\n";
echo "    autosize: false,\n";
echo "    width: 400,\n";
echo "    height: 200,\n";
echo "    margin: {\n";
echo "        l: 25,\n";
echo "        r: 10,\n";
echo "        b: 25,\n";
echo "        t: 10,\n";
echo "        pad: 0\n";
echo "    },\n";
echo "    xaxis: {range: [0, ".($GLOBALS['S_TRACKS_EXP']-$p)."]},\n";
echo "    yaxis: {range: [0, 210]},\n";
echo "    paper_bgcolor: '#b5d1ff',\n";
echo "    plot_bgcolor: '#eff5ff',\n";
echo "    shapes: [\n";
echo "      {";
echo "        type: 'rect',\n";
echo "        xref: 'x',\n";
echo "        x0: 0,\n";
echo "        x1: ".($GLOBALS['S_TRACKS_EXP']-$p).",\n";
echo "        yref: 'y',\n";
echo "        y0: ".$tem_min.",\n";
echo "        y1: ".$tem_max.",\n";
echo "        fillcolor: '#848484',\n";
echo "        opacity: .2,\n";
echo "        line: {\n";
echo "            width: 0\n";
echo "        },\n";
echo "      },\n";
echo "      {";
echo "        type: 'line',\n";
echo "        x0: 0,\n";
echo "        x1: ".($GLOBALS['S_TRACKS_EXP']-$p).",\n";
echo "        y0: $tem_min,\n";
echo "        y1: $tem_min,\n";
echo "        line: {\n";
echo "            color: 'blue',\n";
echo "            width: 1,\n";
echo "            dash: 'dot',\n";
echo "        },\n";
echo "      },\n";
echo "      {";
echo "        type: 'line',\n";
echo "        x0: 0,\n";
echo "        x1: ".($GLOBALS['S_TRACKS_EXP']-$p).",\n";
echo "        y0: $tem_max,\n";
echo "        y1: $tem_max,\n";
echo "        line: {\n";
echo "            color: 'blue',\n";
echo "            width: 1,\n";
echo "            dash: 'dot',\n";
echo "        },\n";
echo "      },\n";
echo "    ],\n";
echo "};\n\n";

echo "var layout3 = {\n";
echo "    autosize: false,\n";
echo "    width: 400,\n";
echo "    height: 400,\n";
echo "    margin: {\n";
echo "        l: 25,\n";
echo "        r: 10,\n";
echo "        b: 25,\n";
echo "        t: 10,\n";
echo "        pad: 0\n";
echo "    },\n";
echo "    xaxis: {\n";
echo "        title: 'low          tempo          high',\n";
echo "        showticklabels: false,\n";
echo "        range: [-1, 1]\n";
echo "    },\n";
echo "    yaxis: {\n";
echo "        title: 'low          mood          high',\n";
echo "        showticklabels: false,\n";
echo "        range: [-1, 1]\n";
echo "    },\n";
echo "    paper_bgcolor: '#b5d1ff',\n";
echo "    plot_bgcolor: '#eff5ff',\n";
echo "    showlegend: false,\n";
echo "};\n\n";

echo "$(document).ready(function(){\n\n";
echo "    var data1 = [trace1, trace2, trace4];\n";
echo "    Plotly.newPlot('metaChart', data1, layout1);\n";

echo "    var data2 = [trace3, trace5];\n";
echo "    Plotly.newPlot('metaChart2', data2, layout2);\n";

echo "    var data3 = [trace7, trace6];\n";
echo "    Plotly.newPlot('metaChart3', data3, layout3);\n";
echo "});\n\n";
?>