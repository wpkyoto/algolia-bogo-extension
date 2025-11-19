<?php
/**
 * Mock functions for Bogo plugin when not available in test environment
 *
 * @package Algolia_Bogo
 */

if ( ! function_exists( 'bogo_get_default_locale' ) ) {
	/**
	 * Mock function for bogo_get_default_locale
	 *
	 * @return string Default locale
	 */
	function bogo_get_default_locale() {
		if ( isset( $GLOBALS['bogo_get_default_locale_mock'] ) ) {
			return $GLOBALS['bogo_get_default_locale_mock'];
		}
		return 'en_US';
	}
}

