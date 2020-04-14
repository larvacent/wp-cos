<?php

if(!defined('WP_UNINSTALL_PLUGIN')){
    exit();
}
delete_option('cos_options');
update_option('upload_url_path', '');