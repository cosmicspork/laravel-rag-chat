param appName string

param loc string

param locAbrv string

param env string

param dbUser string

@secure()
param webAppKey string

@secure()
param dbPass string

param postgresqlServerFqdn string
param postgresqlDatabaseName string

param azureOpenAIEndpoint string
@secure()
param azureOpenAIKey string

param azureSearchEndpoint string
@secure()
param azureSearchKey string

param dockerRegistryUsername string
@secure()
param dockerRegistryPassword string
param dockerRegistryUrl string
param dockerImageNamespace string

param samlIdpEntityId string
param samlIdpSsoUrl string
param samlIdpSloUrl string

@secure()
param samlIdpCert string
@secure()
param samlSpCert string
@secure()
param samlSpKey string


var dockerImageName = 'laravel-rag-chat'
var dockerImageVersion = 'latest'
var dockerRegistryStartupCommand = ''

var appServiceName = 'app-${appName}-${locAbrv}-${env}'

resource appServicePlan 'Microsoft.Web/serverfarms@2024-04-01' = {
  name: 'asp-${appName}-${locAbrv}-${env}'
  location: loc
  sku: {
    name: 'B1'
    capacity: 1
  }
  kind: 'linux'
  properties: {
    reserved: true // specifying the 'kind' above as linux is not enough, this reserved value must also be set to true for linux, false for Windows...it's not very obvious.  see: https://learn.microsoft.com/en-us/azure/templates/microsoft.web/serverfarms?pivots=deployment-language-bicep#appserviceplanproperties
  }
}


resource appSerivce 'Microsoft.Web/sites@2024-04-01' = {
  name: appServiceName
  location: loc
  kind: 'app,linux,container'
  identity: {
    type: 'SystemAssigned'
  }
  properties: {
    serverFarmId: appServicePlan.id
    httpsOnly: true
    siteConfig: {
      linuxFxVersion: 'DOCKER|${dockerRegistryUrl}/${dockerImageNamespace}/${dockerImageName}:${dockerImageVersion}'
      appCommandLine: dockerRegistryStartupCommand
      healthCheckPath: '/api/health'
      
      appSettings: [
        // all App Settings below are associated with and used by the PHP application running inside this Azure App Service
        {
          name: 'APP_NAME'
          value: 'laravel-rag-chat'
        }
        {
          name: 'APP_KEY'
          value: webAppKey
        }
        {
          name: 'APP_ENV'
          value: 'production'
        }
        {
          name: 'APP_DEBUG'
          value: 'false'
        }
        {
          name: 'APP_URL'
          value: 'https://${appServiceName}.azurewebsites.net'
        }
        {
          name: 'APP_LOCALE'
          value: 'en'
        }
        {
          name: 'APP_FALLBACK_LOCALE'
          value: 'en'
        }
        {
          name: 'APP_FAKER_LOCALE'
          value: 'en_US'
        }
        {
          name: 'APP_MAINTENANCE_DRIVER'
          value: 'file'
        }
        {
          name: 'APP_MAINTENANCE_STORE'
          value: 'database'
        }
        {
          name: 'PHP_CLI_SERVER_WORKERS'
          value: '4'
        }
        {
          name: 'BCRYPT_ROUNDS'
          value: '12'
        }
        {
          name: 'LOG_CHANNEL'
          value: 'stderr'
        }
        {
          name: 'LOG_DEPRECATIONS_CHANNEL'
          value: 'null'
        }
        {
          name: 'LOG_LEVEL'
          value: 'debug'
        }
        {
          name: 'DB_CONNECTION'
          value: 'pgsql'
        }
        {
          name: 'DB_HOST'
          value: postgresqlServerFqdn
        }
        {
          name: 'DB_DATABASE'
          value: postgresqlDatabaseName
        }
        {
          name: 'DB_PORT'
          value: '5432'
        }
        {
          name: 'DB_USERNAME'
          value: dbUser
        }
        {
          name: 'DB_PASSWORD'
          value: dbPass
        }
        {
          name: 'DOCKER_REGISTRY_SERVER_PASSWORD'
          value: dockerRegistryPassword
        }
        {
          name: 'DOCKER_REGISTRY_SERVER_URL'
          value: 'https://${dockerRegistryUrl}'
        }
        {
          name: 'DOCKER_REGISTRY_SERVER_USERNAME'
          value: dockerRegistryUsername
        }
        {
          name: 'SAML_PROVIDER'
          value: 'default'
        }
        {
          name: 'SAML_DISK'
          value: 'saml'
        }
        {
          name: 'SAML_ENABLED'
          value: 'true'
        }
        {
          name: 'SAML_IDP_CERT'
          value: samlIdpCert
        }
        {
          name: 'SAML_IDP_ENTITY_ID'
          value: samlIdpEntityId
        }
        {
          name: 'SAML_IDP_SLO_URL'
          value: samlIdpSloUrl
        }
        {
          name: 'SAML_IDP_SSO_URL'
          value: samlIdpSsoUrl
        }
        {
          name: 'SAML_SP_CERT'
          value: samlSpCert
        }
        {
          name: 'SAML_SP_KEY'
          value: samlSpKey
        }
        {
          name: 'SESSION_DRIVER'
          value: 'database'
        }
        {
          name: 'SESSION_LIFETIME'
          value: '120'
        }
        {
          name: 'SESSION_ENCRYPT'
          value: 'false'
        }
        {
          name: 'SESSION_PATH'
          value: '/'
        }
        {
          name: 'SESSION_DOMAIN'
          value: '${appServiceName}.azurewebsites.net'
        }
        {
          name: 'SESSION_SAME_SITE'
          value: 'none'
        }
        {
          name: 'SESSION_SECURE_COOKIE'
          value: 'true'
        }
        {
          name: 'BROADCAST_CONNECTION'
          value: 'log'
        }
        {
          name: 'FILESYSTEM_DISK'
          value: 'local'
        }
        {
          name: 'QUEUE_CONNECTION'
          value: 'sync'
        }
        {
          name: 'CACHE_STORE'
          value: 'database'
        }
        {
          name: 'CACHE_PREFIX'
          value: 'laravel-rag-chat'
        }
        {
          name: 'CHAT_API_ENDPOINT'
          value: azureOpenAIEndpoint
        }
        {
          name: 'CHAT_API_KEY'
          value: azureOpenAIKey
        }
        {
          name: 'CHAT_API_MODEL'
          value: 'gpt-4o-mini'
        }
        {
          name: 'CHAT_API_TEMP'
          value: '0.4'
        }
        {
          name: 'CHAT_API_MAX_TOKENS'
          value: '4024'
        }
        {
          name: 'SEARCH_API_ENDPOINT'
          value: azureSearchEndpoint
        }
        {
          name: 'SEARCH_API_KEY'
          value: azureSearchKey
        }
      ]
    }
  }
}


resource applicationLogs 'Microsoft.Web/sites/config@2024-04-01' = {
  parent: appSerivce
  name: 'logs'
  properties: {
    applicationLogs: {
      fileSystem: {
        level: 'Information'
      }
    }
    httpLogs: {
      fileSystem: {
        enabled: true
        retentionInDays: 7
        retentionInMb: 35
      }
    }
  }
}
