# Search with Algolia Bogo extension #
**Donate link:** https://www.amazon.jp/hz/wishlist/ls/1UYH9PSDMB3FZ?ref_=wl_share  
**Tags:** algolia, bogo  
**Requires at least:** 5.5  
**Tested up to:** 5.5.3  
**Requires PHP:** 7.2  
**Stable tag:** 0.1.4  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  
**Contributors:** [hidetakaokamoto](https://profiles.wordpress.org/hidetakaokamoto/)  

Simply extension of Bogo and WP Search with Algolia.
Put locale attributes into the indices.

## Description ##

The plugin will put the `locale` attribute into the Index created by WP Search wit Algolia depends on the Bogo settings.

## Installation ##

1. Install the plugin
2. Activate it and WP Search with Algolia plugin
3. Configure WP Search with Algolia plugin settings
4. Create indices by WP Search with Algolia
5. The plugin will add the locale attributes automatically.

## Frequently Asked Questions ##

### The facet has not the locale attributes! ###

Probably you have been created the indices before activate the plugin.
You need to delete the indices and re-index it.

Or, you can manually add the facet in the Algolia dashboard.
https://www.algolia.com/doc/guides/managing-results/refine-results/faceting/

### How can we search by the locale attribute? ###

You can search by the following query.

`index.search("", {
 "getRankingInfo": true,
 "analytics": false,
 "enableABTest": false,
 "hitsPerPage": 10,
 "attributesToRetrieve": "*",
 "attributesToSnippet": "*:20",
 "snippetEllipsisText": "…",
 "responseFields": "*",
 "maxValuesPerFacet": 100,
 "page": 0,
 "facets": [
  "*",
  "locale",
 ],
 "facetFilters": [
  [
   "locale:en_US"
  ]
 ]
});`

### When the Bogo plugin deactivated, what the behavior will changes? ###

Bogo will put the `_locale` attributes into the post_meta, and the plugin uses it.
So the plugin still put the locale attributes if exists.

But, if the post has no `_locale` post_meta attribute, the plugin does not put the locale attributes.

## Changelog ##

### 0.1.2 ###
* Release

## Upgrade Notice ##

### 0.1.2 ###
* Release