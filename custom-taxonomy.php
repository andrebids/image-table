<?php

// Registrar taxonomia personalizada para as imagens
add_action('init', 'register_custom_tag_taxonomy');

function register_custom_tag_taxonomy() {
    register_taxonomy(
        'custom_tag',
        'attachment',
        array(
            'label' => __('Custom Tags'),
            'rewrite' => array('slug' => 'custom-tag'),
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
        )
    );
}

// Obter todas as tags existentes para auto-complete
if (!function_exists('get_existing_custom_tags')) {
    function get_existing_custom_tags() {
        $tags = get_terms(array(
            'taxonomy' => 'custom_tag',
            'hide_empty' => false,
        ));

        return wp_list_pluck($tags, 'name');
    }
}
