<?php

// Enfileirar o JavaScript e CSS para o admin e front-end
add_action('admin_enqueue_scripts', 'tag_image_table_scripts');
add_action('wp_enqueue_scripts', 'tag_image_table_scripts');

function tag_image_table_scripts($hook) {
    // Enfileirar o script JavaScript
    wp_enqueue_script(
        'tag-image-table-js', // Nome do script
        ENVIRA_IMAGE_TABLE_PLUGIN_URL . 'tag-image-table.js', // Caminho do script
        array('jquery', 'jquery-ui-autocomplete'), // Dependências
        '1.0', // Versão
        true // Carregar no footer
    );

    // Enfileirar o CSS do jQuery UI
    wp_enqueue_style(
        'jquery-ui-css',
        'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
        array(),
        '1.12.1'
    );

    // Enfileirar o CSS da lightbox
    wp_enqueue_style(
        'lightbox-css',
        'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css',
        array(),
        '2.11.3'
    );

    // Enfileirar o script da lightbox
    wp_enqueue_script(
        'lightbox-js',
        'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js',
        array('jquery'),
        '2.11.3',
        true
    );

    // Enfileirar o CSS personalizado
    wp_enqueue_style(
        'tag-image-table-frontend-css',
        ENVIRA_IMAGE_TABLE_PLUGIN_URL . 'tag-image-table-frontend.css' // Caminho do CSS personalizado
    );

    // Passar dados do PHP para o script JavaScript
    wp_localize_script('tag-image-table-js', 'tagImageTable', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('tag_image_table_nonce'),
        'existing_tags' => get_existing_custom_tags(),
        'excluded_tags' => get_option('excluded_tags', array())
    ));
}