# Elasticsearch Client Setup

:::info

This bundle requires minimum version of Elasticsearch 8.0.

:::

Following configuration is required to set up Elasticsearch. The Elasticsearch client configuration takes place via [Pimcore Elasticsearch Client](https://github.com/pimcore/elasticsearch-client) and has two parts:
1) Configuring an Elasticsearch client.
2) Define the client to be used by Advanced Object Search bundle.

```yaml
# Configuring an Elasticsearch client
pimcore_elasticsearch_client:
    es_clients:
      default:
        hosts: ['elastic:9200']
        username: 'elastic'
        password: 'somethingsecret'
        logger_channel: 'pimcore.elasicsearch'

# Define the client to be used by advanced object search
advanced_object_search:
    client_name: default  # default is default value here, just need to be specified when other client should be used.
    client_type: 'elasticsearch' # default is 'openSearch'
```

If nothing is configured, a default client connecting to `localhost:9200` is used.

For the further configuration of the client, please refer to the [Pimcore Elasticsearch Client documentation](https://github.com/pimcore/elasticsearch-client/blob/1.x/README.md).