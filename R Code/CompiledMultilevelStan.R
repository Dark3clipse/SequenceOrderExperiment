######################################################################
# Create the class that holds the compiled Stan models
# 
# Initializes Stan models
#
######################################################################

CompiledMultilevelStan <- setClass(
  
  # Set the name for the class
  "CompiledMultilevelStan",
  
  # Define the slots
  slots = c(
    
    #public
    model_H0_rt = "list",
    model_H1_rt = "list",
    model_HM_rt = "list",
    model_PE_rt = "list",
    model_H0_sm = "stanmodel",
    model_H1_sm = "stanmodel",
    model_HM_sm = "stanmodel",
    model_PE_sm = "stanmodel"
  ),
  
  # Set the default values for the slots. (optional)
  prototype=list(
    
    #public
    model_H0_sm = NULL,
    model_H1_sm = NULL,
    model_HM_sm = NULL,
    model_PE_sm = NULL
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
  signature="CompiledMultilevelStan",
  definition=function(.Object){
    cat("~~~ CompiledMultilevelStan: constructor ~~~ \n")
    
    # initialize stan engine
    rstan_options(auto_write = TRUE)
    options(mc.cores = parallel::detectCores())
    
    
    # load existing compiled models
    if (file.exists("stan/compiled/sm_H0.RData")){
      load("stan/compiled/sm_H0.RData")
      .Object@model_H0_sm = sm
      rm(sm)
    }
    if (file.exists("stan/compiled/sm_H1.RData")){
      load("stan/compiled/sm_H1.RData")
      .Object@model_H1_sm = sm
      rm(sm)
    }
    if (file.exists("stan/compiled/sm_HM.RData")){
      load("stan/compiled/sm_HM.RData")
      .Object@model_HM_sm = sm
      rm(sm)
    }
    if (file.exists("stan/compiled/sm_PE.RData")){
      load("stan/compiled/sm_PE.RData")
      .Object@model_PE_sm = sm
      rm(sm)
    }
    
    return(.Object) # return of the object
  }
)

# define compile method
setGeneric(name="compile",
  def=function(.Object)
  {
   standardGeneric("compile")
  }
)

setMethod(f="compile",
  signature="CompiledMultilevelStan",
  definition=function(.Object){
    cat("~~~ CompiledMultilevelStan: compile ~~~ \n")
    
    # compile the Stan models
    .Object <- compile_h0(.Object, FALSE)
    .Object <- compile_h1(.Object, FALSE)
    .Object <- compile_hm(.Object, FALSE)
    .Object <- compile_pe(.Object, FALSE)
    
    return(.Object) # return of the object
  }
)

setGeneric(name="compile_h0",
  def=function(.Object, force=TRUE)
  {
   standardGeneric("compile_h0")
  }
)

setMethod(f="compile_h0",
  signature="CompiledMultilevelStan",
  definition=function(.Object, force=TRUE){
    cat("~~~ CompiledMultilevelStan: compile_h0 ~~~ \n")
    
    # block recompile is not specifically called for
    if (!is.null(.Object@model_H0_sm) && force==FALSE){
      cat("Precompiled model already in memory. Compilation skipped. \n")
      return(.Object)
    }
    
    # compile the Stan model
    .Object@model_H0_rt <- stanc("stan/multilevel_ricp_H0r.stan")
    .Object@model_H0_sm <- stan_model(stanc_ret = .Object@model_H0_rt, model_name="General Multilevel Model - ricp, robust, H0", verbose=FALSE)
    
    # save to disk
    sm = .Object@model_H0_sm
    save('sm', file = 'stan/compiled/sm_H0.RData')
    
    return(.Object) # return of the object
  }
)

setGeneric(name="compile_h1",
  def=function(.Object, force=TRUE)
  {
   standardGeneric("compile_h1")
  }
)

setMethod(f="compile_h1",
  signature="CompiledMultilevelStan",
  definition=function(.Object, force=TRUE){
    cat("~~~ CompiledMultilevelStan: compile_h1 ~~~ \n")
    
    # block recompile is not specifically called for
    if (!is.null(.Object@model_H1_sm) && force==FALSE){
      cat("Precompiled model already in memory. Compilation skipped. \n")
      return(.Object)
    }
    
    # compile the Stan model
    .Object@model_H1_rt <- stanc("stan/multilevel_ricp_H1r.stan")
    .Object@model_H1_sm <- stan_model(stanc_ret = .Object@model_H1_rt, model_name="General Multilevel Model - ricp, robust, H1", verbose=FALSE)
    
    # save to disk
    sm = .Object@model_H1_sm
    save('sm', file = 'stan/compiled/sm_H1.RData')
    
    return(.Object) # return of the object
  }
)

setGeneric(name="compile_hm",
  def=function(.Object, force=TRUE)
  {
   standardGeneric("compile_hm")
  }
)

setMethod(f="compile_hm",
  signature="CompiledMultilevelStan",
  definition=function(.Object, force=TRUE){
    cat("~~~ CompiledMultilevelStan: compile_hm ~~~ \n")
    
    # block recompile is not specifically called for
    if (!is.null(.Object@model_HM_sm) && force==FALSE){
      cat("Precompiled model already in memory. Compilation skipped. \n")
      return(.Object)
    }
    
    # compile the Stan model
    .Object@model_HM_rt <- stanc("stan/multilevel_ricp_H1r_multi.stan")
    .Object@model_HM_sm <- stan_model(stanc_ret = .Object@model_HM_rt, model_name="General Multilevel Model - ricp, robust, HM", verbose=FALSE)
    
    # save to disk
    sm = .Object@model_HM_sm
    save('sm', file = 'stan/compiled/sm_HM.RData')
    
    return(.Object) # return of the object
  }
)

setGeneric(name="compile_pe",
  def=function(.Object, force=TRUE)
  {
   standardGeneric("compile_pe")
  }
)

setMethod(f="compile_pe",
  signature="CompiledMultilevelStan",
  definition=function(.Object, force=TRUE){
    cat("~~~ CompiledMultilevelStan: compile_pe ~~~ \n")
    
    # block recompile is not specifically called for
    if (!is.null(.Object@model_PE_sm) && force==FALSE){
      cat("Precompiled model already in memory. Compilation skipped. \n")
      return(.Object)
    }
    
    # compile the Stan model
    .Object@model_PE_rt <- stanc("stan/multilevel_ricp_pest.stan")
    .Object@model_PE_sm <- stan_model(stanc_ret = .Object@model_PE_rt, model_name="General Multilevel Model - parameter estimation", verbose=FALSE)
    
    # save to disk
    sm = .Object@model_PE_sm
    save('sm', file = 'stan/compiled/sm_PE.RData')
    
    return(.Object) # return of the object
  }
)