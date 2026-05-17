param appName string

param loc string

param locAbrv string

param env string

param dbUser string

@secure()
param dbPass string

@description('creates the PostgreSQL server for hosting the database')
resource postgresqlServer 'Microsoft.DBforPostgreSQL/flexibleServers@2024-11-01-preview' = {
  name: 'dbs-${appName}-${locAbrv}-${env}'
  location: loc
  sku: {
    name: 'Standard_B1ms'
    tier: 'Burstable'
  }
  properties: {
    version: '16'
    storage: {
      iops: 120
      tier: 'P4'
      storageSizeGB: 32
      autoGrow: 'Disabled'
    }
    administratorLogin: dbUser
    administratorLoginPassword: dbPass
  }
}

@description('creates the PostgreSQL database that will run within the PostgreSQL server')
resource postgresqlDatabase 'Microsoft.DBforPostgreSQL/flexibleServers/databases@2024-11-01-preview' = {
  name: 'db_${appName}_${locAbrv}_${env}'
  parent: postgresqlServer
}

@description('This firewall rule with 0.0.0.0 as the startIpAddress and endIpAddress allows all Azure internal IPs to access the PostgreSQL server')
resource firewallAzure 'Microsoft.DBforPostgreSQL/flexibleServers/firewallRules@2021-06-01' = {
    name: 'allow-all-azure-internal-IPs'
    parent: postgresqlServer
    properties: {
        startIpAddress: '0.0.0.0'
        endIpAddress: '0.0.0.0'
    }
}

output postgresqlDatabaseName string = postgresqlDatabase.name
output postgresqlServerFqdn string = postgresqlServer.properties.fullyQualifiedDomainName
