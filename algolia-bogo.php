<?php
/**
 * Plugin Name:     Search with Algolia Bogo extension
 * Plugin URI:      https://wp-kyoto.net
 * Description:     Simple extension of Bogo and WP Search with Algolia. Put locale attributes into the indices.
 * Author:          Hidetaka Okamoto
 * Author URI:      https://wp-kyoto.net/en
 * Version:         0.2.1
 * Text Domain:     algolia-bogo
 * Domain Path:     /languages
 *
 * @package         Algolia_Bogo
 */

class Algolia_Bogo {
    private $locale_attribute_name = 'locale';

    public function __construct() {
        add_filter( 'algolia_post_shared_attributes', array( $this, 'put_bogo_attributes' ), 10, 2 );
        add_filter( 'algolia_searchable_post_shared_attributes', array( $this, 'put_bogo_attributes' ), 10, 2 );
        add_filter( 'algolia_posts_index_settings', array( $this, 'put_index_settings' ), 10, 2 );
        add_filter( 'algolia_searchable_posts_index_settings', array( $this, 'put_index_settings' ), 10, 1 );
    }

    public function put_index_settings( $settings, $post_type = 'searchable_posts' ) {
        if ( ! in_array( $post_type, $this->get_allowed_post_types() ) ) {
            return $settings;
        }
        
        // Ensure attributesForFaceting key exists and is an array
        if ( ! isset( $settings['attributesForFaceting'] ) || ! is_array( $settings['attributesForFaceting'] ) ) {
            $settings['attributesForFaceting'] = array();
        }
        $settings['attributesForFaceting'][] = $this->locale_attribute_name;
        
        // Ensure attributesToIndex key exists and is an array
        if ( ! isset( $settings['attributesToIndex'] ) || ! is_array( $settings['attributesToIndex'] ) ) {
            $settings['attributesToIndex'] = array();
        }
        $settings['attributesToIndex'][] = 'unordered(' . $this->locale_attribute_name . ')';
        return $settings;
    }

    /**
     * Get the locale attributes or default locale setting from bogo
     */
    public function get_the_post_locale( $post ) {
        $locale = get_post_meta( $post->ID, '_locale', true );
        if ( empty( $locale ) ) {
            if ( function_exists( 'bogo_get_default_locale' ) ) {
                return bogo_get_default_locale();
            }
            return null;
        }
        return $locale;
    }

    /**
     * Supported post types
     */
    public function get_allowed_post_types() {
        $default_post_types = array(
            'post',
            'page',
            'searchable_posts',
        );
        
        // Backwards compatibility: support old misspelled filter name
        // TODO: Remove 'algolia_bogo_allower_post_type' in a future major release
        $post_types = apply_filters( 'algolia_bogo_allower_post_type', $default_post_types );
        
        // Apply the corrected filter name
        return apply_filters( 'algolia_bogo_allowed_post_type', $post_types );
    }
    
    /**
     * Put attributes
     */
    public function put_bogo_attributes( $shared_attributes, $item ) {
        $allowed_posts = $this->get_allowed_post_types();
        if ( ! in_array( $item->post_type , $allowed_posts, true ) ) {
            return $shared_attributes;
        }
        $locale = $this->get_the_post_locale( $item );
        if ( ! empty( $locale ) ) {
            $shared_attributes[ $this->locale_attribute_name ] = $locale;
        }
        return $shared_attributes;
    }
}

/**
 * Initialize plugin
 */
function algolia_bogo_init() {
    new Algolia_Bogo();
}
add_action( 'plugins_loaded', 'algolia_bogo_init' );