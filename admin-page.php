<?php

// Função para exibir a tabela de imagens no front-end
function display_image_table() {
    // Configurar a paginação
    $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $images_per_page = isset($_GET['images_per_page']) ? absint($_GET['images_per_page']) : 20;
    $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : '';

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

    // Aplicar filtro para imagens sem tags, se necessário
    if ($filter === 'no-tags') {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'custom_tag',
                'field'    => 'term_id',
                'operator' => 'NOT EXISTS',
            ),
        );
    }

    $images_query = new WP_Query($args);

    if (!$images_query->have_posts()) {
        echo '<p style="color: red;">Nenhuma imagem original encontrada.</p>';
        return;
    }

    // Modal para excluir tags (inicialmente oculto)
    echo '<div id="exclude-tags-modal" class="exclude-tags-modal" style="display: none;">';
    echo '<div class="exclude-tags-modal-content">';
    echo '<span class="close-modal">&times;</span>';
    echo '<h2>Excluir Tags do Dropdown</h2>';
    echo '<p>Tags adicionadas aqui não aparecerão no dropdown de tags na página inicial.</p>';
    echo '<div>';
    echo '<select id="existing-tags-list" multiple style="width: 100%; margin-top: 10px;">';
    $existing_tags = get_terms(array('taxonomy' => 'custom_tag', 'hide_empty' => false));
    foreach ($existing_tags as $tag) {
        echo '<option value="' . esc_attr($tag->term_id) . '">' . esc_html($tag->name) . '</option>';
    }
    echo '</select>';
    echo '<button id="add-excluded-tag" class="button">Adicionar Tag Excluída</button>';
    echo '</div>';
    echo '<select id="excluded-tags-list" multiple style="width: 100%; margin-top: 10px;">';
    $excluded_tags = get_option('excluded_tags', array());
    foreach ($excluded_tags as $tag) {
        echo '<option value="' . esc_attr($tag) . '">' . esc_html($tag) . '</option>';
    }
    echo '</select>';
    echo '<button id="remove-excluded-tag" class="button" style="margin-top: 10px;">Remover Tags Selecionadas</button>';
    echo '</div>';
    echo '</div>';



    // Barra de ações em massa
    echo '<div class="bulk-actions-bar" style="margin-bottom: 10px; display: flex; align-items: center;">';
    echo '<input type="checkbox" id="select-all" style="margin-right: 10px; transform: scale(1.2);"> Selecionar Todos';
    echo '<input type="text" id="bulk-tag-input" placeholder="Adicionar tags em massa" style="margin-left: 10px; margin-right: 10px; width: 250px;">';
    echo '<button id="bulk-tag-button" class="button">Adicionar Tag em Massa</button>';
    echo '<button id="filter-no-tags" class="button" style="margin-left: 10px;">Filtrar Imagens sem Tags</button>';
    echo '<button id="show-all" class="button" style="margin-left: 5px;">Mostrar Todas</button>';
    echo '<button id="open-exclude-tags-modal" class="button" style="margin-left: 10px;">Excluir Tags</button>';
    echo '</div>';

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th><input type="checkbox" id="select-all-items"></th><th>Imagem</th><th>Nome do Ficheiro</th><th>Tags Existentes</th><th>Adicionar Tags</th><th>Salvar Tags</th><th>Informações Adicionais</th><th>Ações</th></tr></thead>';
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

        echo '<tr>';
        echo '<th scope="row" class="check-column" style="vertical-align: middle; padding: 0 5px;"><input type="checkbox" class="image-checkbox" value="' . esc_attr($image_id) . '" style="transform: scale(1.2);"></th>';
        echo '<td style="vertical-align: middle; padding: 0;"><a href="' . esc_url($img_url) . '" class="image-link" data-lightbox="image-gallery"><img src="' . esc_url($img_url) . '" style="width: 150px; height: auto;" /></a></td>';
        echo '<td style="vertical-align: middle;">' . esc_html($filename) . '</td>';
        echo '<td class="existing-tags" data-image-id="' . esc_attr($image_id) . '" style="vertical-align: middle;">' . $tags_html . '</td>';
        echo '<td style="vertical-align: middle;"><input type="text" class="custom-tags" data-image-id="' . esc_attr($image_id) . '" value="" placeholder="Adicionar tags" style="width: 150px;" /></td>';
        echo '<td style="vertical-align: middle;"><button class="button save-tags" data-image-id="' . esc_attr($image_id) . '">Salvar</button></td>';
        echo '<td style="vertical-align: middle;">';
        echo '<input type="text" class="dimension-input" data-type="altura" data-image-id="' . esc_attr($image_id) . '" value="' . esc_attr($altura) . '" placeholder="Altura" style="width: 80px; margin-right: 10px;" />'; // Adicionado espaço entre inputs
        echo '<button class="button save-dimensions" data-image-id="' . esc_attr($image_id) . '">Salvar Informações</button>';
        echo '</td>';
        echo '<td style="vertical-align: middle;">';
        echo '<button class="button toggle-visibility" data-image-id="' . esc_attr($image_id) . '">' . (is_image_hidden($image_id) ? 'Mostrar' : 'Esconder') . '</button>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Restaurar dados originais da query
    wp_reset_postdata();

    // Paginação manual
    $total_pages = $images_query->max_num_pages;

    echo '<div class="tablenav" style="display: flex; justify-content: space-between; align-items: center;">'; // Ajustado layout da barra de navegação
    echo '<div class="tablenav-pages">';
    echo paginate_links(array(
        'base'      => add_query_arg('paged', '%#%'),
        'format'    => '',
        'prev_text' => __('&laquo; Anterior'),
        'next_text' => __('Próximo &raquo;'),
        'total'     => $total_pages,
        'current'   => $paged,
    ));
    echo '</div>';

    // Barra de ações em massa duplicada no fundo
    echo '<div class="bulk-actions-bar" style="margin-top: 10px; display: flex; align-items: center;">';
    echo '<input type="checkbox" id="select-all-bottom" style="margin-right: 10px; transform: scale(1.2);"> Selecionar Todos';
    echo '<input type="text" id="bulk-tag-input-bottom" placeholder="Adicionar tags em massa" style="margin-left: 10px; margin-right: 10px; width: 250px;">';
    echo '<button id="bulk-tag-button-bottom" class="button">Adicionar Tag em Massa</button>';
    echo '</div>';

    // Adicionar seletor de número de imagens por página e navegação direta no fundo
    echo '<div class="alignleft actions" style="margin-top: 10px; display: flex; align-items: center;">'; // Alinhar horizontalmente
    echo '<label for="images-per-page" style="margin-right: 5px;">Imagens por página:</label>';
    echo '<select id="images-per-page" name="images_per_page" style="margin-right: 15px;">'; // Adicionando margem entre elementos
    echo '<option value="10"' . selected($images_per_page, 10, false) . '>10</option>';
    echo '<option value="20"' . selected($images_per_page, 20, false) . '>20</option>';
    echo '<option value="50"' . selected($images_per_page, 50, false) . '>50</option>';
    echo '<option value="100"' . selected($images_per_page, 100, false) . '>100</option>';
    echo '</select>';
    echo '<button type="button" id="apply-images-per-page" class="button" style="margin-right: 15px;">Aplicar</button>'; // Adicionando margem entre elementos

    // Campo para navegar para uma página específica
    echo '<label for="goto-page" style="margin-right: 5px;">Ir para a página:</label>';
    echo '<input type="number" id="goto-page" min="1" max="' . $total_pages . '" value="' . $paged . '" style="width: 60px; margin-right: 5px;" />'; // Input para o número da página
    echo '<button type="button" id="apply-goto-page" class="button">Ir</button>';
    echo '</div>';

    echo '</div>'; // .tablenav
}

// Função para verificar se a imagem está oculta
function is_image_hidden($image_id) {
    return get_post_meta($image_id, '_hidden_from_gallery', true) === '1';
}

// Adicionar a página de administração
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
add_action('admin_menu', 'tag_image_table_menu');

// Função de callback para a página de administração
function tag_image_table_page() {
    ?>
    <div class="wrap">
        <h1>Tag Image Table</h1>
        <div id="image-table">
            <?php display_image_table(); ?>
        </div>
    </div>
    <?php
}

// Enfileirar scripts e estilos necessários
function enqueue_tag_image_table_scripts($hook) {
    if ('toplevel_page_tag-image-table' !== $hook) {
        return;
    }

    wp_enqueue_style('tag-image-table-css', plugins_url('css/tag-image-table.css', __FILE__));

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-autocomplete');
    wp_enqueue_script('tag-image-table-js', plugins_url('js/tag-image-table.js', __FILE__), array('jquery', 'jquery-ui-autocomplete'), '1.0', true);

    wp_localize_script('tag-image-table-js', 'tagImageTable', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tag_image_table_nonce'),
        'existing_tags' => get_existing_custom_tags(),
        'excluded_tags' => get_option('excluded_tags', array())
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_tag_image_table_scripts');

// Verificar se a função get_existing_custom_tags já existe
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