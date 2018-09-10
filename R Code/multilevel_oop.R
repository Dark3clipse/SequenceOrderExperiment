######################################################################
# Multilevel analysis using Stan, object-oriented
# 
# Author: Sophia Hadash
#
######################################################################

# clear workspace
graphics.off() # This closes all of R's graphics windows.
rm(list=ls())  # Careful! This clears all of R's memory!
setwd("D:/Dropbox/Dropbox/HTI Abroad Project/experiment_data/src")

# load libraries and source
library(tidyr)
library(ggplot2)
library(rstan)
library(reshape2)
library(Rmisc)
library(dplyr)
library(shinystan)
library(bridgesampling)
library(bayesplot)
library(plotly)
library(varhandle)
library(data.table)
library(pracma)
library(nFactors)
library(psy)
library(ggbiplot)
library(devtools)
library(GPArotation)
library(rgl)
source("ExperimentDataHandler.R")
source("CompiledMultilevelStan.R")
source("MultilevelModelComparison.R")


# compile models ---------------------------------------------------------------------------------------------

# create object
stan_models <- CompiledMultilevelStan()

# compile models
stan_models <- compile(stan_models)

# recompile a specific model (e.g. after a change is made to it)
stan_models <- compile_h0(stan_models)
stan_models <- compile_h1(stan_models)
stan_models <- compile_hm(stan_models)
stan_models <- compile_pe(stan_models)


# generate / load the data -----------------------------------------------------------------------------------

dh <- ExperimentDataHandler()

# set the data simulation parameters
cat(getVariableList(dh))
interaction_effects = matrix(rep(0, 21*21), nrow=21, ncol=21)
dh <- setSimulationParameters(dh, 
                              N=20, 
                              sigma=1, 
                              tau=.7, 
                              effect_size=c(.5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 
                              interaction_effects=interaction_effects)

# generate sample data
dh <- generateData(dh)
dh@truth_gamma_sample
#dh@data_frame$flow = 0
dh <- addMissingValues(dh, .1)
View(dh@data_frame)

# read experiment data
dhr <- ExperimentDataHandler()
dhr <- readData(dhr, TRUE)

# descriptive statistics
View(dhr@data_frame)
View(dhr@data_frame_wide)
descriptiveStatistics(dhr)
sum(is.na(dhr@data_frame$flow))

# execute parameter estimation ----------------------------------------------------------------------------

mc <- MultilevelModelComparison(stan_models, dhr)
mc <- setModelDefinition(mc, rbind(c("dvalence", "dtempo", "companionship", "p_agreeableness", "p_openness", 
                                     "dvalence#companionship", "dvalence#p_extraversion", "dvalence#p_agreeableness", "dvalence#p_conscientiousness", "dvalence#p_openness", "dvalence#spotifyhours", "dvalence#perceive_personalized", "dvalence#gender",
                                     "dtempo#spotifyhours", "dtempo#perceive_personalized")))
mc@model_definition_int[,,1]
mc <- fitParameterEstimator(mc)
printModelTable(mc, 1, TRUE)
launchShinyStan(mc, 1, TRUE)

# traditional statistical analysis -----------------------------------------------------------------------------
library(lme4)
fisher.model = lmer(flow ~ (1|participant) + d_valence + d_tempo + msi + companionship + investment + p_extraversion + p_agreeableness + p_conscientiousness + p_neuroticism + p_openness + perceive_personalized + objective_personalized + gender + age + 
                      d_valence:msi + d_valence:companionship + d_valence:investment + d_valence:p_extraversion + d_valence:p_agreeableness + d_valence:p_conscientiousness + d_valence:p_neuroticism + d_valence:p_openness + d_valence:perceive_personalized + d_valence:objective_personalized + d_valence:gender + d_valence:age + 
                      d_tempo:msi + d_tempo:companionship + d_tempo:investment + d_tempo:p_extraversion + d_tempo:p_agreeableness + d_tempo:p_conscientiousness + d_tempo:p_neuroticism + d_tempo:p_openness + d_tempo:perceive_personalized + d_tempo:objective_personalized + d_tempo:gender + d_tempo:age, data=dhr@data_frame)
summary(fisher.model)

fisher.model.null = lmer(flow ~ (1|participant), data=dhr@data_frame, REML=FALSE)
fisher.model.h1   = lmer(flow ~ (1|participant) + d_tempo, data=dhr@data_frame, REML=FALSE)
anova(fisher.model.null, fisher.model.h1)

fisher.model.null = lmer(flow ~ (1|participant) + d_valence, data=dhr@data_frame, REML=FALSE)
fisher.model.h1   = lmer(flow ~ (1|participant) + d_valence + d_tempo, data=dhr@data_frame, REML=FALSE)
anova(fisher.model.null, fisher.model.h1)

fisher.model.null = lmer(flow ~ (1|participant) + d_valence, data=dhr@data_frame, REML=FALSE)
fisher.model.h1 = lmer(flow ~ (1|participant) + d_valence + d_valence:companionship, data=dhr@data_frame, REML=FALSE)
fisher.model.h2 = lmer(flow ~ (1|participant) + d_valence + d_valence:p_agreeableness, data=dhr@data_frame, REML=FALSE)
fisher.model.h3 = lmer(flow ~ (1|participant) + d_valence + d_valence:spotifyhours, data=dhr@data_frame, REML=FALSE)
fisher.model.h4 = lmer(flow ~ (1|participant) + d_valence + d_valence:perceive_personalized, data=dhr@data_frame, REML=FALSE)
fisher.model.h5 = lmer(flow ~ (1|participant) + d_valence + d_valence:gender, data=dhr@data_frame, REML=FALSE)
fisher.model.h6 = lmer(flow ~ (1|participant) + d_valence + d_tempo:spotifyhours, data=dhr@data_frame, REML=FALSE)
fisher.model.h7 = lmer(flow ~ (1|participant) + d_valence + d_tempo:perceive_personalized, data=dhr@data_frame, REML=FALSE)

# execute model comparison -----------------------------------------------------------------------------------

# create object
mc <- MultilevelModelComparison(stan_models, dhr)

# show available variable names
dhr@xvar

# define which models are to be compared
mc <- setModelDefinition(mc, rbind(c("dvalence", ""),
                                   c("dvalence", "dvalence#companionship"),
                                   c("dvalence", "dvalence#p_agreeableness"),
                                   c("dvalence", "dvalence#spotifyhours"),
                                   c("dvalence", "dvalence#perceive_personalized"),
                                   c("dvalence", "dvalence#gender"),
                                   c("dvalence", "dtempo#spotifyhours"),
                                   c("dvalence", "dtempo#perceive_personalized")))


mc <- setModelDefinition(mc, rbind(c(""),
                                   c("dvalence")))

mc <- setModelDefinition(mc, rbind(c(""),
                                   c("dtempo")))

mc <- setModelDefinition(mc, rbind(c("dvalence", ""),
                                   c("dvalence", "dtempo")))


mc <- setModelDefinition(mc, rbind(c("dvalence", ""),
                                   c("dvalence", "msi"),
                                   c("dvalence", "companionship"),
                                   c("dvalence", "investment"),
                                   c("dvalence", "p_extraversion"),
                                   c("dvalence", "p_agreeableness"),
                                   c("dvalence", "p_conscientiousness"),
                                   c("dvalence", "p_neuroticism"),
                                   c("dvalence", "p_openness"),
                                   c("dvalence", "spotifyhours"),
                                   c("dvalence", "perceive_personalized"),
                                   c("dvalence", "objective_personalized"),
                                   c("dvalence", "gender"),
                                   c("dvalence##msi", ""),
                                   c("dvalence##companionship", ""),
                                   c("dvalence##investment", ""),
                                   c("dvalence##p_extraversion", ""),
                                   c("dvalence##p_agreeableness", ""),
                                   c("dvalence##p_conscientiousness", ""),
                                   c("dvalence##p_neuroticism", ""),
                                   c("dvalence##p_openness", ""),
                                   c("dvalence##spotifyhours", ""),
                                   c("dvalence##perceive_personalized", ""),
                                   c("dvalence##objective_personalized", ""),
                                   c("dvalence##gender", "")))

# check model definition
mc@model_definition
mc@model_definition_int[,,1]

# fit the models
mc <- fitAll(mc)

# output -----------------------------------------------------------------------------------------------------

df = dhr@data_frame[,c(1,2,4,24)]
df = dcast(setDT(df), participant ~ measurement, value.var = c("similar", "flow")) 
write.csv(df, file="output/data.csv")

# show model comparison table
View(getComparisonTable(mc))

# print individual model tables
printModelTable(mc, 1)
printModelTable(mc, 2)

# launch shiny stan
launchShinyStan(mc, 1)

# plot posterior distribution
plot_title <- ggtitle("Posterior distributions",
                      "with medians and 95% intervals")
mcmc_areas(as.matrix(mc@fit_PE),
           pars = c("Rsq"),
           prob = 0.95) + plot_title

# plot divergence
color_scheme_set("darkgray")
mcmc_scatter(
  as.matrix(mc@fit_PE),
  pars = c("beta[1]", "sigma"), 
  np = nuts_params(mc@fit_PE), 
  np_style = scatter_style_np(div_color = "green", div_alpha = 0.8)
)

# NUTS energy statistics
color_scheme_set("red")
np <- nuts_params(mc@fit_PE)
mcmc_nuts_energy(np) + ggtitle("NUTS Energy Diagnostic")