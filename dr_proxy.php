<?php
/*
Plugin Name: Proxy Server Helper
Description: Настраивайте и используйте прокси-серверы с интерфейсом администратора.
Version: 1.5.2
Author: Dr.Slon
Author URI: https://krivoshein.site
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.htm
*/

add_action('admin_init', 'register_proxy_settings');

function register_proxy_settings() {
    register_setting('proxy_settings_group', 'proxy_address', 'encrypt_proxy_data');
    register_setting('proxy_settings_group', 'proxy_port', 'encrypt_proxy_data');
    register_setting('proxy_settings_group', 'proxy_protocol', 'encrypt_proxy_data');
    register_setting('proxy_settings_group', 'proxy_username', 'encrypt_proxy_data');
    register_setting('proxy_settings_group', 'proxy_password', 'encrypt_proxy_data');
}

function encrypt_proxy_data($value) {
    return openssl_encrypt($value, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
}

function decrypt_proxy_data($value) {
    return openssl_decrypt($value, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
}

add_action('admin_menu', 'proxy_plugin_menu');
add_filter('http_request_args', 'proxy_http_request_args', 10, 1);

function proxy_plugin_menu() {
    add_menu_page(
        'Proxy Server Settings',
        'Proxy Settings',
        'manage_options',
        'proxy-server-settings',
        'proxy_settings_page',
        'dashicons-admin-tools',
        99
    );
}

function proxy_settings_page() {
    $ad_html = '<div class="wps-widget" data-w="https://wpwidget.ru/greetings?orientation=3&pid=11291"></div>';
    $image_url = 'https://krivoshein.site/wp-content/uploads/2024/02/logo_k_drslon.png';
    echo '<div>';
    echo '<h2>Dr.slon Proxy Server Plugin</h2>';
    echo $ad_html; 
    echo '<script src="https://wpwidget.ru/js/wps-widget-entry.min.js"></script>';
    echo '<a href="https://t.me/DrSlon" target="_blank"><img src="' . esc_url($image_url) . '" alt="https://krivoshein.site" " /></a>';   
    echo '</div>';
    ?>
    <style>
        .proxy-column {
            display: inline-block;
            width: 30%;
            margin-right: 2%;
        }
    </style>

    <div class="wrap">
        <h1>Настройки прокси-сервера</h1>
        <form method="post" action="options.php">
            <?php settings_fields('proxy_settings_group'); ?>
            <div class="proxy-column">
                <label for="proxy_address">Адрес прокси:</label>
                <input type="text" name="proxy_address" id="proxy_address" value="<?php echo esc_attr(decrypt_proxy_data(get_option('proxy_address', ''))); ?>" />
            </div>

            <div class="proxy-column">
                <label for="proxy_port">Порт прокси:</label>
                <input type="text" name="proxy_port" id="proxy_port" value="<?php echo esc_attr(decrypt_proxy_data(get_option('proxy_port', ''))); ?>" />
            </div>

            <div class="proxy-column">
                <label for="proxy_protocol">Протокол прокси:</label>
                <select name="proxy_protocol" id="proxy_protocol">
                    <option value="http" <?php selected(get_option('proxy_protocol', ''), 'http'); ?>>HTTP</option>
                    <option value="https" <?php selected(get_option('proxy_protocol', ''), 'https'); ?>>HTTPS</option>
                </select>
            </div>

            <div class="proxy-column">
                <label for="proxy_username">Имя пользователя:</label>
                <input type="text" name="proxy_username" id="proxy_username" value="<?php echo esc_attr(decrypt_proxy_data(get_option('proxy_username', ''))); ?>" />
            </div>

            <div class="proxy-column">
                <label for="proxy_password">Пароль:</label>
                <input type="password" name="proxy_password" id="proxy_password" value="<?php echo esc_attr(decrypt_proxy_data(get_option('proxy_password', ''))); ?>" />
            </div>

            <input type="submit" class="button button-primary" value="Сохранить настройки" />
        </form>
        <form method="post" action="">
            <?php
            if (!empty($_POST['reset_proxy_settings'])) {
                delete_option('proxy_address');
                delete_option('proxy_port');
                delete_option('proxy_protocol');
                delete_option('proxy_username');
                delete_option('proxy_password');
            }
            ?>
            <input type="submit" name="reset_proxy_settings" class="button button-secondary" value="Сбросить настройки" />
        </form>
    </div>
    <?php
}

function proxy_http_request_args($args) {
    $proxy_address = decrypt_proxy_data(get_option('proxy_address', ''));
    $proxy_port = decrypt_proxy_data(get_option('proxy_port', ''));
    $proxy_protocol = get_option('proxy_protocol', '');
    $encrypted_username = get_option('proxy_username', '');
    $encrypted_password = get_option('proxy_password', '');

    if (!empty($proxy_address) && !empty($proxy_port) && !empty($proxy_protocol)) {
        $proxy_url = sprintf('%s://%s:%s', $proxy_protocol, $proxy_address, $proxy_port);
        $args['proxy'] = $proxy_url;

        if (!empty($encrypted_username) && !empty($encrypted_password)) {
            $proxy_username = openssl_decrypt($encrypted_username, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
            $proxy_password = openssl_decrypt($encrypted_password, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
            $args['headers']['Proxy-Authorization'] = 'Basic ' . base64_encode("$proxy_username:$proxy_password");
        }
    }

    return $args;
}
