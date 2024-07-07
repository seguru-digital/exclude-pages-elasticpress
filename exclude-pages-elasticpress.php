<?php

 /**
  * Plugin Name:       Exclude Pages from ElasticPress
  * Description:       Allows users to select pages that should not be indexed by ElasticPress.
  * Requires at least: 6.3.0
  * Requires PHP:      7.4
  * Version:           0.0.1
  * Author:            seguru-digital
  * License:           GPL-2.0-or-later
  * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
  * Text Domain:       exclude_pages_from_elasticpress
  * Website:           https://seguru.digital
  */
 
 if ( ! defined( 'ABSPATH' ) ) {
     exit; // Exit if accessed directly.
 }
 
 $plugin_prefix = 'EXCLUDEPAGESFROMELASTICPRESS';
 
 define($plugin_prefix . '_DIR', plugin_basename(__DIR__));
 define($plugin_prefix . '_BASE', plugin_basename(__FILE__));
 define($plugin_prefix . '_PATH', plugin_dir_path(__FILE__));
 define($plugin_prefix . '_VER', '0.0.1');
 define($plugin_prefix . '_CACHE_KEY', 'exclude_pages_from_elasticpress-cache-key-for-plugin');
 define($plugin_prefix . '_REMOTE_URL', 'https://plugins.seguru.xyz/wp-content/uploads/downloads/12/info.json');
 
 require constant($plugin_prefix . '_PATH') . 'inc/update.php';
 
 new DPUpdateChecker(
     constant($plugin_prefix . '_DIR'),
     constant($plugin_prefix . '_VER'),
     constant($plugin_prefix . '_CACHE_KEY'),
     constant($plugin_prefix . '_REMOTE_URL'),
     constant($plugin_prefix . '_BASE')
 );

// Hook to add meta box
add_action('add_meta_boxes', 'epep_add_meta_box');

// Hook to save meta box data
add_action('save_post', 'epep_save_meta_box_data');

// Hook to filter ElasticPress indexing
add_filter('ep_post_sync_args_post_prepare_meta', 'epep_exclude_pages_from_indexing', 10, 2);

function epep_add_meta_box() {
    add_meta_box(
        'epep_meta_box', // ID
        'ElasticPress Exclude', // Title
        'epep_meta_box_callback', // Callback
        'page', // Post type
        'side' // Context
    );
}

function epep_meta_box_callback($post) {
    wp_nonce_field('epep_save_meta_box_data', 'epep_meta_box_nonce');

    $value = get_post_meta($post->ID, '_epep_exclude', true);

    echo '<label for="epep_exclude">';
    echo 'Exclude this page from ElasticPress indexing';
    echo '</label> ';
    echo '<input type="checkbox" id="epep_exclude" name="epep_exclude" value="1" ' . checked($value, '1', false) . ' />';
}

function epep_save_meta_box_data($post_id) {
    if (!isset($_POST['epep_meta_box_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['epep_meta_box_nonce'], 'epep_save_meta_box_data')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    if (!isset($_POST['epep_exclude'])) {
        return;
    }

    $exclude = sanitize_text_field($_POST['epep_exclude']);

    update_post_meta($post_id, '_epep_exclude', $exclude);
}

function epep_exclude_pages_from_indexing($prepared_post, $post) {
    $exclude = get_post_meta($post->ID, '_epep_exclude', true);

    if ($exclude == '1') {
        return false; // Prevent indexing
    }

    return $prepared_post;
}
?>