# Extending Filters

The advanced object search enables the definition of custom search filters that can be used to filter the result. This 
gives the possibility to filter lists of products by article numbers, types or colors e.g. without having to create
a complete custom advanced object search for it.

In order to create a custom filter for an advanced object search you have to follow these two steps:
- create an event listener that adds the necessary condition(s) to the search
- create the corresponding javascript filter that handles the input and is shown in the toolbar of the result tab

## Event Listeners

To add your custom condition(s) to the search, you have two possibilities
- add the condition(s) to the elastic search directly
- add the condition(s) to the pimcore listing which will load all objects the elastic search found

For convenience reasons you can extend the ``AdvancedObjectSearchBundle\Event\AbstractFilterListener`` and register it
as an event subscriber in your container. This class provides you with two methods
- ``public function onElasticSearch(FilterSearchEvent $event)`` to add condition(s) to the elastic search
- ``public function onListing(FilterListingEvent $event)`` to add the condition(s) to the pimcore listing

If you use the ``AbstractFilterListener`` you have to implement the ``supports(): bool`` method, which defines if the 
listener should actually add condition(s) to the current search. For example if you build an event listener to filter
by article numbers, you only want to add the condition if an article number is in the query parameters.

Here is an example for such a ``supports(): bool`` method

```php
class ArticleNumber extends AbstractFilterListener
{
    protected function supports(): bool
    {
        return !empty($this->getParameters()->get("articleNumber"));
    }
}
```

### Elastic Search Conditions

Condition(s) can be added to the elastic search directly by creating an event listener that listens to the 
``advanced_object_search.elastic_filter`` event (``AdvancedObjectSearchBundle\Event::ELASITIC_FILTER``). As mentioned
before the ``AbstractFilterListener`` already takes care of that for you, if you extend it.

Here is an example for the article number filter again, if your product has a field called ``artno``. As defined in the
previous example, this condition will only be added if the ``articleNumber`` query parameter is present.

```php
class ArticleNumber extends AbstractFilterListener
{
    protected function addElasticSearchFilter(FilterSearchEvent $event)
    {
        $path = "artno." . DefaultAdapter::ES_MAPPING_PROPERTY_STANDARD;

        $query = new BoolQuery();
        $query->add(
            new MatchQuery($path, $this->getParameters()->get("articleNumber"))
        );

        $event->getSearch()->addPostFilter($query);
    }
}
```

### Listing Conditions

In some cases you might have to join your result with another table before you can actually achieve the filter that you
want. For that you can use the listing filter. The elastic search only returns the object ids which were found. These
will be loaded using a basic pimcore listing of that class which enables you to add more mysql queries to that listing.

For that you can use the ``advanced_object_search.listing_filter`` event (``AdvancedObjectSearchBundle\Event::LISTING_FILER``).

Here is an example to filter for the article number as well, but with the listing instead of the elastic search. Keep
in mind that this resolves in more overhead and less performant searches, only use this if you actually have to join 
your result to filter. If the data is available in elastic search use Elastic Search Conditions instead.

```php
class ArticleNumber extends AbstractFilterListener
{
    protected function addListingFiler(FilterListingEvent $event)
    {
        $listing = $event->getListing();
    
        $listing->addConditionParam("artno = :artno", ["artno" => $this->getParameters()->get("articleNumber")]);
    }
}
```

### Javascript Filter

Now that the result can be filtered by an article number, you have to create an input with javascript to be able to type
in an article number.

For that you have to create a javascript class that extends ``pimcore.bundle.advancedObjectSearch.searchConfig.ResultExtension`` 
and register it in the extension bag when the result page of an advanced object search is opened. 

Here is a basic implementation of such an extension that creates a simple input if the field ``artno`` is present in the result.

```js
pimcore.registerNS("pimcore.bundle.AppBundle.AdvancedObjectSearch.Extension.*");
pimcore.bundle.AppBundle.AdvancedObjectSearch.Extension.ArticleNumber = Class.create(pimcore.bundle.advancedObjectSearch.searchConfig.ResultExtension, {
    supports: function(extensionBag) {
        // this filter will be displayed in the search if the field "artno" is present in the result tab
        return extensionBag.hasField("artno");
    },

    getLayout: function() {
        // create a simple input
        if (!this.input) {
            this.input = new Ext.create("Ext.form.Text", {
                listeners: {
                    specialkey: function (field, e) {
                        if (e.getKey() === e.ENTER) {
                            this.getExtensionBag().update();
                        }
                    }.bind(this)
                }
            });

            this.input.setValue(this.getExtensionBag().getPredefinedFilter("articleNumber"));
        }

        return this.input;
    },

    getFilterData: function () {
        var value = null;

        if (this.input) {
            value = this.input.getValue();
        }

        // extract the value of your input, return value has to be an object
        // all custom filter return values will be merged
        // e.g. return { articleNumber: 'mynumber'} and return { articleType: 'concrete' } will result in
        // the query parameters { articleNumber: 'mynumber', articleType: 'concrete' }
        return {
            articleNumber: value
        };
    }
});
```

The extension has to be registered in the extension bag of a result panel. This can be done in the ``onAdvancedObjectSearchResult``
of the pimcore plugin of your bundle.

```js
pimcore.registerNS("pimcore.bundle.AppBundle.*");

pimcore.bundle.AppBundle.Bundle = Class.create({
    getClassName: function() {
        return "pimcore.bundle.AppBundle.Bundle";
    },

    initialize: function() {
        document.addEventListener(pimcore.events.onAdvancedObjectSearchResult, this.onAdvancedObjectSearchResult.bind(this));
    },

    onAdvancedObjectSearchResult: function (e) {
        let extensionBag = e.detail.extensionBag;
        // here you can register all the extension that you implement
        extensionBag.addExtension(new pimcore.bundle.AppBundle.AdvancedObjectSearch.Extension.ArticleNumber());
    }
});

var AppBundle = new pimcore.bundle.AppBundle.Bundle();
```