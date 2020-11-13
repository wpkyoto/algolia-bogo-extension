<?php
/**
 * Plugin Name:     WP Search with Algolia Bogo extension
 * Plugin URI:      https://wp-kyoto.net
 * Description:     Simply extension of Bogo and WP Search with Algolia. Put locale attributes into the indices.
 * Author:          Hidetaka Okamoto
 * Author URI:      https://wp-kyoto.net/en
 * Version:         0.1.0
 *
 * @package         Algolia_Bogo
 */

class Algolia_Bogo {
    private $locale_attribute_name = 'locale';

    function __construct() {
        add_filter( 'algolia_post_shared_attributes', array( $this, "put_bogo_attributes" ), 10, 2);
        add_filter( 'algolia_searchable_post_shared_attributes', array( $this, "put_bogo_attributes" ), 10, 2);
        add_filter( 'algolia_posts_index_settings', array( $this, 'put_index_settings' ), 10, 2 );
        add_filter( 'algolia_searchable_posts_index_settings', array( $this, 'put_index_settings' ), 10, 1 );
    }

    public function put_index_settings( $settings, $post_type = 'searchable_posts' ) {
        if ( ! in_array( $post_type, $this->get_allowed_post_types() ) ) {
            return $settings;
        }
        array_push( $settings['attributesForFaceting'], $this->locale_attribute_name );
        array_push( $settings['attributesToIndex'], 'unordered(' .$this->locale_attribute_name . ')' );
        error_log( json_encode( $settings ) );
        return $settings;
    }

    /**
     * Get the locale attribtues or default locale setting from bogo
     */
    public function get_the_post_locale ( $post ) {
        $locales = get_post_meta( $post->ID, "_locale" );
        if ( empty( $locales ) ) {
            if ( function_exists( 'bogo_get_default_locale' ) ) {
                return bogo_get_default_locale();
            }
            return null;
        }
        $locale = $locales[0];
		return $locale;
    }

    /**
     * Supported post types
     */
    public function get_allowed_post_types() {
        return apply_filters( 'algolia_bogo_allower_post_type', array(
            'post',
            'page',
            'searchable_posts',
        ) );
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
        if ( $locale && $locale !== null ) {
            $shared_attributes[ $this->locale_attribute_name ] = $locale;
        }
        return $shared_attributes;
    }
}

new Algolia_Bogo();