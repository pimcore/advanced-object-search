# OpenSearch Client Setup

:::info

This bundle requires minimum version of OpenSearch 2.7.

:::

Following configuration is required to set up OpenSearch. The OpenSearch client configuration takes place via [Pimcore Opensearch Client](https://github.com/pimcore/opensearch-client) and has two parts:
1) Configuring an OpenSearch client.
2) Define the client to be used by Advanced Object Search bundle.

```yaml
# Configuring an OpenSearch client
pimcore_open_search_client:
    clients:
        default:
            hosts: ['https://opensearch:9200']
            password: 'admin'
            username: 'admin'
            ssl_verification: false


# Define the client to be used by advanced object search
advanced_object_search:
    client_name: default  # default is default value here, just need to be specified when other client should be used.
```

If nothing is configured, a default client connecting to `localhost:9200` is used.

For the further configuration of the client, please refer to the [Pimcore OpenSearch Client documentation](https://github.com/pimcore/opensearch-client/blob/1.x/doc/02_Configuration.md).