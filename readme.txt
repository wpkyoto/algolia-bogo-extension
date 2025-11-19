=== Search with Algolia Bogo extension ===
Donate link: https://www.amazon.jp/hz/wishlist/ls/1UYH9PSDMB3FZ?ref_=wl_share
Tags: algolia, bogo
Requires at least: 5.5
Tested up to: 5.5.3
Requires PHP: 7.3
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Contributors: hidetakaokamoto

Simple extension of Bogo and WP Search with Algolia.
Put locale attributes into the indices.

== Description ==

The plugin will put the `locale` attribute into the Index created by WP Search wit Algolia depends on the Bogo settings.

== Installation ==

1. Install the plugin
2. Activate it and WP Search with Algolia plugin
3. Configure WP Search with Algolia plugin settings
4. Create indices by WP Search with Algolia
5. The plugin will add the locale attributes automatically.

== Frequently Asked Questions ==

= The facet has not the locale attributes! =

Probably you have been created the indices before activate the plugin.
You need to delete the indices and re-index it.

Or, you can manually add the facet in the Algolia dashboard.
https://www.algolia.com/doc/guides/managing-results/refine-results/faceting/

= How can we search by the locale attribute? =

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

= When the Bogo plugin deactivated, what the behavior will changes? =

Bogo will put the `_locale` attributes into the post_meta, and the plugin uses it.
So the plugin still put the locale attributes if exists.

But, if the post has no `_locale` post_meta attribute, the plugin does not put the locale attributes.

== Contributing ==

= Development Environment Setup =

This project supports two development environments: wp-env and wp-now. Both provide a local WordPress environment for development and testing.

= Using wp-env =

1. Install dependencies:
	npm install

2. Start the development environment:
	npm run env:start

Required plugins (Bogo and WP Search with Algolia) will be automatically installed from WordPress.org.

3. Access WordPress at `http://localhost:8888`

4. Stop the environment:
	npm run env:stop

5. Clean up the environment:
	npm run env:clean

6. Run WP-CLI commands:
	npm run env:cli wp plugin list

= Using wp-now =

1. Install dependencies:
	npm install

2. Start the development environment:
	npm run start

The environment will automatically install plugins specified in `blueprint.json` after WordPress initial setup.

3. Access WordPress at `http://localhost:8888`

4. Run PHP/WP-CLI commands:
	npm run php wp plugin list

= Generating POT file for translations =

To generate the POT (Portable Object Template) file for translations, use the following command:

	npm run i18n

This command uses wp-env to run WordPress CLI's `i18n make-pot` command. Make sure Docker is running before executing this command.

The generated POT file will be saved to `languages/algolia-bogo.pot`.

== Changelog ==

= 0.2.0 =
* Fix PHP 8+ compatibility: Add defensive checks for array keys before array operations
* Fix filter name typo (`algolia_bogo_allower_post_type` -> `algolia_bogo_allowed_post_type`) with backwards compatibility
* Add PHPUnit tests with comprehensive test coverage
* Add GitHub Actions workflow for automated testing
* Improve development environment support (wp-env, wp-now)
* Modernize code and fix coding standards
* Update PHP requirement from 7.2 to 7.3

= 0.1.2 =
* Release

== Upgrade Notice ==

= 0.2.0 =
* This release includes PHP 8+ compatibility fixes and improved code quality. No breaking changes.

= 0.1.2 =
* Release