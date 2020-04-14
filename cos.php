<?php
/*
Plugin Name: COS
Plugin URI: https://www.tintsoft.com
Description: WordPress同步附件内容远程至腾讯云COS云存储中，实现网站数据与静态资源分离，提高网站加载速度。
Version: 1.0
Author: 天智软件
Author URI: https://www.tintsoft.com
*/
require_once 'cos_actions.php';

$currentWordPressVersion = (float)get_bloginfo('version');

//激活插件钩子
register_activation_hook(__FILE__, 'cos_set_options');
//停用插件钩子
register_deactivation_hook(__FILE__, 'cos_restore_options');

add_filter('sanitize_file_name', 'cos_sanitize_file_name', 10, 1);
add_filter('wp_unique_filename', 'cos_unique_filename');

if (substr_count($_SERVER['REQUEST_URI'], '/update.php') <= 0) {
    add_filter('wp_handle_upload', 'cos_handle_upload');
    if ($currentWordPressVersion >= 5.3) {
        add_filter('wp_generate_attachment_metadata', 'cos_update_attachment_metadata');
    }
}
if ($currentWordPressVersion < 5.3) {
    add_filter('wp_update_attachment_metadata', 'cos_update_attachment_metadata');
}
add_action('delete_attachment', 'cos_delete_attachment');
add_action('admin_menu', 'cos_add_setting_page');