<?php
/*
Plugin Name: Proxy Server Helper
Description: Настраивайте и используйте прокси-серверы с интерфейсом администратора.
Version: 1.5
Author: Dr.Slon
Author URI: https://krivoshein.site
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.htm
*/

add_action('admin_init', 'register_proxy_settings');

function register_proxy_settings() {
    register_setting('proxy_settings_group', 'proxy_address');
    register_setting('proxy_settings_group', 'proxy_port');
    register_setting('proxy_settings_group', 'proxy_protocol');
    register_setting('proxy_settings_group', 'proxy_username');
    register_setting('proxy_settings_group', 'proxy_password', 'encrypt_proxy_password');
    register_setting('proxy_settings_group', 'proxy_priority');
    register_setting('proxy_settings_group', 'proxy_server_list', 'encrypt_proxy_server_list');
}

function encrypt_proxy_password($value) {
    return openssl_encrypt($value, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
}

function encrypt_proxy_server_list($value) {
    return openssl_encrypt($value, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
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
        #proxy_server_list {
            width: 100%;
        }
    </style>

    <div class="wrap">
        <h1>Настройки прокси-сервера</h1>
        <form method="post" action="options.php">
            <?php settings_fields('proxy_settings_group'); ?>
            <div class="proxy-column">
                <label for="proxy_address">Адрес прокси:</label>
                <input type="text" name="proxy_address" id="proxy_address" value="<?php echo esc_attr(get_option('proxy_address', '')); ?>" />
            </div>

            <div class="proxy-column">
                <label for="proxy_port">Порт прокси:</label>
                <input type="number" name="proxy_port" id="proxy_port" value="<?php echo esc_attr(get_option('proxy_port', '')); ?>" />
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
                <input type="text" name="proxy_username" id="proxy_username" value="<?php echo esc_attr(get_option('proxy_username', '')); ?>" />
            </div>

            <div class="proxy-column">
                <label for="proxy_password">Пароль:</label>
                <input type="password" name="proxy_password" id="proxy_password" value="<?php echo esc_attr(get_option('proxy_password', '')); ?>" />
            </div>

            <div class="proxy-column">
                <label for="proxy_priority">Приоритет:</label>
                <input type="number" name="proxy_priority" id="proxy_priority" value="<?php echo esc_attr(get_option('proxy_priority', '')); ?>" />
            </div>

            <input type="submit" class="button button-primary" value="Сохранить настройки" />
        </form>

        <h2>Список прокси-серверов (в формате JSON)</h2>
        <form method="post" action="options.php">
            <?php settings_fields('proxy_settings_group'); ?>
            <textarea name="proxy_server_list" id="proxy_server_list" rows="5"><?php echo esc_textarea(get_option('proxy_server_list', '')); ?></textarea>
            <p class="description">Введите прокси-серверы в формате JSON. Каждый сервер должен быть в формате {"address": "адрес", "port": порт, "protocol": "протокол", "username": "имя пользователя", "password": "пароль", "priority": приоритет}.</p>
            <input type="submit" class="button button-primary" value="Сохранить настройки" />
        </form>
    </div>
    <?php
}

function proxy_http_request_args($args) {
    $proxy_address = get_option('proxy_address', '');
    $proxy_port = get_option('proxy_port', '');
    $proxy_protocol = get_option('proxy_protocol', '');
    $encrypted_username = get_option('proxy_username', '');
    $encrypted_password = get_option('proxy_password', '');
    $proxy_server_list_json = get_option('proxy_server_list', ''); // Получаем JSON данные списка прокси-серверов

    if (!empty($proxy_address) && !empty($proxy_port) && !empty($proxy_protocol)) {
        $proxy_url = sprintf('%s://%s:%d', $proxy_protocol, $proxy_address, $proxy_port);

        // Если есть JSON данные списка прокси-серверов, используем их
        if (!empty($proxy_server_list_json)) {
            $proxy_servers = json_decode($proxy_server_list_json, true); // Декодируем JSON в массив PHP
            if (!empty($proxy_servers) && is_array($proxy_servers)) {
                usort($proxy_servers, function($a, $b) {
                    return $b['priority'] - $a['priority']; // Сортируем по убыванию приоритета
                });
                foreach ($proxy_servers as $proxy) {
                    $proxy_url = sprintf('%s://%s:%d', $proxy['protocol'], $proxy['address'], $proxy['port']);
                    if (!empty($proxy['username']) && !empty($proxy['password'])) {
                        $proxy_username = openssl_decrypt($proxy['username'], 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
                        $proxy_password = openssl_decrypt($proxy['password'], 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
                        $args['headers']['Proxy-Authorization'] = 'Basic ' . base64_encode("$proxy_username:$proxy_password");
                    }
                    $args['proxy'] = $proxy_url;
                    break; // Прерываем цикл после первого прокси с наивысшим приоритетом
                }
            }
        }
    }

    return $args;
}
