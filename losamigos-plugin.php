<?php
/**
 * Plugin Name: Artists & Medals
 * Description: Fügt die Custom Post Types "Künstler" und "Medaillen" hinzu, inklusive ACF-Felder und REST-API-Schnittstellen.
 * Version: 1.0
 * Author: Nils
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Sicherheitscheck
}

// Custom Post Types registrieren
function create_custom_post_type() {
    register_post_type('artists', array(
        'labels' => array(
            'name' => __('Künstler'),
            'singular_name' => __('Künstler'),
            'add_new_item' => __('Neuen Künstler hinzufügen'),
            'edit_item' => __('Künstler bearbeiten')
        ),
        'public' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor'),
    ));

    register_post_type('medals', array(
        'labels' => array(
            'name' => __('Medaillen'),
            'singular_name' => __('Medaille'),
            'add_new_item' => __('Neue Medaille erstellen'),
            'edit_item' => __('Medaille bearbeiten')
        ),
        'public' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-awards',
    ));
}
add_action('init', 'create_custom_post_type');

// ACF-Felder registrieren
function register_acf_fields() {
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group(array(
            'key' => 'group_artist_info',
            'title' => 'Künstler Informationen',
            'fields' => array(
                array(
                    'key' => 'field_code',
                    'name' => 'code',
                    'label' => 'Eindeutiger Künstler-Code',
                    'type' => 'text',
                    'required' => 1,
                )
            ),
            'location' => array(
                array(array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'artists',
                )),
            ),
        ));

        acf_add_local_field_group(array(
            'key' => 'group_medal_info',
            'title' => 'Medaillen Informationen',
            'fields' => array(
                array(
                    'key' => 'field_required_codes',
                    'name' => 'required_codes',
                    'label' => 'Benötigte Codes',
                    'type' => 'number',
                    'required' => 1,
                    'min' => 1,
                    'step' => 1,
                ),
                array(
                    'key' => 'field_medal_image',
                    'name' => 'medal_image',
                    'label' => 'Medaillen-Bild',
                    'type' => 'image',
                    'return_format' => 'url',
                    'preview_size' => 'thumbnail',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_reward_description',
                    'name' => 'reward_description',
                    'label' => 'Belohnungsbeschreibung',
                    'type' => 'textarea',
                    'rows' => 3,
                    'required' => 1,
                )
            ),
            'location' => array(
                array(array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'medals',
                )),
            ),
            'show_in_rest' => true
        ));
    }
}
add_action('acf/init', 'register_acf_fields');

// REST-API Erweiterungen
add_filter('rest_artists_query', function($args, $request) {
    $code = $request->get_param('meta_value');
    if ($code) {
        $args['meta_query'] = [[
            'key' => 'code',
            'value' => $code,
            'compare' => '='
        ]];
    }
    return $args;
}, 10, 2);

// REST-API Route für QR-Code Count
function get_total_artists_count() {
    $count_posts = wp_count_posts('artists');
    return isset($count_posts->publish) ? $count_posts->publish : 0;
}

function get_scanned_artists($request) {
    $codes = $request->get_param('codes');
    if(!$codes) return array();

    $args = array(
        'post_type' => 'artists',
        'meta_query' => array(
            array(
                'key' => 'code',
                'value' => $codes,
                'compare' => 'IN'
            )
        )
    );

    $query = new WP_Query($args);
    $artists = array();

    if($query->have_posts()) {
        while($query->have_posts()) {
            $query->the_post();
            $artists[] = array(
                'title' => get_the_title(),
                'content' => wp_strip_all_tags(apply_filters('the_content', get_the_content())),
                'code' => get_post_meta(get_the_ID(), 'code', true)
            );
        }
    }
    
    wp_reset_postdata();
    return $artists;
}

add_action('rest_api_init', function() {
    register_rest_route('wp/v2', '/artists-count', array(
        'methods'  => 'GET',
        'callback' => 'get_total_artists_count'
    ));

    register_rest_route('wp/v2', '/scanned-artists', array(
        'methods'  => 'GET',
        'callback' => 'get_scanned_artists',
    ));
});

// ACF API Format setzen
add_filter('acf/settings/rest_api_format', function() {
    return 'light';
});
