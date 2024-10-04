<?php
// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Handle AJAX request to save tags
add_action('wp_ajax_save_custom_tags', 'save_custom_tags');
add_action('wp_ajax_nopriv_save_custom_tags', 'save_custom_tags');

function save_custom_tags() {
    // Verify nonce for security
    check_ajax_referer('tag_image_table_nonce', 'nonce');

    // Get tag data
    $image_id = intval($_POST['image_id']);
    $new_tags = array_map('sanitize_text_field', explode(',', $_POST['tags']));

    // Filter out empty tags
    $new_tags = array_filter($new_tags, function($tag) {
        return !empty($tag);
    });

    // Check if there are new tags to add
    if (empty($new_tags)) {
        wp_send_json_error('Please enter at least one valid tag.');
        return;
    }

    // Get current tags and add new ones
    $current_tags = get_the_terms($image_id, 'custom_tag');
    if ($current_tags && !is_wp_error($current_tags)) {
        $current_tags_names = wp_list_pluck($current_tags, 'name');
        $tags = array_unique(array_merge($current_tags_names, $new_tags));
    } else {
        $tags = $new_tags;
    }

    // Update tags as taxonomy
    wp_set_object_terms($image_id, $tags, 'custom_tag');

    // Return updated tags
    $updated_tags = get_the_terms($image_id, 'custom_tag');
    $tags_string = !empty($updated_tags) && !is_wp_error($updated_tags) ? implode(', ', wp_list_pluck($updated_tags, 'name')) : '';

    // Return success and updated tags
    wp_send_json_success(array('tags' => $tags_string));
}

// Handle AJAX request to remove a specific tag
add_action('wp_ajax_remove_custom_tag', 'remove_custom_tag');
add_action('wp_ajax_nopriv_remove_custom_tag', 'remove_custom_tag');

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

// Handle AJAX request to save additional information
add_action('wp_ajax_save_dimensions', 'save_dimensions');
add_action('wp_ajax_nopriv_save_dimensions', 'save_dimensions');

function save_dimensions() {
    // Verify nonce for security
    check_ajax_referer('tag_image_table_nonce', 'nonce');

    // Get dimension data
    $image_id = intval($_POST['image_id']);
    $dimensions = array_map('sanitize_text_field', $_POST['dimensions']);

    // Save additional information as meta data
    update_post_meta($image_id, 'image_dimensions', $dimensions);

    // Return success
    wp_send_json_success();
}

// Handle AJAX request to add bulk tags
add_action('wp_ajax_add_bulk_tags', 'add_bulk_tags');
add_action('wp_ajax_nopriv_add_bulk_tags', 'add_bulk_tags');

function add_bulk_tags() {
    // Verify nonce for security
    check_ajax_referer('tag_image_table_nonce', 'nonce');

    // Get tag data
    $image_ids = isset($_POST['image_ids']) ? array_map('intval', $_POST['image_ids']) : array();
    $tags = isset($_POST['tags']) ? array_map('sanitize_text_field', explode(',', $_POST['tags'])) : array();

    // Filter out empty tags
    $tags = array_filter($tags, function($tag) {
        return !empty($tag);
    });

    if (empty($tags) || empty($image_ids)) {
        wp_send_json_error('Image IDs or tags not provided.');
        return;
    }

    foreach ($image_ids as $image_id) {
        // Get current tags and add new ones
        $current_tags = get_the_terms($image_id, 'custom_tag');
        if ($current_tags && !is_wp_error($current_tags)) {
            $current_tags_names = wp_list_pluck($current_tags, 'name');
            $all_tags = array_unique(array_merge($current_tags_names, $tags));
        } else {
            $all_tags = $tags;
        }

        // Update tags in WordPress
        wp_set_object_terms($image_id, $all_tags, 'custom_tag');
    }

    // Return success
    wp_send_json_success('Bulk tags added successfully.');
}

// Handle AJAX request to toggle image visibility
add_action('wp_ajax_toggle_image_visibility', 'toggle_image_visibility');
add_action('wp_ajax_nopriv_toggle_image_visibility', 'toggle_image_visibility');

function toggle_image_visibility() {
    // Verify nonce for security
    check_ajax_referer('tag_image_table_nonce', 'nonce');

    // Get image ID
    $image_id = intval($_POST['image_id']);

    // Toggle visibility status
    $current_status = get_post_meta($image_id, '_hidden_from_gallery', true);
    $new_status = $current_status === '1' ? '0' : '1';

    update_post_meta($image_id, '_hidden_from_gallery', $new_status);

    // Return new status
    wp_send_json_success(array('new_status' => $new_status));
}

// Handle AJAX request to filter images
add_action('wp_ajax_envira_filter_images', 'envira_filter_images');
add_action('wp_ajax_nopriv_envira_filter_images', 'envira_filter_images');

function envira_filter_images() {
    check_ajax_referer('filter_images_nonce', 'security');

    // Get request data
    $selected_tags = isset($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : [];
    $dimensions = isset($_POST['dimensions']) ? $_POST['dimensions'] : [];
    $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;

    // IDs of images to exclude
    $excluded_image_ids = array(3728, 8864); // Logo ID and SVG ID

    // Set up the query
    $args = array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 20,
        'paged'          => $paged,
        'post__not_in'   => $excluded_image_ids,
        'tax_query'      => array(),
        'meta_query'     => array('relation' => 'AND'),
    );

    // Add tag filter if necessary
    if (!empty($selected_tags)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'custom_tag',
            'field'    => 'name',
            'terms'    => $selected_tags,
            'operator' => 'AND',
        );
    }

    // Add dimension filters
    foreach ($dimensions as $key => $value) {
        if (!empty($value)) {
            $args['meta_query'][] = array(
                'key'     => 'image_dimensions',
                'value'   => sprintf(':"%s";s:%d:"%s";', $key, strlen($value), $value),
                'compare' => 'LIKE',
            );
        }
    }

    // Ensure we're getting all image mime types, including SVG
    $args['post_mime_type'] = array('image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/svg+xml');

    $images_query = new WP_Query($args);

    ob_start();
    if ($images_query->have_posts()) {
        echo '<div class="custom-gallery-masonry">';
        while ($images_query->have_posts()) {
            $images_query->the_post();
            $image_id = get_the_ID();
            $img_url = wp_get_attachment_url($image_id);
            $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            echo '<div class="masonry-item">';
            echo '<a href="' . esc_url($img_url) . '" data-lightbox="gallery" data-title="' . esc_attr($alt_text) . '">';
            echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($alt_text) . '" />';
            echo '</a>';
            echo '</div>';
        }
        echo '</div>';
        
        // Render pagination
        echo '<div id="gallery-pagination" class="pagination" style="display: flex; justify-content: center; padding: 10px 0;">';
        echo paginate_links(array(
            'total' => $images_query->max_num_pages,
            'current' => $paged,
            'prev_text' => __('&laquo; Previous'),
            'next_text' => __('Next &raquo;'),
        ));
        echo '</div>';
    } else {
        echo '<p>No images found.</p>';
    }
    $output = ob_get_clean();
    wp_reset_postdata();

    wp_send_json_success(array(
        'html' => $output,
        'max_pages' => $images_query->max_num_pages,
        'current_page' => $paged
    ));
}

// Handle AJAX request to delete excluded tags
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
    error_log('Função add_excluded_tags() chamada');

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
        error_log('Nenhuma tag fornecida');
        wp_send_json_error('Nenhuma tag fornecida.');
        return;
    }

    $excluded_tags = get_option('excluded_tags', array());
    error_log('Tags excluídas existentes: ' . print_r($excluded_tags, true));

    foreach ($tags as $tag) {
        if (!in_array($tag, $excluded_tags)) {
            $excluded_tags[] = $tag;
        }
    }

    error_log('Novas tags excluídas: ' . print_r($excluded_tags, true));

    // Atualizar a opção no banco de dados
    $updated = update_option('excluded_tags', array_unique($excluded_tags));

    if ($updated) {
        error_log('Tags adicionadas com sucesso');
        wp_send_json_success('Tags adicionadas à exclusão com sucesso.');
    } else {
        error_log('Falha ao atualizar as tags excluídas');
        wp_send_json_error('Não foi possível atualizar as tags excluídas.');
    }
}