# Search with Algolia Bogo extension #
**Donate link:** <https://www.amazon.jp/hz/wishlist/ls/1UYH9PSDMB3FZ?ref_=wl_share>
**Tags:** algolia, bogo
**Requires at least:** 5.5
**Tested up to:** 5.5.3
**Requires PHP:** 7.2
**Stable tag:** 0.1.4
**License:** GPLv2 or later
**License URI:** <https://www.gnu.org/licenses/gpl-2.0.html>

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

### 0.1.4 ###
* Fixed filter name typo: `algolia_bogo_allower_post_type` → `algolia_bogo_allowed_post_type`
* Maintained backwards compatibility with the old filter name (deprecated, will be removed in a future major release)

### 0.1.2 ###
* Release

## Upgrade Notice ##

### 0.1.4 ###
**Filter Name Correction:** The filter name `algolia_bogo_allower_post_type` has been corrected to `algolia_bogo_allowed_post_type`. The old filter name is still supported for backwards compatibility but is deprecated and will be removed in a future major release. Please update your code to use the corrected filter name `algolia_bogo_allowed_post_type`.

### 0.1.2 ###
* Release

## Contributing ##

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Environment ###

This plugin uses [wp-now](https://developer.wordpress.org/playground) for local development.

**Quick start:**

```bash
npm install
npm run start
```

The development server will start on `http://localhost:8888`. WordPress will be automatically set up, and required plugins (Bogo and WP Search with Algolia) will be installed via the blueprint configuration.

### Development Scripts ###

- `npm run start` - Start wp-now development server
- `npm run php` - Execute PHP commands in wp-now environment
- `npm run readme` - Generate README.md from readme.txt
- `npm run i18n` - Generate translation template (pot file)