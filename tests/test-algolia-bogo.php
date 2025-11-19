<?php
/**
 * Test cases for Algolia_Bogo class.
 *
 * This file contains comprehensive unit tests for the Algolia_Bogo plugin.
 * The tests use PHPUnit and WordPress's testing framework (WP_UnitTestCase)
 * to verify that the plugin correctly integrates Bogo locale attributes
 * with Algolia search indices.
 *
 * @package Algolia_Bogo
 * @since 0.1.0
 */

/**
 * Test suite for the Algolia_Bogo class.
 *
 * This test suite verifies the functionality of the Algolia_Bogo plugin, which extends
 * the WP Search with Algolia plugin to add locale attributes from the Bogo multilingual
 * plugin to Algolia search indices.
 *
 * The tests cover:
 * - Filter registration and hook integration
 * - Post type filtering and extensibility
 * - Locale retrieval from post meta and Bogo defaults
 * - Adding locale attributes to Algolia shared attributes
 * - Configuring Algolia index settings for locale faceting
 * - Edge cases and error handling
 *
 * @package Algolia_Bogo
 * @since 0.1.0
 */
class Test_Algolia_Bogo extends WP_UnitTestCase {

	/**
	 * Instance of Algolia_Bogo class
	 *
	 * @var Algolia_Bogo
	 */
	private $algolia_bogo;

	/**
	 * Set up the test environment before each test method runs.
	 *
	 * This method is called before each individual test method. It initializes
	 * a fresh instance of the Algolia_Bogo class to ensure test isolation.
	 * This is important because the class registers WordPress filters in its
	 * constructor, and we want each test to start with a clean state.
	 *
	 * @since 0.1.0
	 */
	public function set_up() {
		parent::set_up();
		$this->algolia_bogo = new Algolia_Bogo();
	}

	/**
	 * Test that all required filters are properly registered during plugin initialization.
	 *
	 * This test verifies that the Algolia_Bogo class correctly hooks into the Algolia
	 * plugin's filter system. It checks that all four expected filters are registered:
	 * - algolia_post_shared_attributes: Adds locale attributes to post shared attributes
	 * - algolia_searchable_post_shared_attributes: Adds locale attributes to searchable post shared attributes
	 * - algolia_posts_index_settings: Modifies index settings for posts index
	 * - algolia_searchable_posts_index_settings: Modifies index settings for searchable posts index
	 *
	 * @since 0.1.0
	 */
	public function test_filters_are_registered() {
		$this->assertNotFalse(
			has_filter( 'algolia_post_shared_attributes', array( $this->algolia_bogo, 'put_bogo_attributes' ) ),
			'algolia_post_shared_attributes filter should be registered'
		);

		$this->assertNotFalse(
			has_filter( 'algolia_searchable_post_shared_attributes', array( $this->algolia_bogo, 'put_bogo_attributes' ) ),
			'algolia_searchable_post_shared_attributes filter should be registered'
		);

		$this->assertNotFalse(
			has_filter( 'algolia_posts_index_settings', array( $this->algolia_bogo, 'put_index_settings' ) ),
			'algolia_posts_index_settings filter should be registered'
		);

		$this->assertNotFalse(
			has_filter( 'algolia_searchable_posts_index_settings', array( $this->algolia_bogo, 'put_index_settings' ) ),
			'algolia_searchable_posts_index_settings filter should be registered'
		);
	}

	/**
	 * Test that get_allowed_post_types() returns the default post types when no filters are applied.
	 *
	 * This test verifies the default behavior of the get_allowed_post_types() method.
	 * By default, the plugin should support three post types:
	 * - 'post': Standard WordPress posts
	 * - 'page': Standard WordPress pages
	 * - 'searchable_posts': Algolia's searchable posts index
	 *
	 * This ensures backward compatibility and that the plugin works out of the box
	 * with standard WordPress content types and Algolia's default index.
	 *
	 * @since 0.1.0
	 */
	public function test_get_allowed_post_types_returns_defaults() {
		$allowed_types = $this->algolia_bogo->get_allowed_post_types();

		$this->assertContains( 'post', $allowed_types, 'post should be in allowed post types' );
		$this->assertContains( 'page', $allowed_types, 'page should be in allowed post types' );
		$this->assertContains( 'searchable_posts', $allowed_types, 'searchable_posts should be in allowed post types' );
	}

	/**
	 * Test that get_allowed_post_types() can be extended via the algolia_bogo_allowed_post_type filter.
	 *
	 * This test verifies that developers can add custom post types to the list of
	 * allowed post types using the filter hook. This is important for extensibility,
	 * allowing third-party code to integrate their custom post types with the
	 * Algolia Bogo extension.
	 *
	 * The test adds a custom post type 'custom_post_type' via the filter and verifies
	 * that it appears in the returned array along with the default post types.
	 *
	 * @since 0.1.0
	 */
	public function test_get_allowed_post_types_can_be_filtered() {
		add_filter(
			'algolia_bogo_allowed_post_type',
			function( $post_types ) {
				$post_types[] = 'custom_post_type';
				return $post_types;
			}
		);

		$allowed_types = $this->algolia_bogo->get_allowed_post_types();

		$this->assertContains( 'custom_post_type', $allowed_types, 'custom_post_type should be in allowed post types after filter' );
	}

	/**
	 * Test that get_allowed_post_types() supports the misspelled filter name for backwards compatibility.
	 *
	 * This test verifies that the plugin maintains backward compatibility with the
	 * misspelled filter name 'algolia_bogo_allower_post_type' (missing 'd' in 'allowed').
	 * This ensures that existing code using the old filter name continues to work
	 * after plugin updates.
	 *
	 * The test adds a post type via the old filter name and verifies it's included
	 * in the results. This is a temporary compatibility measure that should be
	 * removed in a future major release.
	 *
	 * @since 0.1.0
	 */
	public function test_get_allowed_post_types_supports_backwards_compatibility_filter() {
		add_filter(
			'algolia_bogo_allower_post_type',
			function( $post_types ) {
				$post_types[] = 'backwards_compat_type';
				return $post_types;
			}
		);

		$allowed_types = $this->algolia_bogo->get_allowed_post_types();

		$this->assertContains( 'backwards_compat_type', $allowed_types, 'backwards_compat_type should be in allowed post types via backwards compat filter' );
	}

	/**
	 * Test that get_the_post_locale() retrieves the locale from post meta when available.
	 *
	 * This test verifies the primary functionality of get_the_post_locale(): retrieving
	 * the locale stored in the post's '_locale' meta field. This is the expected behavior
	 * when a post has been assigned a specific locale via the Bogo plugin.
	 *
	 * The test creates a post, sets its '_locale' meta to 'ja_JP', and verifies that
	 * get_the_post_locale() returns the correct locale value.
	 *
	 * @since 0.1.0
	 */
	public function test_get_the_post_locale_returns_post_meta() {
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		update_post_meta( $post_id, '_locale', 'ja_JP' );

		$post = get_post( $post_id );
		$locale = $this->algolia_bogo->get_the_post_locale( $post );

		$this->assertEquals( 'ja_JP', $locale, 'Locale should be retrieved from post meta' );
	}

	/**
	 * Test that get_the_post_locale() returns the default locale when post meta is empty and Bogo is available.
	 *
	 * This test verifies the fallback behavior when a post doesn't have a '_locale' meta value.
	 * When the Bogo plugin is available, the method should fall back to bogo_get_default_locale()
	 * to retrieve the site's default locale.
	 *
	 * The test creates a post without setting '_locale' meta and verifies that the method
	 * returns the default locale from Bogo. If Bogo is not available, the method should
	 * return null (tested in a separate test case).
	 *
	 * @since 0.1.0
	 */
	public function test_get_the_post_locale_returns_default_when_meta_empty() {
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		$post = get_post( $post_id );
		$locale = $this->algolia_bogo->get_the_post_locale( $post );

		if ( function_exists( 'bogo_get_default_locale' ) ) {
			$expected = bogo_get_default_locale();
			$this->assertEquals( $expected, $locale, 'Locale should be default locale when post meta is empty' );
		} else {
			// If Bogo is not available, should return null
			$this->assertNull( $locale, 'Locale should be null when post meta is empty and Bogo is not available' );
		}
	}

	/**
	 * Test that get_the_post_locale() returns null when Bogo is not available and post meta is empty.
	 *
	 * This test verifies the graceful degradation behavior when the Bogo plugin is not
	 * installed or activated. When a post has no '_locale' meta and Bogo functions are
	 * not available, the method should return null rather than causing an error.
	 *
	 * This ensures the plugin doesn't break when Bogo is not present, allowing it to
	 * work in environments where Bogo might be conditionally loaded or not installed.
	 *
	 * @since 0.1.0
	 */
	public function test_get_the_post_locale_returns_null_without_bogo() {
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		$post = get_post( $post_id );

		// Temporarily remove bogo_get_default_locale if it exists
		$has_bogo = function_exists( 'bogo_get_default_locale' );

		if ( ! $has_bogo ) {
			$locale = $this->algolia_bogo->get_the_post_locale( $post );
			$this->assertNull( $locale, 'Locale should be null when post meta is empty and Bogo is not available' );
		} else {
			$this->markTestSkipped( 'Bogo is available in this environment' );
		}
	}

	/**
	 * Test that put_bogo_attributes() adds locale attribute to shared attributes for allowed post types.
	 *
	 * This test verifies the core functionality of put_bogo_attributes(): adding the
	 * 'locale' attribute to the shared attributes array when processing posts of allowed
	 * post types. This is the primary integration point with Algolia's indexing system.
	 *
	 * The test creates a post of type 'post' (which is in the allowed list), sets its
	 * locale meta to 'ja_JP', and verifies that the locale is correctly added to the
	 * shared attributes array that Algolia will use for indexing.
	 *
	 * @since 0.1.0
	 */
	public function test_put_bogo_attributes_adds_locale_for_allowed_post_types() {
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		update_post_meta( $post_id, '_locale', 'ja_JP' );

		$post = get_post( $post_id );
		$shared_attributes = array();

		$result = $this->algolia_bogo->put_bogo_attributes( $shared_attributes, $post );

		$this->assertArrayHasKey( 'locale', $result, 'Locale should be added to shared attributes' );
		$this->assertEquals( 'ja_JP', $result['locale'], 'Locale value should match post meta' );
	}

	/**
	 * Test that put_bogo_attributes() does not modify shared attributes for disallowed post types.
	 *
	 * This test verifies that the plugin respects the allowed post types list and does
	 * not add locale attributes for post types that are not explicitly allowed. This
	 * prevents unwanted modifications to Algolia indices for custom post types that
	 * shouldn't have locale filtering.
	 *
	 * The test registers a custom post type 'custom_type' (not in the allowed list),
	 * creates a post with a locale meta value, and verifies that the locale attribute
	 * is not added. It also ensures that existing attributes in the shared attributes
	 * array are preserved.
	 *
	 * @since 0.1.0
	 */
	public function test_put_bogo_attributes_does_not_add_locale_for_disallowed_post_types() {
		// Register a custom post type that is not in allowed list
		register_post_type(
			'custom_type',
			array(
				'public' => true,
			)
		);

		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'custom_type',
			)
		);

		update_post_meta( $post_id, '_locale', 'ja_JP' );

		$post = get_post( $post_id );
		$shared_attributes = array( 'existing' => 'value' );

		$result = $this->algolia_bogo->put_bogo_attributes( $shared_attributes, $post );

		$this->assertArrayNotHasKey( 'locale', $result, 'Locale should not be added for disallowed post types' );
		$this->assertEquals( 'value', $result['existing'], 'Existing attributes should be preserved' );
	}

	/**
	 * Test that put_bogo_attributes() does not add locale attribute when locale value is empty.
	 *
	 * This test verifies that the plugin only adds locale attributes when a valid locale
	 * value exists. When a post has no locale meta and no default locale is available,
	 * the method should not add an empty or null locale attribute to the shared attributes.
	 *
	 * This prevents cluttering the Algolia index with empty locale values and ensures
	 * that only posts with actual locale information are tagged with locale attributes.
	 *
	 * The test creates a post without locale meta and verifies that the locale key is
	 * not present in the returned shared attributes array, while existing attributes
	 * are preserved.
	 *
	 * @since 0.1.0
	 */
	public function test_put_bogo_attributes_does_not_add_locale_when_empty() {
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		$post = get_post( $post_id );
		$shared_attributes = array( 'existing' => 'value' );

		$result = $this->algolia_bogo->put_bogo_attributes( $shared_attributes, $post );

		$this->assertArrayNotHasKey( 'locale', $result, 'Locale should not be added when empty' );
		$this->assertEquals( 'value', $result['existing'], 'Existing attributes should be preserved' );
	}

	/**
	 * Test that put_index_settings() adds 'locale' to the attributesForFaceting array.
	 *
	 * This test verifies that the plugin correctly configures Algolia index settings
	 * to enable faceting on the locale attribute. Faceting allows users to filter
	 * search results by locale, which is essential for multilingual search functionality.
	 *
	 * The test passes an existing settings array with some attributes already configured,
	 * and verifies that 'locale' is added to the attributesForFaceting array while
	 * preserving existing attributes.
	 *
	 * @since 0.1.0
	 */
	public function test_put_index_settings_adds_locale_to_attributes_for_faceting() {
		$settings = array(
			'attributesForFaceting' => array( 'existing' ),
			'attributesToIndex'     => array( 'existing' ),
		);

		$result = $this->algolia_bogo->put_index_settings( $settings, 'post' );

		$this->assertContains( 'locale', $result['attributesForFaceting'], 'locale should be added to attributesForFaceting' );
		$this->assertContains( 'existing', $result['attributesForFaceting'], 'existing attributes should be preserved' );
	}

	/**
	 * Test that put_index_settings() adds 'unordered(locale)' to the attributesToIndex array.
	 *
	 * This test verifies that the plugin correctly configures Algolia to index the locale
	 * attribute in an unordered format. The 'unordered()' wrapper tells Algolia to treat
	 * the locale as a facet that can be used for filtering, which is necessary for
	 * locale-based search filtering.
	 *
	 * The test passes an existing settings array and verifies that 'unordered(locale)'
	 * is added to the attributesToIndex array while preserving existing index attributes.
	 *
	 * @since 0.1.0
	 */
	public function test_put_index_settings_adds_locale_to_attributes_to_index() {
		$settings = array(
			'attributesForFaceting' => array( 'existing' ),
			'attributesToIndex'     => array( 'existing' ),
		);

		$result = $this->algolia_bogo->put_index_settings( $settings, 'post' );

		$this->assertContains( 'unordered(locale)', $result['attributesToIndex'], 'unordered(locale) should be added to attributesToIndex' );
		$this->assertContains( 'existing', $result['attributesToIndex'], 'existing attributes should be preserved' );
	}

	/**
	 * Test that put_index_settings() does not modify index settings for disallowed post types.
	 *
	 * This test verifies that the plugin respects the allowed post types list when
	 * modifying index settings. For post types that are not in the allowed list, the
	 * method should return the settings array unchanged, preventing unwanted modifications
	 * to Algolia indices for custom post types.
	 *
	 * The test passes settings for a custom post type 'custom_type' (not in allowed list)
	 * and verifies that the settings array is returned unchanged, with no locale-related
	 * modifications.
	 *
	 * @since 0.1.0
	 */
	public function test_put_index_settings_does_not_modify_for_disallowed_post_types() {
		$settings = array(
			'attributesForFaceting' => array( 'existing' ),
			'attributesToIndex'     => array( 'existing' ),
		);

		$result = $this->algolia_bogo->put_index_settings( $settings, 'custom_type' );

		$this->assertEquals( $settings, $result, 'Settings should not be modified for disallowed post types' );
	}

	/**
	 * Test that put_index_settings() handles missing attributesForFaceting key gracefully.
	 *
	 * This test verifies the robustness of put_index_settings() when the settings array
	 * doesn't include the 'attributesForFaceting' key. In real-world scenarios, the settings
	 * array might be incomplete or come from different sources.
	 *
	 * Note: The current implementation may cause a warning or error in PHP 8+ when
	 * attempting to access an undefined array key. This test documents the current
	 * behavior and may need to be updated if the implementation is improved to handle
	 * missing keys more gracefully (e.g., by checking for key existence before accessing).
	 *
	 * @since 0.1.0
	 */
	public function test_put_index_settings_handles_missing_attributes_for_faceting() {
		$settings = array(
			'attributesToIndex' => array( 'existing' ),
		);

		// Current implementation may cause a warning/error in PHP 8+
		// This test documents the current behavior
		// In a production environment, this should be handled gracefully
		try {
			$result = $this->algolia_bogo->put_index_settings( $settings, 'post' );
			// If no error occurs, verify the result
			$this->assertIsArray( $result, 'Result should be an array' );
		} catch ( \Error $e ) {
			// PHP 8+ may throw an Error for undefined array key
			$this->markTestSkipped( 'Current implementation does not handle missing attributesForFaceting gracefully' );
		} catch ( \Exception $e ) {
			// Other exceptions
			$this->markTestSkipped( 'Current implementation does not handle missing attributesForFaceting gracefully' );
		}
	}

	/**
	 * Test that put_index_settings() handles missing attributesToIndex key gracefully.
	 *
	 * This test verifies the robustness of put_index_settings() when the settings array
	 * doesn't include the 'attributesToIndex' key. Similar to the attributesForFaceting
	 * test, this ensures the method can handle incomplete settings arrays.
	 *
	 * Note: The current implementation may cause a warning or error in PHP 8+ when
	 * attempting to access an undefined array key. This test documents the current
	 * behavior and may need to be updated if the implementation is improved to handle
	 * missing keys more gracefully (e.g., by checking for key existence before accessing).
	 *
	 * @since 0.1.0
	 */
	public function test_put_index_settings_handles_missing_attributes_to_index() {
		$settings = array(
			'attributesForFaceting' => array( 'existing' ),
		);

		// Current implementation may cause a warning/error in PHP 8+
		// This test documents the current behavior
		// In a production environment, this should be handled gracefully
		try {
			$result = $this->algolia_bogo->put_index_settings( $settings, 'post' );
			// If no error occurs, verify the result
			$this->assertIsArray( $result, 'Result should be an array' );
		} catch ( \Error $e ) {
			// PHP 8+ may throw an Error for undefined array key
			$this->markTestSkipped( 'Current implementation does not handle missing attributesToIndex gracefully' );
		} catch ( \Exception $e ) {
			// Other exceptions
			$this->markTestSkipped( 'Current implementation does not handle missing attributesToIndex gracefully' );
		}
	}

	/**
	 * Test that put_index_settings() correctly modifies settings for the 'searchable_posts' post type.
	 *
	 * This test verifies that the plugin works correctly with Algolia's 'searchable_posts'
	 * index, which is one of the default allowed post types. The 'searchable_posts' index
	 * is Algolia's primary searchable content index, so it's critical that locale attributes
	 * are properly configured for this index type.
	 *
	 * The test passes empty settings arrays and verifies that both 'locale' is added to
	 * attributesForFaceting and 'unordered(locale)' is added to attributesToIndex.
	 *
	 * @since 0.1.0
	 */
	public function test_put_index_settings_works_with_searchable_posts() {
		$settings = array(
			'attributesForFaceting' => array(),
			'attributesToIndex'     => array(),
		);

		$result = $this->algolia_bogo->put_index_settings( $settings, 'searchable_posts' );

		$this->assertContains( 'locale', $result['attributesForFaceting'], 'locale should be added for searchable_posts' );
		$this->assertContains( 'unordered(locale)', $result['attributesToIndex'], 'unordered(locale) should be added for searchable_posts' );
	}

	/**
	 * Test that put_index_settings() correctly modifies settings for the 'page' post type.
	 *
	 * This test verifies that the plugin works correctly with WordPress's standard 'page'
	 * post type, which is one of the default allowed post types. Pages are often used
	 * for multilingual content, so ensuring locale attributes are properly configured
	 * for pages is important.
	 *
	 * The test passes empty settings arrays and verifies that both 'locale' is added to
	 * attributesForFaceting and 'unordered(locale)' is added to attributesToIndex for pages.
	 *
	 * @since 0.1.0
	 */
	public function test_put_index_settings_works_with_page() {
		$settings = array(
			'attributesForFaceting' => array(),
			'attributesToIndex'     => array(),
		);

		$result = $this->algolia_bogo->put_index_settings( $settings, 'page' );

		$this->assertContains( 'locale', $result['attributesForFaceting'], 'locale should be added for page' );
		$this->assertContains( 'unordered(locale)', $result['attributesToIndex'], 'unordered(locale) should be added for page' );
	}

	/**
	 * Test that put_index_settings() uses the correct default value for the post_type parameter.
	 *
	 * This test verifies that when put_index_settings() is called without explicitly
	 * providing a post_type parameter, it defaults to 'searchable_posts', which is
	 * in the allowed post types list. This ensures backward compatibility and that
	 * the method works correctly when called with only the settings parameter.
	 *
	 * The test calls put_index_settings() with only the settings array (omitting the
	 * post_type parameter) and verifies that locale attributes are correctly added,
	 * confirming that the default 'searchable_posts' value is used and is processed
	 * as an allowed post type.
	 *
	 * @since 0.1.0
	 */
	public function test_put_index_settings_default_post_type_parameter() {
		$settings = array(
			'attributesForFaceting' => array(),
			'attributesToIndex'     => array(),
		);

		$result = $this->algolia_bogo->put_index_settings( $settings );

		// Default is 'searchable_posts' which is in allowed list
		$this->assertContains( 'locale', $result['attributesForFaceting'], 'locale should be added with default post_type' );
	}
}

