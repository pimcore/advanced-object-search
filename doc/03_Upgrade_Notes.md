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
