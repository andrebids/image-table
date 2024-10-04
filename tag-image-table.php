<?php


// Ação para adicionar a página de administração
add_action('admin_menu', 'tag_image_table_menu');

function tag_image_table_menu() {
    add_menu_page(
        'Tag Image Table',  // Título da página
        'Tag Image Table',  // Título do menu
        'manage_options',   // Capacidade
        'tag-image-table',  // Slug do menu
        'tag_image_table_page', // Função de callback
        'dashicons-images-alt2' // Ícone do menu
    );
}

// Função de callback para a página de administração
function tag_image_table_page() {
    ?>
    <div class="wrap">
        <h1>Tag Image Table</h1>
        <div id="image-table">
            <!-- A tabela será gerada aqui -->
            <?php display_image_table(); ?>
        </div>
    </div>
    <?php
}

// Função para exibir a tabela com paginação e verificação de existência de arquivos
function display_image_table() {
    // Configurar a paginação
    $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $images_per_page = 20;

    // Query para obter apenas imagens originais da biblioteca de mídia
    $args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => $images_per_page,
        'paged'          => $paged,
        'meta_query'     => array(
            array(
                'key'     => '_wp_attachment_metadata',
                'compare' => 'EXISTS',
            ),
        ),
        'fields'         => 'ids', // Retornar apenas IDs para otimização
    );

    $images_query = new WP_Query($args);

    if (!$images_query->have_posts()) {
        echo '<p style="color: red;">Nenhuma imagem original encontrada.</p>';
        return;
    }

    echo '<div class="bulk-actions-bar" style="margin-bottom: 10px; display: flex; align-items: center;">';
    echo '<input type="checkbox" id="select-all" style="margin-right: 10px; transform: scale(1.2);"> Selecionar Todos';
    echo '<input type="text" id="bulk-tag-input" placeholder="Adicionar tags em massa" style="margin-left: 10px; margin-right: 10px; width: 250px;">';
    echo '<button id="bulk-tag-button" class="button">Adicionar Tag em Massa</button>';
    echo '</div>';

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th><input type="checkbox" id="select-all-items"></th><th>Imagem</th><th>Nome do Ficheiro</th><th>Tags Existentes</th><th>Adicionar Tags</th><th>Salvar Tags</th><th>Informações Adicionais</th></tr></thead>';
    echo '<tbody>';

    foreach ($images_query->posts as $image_id) {
        $img_url = wp_get_attachment_url($image_id);
        $filename = basename($img_url);
        $tags = get_the_terms($image_id, 'custom_tag');
        $excluded_tags = get_option('excluded_tags', array());

        // Exibir as tags como elementos removíveis
        $tags_html = '';
        if (!empty($tags) && !is_wp_error($tags)) {
            foreach ($tags as $tag) {
                if (!in_array($tag->term_id, $excluded_tags)) {
                    $tags_html .= '<span class="tag-item" data-tag-id="' . esc_attr($tag->term_id) . '" style="display: inline-block; margin-right: 5px; padding: 3px 6px; border: 1px solid #ddd; border-radius: 4px;">' . esc_html($tag->name) . ' <span class="remove-tag" style="cursor:pointer;">&times;</span></span> ';
                }
            }
        } else {
            $tags_html = '<span class="no-tags">Nenhuma tag</span>'; // Exibir como texto informativo
        }

        // Recuperar informações adicionais, se existirem
        $dimensions = get_post_meta($image_id, 'image_dimensions', true);
        $altura = $dimensions['altura'] ?? '';
        $largura = $dimensions['largura'] ?? '';
        $profundidade = $dimensions['profundidade'] ?? '';
        $diametro = $dimensions['diametro'] ?? '';

        echo '<tr>';
        echo '<th scope="row" class="check-column" style="vertical-align: middle; padding: 0 5px;"><input type="checkbox" class="image-checkbox" value="' . esc_attr($image_id) . '" style="transform: scale(1.2);"></th>';
        echo '<td style="vertical-align: middle;"><a href="' . esc_url($img_url) . '" class="image-link" data-lightbox="image-gallery"><img src="' . esc_url($img_url) . '" style="width: 150px; height: auto; margin-right: 10px;" /></a></td>';
        echo '<td style="vertical-align: middle;">' . esc_html($filename) . '</td>';
        echo '<td class="existing-tags" data-image-id="' . esc_attr($image_id) . '" style="vertical-align: middle;">' . $tags_html . '</td>';
        echo '<td style="vertical-align: middle;"><input type="text" class="custom-tags" data-image-id="' . esc_attr($image_id) . '" value="" placeholder="Adicionar tags" style="width: 150px;" /></td>';
        echo '<td style="vertical-align: middle;"><button class="button save-tags" data-image-id="' . esc_attr($image_id) . '">Salvar</button></td>';
        echo '<td style="vertical-align: middle;">';
        echo '<input type="text" class="dimension-input" data-type="altura" data-image-id="' . esc_attr($image_id) . '" value="' . esc_attr($altura) . '" placeholder="Altura" style="width: 80px;" />';
        echo '<input type="text" class="dimension-input" data-type="largura" data-image-id="' . esc_attr($image_id) . '" value="' . esc_attr($largura) . '" placeholder="Largura" style="width: 80px;" />';
        echo '<input type="text" class="dimension-input" data-type="profundidade" data-image-id="' . esc_attr($image_id) . '" value="' . esc_attr($profundidade) . '" placeholder="Profundidade" style="width: 80px;" />';
        echo '<input type="text" class="dimension-input" data-type="diametro" data-image-id="' . esc_attr($image_id) . '" value="' . esc_attr($diametro) . '" placeholder="Diâmetro" style="width: 80px;" />';
        echo '<button class="button save-dimensions" data-image-id="' . esc_attr($image_id) . '">Salvar Informações</button>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Restaurar dados originais da query
    wp_reset_postdata();

    // Paginação manual
    $total_pages = $images_query->max_num_pages;

    echo '<div class="tablenav"><div class="tablenav-pages">';
    echo paginate_links(array(
        'base'      => add_query_arg('paged', '%#%'),
        'format'    => '',
        'prev_text' => __('&laquo; Anterior'),
        'next_text' => __('Próximo &raquo;'),
        'total'     => $total_pages,
        'current'   => $paged,
    ));
    echo '</div></div>';
}

// Enfileirar o JavaScript e CSS
add_action('admin_enqueue_scripts', 'tag_image_table_scripts');

function tag_image_table_scripts($hook) {
    // Verifique se estamos na página correta do plugin
    if ($hook != 'toplevel_page_tag-image-table') {
        return;
    }

    // Enfileirar o script JavaScript
    wp_enqueue_script(
        'tag-image-table-js', // Nome do script
        plugins_url('tag-image-table.js', __FILE__), // Caminho do script
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

    // Passar dados do PHP para o script JavaScript
    wp_localize_script('tag-image-table-js', 'tagImageTable', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('tag_image_table_nonce'),
        'existing_tags' => get_existing_custom_tags(),
        'excluded_tags' => get_option('excluded_tags', array())
    ));
}

// Registrar taxonomia personalizada para as imagens
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
add_action('init', 'register_custom_tag_taxonomy');

// Obter todas as tags existentes para auto-complete
if (!function_exists('get_existing_custom_tags')) {
    function get_existing_custom_tags() {
        $tags = get_terms(array(
            'taxonomy' => 'custom_tag',
            'hide_empty' => false,
        ));
        $excluded_tags = get_option('excluded_tags', array());
        $tags = array_filter($tags, function($tag) use ($excluded_tags) {
            return !in_array($tag->term_id, $excluded_tags);
        });
        return wp_list_pluck($tags, 'name');
    }
}

// Manipular a requisição AJAX para salvar tags
add_action('wp_ajax_save_custom_tags', 'save_custom_tags');

function save_custom_tags() {
    // Verificar o nonce para segurança
    check_ajax_referer('tag_image_table_nonce', 'nonce');

    // Obter os dados das tags
    $image_id = intval($_POST['image_id']);
    $new_tags = array_map('sanitize_text_field', explode(',', $_POST['tags']));

    // Filtrar tags vazias
    $new_tags = array_filter($new_tags, function($tag) {
        return !empty($tag);
    });

    // Verificar se há novas tags a adicionar
    if (empty($new_tags)) {
        wp_send_json_error('Por favor, insira ao menos uma tag válida.');
        return;
    }

    // Obter as tags atuais e adicionar as novas
    $current_tags = get_the_terms($image_id, 'custom_tag');
    if ($current_tags && !is_wp_error($current_tags)) {
        $current_tags_names = wp_list_pluck($current_tags, 'name');
        $tags = array_unique(array_merge($current_tags_names, $new_tags));
    } else {
        $tags = $new_tags;
    }

    // Atualizar as tags como uma taxonomia
    wp_set_object_terms($image_id, $tags, 'custom_tag');

    // Retornar as tags atualizadas
    $updated_tags = get_the_terms($image_id, 'custom_tag');
    $tags_string = !empty($updated_tags) && !is_wp_error($updated_tags) ? implode(', ', wp_list_pluck($updated_tags, 'name')) : '';

    // Retornar sucesso e tags atualizadas
    wp_send_json_success(array('tags' => $tags_string));
}

// Manipular a requisição AJAX para remover uma tag específica
add_action('wp_ajax_remove_custom_tag', 'remove_custom_tag');

function remove_custom_tag() {
    // Verificar o nonce para segurança
    check_ajax_referer('tag_image_table_nonce', 'nonce');

    // Obter os dados da tag
    $image_id = intval($_POST['image_id']);
    $tag_id = intval($_POST['tag_id']);

    // Obter as tags atuais
    $current_tags = get_the_terms($image_id, 'custom_tag');
    if ($current_tags && !is_wp_error($current_tags)) {
        // Filtrar e remover a tag específica
        $tags_to_keep = array_filter($current_tags, function($tag) use ($tag_id) {
            return $tag->term_id !== $tag_id;
        });

        // Atualizar as tags no WordPress
        wp_set_object_terms($image_id, wp_list_pluck($tags_to_keep, 'name'), 'custom_tag');
    }

    // Retornar sucesso
    wp_send_json_success();
}

// Manipular a requisição AJAX para salvar as informações adicionais
add_action('wp_ajax_save_dimensions', 'save_dimensions');

function save_dimensions() {
    // Verificar o nonce para segurança
    check_ajax_referer('tag_image_table_nonce', 'nonce');

    // Obter os dados das dimensões
    $image_id = intval($_POST['image_id']);
    $dimensions = array_map('sanitize_text_field', $_POST['dimensions']);

    // Salvar as informações adicionais como meta dados
    update_post_meta($image_id, 'image_dimensions', $dimensions);

    // Retornar sucesso
    wp_send_json_success();
}

// Manipular a requisição AJAX para adicionar tags em massa
add_action('wp_ajax_add_bulk_tags', 'add_bulk_tags');

function add_bulk_tags() {
    // Verificar o nonce para segurança
    check_ajax_referer('tag_image_table_nonce', 'nonce');

    // Obter os dados das tags
    $image_ids = isset($_POST['image_ids']) ? array_map('intval', $_POST['image_ids']) : array();
    $tags = isset($_POST['tags']) ? array_map('sanitize_text_field', explode(',', $_POST['tags'])) : array();

    // Filtrar tags vazias
    $tags = array_filter($tags, function($tag) {
        return !empty($tag);
    });

    if (empty($tags) || empty($image_ids)) {
        wp_send_json_error('IDs de imagens ou tags não fornecidos.');
        return;
    }

    foreach ($image_ids as $image_id) {
        // Obter as tags atuais e adicionar as novas
        $current_tags = get_the_terms($image_id, 'custom_tag');
        if ($current_tags && !is_wp_error($current_tags)) {
            $current_tags_names = wp_list_pluck($current_tags, 'name');
            $all_tags = array_unique(array_merge($current_tags_names, $tags));
        } else {
            $all_tags = $tags;
        }

        // Atualizar as tags no WordPress
        wp_set_object_terms($image_id, $all_tags, 'custom_tag');
    }

    // Retornar sucesso
    wp_send_json_success('Tags em massa adicionadas com sucesso.');
}

// Manipular a requisição AJAX para excluir tags
add_action('wp_ajax_delete_excluded_tags', 'delete_excluded_tags');
add_action('wp_ajax_add_excluded_tags', 'add_excluded_tags');

function delete_excluded_tags() {
    // Verificar o nonce para segurança
    check_ajax_referer('tag_image_table_nonce', 'nonce');

    // Obter os dados das tags
    $tags = isset($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : array();

    if (empty($tags)) {
        wp_send_json_error('Nenhuma tag fornecida.');
        return;
    }

    $excluded_tags = get_option('excluded_tags', array());

    foreach ($tags as $tag) {
        if (($key = array_search($tag, $excluded_tags)) !== false) {
            unset($excluded_tags[$key]);
        }
    }

    update_option('excluded_tags', $excluded_tags);

    // Retornar sucesso
    wp_send_json_success('Tags excluídas com sucesso.');
}

function add_excluded_tags() {
    // Verificar o nonce para segurança
    if (!check_ajax_referer('tag_image_table_nonce', 'nonce', false)) {
        error_log('Nonce inválido');
        wp_send_json_error('Nonce inválido.');
        return;
    }

    // Obter os dados das tags
    $tags = isset($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : array();
    error_log('Tags recebidas: ' . print_r($tags, true));

    if (empty($tags)) {
        wp_send_json_error('Nenhuma tag fornecida.');
        return;
    }

    $excluded_tags = get_option('excluded_tags', array());

    foreach ($tags as $tag) {
        if (!in_array($tag, $excluded_tags)) {
            $excluded_tags[] = $tag;
        }
    }

    update_option('excluded_tags', $excluded_tags);

    // Retornar sucesso
    wp_send_json_success('Tags adicionadas à exclusão com sucesso.');
}
