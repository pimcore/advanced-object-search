# Exclude Fields

It is possible to exclude specified fields from the search index by extending the services.yaml:
```yaml
advanced_object_search:
    index_configuration:
        exclude_classes:
            - CustomerSegment
            - CustomerSegmentGroup
        exclude_fields:
            OfferToolOffer:
                - offernumber
            OfferToolOfferItem:
                - productName
                - productNumber
```
Please Note: Currently is not possible to exclude any specific field under a structured fieldset like `Object Bricks` and `Field collections`, but it is only possible to exclude the field entirely for a specific class. 