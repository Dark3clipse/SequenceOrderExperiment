User Experiment: The Effects of Tempo and Mood Variations on the Flow of Music Track Transitions
Author: Sophia Hadash, Eindhoven University of Technology, Faculty of Industrial Engineering and Innovation Sciences, E-mail: s.hadash@student.tue.nl

Description
This contains the source code for my experiment. It contains a web application where participants sign in to their Spotify account and run the experiment. Participants listen to a shortened playlist and dynamically answer questions, followed by several questionnaires.

The package contains:
- the web application
- sql database
- R code for a Bayesian analysis of the results


How to use:

Setup the website / application
1. open create_session.php and insert your client id, client secret, and callback url
2. open globals.php and fill in your premium accounts under $S_PREMIUM_NAME and $S_PREMIUM_USERNAME
3. open mysql connect and fill in your mysql server and user credentials
4. edit global experiment settings in globals.php if needed
5. edit recommendation constraints in rec_options.php if needed

Create the database
1. import a sql database from sophiaha_experiment.sql
2. Add premium account credentials to the spotify_premium table. You can leave the token fields empty.

Collect data

Data analysis
1. Export the tables participants, questionnaire, recommendations, top_tracks, and transitions in separate csv files
2. Place the tables in the data folder
3. Open multilevel_oop.R and run your analysis