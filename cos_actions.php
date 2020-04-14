<?php
require_once 'vendor/autoload.php';

define('COS_VERSION', 1.0);
define('COS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COS_BASENAME', plugin_basename(__FILE__));
define('COS_BASE_FOLDER', plugin_basename(dirname(__FILE__)));

/**
 * 初始化COS设置
 */
function cos_set_options()
{
    $default_options = [
        'version' => COS_VERSION,
        'bucket' => "",
        'region' => "",
        'app_id' => "",
        'secret_id' => "",
        'secret_key' => "",
        'no_local_file' => false,
        'cos_url_path' => '',
        'opt' => [
            'auto_rename' => 0,
        ],
    ];
    $cos_options = get_option('cos_options');
    if (!$cos_options) {
        add_option('cos_options', $default_options, '', 'yes');
    };
    if (isset($cos_options['cos_url_path']) && $cos_options['cos_url_path'] != '') {
        update_option('upload_url_path', $cos_options['cos_url_path']);
    }
}

function cos_restore_options()
{
    $cos_options = get_option('cos_options');
    $cos_options['cos_url_path'] = get_option('upload_url_path');
    if (!array_key_exists('opt', $cos_options)) {
        $cos_options['opt'] = ['auto_rename' => 0];
    }
    update_option('cos_options', $cos_options);
    update_option('upload_url_path', '');
}

function cos_add_setting_page()
{
    if (!function_exists('cos_setting_page')) {
        require_once 'cos_setting_page.php';
    }
    add_menu_page('COS设置', 'COS设置', 'manage_options', __FILE__, 'cos_setting_page');
}

/**
 * 删除本地文件
 * @param $file_path
 * @return bool
 */
function cos_delete_local_file($file_path)
{
    try {
        if (!@file_exists($file_path)) {
            return true;
        }
        if (!@unlink($file_path)) {
            return false;
        }
        return true;
    } catch (Exception $ex) {
        return false;
    }
}

/**
 * 路径处理
 * @param string $key
 * @param string $uploadUrlPath
 * @return string
 */
function cos_key_handle($key, $uploadUrlPath)
{
    # 参数2 为了减少option的获取次数
    $url_parse = wp_parse_url($uploadUrlPath);
    # 约定url不要以/结尾，减少判断条件
    if (array_key_exists('path', $url_parse)) {
        $key = $url_parse['path'] . $key;
    }
    return $key;
}

/**
 * COS 远程文件是否存在
 * @param string $key
 * @return bool
 */
function cos_file_exists($key)
{
    $cos_options = get_option('cos_options');
    $upload_url_path = get_option('upload_url_path');
    try {
        cos_client()->headObject(['Bucket' => $cos_options['bucket'], 'Key' => cos_key_handle($key, $upload_url_path),]);
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * COS 文件上传
 * @param string $key
 * @param string $file_local_path
 * @param array $opt
 * @param bool $no_local_file
 */
function cos_file_upload($key, $file_local_path, $opt = [], $no_local_file = false)
{
    $cos_options = get_option('cos_options');
    $upload_url_path = get_option('upload_url_path');
    try {
        cos_client()->Upload($cos_options['bucket'], cos_key_handle($key, $upload_url_path), $body = fopen($file_local_path, 'rb'));
        if ($no_local_file) {
            cos_delete_local_file($file_local_path);
        }
    } catch (\Exception $e) {
        return;
    }
}

/**
 * Handle PHP uploads in WordPress, sanitizing file names, checking extensions for mime type,
 * and moving the file to the appropriate directory within the uploads directory.
 * @param array $upload
 * @return array
 */
function cos_handle_upload($upload)
{
    $mime_types = get_allowed_mime_types();
    $image_mime_types = [$mime_types['jpg|jpeg|jpe'], $mime_types['gif'], $mime_types['png'], $mime_types['bmp'], $mime_types['tiff|tif'], $mime_types['ico'],];
    if (!in_array($upload['type'], $image_mime_types)) {
        $key = str_replace(wp_upload_dir()['basedir'], '', $upload['file']);
        $local_path = $upload['file'];
        cos_file_upload($key, $local_path, ['Content-Type' => $upload['type']], get_option('cos_options')['no_local_file']);
    }
    return $upload;
}

/**
 * Sanitizes a filename, replacing whitespace with dashes.
 * @param string $filename
 * @return string
 */
function cos_sanitize_file_name($filename)
{
    $cos_options = get_option('cos_options');
    if ($cos_options['opt']['auto_rename']) {
        return date("YmdHis") . "" . mt_rand(100, 999) . "." . pathinfo($filename, PATHINFO_EXTENSION);
    } else {
        return $filename;
    }
}

/**
 * Get a filename that is sanitized and unique for the given directory.
 * @param string $filename
 * @return string|string[]
 */
function cos_unique_filename($filename)
{
    $ext = '.' . pathinfo($filename, PATHINFO_EXTENSION);
    $number = '';
    $upload_url_path = get_option('upload_url_path');
    while (cos_file_exists(cos_key_handle(wp_get_upload_dir()['subdir'] . "/$filename", $upload_url_path))) {
        $new_number = (int)$number + 1;
        if ('' == "$number$ext") {
            $filename = "$filename-" . $new_number;
        } else {
            $filename = str_replace(["-$number$ext", "$number$ext"], '-' . $new_number . $ext, $filename);
        }
        $number = $new_number;
    }
    return $filename;
}

/**
 * Update metadata for an attachment.
 * @param array $metadata
 * @return mixed
 */
function cos_update_attachment_metadata($metadata)
{
    $cos_options = get_option('cos_options');
    $wp_uploads = wp_upload_dir();
    if (isset($metadata['file'])) {
        $attachment_key = '/' . $metadata['file'];
        $attachment_local_path = $wp_uploads['basedir'] . $attachment_key;
        $opt = ['Content-Type' => $metadata['type']];
        cos_file_upload($attachment_key, $attachment_local_path, $opt, $cos_options['no_local_file']);
    }
    if (isset($metadata['sizes']) && count($metadata['sizes']) > 0) {
        foreach ($metadata['sizes'] as $val) {
            $attachment_thumbs_key = '/' . dirname($metadata['file']) . '/' . $val['file'];
            $attachment_thumbs_local_path = $wp_uploads['basedir'] . $attachment_thumbs_key;
            $opt = ['Content-Type' => $val['mime-type']];
            cos_file_upload($attachment_thumbs_key, $attachment_thumbs_local_path, $opt, $cos_options['no_local_file']);
        }
    }
    return $metadata;
}

/**
 * 删除远程附件
 * @param $post_id
 */
function cos_delete_attachment($post_id)
{
    $deleteObjects = [];
    $meta = wp_get_attachment_metadata($post_id);  // 以下获取的key都不以/开头, 但该sdk方法必须非/开头
    $upload_url_path = get_option('upload_url_path');
    if (isset($meta['file'])) {
        $attachment_key = '/' . $meta['file'];
        array_push($deleteObjects, ['Key' => ltrim(cos_key_handle($attachment_key, $upload_url_path), '/')]);
    } else {
        $file = get_attached_file($post_id);
        if ($file) {
            $attached_key = '/' . str_replace(wp_get_upload_dir()['basedir'] . '/', '', $file);
            $deleteObjects[] = ['Key' => ltrim(cos_key_handle($attached_key, $upload_url_path), '/')];
        }
    }
    if (isset($meta['sizes']) && count($meta['sizes']) > 0) {
        foreach ($meta['sizes'] as $val) {
            $attachment_thumbs_key = '/' . dirname($meta['file']) . '/' . $val['file'];
            $deleteObjects[] = ['Key' => ltrim(cos_key_handle($attachment_thumbs_key, $upload_url_path), '/')];
        }
    }
    if (!empty($deleteObjects)) {
        try {
            cos_client()->deleteObjects(['Bucket' => esc_attr(get_option('cos_options')['bucket']), 'Objects' => $deleteObjects,]);
        } catch (Exception $ex) {

        }
    }
}

/**
 * Get cos client.
 * @return \Qcloud\Cos\Client
 */
function cos_client()
{
    $cos_options = get_option('cos_options', true);
    return new \Qcloud\Cos\Client([
        'region' => $cos_options['region'],
        'credentials' => [
            'secretId' => $cos_options['secret_id'],
            'secretKey' => $cos_options['secret_key']
        ]
    ]);
}