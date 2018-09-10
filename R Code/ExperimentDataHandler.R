######################################################################
# Create the class that handles experiment data
# 
# Able to read data from file, or generate simulated data
#
######################################################################

ExperimentDataHandler <- setClass(
  
  # Set the name for the class
  "ExperimentDataHandler",
  
  # Define the slots
  slots = c(
    
    #public
    N = "numeric",
    yvar = "vector",
    levelvar = "vector",
    xvar = "vector",
    xvar_active = "vector",
    truth_sigma = "numeric",
    truth_effect_size = "vector",
    truth_interaction_effects = "matrix",
    truth_tau = "numeric",
    
    data_frame = "data.frame",
    data_frame_wide = "data.frame",
    
    #private
    xvar_excluded = "vector",
    n_excluded = "numeric",
    truth_gamma_sample = "vector"
  ),
  
  # Set the default values for the slots. (optional)
  prototype=list(
    
    #public
    N = 25,
    yvar = c("flow"),
    levelvar = c("participant", "measurement"),
    xvar = c("intercept", "similar", "varying_mood", "varying_tempo", "dvalence", "denergy", "dtempo", "msi", "companionship", "investment", "p_extraversion", "p_agreeableness", "p_conscientiousness", "p_neuroticism", "p_openness", "spotifyhours", "perceive_personalized", "objective_personalized", "gender", "age", "experiment_version"),
    #xvar_active = c(1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
    truth_sigma = .25,
    truth_effect_size = c(.5, rep(0, 20)),
    truth_interaction_effects = matrix(rep(0, 21*21), nrow=21, ncol=21),
    truth_tau = .5
    
    #private
    #xvar_excluded = vector(),
    #n_excluded = 0
    
  ),
  
  # Make a function that can test to see if the data is consistent.
  # This is not called if you have an initialize function defined!
  validity=function(object)
  {
    if((object@N%%1 != 0) || (object@N <= 0)) {
      return("A nonvalid sample size is given")
    }
    
    return(TRUE)
  }
)

# define constructor
setMethod(f="initialize",
  signature="ExperimentDataHandler",
  definition=function(.Object){
    cat("~~~ ExperimentDataHandler: constructor ~~~ \n")
      
    
    #compute excluded variables
    #for (i in 1:length(.Object@truth_effect_size)){
    #  if (.Object@xvar_active[i]==0){
    #    .Object@xvar_excluded <- c(.Object@xvar_excluded, i)
    #    .Object@n_excluded<- .Object@n_excluded + 1
    #  }
    #}
    
    return(.Object) # return of the object
  }
)

# create a method to assign the value of a coordinate
setGeneric(name="generateData",
  def=function(.Object)
  {
   standardGeneric("generateData")
  }
)

setMethod(f="generateData",
  signature="ExperimentDataHandler",
  definition=function(.Object)
  {
    cat("~~~ ExperimentDataHandler: generateData ~~~ \n")
    
    #.Object <- ExperimentDataHandler()
    N_transitions = 24
    
    #derivatives of the parameters
    truth_gamma = .Object@truth_sigma * .Object@truth_effect_size;
    truth_tau = c(.Object@truth_tau, rep(0, length(.Object@truth_effect_size)-1))

    truth_beta<-mapply(function(x,y){rnorm(x,y,n=.Object@N)},x=truth_gamma,y=truth_tau)
    colnames(truth_beta) <- .Object@xvar
    truth_gamma_sample = vector()
    for (i in 1:ncol(truth_beta)){
      truth_gamma_sample = c(truth_gamma_sample, mean(truth_beta[,i]))
    }
    .Object@truth_gamma_sample = truth_gamma_sample
    
    #truth_beta<-truth_beta[, -.Object@xvar_excluded]

    #generate data
    #data_x = matrix(, nrow = 0, ncol = length(.Object@xvar)+length(.Object@yvar)+length(.Object@levelvar) -.Object@n_excluded)
    data_x = matrix(, nrow = 0, ncol = length(.Object@xvar)+length(.Object@yvar)+length(.Object@levelvar))
    for (i in 1:.Object@N){
      
      state = round(runif(2))
      cycle_dir = round(runif(1))
      tempo_old = 0
      energy_old = 0
      valence_old = 0
      data_xi = matrix(nrow = 0, ncol = length(.Object@truth_effect_size))
      msi = rnorm(1, 0, 1) # normalized
      for (j in 0:N_transitions){
        if (j > 0){
          cycle_dir = cycle_dir + ((j-1)%%12==0)%%2
          if(cycle_dir > 1){
            cycle_dir = 0
          }
          #print( cycle_dir)
          
          #follow within-cycle pattern: T S C C M S C C T S M S
          tempo = ( ((j-1)%%12==0) + ((j-9)%%12==0) ) %%2
          mood = ( ((j-5)%%12==0) + ((j-11)%%12==0) ) %%2
          cross = ((j-3)%%12==0) + ((j-4)%%12==0) + ((j-7)%%12==0) + ((j-8)%%12==0)
          same = ((j-2)%%12==0) + ((j-6)%%12==0) + ((j-10)%%12==0) + ((j-12)%%12==0)
          
          if (cycle_dir == 1){
            tmp = tempo
            tempo = mood
            mood = tmp
            rm(tmp)
          }
        }else{
          tempo = 0
          mood = 0
          cross = 0
          same = 1
        }
        
        #print(c(tempo, mood, cross, same))
        
        if (tempo==1){
          state[1] = 1 - state[1]
        }
        if (mood==1){
          state[2] = 1 - state[2]
        }
        if (cross==1){
          state[1] = 1 - state[1]
          state[2] = 1 - state[2]
        }
        
        #print(state)
        
        if (state[2] == 1){
          valence = rnorm(1, .8, .1)
          valence[valence < .61] <- .61
          energy = rnorm(1, .9, .08)
          energy[energy < .81] <- .81
        }else{
          valence = rnorm(1, .1, .08);
          valence[valence > .18] <- .18
          energy = rnorm(1, .2, .14)
          energy[energy > .37] <- .37
        }
        if (state[1] == 1){
          tempo = rnorm(1, 180, 15)
          tempo[tempo < 145] <- 145
        }else{
          tempo = rnorm(1, 60, 20)
          tempo[tempo > 91] <- 91
        }
        valence[valence < 0] <- 0
        valence[valence > 1] <- 1
        energy[energy < 0] <- 0
        energy[energy > 1] <- 1
        tempo[tempo < 0] <- 0
        
        transition = c(1, same, mood||cross, tempo||cross, valence-valence_old, energy-energy_old, tempo-tempo_old, msi)
        data_xi <- rbind(data_xi, transition)
        valence_old = valence
        energy_old = energy
        tempo_old = tempo
      }
      data_xi <- data_xi[-1,]
      
      # apply main effects
      data_yi_main = as.vector(as.matrix(data_xi) %*% as.matrix(truth_beta[i,])) # main effects
      
      # apply interaction effects
      data_yi_intm = array(0, dim=c(length(.Object@truth_effect_size), length(.Object@truth_effect_size), N_transitions))
      for(q in 1:length(.Object@truth_effect_size)){
        for(k in 1:length(.Object@truth_effect_size)){
          data_yi_intm[q,k,] = data_xi[,q] * data_xi[,k] * .Object@truth_interaction_effects[q,k]
        }
      }
      data_yi_int = array(0, dim=N_transitions)
      data_yi_int = apply(data_yi_intm, 3, sum)
      
      # noise
      data_yi_rand = rnorm(N_transitions, 0, .Object@truth_sigma)
      
      # combine main effects, interaction effects and noise to form the dependent variable
      data_yi = data_yi_main + data_yi_int + data_yi_rand
      
      # add the level variables
      data_xi = cbind(rep(i, N_transitions), seq(1, N_transitions), data_xi, data_yi)
      
      # add participant to full dataset
      data_x = rbind(data_x, data_xi)
    }
    
    .Object@data_frame <- data.frame(data_x)
    #colnames(.Object@data_frame) <- c(.Object@levelvar, .Object@xvar[-.Object@xvar_excluded], .Object@yvar)
    colnames(.Object@data_frame) <- c(.Object@levelvar, .Object@xvar, .Object@yvar)
    rownames(.Object@data_frame) <- c()
    rm(data_x, data_xi, data_yi, truth_beta, cross, cycle_dir, data_yi_main, data_yi_int, data_yi_rand, energy, energy_old, i, j, mood, N_transitions, same, state, tempo, tempo_old, transition, truth_gamma, truth_gamma_sample, truth_tau, valence, valence_old)
    
    return(.Object)
  }
)

setGeneric(name="addMissingValues",
  def=function(.Object, amount)
  {
   standardGeneric("addMissingValues")
  }
)

setMethod(f="addMissingValues",
  signature="ExperimentDataHandler",
  definition=function(.Object, amount)
  {
    cat("~~~ ExperimentDataHandler: addMissingValues ~~~ \n")
    
    for (i in 1:nrow(.Object@data_frame)){
      if (runif(1) < amount){
        .Object@data_frame$flow[i] = NA
      }
    }
    
    return(.Object)
  }
)

setGeneric(name="getVariableList",
           def=function(.Object)
           {
             standardGeneric("getVariableList")
           }
)

setMethod(f="getVariableList",
  signature="ExperimentDataHandler",
  definition=function(.Object)
  {
    cat("~~~ ExperimentDataHandler: getVariableList ~~~ \n")
    
    return(.Object@xvar[c(-1)])
  }
)

setGeneric(name="setSimulationParameters",
  def=function(.Object, N, sigma, tau, effect_size, interaction_effects)
  {
   standardGeneric("setSimulationParameters")
  }
)

setMethod(f="setSimulationParameters",
  signature="ExperimentDataHandler",
  definition=function(.Object, N, sigma, tau, effect_size, interaction_effects)
  {
    cat("~~~ ExperimentDataHandler: setSimulationParameters ~~~ \n")
    
    .Object@N = N
    .Object@truth_sigma = sigma
    .Object@truth_tau = tau
    .Object@truth_effect_size = effect_size
    .Object@truth_interaction_effects = interaction_effects
    
    return(.Object)
  }
)

setGeneric(name="readData",
  def=function(.Object, normalize)
  {
   standardGeneric("readData")
  }
)

setMethod(f="readData",
  signature="ExperimentDataHandler",
  definition=function(.Object, normalize)
  {
    cat("~~~ ExperimentDataHandler: readData ~~~ \n")
    
    t_participants <- read.table("D:/Dropbox/Dropbox/HTI Abroad Project/experiment_data/src/data/sophiaha_experiment_table_participants.csv", TRUE, ",")
    t_questionnaire <- read.table("D:/Dropbox/Dropbox/HTI Abroad Project/experiment_data/src/data/sophiaha_experiment_table_questionnaire.csv", TRUE, ",")
    t_recommendations <- read.table("D:/Dropbox/Dropbox/HTI Abroad Project/experiment_data/src/data/sophiaha_experiment_table_recommendations.csv", TRUE, ",")
    t_toptracks <- read.table("D:/Dropbox/Dropbox/HTI Abroad Project/experiment_data/src/data/sophiaha_experiment_table_top_tracks.csv", TRUE, ",")
    t_transitions <- read.table("D:/Dropbox/Dropbox/HTI Abroad Project/experiment_data/src/data/sophiaha_experiment_table_transitions.csv", TRUE, ",")
    
    # generate discrete conditions
    t_transitions$similar <- (t_transitions$mood_from == t_transitions$mood_to & t_transitions$tempo_from == t_transitions$tempo_to)
    t_transitions$varying_mood <- (t_transitions$mood_from != t_transitions$mood_to)
    t_transitions$varying_tempo <- (t_transitions$tempo_from != t_transitions$tempo_to)
    t_transitions$similar_mood_bi <- 1*(t_transitions$mood_from == "low_valence" & t_transitions$mood_to == "high_valence")-1*(t_transitions$mood_from == "high_valence" & t_transitions$mood_to == "low_valence")
    t_transitions$similar_tempo_bi <- 1*(t_transitions$tempo_from == "low" & t_transitions$tempo_to == "high")-1*(t_transitions$tempo_from == "high" & t_transitions$tempo_to == "low")
    
    t_transitions$similar <- as.numeric(t_transitions$similar)
    t_transitions$varying_mood <- as.numeric(t_transitions$varying_mood)
    t_transitions$varying_tempo <- as.numeric(t_transitions$varying_tempo)
    t_transitions$similar_mood_bi <- as.numeric(t_transitions$similar_mood_bi)
    t_transitions$similar_tempo_bi <- as.numeric(t_transitions$similar_tempo_bi)
    
    # remove unwanted variables
    t_transitions$id <- NULL
    t_transitions$rec_from <- NULL
    t_transitions$rec_to <-NULL
    t_transitions$mood_from <- NULL
    t_transitions$mood_to <- NULL
    t_transitions$tempo_from <- NULL
    t_transitions$tempo_to <- NULL
    t_transitions$d_key <- NULL
    t_transitions$similar_mood_bi <- NULL
    t_transitions$similar_tempo_bi <- NULL
    
    # generate dependent variable: flow
    t_transitions$survey_1[t_transitions$survey_1 == "NULL"] = NA
    t_transitions$survey_2[t_transitions$survey_2 == "NULL"] = NA
    t_transitions$survey_3[t_transitions$survey_3 == "NULL"] = NA
    t_transitions$survey_1 <- as.numeric(as.character(t_transitions$survey_1))
    t_transitions$survey_2 <- as.numeric(as.character(t_transitions$survey_2))
    t_transitions$survey_3 <- as.numeric(as.character(t_transitions$survey_3))
    t_transitions$flow <- (t_transitions$survey_1 + t_transitions$survey_2 - t_transitions$survey_3)/3
    t_transitions$survey_1 <- NULL
    t_transitions$survey_2 <- NULL
    t_transitions$survey_3 <- NULL
    
    # remove incomplete data and unwanted participants
    t_transitions<-t_transitions[!(t_transitions$participant_id==2 ),] # my own account
    t_transitions<-t_transitions[!(t_transitions$participant_id==4 ),] # technical problem
    t_transitions<-t_transitions[!(t_transitions$participant_id==14),] # technical problem
    t_transitions<-t_transitions[!(t_transitions$participant_id==3 ),] # empty account
    t_transitions<-t_transitions[!(t_transitions$participant_id==17),] # empty account
    t_transitions<-t_transitions[!(t_transitions$participant_id==31),] # empty account
    t_transitions<-t_transitions[!(t_transitions$participant_id==41),] # empty account
    
    # generate level variables: participant, measurement
    t_transitions$participant = t_transitions$participant_id
    for (i in 1:length(unique(t_transitions$participant_id))){
      t_transitions[(t_transitions$participant_id==unique(t_transitions$participant_id)[i]),]$participant <- i
    }
    t_transitions$measurement <- rowid(t_transitions$participant)
    
    # generate intercept
    t_transitions$intercept <- 1
    
    # order and sort data
    t_transitions <- t_transitions[c(1,9,10,11,5,6,7,2,3, 4, 8)]
    t_transitions <- t_transitions[with(t_transitions, order(participant, measurement)), ]
    
    
    # compute msi
    t_questionnaire$msi6 = -t_questionnaire$msi6 + 8
    t_questionnaire$msi8 = -t_questionnaire$msi8 + 8
    t_questionnaire$msi10 = -t_questionnaire$msi10 + 8
    t_questionnaire$msi12 = -t_questionnaire$msi12 + 8
    t_questionnaire$msi13 = -t_questionnaire$msi13 + 8
    msi = rowSums(dplyr::select(t_questionnaire, starts_with("msi")))
    t_questionnaire = dplyr::select(t_questionnaire, -starts_with("msi"))
    t_questionnaire$msi = msi
    rm(msi)
    
    # compute persona
    t_questionnaire$persona0 = -t_questionnaire$persona0 + 6
    t_questionnaire$persona1 = -t_questionnaire$persona1 + 6
    t_questionnaire$persona3 = -t_questionnaire$persona3 + 6
    t_questionnaire$persona4 = -t_questionnaire$persona4 + 6
    t_questionnaire$persona5 = -t_questionnaire$persona5 + 6
    t_questionnaire$persona12 = -t_questionnaire$persona12 + 6
    t_questionnaire$persona13 = -t_questionnaire$persona13 + 6
    t_questionnaire$persona14 = -t_questionnaire$persona14 + 6

    data = dplyr::select(t_questionnaire, starts_with("persona"))
    fit <- princomp(data, cor=FALSE, )
    fit$loadings[,3]
    t_questionnaire$companionship = -as.matrix(data) %*% fit$loadings[,1]
    t_questionnaire$investment = as.matrix(data) %*% fit$loadings[,2]
    t_questionnaire$usage_of_mrs = -as.matrix(data) %*% fit$loadings[,3]
    rm(data, fit)
    
    t_questionnaire = dplyr::select(t_questionnaire, -starts_with("persona"))
    t_questionnaire$usage_of_mrs <- NULL
    
    # compute personality
    t_questionnaire$bfi0 = -t_questionnaire$bfi0 + 6
    t_questionnaire$bfi6 = -t_questionnaire$bfi6 + 6
    t_questionnaire$bfi2 = -t_questionnaire$bfi2 + 6
    t_questionnaire$bfi3 = -t_questionnaire$bfi3 + 6
    t_questionnaire$bfi4 = -t_questionnaire$bfi4 + 6
    t_questionnaire$p_extraversion = rowSums(t_questionnaire[,c("bfi0","bfi5")])
    t_questionnaire$p_agreeableness = rowSums(t_questionnaire[,c("bfi1","bfi6")])
    t_questionnaire$p_conscientiousness = rowSums(t_questionnaire[,c("bfi2","bfi7")])
    t_questionnaire$p_neuroticism = rowSums(t_questionnaire[,c("bfi3","bfi8")])
    t_questionnaire$p_openness = rowSums(t_questionnaire[,c("bfi4","bfi9")])
    t_questionnaire = dplyr::select(t_questionnaire, -starts_with("bfi"))
    
    # merge questionnaire data with general table
    t_transitions <- merge(t_transitions, t_questionnaire,by="participant_id")
    rm(t_questionnaire)
    
    # remove identifiers
    t_transitions$id <- NULL
    
    # merge participant data with general table
    t_participants$participant_id = t_participants$id
    t_participants$id <- NULL
    t_transitions <- merge(t_transitions, t_participants,by="participant_id")
    rm(t_participants)
    
    
    # drop unwanted columns
    t_transitions$spotify_id <- NULL
    t_transitions$access_token <- NULL
    t_transitions$refresh_token <- NULL
    t_transitions = dplyr::select(t_transitions, -starts_with("top_track"))
    t_transitions$invalid_recs <- NULL
    t_transitions$participant_id <- NULL
    
    # reorder table
    t_transitions <- t_transitions[c(1,2,3,4,5,6,8,7,9,15,16,17,18,19, 20, 21, 22, 13, 14, 23, 11, 12, 24, 10)]
    
    #convert seeds needed to an objective measure of personalization
    t_transitions$objective_personalized <- -t_transitions$seeds_needed
    t_transitions$objective_personalized <- (t_transitions$objective_personalized - mean(t_transitions$objective_personalized))/sd(t_transitions$objective_personalized)
    t_transitions <- t_transitions[c(1:19, 25, 21:24)]
    
    # convert these anyways
    t_transitions$p_extraversion <- (t_transitions$p_extraversion/2)-3
    t_transitions$p_agreeableness <- (t_transitions$p_agreeableness/2)-3
    t_transitions$p_conscientiousness <- (t_transitions$p_conscientiousness/2)-3
    t_transitions$p_openness <- (t_transitions$p_openness/2)-3
    t_transitions$p_neuroticism <- (t_transitions$p_neuroticism/2)-3
    t_transitions$spotifyhours <- t_transitions$spotifyhours-4
    t_transitions$perceive_personalized <- t_transitions$perceive_personalized-3
    t_transitions$gender <- t_transitions$gender - 1
    
    #center and standardize data
    if (normalize == TRUE){
      t_transitions$msi = (t_transitions$msi-mean(t_transitions$msi)/sd(t_transitions$msi))
      t_transitions$d_tempo <- abs((t_transitions$d_tempo-mean(t_transitions$d_tempo))/sd(t_transitions$d_tempo))
      t_transitions$d_valence <- abs((t_transitions$d_valence-mean(t_transitions$d_valence))/sd(t_transitions$d_valence))
      t_transitions$d_energy <- abs((t_transitions$d_energy-mean(t_transitions$d_energy))/sd(t_transitions$d_energy))
      t_transitions$companionship <- (t_transitions$companionship-mean(t_transitions$companionship))/sd(t_transitions$companionship)
      t_transitions$investment <- (t_transitions$investment-mean(t_transitions$investment))/sd(t_transitions$investment)
    
      t_transitions$p_extraversion = (t_transitions$p_extraversion-mean(t_transitions$p_extraversion))/sd(t_transitions$p_extraversion)
      t_transitions$p_agreeableness = (t_transitions$p_agreeableness-mean(t_transitions$p_agreeableness))/sd(t_transitions$p_agreeableness)
      t_transitions$p_conscientiousness = (t_transitions$p_conscientiousness-mean(t_transitions$p_conscientiousness))/sd(t_transitions$p_conscientiousness)
      t_transitions$p_neuroticism = (t_transitions$p_neuroticism-mean(t_transitions$p_neuroticism))/sd(t_transitions$p_neuroticism)
      t_transitions$p_openness = (t_transitions$p_openness-mean(t_transitions$p_openness))/sd(t_transitions$p_openness)
      
      t_transitions$spotifyhours = (t_transitions$spotifyhours-mean(t_transitions$spotifyhours))/sd(t_transitions$spotifyhours)
      t_transitions$perceive_personalized = (t_transitions$perceive_personalized-mean(t_transitions$perceive_personalized))/sd(t_transitions$perceive_personalized)
      
      t_transitions$gender = (t_transitions$gender-mean(t_transitions$gender))/sd(t_transitions$gender)
      t_transitions$age = (t_transitions$age-mean(t_transitions$age))/sd(t_transitions$age)
      t_transitions$experiment_version = (t_transitions$experiment_version-mean(t_transitions$experiment_version))/sd(t_transitions$experiment_version)
      }
    
    # store result in object
    .Object@data_frame <- t_transitions
    
    # convert to wide format
    .Object@data_frame_wide = data.frame()
    for(i in seq(1,nrow(.Object@data_frame),24)){
      .Object@data_frame_wide = rbind(.Object@data_frame_wide, .Object@data_frame[i,c(1,10:23)])
    }
  
    return(.Object)
  }
)

setGeneric(name="factorAnalysis",
  def=function(.Object)
  {
   standardGeneric("factorAnalysis")
  }
)

setMethod(f="factorAnalysis",
  signature="ExperimentDataHandler",
  definition=function(.Object)
  {
    cat("~~~ ExperimentDataHandler: factorAnalysis ~~~ \n")
    
    # load dataset
    t_questionnaire <- read.table("D:/Dropbox/Dropbox/HTI Abroad Project/experiment_data/src/data/sophiaha_experiment_table_questionnaire.csv", TRUE, ",")
    
    t_questionnaire$msi6 = -t_questionnaire$msi6 + 8
    t_questionnaire$msi8 = -t_questionnaire$msi8 + 8
    t_questionnaire$msi10 = -t_questionnaire$msi10 + 8
    t_questionnaire$msi12 = -t_questionnaire$msi12 + 8
    t_questionnaire$msi13 = -t_questionnaire$msi13 + 8
    msi = dplyr::select(t_questionnaire, starts_with("msi"))
    
    # select persona columns
    t_questionnaire = dplyr::select(t_questionnaire, starts_with("persona"))
    
    # invert negatives
    t_questionnaire$persona0 = -t_questionnaire$persona0 + 6
    t_questionnaire$persona1 = -t_questionnaire$persona1 + 6
    t_questionnaire$persona3 = -t_questionnaire$persona3 + 6
    t_questionnaire$persona4 = -t_questionnaire$persona4 + 6
    t_questionnaire$persona5 = -t_questionnaire$persona5 + 6
    t_questionnaire$persona12 = -t_questionnaire$persona12 + 6
    t_questionnaire$persona13 = -t_questionnaire$persona13 + 6
    t_questionnaire$persona14 = -t_questionnaire$persona14 + 6
    
    # strip into the two factors
    persona = t_questionnaire
    companionship = t_questionnaire[,c("persona0","persona1","persona2","persona3","persona4","persona5","persona6")]
    investment = t_questionnaire[,c("persona7","persona8","persona9","persona10","persona11","persona12","persona13","persona14")]
    rm(t_questionnaire)
    
    # select one of the datasets for analysis
    data <- persona
    #data <- persona[-c(6, 7, 13, 15)]
    #data <- persona[c(1,4,5, 8, 9, 11)]
    
    # step 1: suitability of the data for factor analysis ----------------------------------------
    
    KMO(data)
    cortest.bartlett(cor(data), n = 37,diag=TRUE)
    
    # number of factors -------------------------------------------------------------------------
    
    # compute eigenvalues and vectors
    S <- cov(data)
    S.eigen <- eigen(S)
    
    # make a scree plot
    plot(S.eigen$values, xlab = 'Eigenvalue Number', ylab = 'Eigenvalue Size', main = 'Scree Graph', type = 'b', xaxt = 'n')
    axis(1, at = seq(1, 10, by = 1))
    
    # make a scree plot
    fit <- factanal(data, 
                    5,                # number of factors to extract
                    scores=c("regression"),
                    rotation="promax")
    scree.plot(fit$correlation)
    
    # Using the Cattell scree test, the number of components is either 3 or 7. The steepest descent stops at n=3, and since 7 is relatively high compared to the number of questions,
    # it is better to stick to 3 components. A disadvantage of this method is that it is amenable to researcher-controlled fudging. 
    # Therefore, the decision of choosing three factors should be grounded on stronger evidence
    
    # Use a more elaborate test
    ap <- parallel(subject=nrow(data),var=ncol(data), rep=100,cent=.05)
    nS <- nScree(x=S.eigen$values, aparallel=ap$eigen$qevpea)
    plotnScree(nS)
    
    # following the Kaiser-Guttman rule, we should choose all components with eigenvalue greater than one. This results in choosing 6 factors.
    # The most modern criteria, Horn's parallel analysis, results in 15 factors. However, according to Formann (cite), this method may 
    # be strongly dependent on sample size, item discrimination, and type of correlation coefficient.
    
    my.vss <- VSS(data, rotate=c("promax"))      
    
    # very simple structure fit curves show that the increase in fit from 1 factor to 2 factors is more compared to 
    # the increase from 2 to 3 and 3 to 4.
    
    nfactors(data,n=10,rotate="promax",diagonal=FALSE,fm="minres",n.obs=NULL,
             title="Number of Factors",pch=16,use="pairwise", cor="cor")
    
    # Velicer's MAP test achieves a minimum of 0.04 with 2 factors
    # The VSS complexity does not have a clear maximum
    # Empirical BIC has a minimum at 2 factors. Adding more factors adds more complexity
    # However, when adjusted for sample size, the BIC has a minimum at 8 factors
    
    # determine amount of variance explained when choosing n factors ------------------------------------------------------------------
    nfac = 3
    
    C <- as.matrix(S.eigen$vectors[,1:nfac])
    D <- matrix(0, dim(C)[2], dim(C)[2])
    diag(D) <- S.eigen$values[1:nfac]
    
    # compute unrotated factor loadings
    S.loadings <- C %*% sqrt(D)
    S.loadings
    
    
    data.pca <- prcomp(data)$rotation[,1:nfac] # Perform PCA on the rootstock data and take the resulting first two PCs
    data.pca
    
    # communality, the variance of the variables explained by the common factors
    S.h2 <- rowSums(S.loadings^2)
    S.h2
    
    # eigenvalues of S
    colSums(S.loadings^2)
    S.eigen$values[1:nfac]
    
    # specific variance
    S.u2 <- diag(S) - S.h2
    S.u2
    
    # compute proportions
    prop.loadings <- colSums(S.loadings^2)
    prop.var = c()
    prop.exp = c()
    for(i in 1:nfac){
      prop.var = cbind(prop.var, prop.loadings[i] / sum(S.eigen$values))
      prop.exp = cbind(prop.exp, prop.loadings[i] / sum(prop.loadings))
    }
    
    # The proportion of variance of the loadings
    prop.var
    sum(prop.var)
    
    # The proportion of variance explained by the loadings
    prop.exp
    
    # Using three factors, the first factor explains 21% of the variance in the data. 
    # The second and third factor explain 14% and 11% respectively
    
    # with 2 factors, a total of 35% of the variance is explained
    
    data.fa.covar <- principal(data, nfactors = nfac, rotate = 'promax', covar = TRUE)
    data.fa.covar
    
    # factor analysis -------------------------------------------------------------------------
    
    # compute
    fit <- factanal(data, 
                    3,                # number of factors to extract
                    scores=c("regression"),
                    rotation="promax")
    
    # print
    print(fit, digits=2, cutoff=.3, sort=TRUE)
    head(fit$scores)
    
    # figure
    load <- fit$loadings[,1:2] 
    plot(load,type="n") # set up plot 
    text(load,labels=names(data),cex=.7) # add variable names
    
    
    # PCA -------------------------------------------------------------------------
    
    # fit a pca
    fit <- princomp(data, cor=FALSE, )
    
    fit <- prcomp(data, center=T, scale=T)
    
    # summarize
    summary(fit) # print variance accounted for 
    loadings(fit) # pc loadings 
    fit$scores # the principal components
    
    # scree plot
    plot(fit,type="lines") # scree plot 
    
    # biplot
    biplot(fit)
    
    # more elaborate biplot
    g <- ggbiplot(fit, obs.scale = 1, var.scale = 1, ellipse = TRUE, circle = TRUE)
    g <- g + scale_color_discrete(name = '')
    g <- g + theme(legend.direction = 'horizontal', 
                   legend.position = 'top')
    print(g)
    
    View(data[1,])
    View(fit$loadings[,3])
    data$usage_of_mrs = as.matrix(data) %*% fit$loadings[,3]
    
    # plot in 3d
    plot3d(fit$scores)
    # text3d(fit$scores, texts=rownames(data))
    text3d(fit$loadings*10, texts=rownames(fit$loadings), col="red")
    coords <- NULL
    for (i in 1:nrow(fit$loadings)) {
      coords <- rbind(coords, rbind(c(0,0,0),fit$loadings[i,1:3]*10))
    }
    lines3d(coords, col="red", lwd=4)
    
    
    # the PCA analysis shows that persona5 has very low loading (.195) compared to the rest.
    # The biplot shows it goes in a completely different direction as the other questions
    # It is the question "I tend to repeat songs I like very often. ". Possibly it is not
    # a good explanation of the companionship and investment factors.
    
    # Therefore I remove it.
    
    # CONCEPT: time investment (7, 8, 10)
    # persona 7, 8 and 10 are pointing in approximately the exact same direction.
    # inspecting the questions, they are closely related to time investment
    # (when spending a lot of time, you will also probably be proud of your music)
    
    # CONCEPT: importance of technical aspects (9, 11)
    # , whereas persona 9 and 11 are more technical aspects. (expectations of MRS,
    # and sound quality)
    # This difference may explain the different direction between those two groups
    
    # CONCEPT: companionship (0, 3, 4)
    # persona 0, 3, and 4 are also extremely close together. Inspecting the questions,
    # this group composes the core concept of companionship:
    
    # 0: I actively avoid usage of social functions of music services
    # 3: I consider music listening to be a private thing
    # 4: I consider it very important to guard my privacy while listening to music from music services. 
    
    # while persona 4 is less related to the core concept, it seems logically related to persona 3.
    # and it is not strange to see it point to the same direction.
    
    # persona 6 is about relying on friends for recommendations. It points in between the concepts
    # companionship and technical aspects. Also, the strength is lower.
    # it seems logical that it's direction is close to that of companionship,
    # but not that it is close to the technical aspects dimension.
    # regarding this issue, this question is discarded.
    
    # CONCEPT: usage of MRS / absolute control (2, 13, 1)
    # This group (1, 2, 12, 13, 14) is more spread out in direction. First of all,
    # I notice that persona 12 and 14 have very low strength. They do not seem related
    # to music recommendation systems as well. I discard those questions
    # persona 1, 2, and 13 are about usage of streaming services and having absolute control
    # together, they form the concept of wanting to have absolute control, versus handing control to streaming/MRS services
    # they all tough a different part of the concept, but all-by-all point in the same direction
    
    
    # conpute Cronbach's alpha values --------------------------------------------------------------------------------------------------
    
    # get data with dropped variables
    data <- persona[-c(6, 7, 13, 15)]
    
    # CONCEPT: investment (+technical aspects) - alpha = .59, 95%-CI = [.38, .80]
    psych::alpha(data[c(6,7,8,9,10)])
    
    # CONCEPT: companionship - alpha = .53, 95%-CI = [.26, .79]
    psych::alpha(data[c(1,4,5)])
    
    # CONCEPT: usage of MRS / absolute control - alpha = .60, 95%-CI = [.38, .83]
    psych::alpha(data[c(2,3,11)])
    
    # without the removed questions: shows largest increase if 14 and 12 are indeed dropped
    psych::alpha(persona[c(2,3,13,14,15)])
    
    return(.Object)
  }
)

setGeneric(name="descriptiveStatistics",
  def=function(.Object)
  {
   standardGeneric("descriptiveStatistics")
  }
)

setMethod(f="descriptiveStatistics",
  signature="ExperimentDataHandler",
  definition=function(.Object)
  {
    cat("~~~ ExperimentDataHandler: descriptiveStatistics ~~~ \n")

    df = .Object@data_frame_wide
    df2 = .Object@data_frame
    
    # correlation matrix
    z=cor(df[,-c(1,15)])
    z[z == 1] <- NA #drop perfect
    z[abs(z) < 0.3] <- NA # drop less than abs(0.5)
    z <- na.omit(melt(z)) # melt! 
    z[order(-abs(z$value)),] # sort
    z
    # high correlations:
    # msi - investment                     .37
    # msi - perceive_personalized          .32
    # gender - companionship              -.47
    # neuroticism - extraversion          -.47
    # neuroticism - spotifyhours          -.38
    # investment - perceive_personalized   .32
    # objective_personalized - gender     -.29
    # age - neuroticism                   -.37
    
    
    # correlation between perceived personalization and objective personalization
    cor.test(df$perceive_personalized, df$objective_personalized)
    
    # show gender
    table(df$gender) # 0 = male, 1 = female
    
    # age
    ggplot(df,aes(x=age))+
      geom_histogram(position="identity",binwidth=1,colour="black")+
      theme_light()
    mean(df$age)
    sd(df$age)
    
    # show valence and tempo range
    df2$varying_mood = df2$varying_mood + 1
    df2$varying_mood[df2$varying_mood > 1] = 0
    p1 = ggplot(df2,aes(x=d_valence,group=varying_mood,fill=factor(varying_mood)))+
      geom_histogram(position="identity",alpha=0.5,binwidth=0.05,colour="black")+
      theme_light()+
      theme(legend.position="top")+
      scale_fill_discrete(name = "Varying: ", labels=c("mood", "tempo"))
    p2 = ggplot(df2,aes(x=d_tempo,group=varying_tempo,fill=factor(varying_tempo)))+
      geom_histogram(position="identity",alpha=0.5,binwidth=5,colour="black")+
      theme_light()+
      theme(legend.position="top")+
      scale_fill_discrete(name = "Varying: ", labels=c("mood", "tempo"))
    multiplot(p1, p2, cols=2)
    df2$varying_mood = df2$varying_mood + 1
    df2$varying_mood[df2$varying_mood > 1] = 0
    rm(p1, p2)
    
    # show energy range
    ggplot(df2,aes(x=d_energy,group=varying_mood,fill=factor(varying_mood)))+
      geom_histogram(position="identity",alpha=0.5,binwidth=0.05,colour="black")+
      theme_light()
    
    # show msi
    ggplot(df,aes(x=msi,fill="red"))+
      geom_histogram(binwidth=5,colour="black")+
      theme_light()
    shapiro.test(df$msi) # normally distributed
    mean(df$msi)
    sd(df$msi)
    
    # show companionship
    ggplot(df,aes(x=companionship,fill="red"))+
      geom_histogram(binwidth=1,colour="black")+
      theme_light()
    shapiro.test(df$companionship) # normally distributed
    mean(df$companionship)
    sd(df$companionship)
    
    # show investment
    ggplot(df,aes(x=investment,fill="red"))+
      geom_histogram(binwidth=1,colour="black")+
      theme_light()
    shapiro.test(df$investment) # normally distributed
    mean(df$investment)
    sd(df$investment)
    
    # show personality
    data_long <- gather(df, personality_trait, value, p_extraversion:p_openness, factor_key=TRUE)
    ggplot(data_long, aes(x=personality_trait, y=value, fill=personality_trait))+
      geom_boxplot()+
      guides(fill=FALSE)
    rm(data_long)
    
    # personalization
    data_long <- gather(df, personalization_type, value, perceive_personalized:objective_personalized, factor_key=TRUE)
    ggplot(data_long, aes(x=personalization_type, y=value, fill=personalization_type))+
      geom_boxplot()+
      guides(fill=FALSE)
    ggplot(df, aes(x=perceive_personalized, y=objective_personalized))+
      geom_point(shape=1)+
      geom_smooth(method=lm)
    rm(data_long)
    # no relation between both variables! Better personalization has no influence on perceived personalization
    
    rm(df,df2)
  }
)
    