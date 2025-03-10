<?php
/**
 * Plugin Name: QR Codes & Medals
 * Description: Fügt die Custom Post Types "QR Codes" und "Medaillen" hinzu, inklusive ACF-Felder und REST-API-Schnittstellen.
 * Version: 1.0
 * Author: Nils
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Sicherheitscheck
}

// Custom Post Types registrieren
function create_qr_code_post_type() {
    register_post_type('qr_codes', array(
        'labels' => array(
            'name' => __('QR Codes'),
            'singular_name' => __('QR Code'),
            'add_new_item' => __('Neuen QR Code erstellen'),
            'edit_item' => __('QR Code bearbeiten')
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
add_action('init', 'create_qr_code_post_type');

// ACF-Felder registrieren
function register_acf_fields() {
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group(array(
            'key' => 'group_qr_code_info',
            'title' => 'QR Code Informationen',
            'fields' => array(
                array(
                    'key' => 'field_code',
                    'name' => 'code',
                    'label' => 'Eindeutiger Code',
                    'type' => 'text',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_artist',
                    'name' => 'artist',
                    'label' => 'Künstler',
                    'type' => 'text',
                )
            ),
            'location' => array(
                array(array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'qr_codes',
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
add_filter('rest_qr_codes_query', function($args, $request) {
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
function get_total_qr_codes_count() {
    $count_posts = wp_count_posts('qr_codes');
    return isset($count_posts->publish) ? $count_posts->publish : 0;
}

add_action('rest_api_init', function() {
    register_rest_route('wp/v2', '/qr-codes-count', array(
        'methods'  => 'GET',
        'callback' => 'get_total_qr_codes_count'
    ));
});

// ACF API Format setzen
add_filter('acf/settings/rest_api_format', function() {
    return 'light';
});
