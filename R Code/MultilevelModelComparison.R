######################################################################
# Create the class that fits the multilevel models
# 
# Able to fit models, do model comparison, and output results
#
######################################################################

MultilevelModelComparison <- setClass(
  
  # Set the name for the class
  "MultilevelModelComparison",
  
  # Define the slots
  slots = c(
    
    #public
    stan_models = "CompiledMultilevelStan",
    dh = "ExperimentDataHandler",
    model_definition = "matrix",
    model_definition_int = "array",
    result = "data.frame",
    
    #private
    test_coefficients = "vector",
    interaction_coefficients = "matrix",
    Nchains = "numeric",
    lastfit = "stanfit",
    fit_H0 = "stanfit",
    fit_PE = "stanfit",
    lastModelName = "character",
    fit_M = "list",
    fit_M_bridge = "list"
    
  ),
  
  # Set the default values for the slots. (optional)
  prototype=list(
    
    #public
    
    #private
    test_coefficients = c(),
    interaction_coefficients = c(),
    Nchains = parallel::detectCores(),
    fit_H0 = NULL
  ),
  
  # Make a function that can test to see if the data is consistent.
  # This is not called if you have an initialize function defined!
  validity=function(object)
  {
    return(TRUE)
  }
)

# define constructor
setMethod(f="initialize",
  signature="MultilevelModelComparison",
  definition=function(.Object, models, dh){
    cat("~~~ MultilevelModelComparison: constructor ~~~ \n")
    
    # set the stan models
    .Object@stan_models <- models
    .Object@dh <- dh
    
    return(.Object) # return of the object
  }
)

# define method for setting the model definition
setGeneric(name="setModelDefinition",
  def=function(.Object, mdef)
  {
   standardGeneric("setModelDefinition")
  }
)

setMethod(f="setModelDefinition",
  signature="MultilevelModelComparison",
  definition=function(.Object, mdef){
    cat("~~~ MultilevelModelComparison: setModelDefinition ~~~ \n")
    
    .Object@model_definition = matrix(ncol=length(.Object@dh@xvar)-1, nrow=0)
    .Object@model_definition_int = array(0, dim=c(length(.Object@dh@xvar)-1, length(.Object@dh@xvar)-1, nrow(mdef)))
    for (i in 1:nrow(mdef)){
      .Object <- varnameToVector(.Object, mdef[i,])
      .Object@model_definition = rbind(.Object@model_definition, .Object@test_coefficients)
      .Object@model_definition_int[,,i] = .Object@interaction_coefficients
    }
    #print(.Object@model_definition)
    
    return(.Object) # return of the object
  }
)

# define method for converting variable names to vector format
setGeneric(name="varnameToVector",
  def=function(.Object, variables)
  {
   standardGeneric("varnameToVector")
  }
)

setMethod(f="varnameToVector",
  signature="MultilevelModelComparison",
  definition=function(.Object, variables){
    cat("~~~ MultilevelModelComparison: varnameToVector ~~~ \n")
    
    xvar = .Object@dh@xvar[c(-1)]
    
    r = rbind(rep(0, length(xvar)), rep(0, length(xvar)))
    rint = matrix(rep(0, length(xvar)^2), nrow=length(xvar), ncol=length(xvar))
    for (var in variables){
      
      # check for interaction effects
      vars = strsplit(var, "##", TRUE)[[1]]
      vars_intonly = strsplit(var, "#", TRUE)[[1]]

      if((!("" %in% vars_intonly) & length(vars_intonly)==2) | length(vars)==2){
        # apply interaction effects
        if (length(vars_intonly)!=2){
          vars2 = vars
        }else{
          vars2 = vars_intonly
        }
        
        rint[which(xvar == vars2[1]), which(xvar == vars2[2])] = 1
      }
      
      # apply main effects
      if (!(!("" %in% vars_intonly) & length(vars_intonly)==2)){
        for (v in vars){
          res = sapply(lapply(xvar, function(ch) strcmp(v, ch)==TRUE), function(x) as.numeric(x[1]==TRUE))
          for (i in 1:length(res)){
            rint[i, i] = rint[i, i] + res[i]
            if (rint[i, i] > 1){
              rint[i, i] = 1
            }
            res[i] = res[i] * i
          }
          r = rbind(r, t(res))
        }
      }
    }
    .Object@test_coefficients = colSums(r, na.rm = FALSE, dims = 1)
    .Object@interaction_coefficients = rint
    #cat(.Object@test_coefficients)
    
    return(.Object) # return of the object
  }
)

# define method for fitting models
setGeneric(name="fit",
  def=function(.Object, model_def, estimation = FALSE)
  {
   standardGeneric("fit")
  }
)

setMethod(f="fit",
  signature="MultilevelModelComparison",
  definition=function(.Object, model_def, estimation = FALSE){
    cat("~~~ MultilevelModelComparison: fit ~~~ \n")
    
    #.Object=mc
    #model_def=mc@model_definition_int[,,1]
    
    .Object <- getModelName(.Object, model_def)
    cat("model: ", .Object@lastModelName, "\n")
        
    # hold the number of coefficients
    ncoefs = 0
    
    # generate coefficient identification array for the main effects
    main_coefs = diag(model_def)
    for(i in 1:length(main_coefs)){
      if (main_coefs[i] == 1){
        main_coefs[i] = i
        ncoefs = ncoefs + 1
      }
    }

    # strip the intercept from the data frame
    data = .Object@dh@data_frame
    data$intercept <- NULL
    
    # get the Xmatrix for the main coefficients
    Xmatrix <- data.matrix(data[, -c(1:length(.Object@dh@levelvar), ncol(data))])
    Xmatrix_i <- Xmatrix[,c(main_coefs)]
    
    # add interaction effects to the Xmatrix
    for(i in 1:length(main_coefs)){
      for(j in 1:length(main_coefs)){
        if (i==j){
          next
        }else{
          if (model_def[i, j] != 0){
            Xmatrix_i <- cbind(Xmatrix_i, Xmatrix[,c(i)] * Xmatrix[,c(j)])
            colnames(Xmatrix_i)[ncol(Xmatrix_i)] <- paste(colnames(Xmatrix)[i],"#",colnames(Xmatrix)[j], sep="")
            ncoefs = ncoefs + 1
          }
        }
      }
    }

    if (!is.null(dim(Xmatrix_i)) && length(dim(Xmatrix_i))==2 && dim(Xmatrix_i)[2] == 1){
      Xmatrix_i = Xmatrix_i[,1]
    }
    Xmatrix = Xmatrix_i
    rm(Xmatrix_i)
    
    # find missing values
    missing = c()
    N_missing = 0
    for (i in 1:nrow(data)){
      if (is.na(data$flow[i])){
        N_missing <- N_missing + 1
        missing <- c(missing, N_missing)
        data$flow[i] <- 0
      }else{
        missing <- c(missing, 0)
      }
    }
    
    #View(Xmatrix)
    #return(.Object)
    
    if (estimation == TRUE){
      
      sampdat <- list(N_transitions=nrow(data),
                      N_participants=length(unique(data$participant)),
                      N_coefficients=ncol(Xmatrix),
                      id=as.numeric(data$participant),
                      X=Xmatrix,
                      y=data$flow,
                      N_missing = N_missing,
                      missing = missing)
      
      .Object@lastfit = sampling(.Object@stan_models@model_PE_sm, data=sampdat, chains=.Object@Nchains)
    
    # fit H0 model
    }else if (ncoefs == 0){
      sampdat <- list(N_transitions=nrow(data),
                      N_participants=length(unique(data$participant)),
                      id=as.numeric(data$participant),
                      y=data$flow,
                      N_missing = N_missing,
                      missing = missing)
      
      if (is.null(.Object@fit_H0)){
        .Object@lastfit = sampling(.Object@stan_models@model_H0_sm, data=sampdat, chains=.Object@Nchains)
        .Object@fit_H0 = .Object@lastfit
      }else{
        .Object@lastfit = .Object@fit_H0
        cat("Skipping H0-fitting because a fit is already available. \n")
      }
      
    # fit H1 model
    }else if (ncoefs == 1){
      sampdat <- list(N_transitions=nrow(data),
                      N_participants=length(unique(data$participant)),
                      id=as.numeric(data$participant),
                      X=Xmatrix,
                      y=data$flow,
                      N_missing = N_missing,
                      missing = missing)
      
      .Object@lastfit = sampling(.Object@stan_models@model_H1_sm, data=sampdat, chains=.Object@Nchains)
      
    # fit HM model
    }else{
      sampdat <- list(N_transitions=nrow(data),
                      N_participants=length(unique(data$participant)),
                      N_coefficients=ncol(Xmatrix),
                      id=as.numeric(data$participant),
                      X=Xmatrix,
                      y=data$flow,
                      N_missing = N_missing,
                      missing = missing)
      
      .Object@lastfit = sampling(.Object@stan_models@model_HM_sm, data=sampdat, chains=.Object@Nchains)
    }
    
    return(.Object) # return of the object
  }
)

setGeneric(name="getModelName",
  def=function(.Object, model_def)
  {
   standardGeneric("getModelName")
  }
)

setMethod(f="getModelName",
  signature="MultilevelModelComparison",
  definition=function(.Object, model_def){
    cat("~~~ MultilevelModelComparison: getModelName ~~~ \n")
    
    if (length(model_def)==0 || sum(model_def > 0) == 0){
      .Object@lastModelName = "Null model"
      
    }else{
      cnames = .Object@dh@xvar[-c(1, .Object@dh@xvar_excluded)]
      str = ""
      first = TRUE
      for (i in 1:nrow(model_def)){
        for (j in 1:nrow(model_def)){
          if (model_def[i,j] > 0){
            if (!first){
              str <- paste(str, ", ", sep="")
            }
            if (i==j){
              str <- paste(str, cnames[i], sep="")
            }else{
              str <- paste(str, cnames[i], "#", cnames[j], sep="")
            }
            
            first = FALSE
          }
        }
      }
      
      .Object@lastModelName = str
    }
    
    return(.Object) # return of the object
  }
)


# define method for fitting all models and running the comparison
setGeneric(name="fitAll",
  def=function(.Object)
  {
   standardGeneric("fitAll")
  }
)

setMethod(f="fitAll",
  signature="MultilevelModelComparison",
  definition=function(.Object){
    cat("~~~ MultilevelModelComparison: fitAll ~~~ \n")
    
    # get number of models
    N_model = length(.Object@model_definition_int[1,1,])
    if (N_model <= 0){
      cat("Model definition empty or invalid.")
      return(.Object)
    }
    
    # clear model fit results
    .Object@fit_M <- vector("list", N_model)
    .Object@fit_M_bridge <- vector("list", N_model)
    
    # compute equal prior
    prior_model = 1/N_model
    
    # fit the models
    for (i in 1:N_model){
      cat(paste("fitting model", i, "of", N_model,"\n"))
      .Object <- fit(.Object, .Object@model_definition_int[,,i])
      .Object@fit_M[[i]] <- .Object@lastfit
      .Object@fit_M_bridge[[i]] <- bridge_sampler(.Object@fit_M[[i]], silent=TRUE)
    }
    
    # store results
    df_out <- data.frame(numeric(0), numeric(0), numeric(0), numeric(0), numeric(0))
    cnames = c("P(M)", "P(M|D)", "log LL", "BF_10", "error %")
    colnames(df_out) <- cnames
    
    str="post_prob("
    for(i in 1:length(.Object@fit_M_bridge)){
      str = paste(str, ".Object@fit_M_bridge[[",i,"]]", sep="")
      if (i < length(.Object@fit_M_bridge)){
        str = paste(str,", ", sep="")
      }
    }
    str = paste(str, ")", sep="")
    posteriors = eval(parse(text=str))
    
    #posteriors <- post_prob(fit_M.bridge[[1]], fit_M.bridge[[2]], fit_M.bridge[[3]], fit_M.bridge[[4]])
    for (i in 1:N_model){
      dfn <- data.frame(prior_model,
                        posteriors[i],
                        .Object@fit_M_bridge[[i]]$logml, 
                        bf(.Object@fit_M_bridge[[i]], .Object@fit_M_bridge[[1]])['bf'][[1]], 
                        error_measures(.Object@fit_M_bridge[[i]])$percentage)
      colnames(dfn) <- cnames
      .Object <- getModelName(.Object, .Object@model_definition_int[,,i])
      rownames(dfn) <- .Object@lastModelName
      df_out <- rbind(df_out, dfn)
    }
    .Object@result = df_out
    
    # save output to disk
    mc_disk = .Object
    save('mc_disk', file = paste('output/model_fit_',format(Sys.time(), "%Y%m%d_%H%M%S_"),'.RData', sep=""))
    rm(mc_disk)
    
    return(.Object) # return of the object
  }
)


# define output methods
setGeneric(name="getComparisonTable",
  def=function(.Object)
  {
   standardGeneric("getComparisonTable")
  }
)

setMethod(f="getComparisonTable",
  signature="MultilevelModelComparison",
  definition=function(.Object){
    cat("~~~ MultilevelModelComparison: getComparisonTable ~~~ \n")
    return(.Object@result)
  }
)

# define output methods
setGeneric(name="printModelTable",
  def=function(.Object, modelnr, estimation=FALSE)
  {
   standardGeneric("printModelTable")
  }
)

setMethod(f="printModelTable",
  signature="MultilevelModelComparison",
  definition=function(.Object, modelnr, estimation=FALSE){
    cat("~~~ MultilevelModelComparison: printModelTable ~~~ \n")
    
    p = c("Rsq", "sigma", "lambda", "tau", "nu")
    
    coefs = .Object@model_definition[modelnr,]
    if (length(coefs)!=0 && sum(coefs > 0) > 0){
      if (estimation == TRUE){
        p = c(p, "beta")
      }else{
        p = c(p, "beta", "delta")
      }
    }
    if (estimation == FALSE){
      print(.Object@fit_M[[modelnr]], pars = p)
    }else{
      print(.Object@fit_PE, pars = p)
    }
  }
)

setGeneric(name="launchShinyStan",
  def=function(.Object, modelnr, estimate=FALSE)
  {
   standardGeneric("launchShinyStan")
  }
)

setMethod(f="launchShinyStan",
  signature="MultilevelModelComparison",
  definition=function(.Object, modelnr, estimate=FALSE){
    cat("~~~ MultilevelModelComparison: launchShinyStan ~~~ \n")
    
    if (estimate == FALSE){
      launch_shinystan(as.shinystan(.Object@fit_M[[modelnr]]))
    }else{
      launch_shinystan(as.shinystan(.Object@fit_PE))
    }
    
  }
)

setGeneric(name="fitParameterEstimator",
  def=function(.Object)
  {
   standardGeneric("fitParameterEstimator")
  }
)
setMethod(f="fitParameterEstimator",
  signature="MultilevelModelComparison",
  definition=function(.Object){
    cat("~~~ MultilevelModelComparison: fitParameterEstimator ~~~ \n")
    
    # check number of models
    N_model = length(.Object@model_definition_int[1,1,])
    if (N_model > 1){
      cat("Model definition contains multiple models. Only first model is used.")
    }else if (N_model < 1){
      cat("Model definition should contain at least 1 model.")
      return(.Object)
    }
    
    # fit the model
    .Object <- fit(.Object, .Object@model_definition_int[,,1], TRUE)
    .Object@fit_PE <- .Object@lastfit

    # save output to disk
    mc_disk = .Object
    save('mc_disk', file = paste('output/model_fit_',format(Sys.time(), "%Y%m%d_%H%M%S_"),'.RData', sep=""))
    rm(mc_disk)
    
    return(.Object) # return of the object
  }
)