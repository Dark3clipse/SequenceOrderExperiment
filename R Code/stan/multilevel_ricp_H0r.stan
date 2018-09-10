data {
  int<lower=1>                          N_transitions;            //the number of transitions
  int<lower=1>                          N_participants;           //the number of participants
  //int<lower=1>                          N_coefficients;           //the number of coefficients
  int<lower=1,upper=N_participants>     id[N_transitions];        //vector of group indices
  //matrix[N_transitions,N_coefficients]  X;                        //the data
  vector[N_transitions]                 y;                        //the response variable
  int<lower=0>                          N_missing;                //number of missing values
  int<lower=0,upper=N_transitions>      missing[N_transitions];   //indicators for missing data
}
parameters {
  real<lower=0.01>                      sigma;                    //sd of the error
  real                                  lambda;                   //population-level intercept mean
  real<lower=0.01>                      tau;                      //population level intercept sd
  vector[N_participants]                d;                        //individual random effects
  //vector[N_coefficients]                beta;                     //coefficients
  real<lower=0>                         nuMinusOne;               //broadness variable of t-distribution
  vector[N_missing]                     y_mis;                    //missing data
}

transformed parameters {
  real<lower=1>                         nu;                       //broadness variable of t-distribution
  vector[N_participants]                beta_0i;                  //intercept for each participant
  vector[N_transitions]                 mu;                       //linear predictor
  real<lower=0.0001>                    sigma2;                   //error variance
  real<lower=0.0001>                    tau2;                     //error variance
  
  nu = nuMinusOne + 1;                                            //add one to nuMinusOne
  
  for (n in 1:N_participants){
    beta_0i[n] = lambda + d[n];
  }
  
  for(n in 1:N_transitions){
    mu[n] = beta_0i[id[n]] /*+ X[n] * beta*/;
  }
  
  sigma2 = sigma * sigma;
  tau2 = tau * tau;
}
model {
  
  // PRIOR: Cauchy prior on the population mean
  target += cauchy_lpdf(lambda | 0, 5);
  
  // PRIOR: half-cauchy prior on the population sd
  target += cauchy_lpdf(tau | 0, 5) - cauchy_lccdf(0 | 0, 5);
  
  // PRIOR: nu ~ exp( 1 / 29 ) + 1
  target += exponential_lpdf(nuMinusOne | 1/29.0) - exponential_lccdf(0 | 1/29.0);
  
  // PRIOR: Jeffrey's prior on sigma
  target += -log(sigma2);
  
  // PRIOR: random intercepts
  target += normal_lpdf(d | 0, tau);

  
  for(n in 1:N_transitions){
    if (missing[n] == 0){
      
      // LIKELIHOOD
      target += student_t_lpdf(y[n] | nu, mu[n], sigma);
    }else{
      
      // PRIOR: missing value
      target += student_t_lpdf(y_mis[missing[n]] | nu, mu[n], sigma);
    }
  }
}
generated quantities {
  vector[N_transitions] yhat;           // linear predictor
  real<lower=0> rss;                    // residual sum of squares
  real<lower=0> totalss;                // total SS              
  real Rsq;                             // Rsq
  
  for(n in 1:N_transitions){
    yhat[n] = beta_0i[id[n]] /*+ X[n] * beta*/;
  }
  
  rss = dot_self(y-yhat);               // compute residuals
  totalss = dot_self(y-mean(y));        // compute total sum of squares
  Rsq = 1 - rss/totalss;                // compute R-squared
}
