# Upgrade Notes

### Upgrade to v3.0.0
- Reinstall of Bundle might be necessary - due to switch to MigrationInstaller.
- Update ES mapping and reindex is necessary - run commands `advanced-object-search:update-mapping` and `advanced-object-search:re-index`.

### Upgrade to v4.0.0
- Removed BC Layer for old configuration file. Configuration now only in symfony configuration tree.
- Removed deprecated `IFieldDefinitionAdapter`, use `FieldDefinitionAdapterInterface` instead. 
- Data in Elasticsearch might be different, so recheck if you are depending directly on the data in Elasticsearch.
- Execute all migrations of the bundle.

#### Upgrade to Pimcore X
- Update to latest (allowed) bundle version in Pimcore 6.9 and execute all migrations.
- Make sure you are using ElasticSearch 7. 
- Then update to Pimcore X.

### Upgrade to v5.0.0
- Removed Elasticsearch v6 and v7 support
- Changed elasticsearch client configuration

### Upgrade to v6.0.0
- Removed Pimcore 10 support
- Removed Elasticsearch support and added OpenSearch support (Kept ONGR Elasticsearch library as it is compatible with OpenSearch)

### Upgrade to v6.1.0
- Added support for Elasticsearch in parallel to Opensearch. Opensearch remains the default search technology. If you are using Elasticsearch, you need to update your symfony configuration as follows:
```yml 
advanced_object_search:
    client_name: default
    client_type: 'elasticsearch'
```
- Introduced new service alias `pimcore.advanced_object_search.search-client`. This will replace deprecated alias `pimcore.advanced_object_search.opensearch-client` which will be removed in the next major version.
  The new service alias can be used to inject the search client into your services. This search client is an instance of `Pimcore\SearchClient\SearchClientInterface` which is a common interface for OpenSearch and Elasticsearch clients.
