## Exclude Fields

It is possible to exclude specified fields from the elasticsearch index by extending the services.yaml:
```yaml
advanced_object_search:
    index_configuration:
        exclude_fields:
            OfferToolOffer:
                - offernumber
            OfferToolOfferItem:
                - productName
                - productNumber
```
