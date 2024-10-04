<?php
/*
Plugin Name: Envira Image Table
Description: Exibe uma tabela com imagens da biblioteca de mídia e permite adicionar tags personalizadas e informações adicionais.
Version: 1.2
Author: Seu Nome
*/

// Definir constantes do plugin
define('ENVIRA_IMAGE_TABLE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ENVIRA_IMAGE_TABLE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir arquivos necessários
require_once ENVIRA_IMAGE_TABLE_PLUGIN_DIR . 'admin-page.php';
require_once ENVIRA_IMAGE_TABLE_PLUGIN_DIR . 'custom-taxonomy.php';
require_once ENVIRA_IMAGE_TABLE_PLUGIN_DIR . 'ajax-handlers.php';
require_once ENVIRA_IMAGE_TABLE_PLUGIN_DIR . 'enqueue-scripts.php';

// Adicionar o shortcode para exibir a tabela de imagens no front-end
add_shortcode('image_table', 'display_image_table_shortcode');

function display_image_table_shortcode() {
    ob_start();
    display_image_table();
    return ob_get_clean();
}
