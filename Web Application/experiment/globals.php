<?php 
global $S_RELOG, $S_REFETCH_RECS, $S_SHOW_WARNINGS, $S_CONSTRAIN_RECS, $S_FETCH_DISTRIBUTION_AND_DIE, $S_GROUPS, $S_TRACKS_TRIAL, $S_TRACKS_EXP, $S_CONTROL_SPOTIFY,
$S_SHOW_TRACK_METADATA, $S_SEGMENT_DURATION, $WEB_PLAYER_NAME, $PLAYLIST_NAME, $S_OVERRIDE_FEATURE_WITH_SEGMENT_IF_CONFIDENCE, 
$S_PREMIUM_USERNAME, $S_PREMIUM_PASSWORD, $S_PREMIUM_NAME, $S_EXPERIMENT_VERSION;

$S_RELOG = False;
$S_REFETCH_RECS = True;
$S_SHOW_WARNINGS = True;
$S_CONSTRAIN_RECS = True;
$S_CONTROL_SPOTIFY = True;
$S_FETCH_DISTRIBUTION_AND_DIE = False;
$S_PLAY_PREVIEWS_ONLY = False;
$S_SHOW_TRACK_METADATA = False;

$S_EXPERIMENT_VERSION = 1;
$S_GROUPS = 4;//nr of condition groups
$S_TRACKS_TRIAL = 3;//tracks played in the trial
$S_TRACKS_EXP = 28;//tracks in total

$S_SEGMENT_DURATION = 30;//duration for each track in seconds
$S_OVERRIDE_FEATURE_WITH_SEGMENT_IF_CONFIDENCE = .5;//confidence required to override features with segment feature

$WEB_PLAYER_NAME = "Experiment Web Player";
$PLAYLIST_NAME = "Experiment Playlist - v2rtg_b3q";

$S_PREMIUM_NAME = ["Researcher", "Participant One", "Participant Two", "Participant Three", "Participant Four"];
$S_PREMIUM_USERNAME = ["s.hadash@student.tue.nl", "htilabs.participant1@gmail.com", "htilabs.participant2@gmail.com", "htilabs.participant3@gmail.com", "htilabs.participant4@gmail.com"];
$S_PREMIUM_PASSWORD = "xxx";



?>