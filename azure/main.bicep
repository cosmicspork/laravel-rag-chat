@description('The name of the application that will be deployed')
param appName string = 'laravel-rag-chat'

@description('The region (i.e. datacenter) where the resources will be deployed')
param loc string = 'northcentralus' 

@description('An acronym for the location (i.e. datacenter) where the resources will be deployed')
param locAbrv string = 'ncus'

@description('The environment name where the resources will be deployed (i.e. dev, test, prod)')
param env string = 'prod'

/*
 * Non-sensitive parameters supplied by the deployment pipeline
 */
param dockerRegistryUrl string

param dockerRegistryUsername string

param dockerImageNamespace string

param databaseUser string

param azureOpenAIEndpoint string

param azureSearchEndpoint string

param samlIdpEntityId string
param samlIdpSsoUrl string
param samlIdpSloUrl string

/*
 * Sensitive parameters — supplied via secrets at deployment time, never checked into source control
 */
@secure()
param webAppKey string

@secure()
param databasePass string

@secure()
param azureOpenAIKey string

@secure()
param azureSearchKey string

@secure()
param dockerRegistryPassword string

@secure()
param samlIdpCert string
@secure()
param samlSpCert string
@secure()
param samlSpKey string

/* ************* End of Parameters section ************* */


var rg = resourceGroup('rg-${appName}-${locAbrv}-${env}')


@description('Creates the Azure PostgreSQL server and database for the logging user usage')
module postgresqlModule 'modules/postgresql.bicep' = {
  name: 'postgresqlModule'
  scope: rg
  params: {
    appName: appName
    loc: loc
    locAbrv: locAbrv
    env: env
    dbUser: databaseUser
    dbPass: databasePass
  }
}

@description('Creates the application container to be deployed to, the App Service Plan (web server), and the App Service (web application the container is deployed to)')
module webApplicationModule 'modules/webApi.bicep' = {
  name: 'webApplicationModule'
  scope: rg
  params: {
    appName: appName
    loc: loc
    locAbrv: locAbrv
    env: env
    webAppKey: webAppKey
    dbUser: databaseUser
    dbPass: databasePass
    postgresqlServerFqdn: postgresqlModule.outputs.postgresqlServerFqdn
    postgresqlDatabaseName: postgresqlModule.outputs.postgresqlDatabaseName
    azureOpenAIEndpoint: azureOpenAIEndpoint
    azureOpenAIKey: azureOpenAIKey
    azureSearchEndpoint: azureSearchEndpoint
    azureSearchKey: azureSearchKey
    dockerRegistryUsername: dockerRegistryUsername
    dockerRegistryPassword: dockerRegistryPassword
    dockerRegistryUrl: dockerRegistryUrl
    dockerImageNamespace: dockerImageNamespace
    samlIdpEntityId: samlIdpEntityId
    samlIdpSsoUrl: samlIdpSsoUrl
    samlIdpSloUrl: samlIdpSloUrl
    samlIdpCert: samlIdpCert
    samlSpCert: samlSpCert
    samlSpKey: samlSpKey
  }
}
