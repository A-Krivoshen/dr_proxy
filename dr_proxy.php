<?php
/*
Plugin Name: Dr.slon Proxy Server Plugin
Description: Настраивайте и используйте прокси-серверы с интерфейсом администратора.
Version: 1.4
Author: Dr.Slon
*/

add_action('admin_menu', 'proxy_plugin_menu');

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
    $ad_html = '<div class="wps-widget" data-w="https:////wpwidget.ru/greetings?orientation=3&pid=11291"></div>';
    $image_url = 'https://krivoshein.site/wp-content/uploads/2024/02/logo_k_drslon.png';
    echo '<div>';
    echo '<h2>Dr.slon Proxy Server Plugin</h2>';
    echo '<a href="https://t.me/DrSlon" target="_blank"><img src="' . esc_url($image_url) . '" alt="https://krivoshein.site" style="max-width: 100%;" /></a>';
    echo $ad_html; 
    echo '<script src="https://wpwidget.ru/js/wps-widget-entry.min.js"></script>';
    echo '</div>';
?>
    <div class="wrap">
    	<h1>Настройки прокси-сервера</h1>
        <form method="post" action="">
            <?php wp_nonce_field('proxy_settings', 'proxy_nonce'); ?>

            <label for="proxy_address">Адрес прокси:</label>
            <input type="text" name="proxy_address" id="proxy_address" value="<?php echo esc_attr(get_option('proxy_address', '')); ?>" />

            <label for="proxy_port">Порт прокси:</label>
            <input type="number" name="proxy_port" id="proxy_port" value="<?php echo esc_attr(get_option('proxy_port', '')); ?>" />

            <label for="proxy_protocol">Протокол прокси:</label>
            <select name="proxy_protocol" id="proxy_protocol">
                <option value="http" <?php selected(get_option('proxy_protocol', ''), 'http'); ?>>HTTP</option>
                <option value="https" <?php selected(get_option('proxy_protocol', ''), 'https'); ?>>HTTPS</option>
            </select>

            <label for="proxy_username">Имя пользователя:</label>
            <input type="text" name="proxy_username" id="proxy_username" value="<?php echo esc_attr(get_option('proxy_username', '')); ?>" />

            <label for="proxy_password">Пароль:</label>
            <input type="password" name="proxy_password" id="proxy_password" value="<?php echo esc_attr(get_option('proxy_password', '')); ?>" />

            <p class="description">Введите данные прокси-сервера.</p>

            <input type="submit" class="button button-primary" value="Сохранить настройки" />
        </form>

        <h2>Загружать прокси-серверы</h2>
        <form method="post" action="">
            <?php wp_nonce_field('load_proxy_servers', 'load_proxy_servers_nonce'); ?>
            <label for="proxy_server_list">Список прокси-серверов (по одному в строке):</label>
            <textarea name="proxy_server_list" id="proxy_server_list" rows="5"><?php echo esc_textarea(get_option('proxy_server_list', '')); ?></textarea>
            <p class="description">Введите прокси-серверы в формате: адрес:порт:протокол:имя пользователя:пароль (например, proxy.example.com:8080:http:user:pass).</p>
            <input type="submit" class="button button-primary" value="Загружать прокси-серверы" />
        </form>
    </div>
    <?php
}

add_action('admin_init', 'save_proxy_settings');

function save_proxy_settings() {
    if (isset($_POST['proxy_nonce']) && wp_verify_nonce($_POST['proxy_nonce'], 'proxy_settings')) {
        $proxy_address = sanitize_text_field($_POST['proxy_address']);
        $proxy_port = absint($_POST['proxy_port']);
        $proxy_protocol = sanitize_text_field($_POST['proxy_protocol']);
        $proxy_username = sanitize_text_field($_POST['proxy_username']);
        $proxy_password = sanitize_text_field($_POST['proxy_password']);

        update_option('proxy_address', $proxy_address);
        update_option('proxy_port', $proxy_port);
        update_option('proxy_protocol', $proxy_protocol);

        // Encrypt 
        if (!empty($proxy_username)) {
            $encrypted_username = openssl_encrypt($proxy_username, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
            update_option('proxy_username', $encrypted_username);
        }

        if (!empty($proxy_password)) {
            $encrypted_password = openssl_encrypt($proxy_password, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
            update_option('proxy_password', $encrypted_password);
        }
    }

    if (isset($_POST['load_proxy_servers_nonce']) && wp_verify_nonce($_POST['load_proxy_servers_nonce'], 'load_proxy_servers')) {
        $proxy_server_list = sanitize_textarea_field($_POST['proxy_server_list']);
        update_option('proxy_server_list', $proxy_server_list);
    }
}

add_filter('http_request_args', 'proxy_http_request_args', 10, 1);

function proxy_http_request_args($args) {
    $proxy_address = get_option('proxy_address', '');
    $proxy_port = get_option('proxy_port', '');
    $proxy_protocol = get_option('proxy_protocol', '');
    $encrypted_username = get_option('proxy_username', '');
    $encrypted_password = get_option('proxy_password', '');
    $proxy_server_list = get_option('proxy_server_list', '');

    if (!empty($proxy_address) && !empty($proxy_port) && !empty($proxy_protocol)) {
        $proxy_url = sprintf('%s://%s:%d', $proxy_protocol, $proxy_address, $proxy_port);
        $args['proxy'] = $proxy_url;

        if (!empty($encrypted_username) && !empty($encrypted_password)) {
            $proxy_username = openssl_decrypt($encrypted_username, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
            $proxy_password = openssl_decrypt($encrypted_password, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);

            $args['headers']['Proxy-Authorization'] = 'Basic ' . base64_encode("$proxy_username:$proxy_password");
        }
    } elseif (!empty($proxy_server_list)) {
        $proxy_servers = explode("\n", $proxy_server_list);
        $random_proxy = $proxy_servers[array_rand($proxy_servers)];
        $proxy_parts = array_map('trim', explode(':', $random_proxy));

        if (count($proxy_parts) >= 3) {
            list($proxy_address, $proxy_port, $proxy_protocol) = $proxy_parts;
            $proxy_url = sprintf('%s://%s:%d', $proxy_protocol, $proxy_address, $proxy_port);
            $args['proxy'] = $proxy_url;

            if (count($proxy_parts) == 5) {
                list($encrypted_username, $encrypted_password) = array_slice($proxy_parts, -2);
                $proxy_username = openssl_decrypt($encrypted_username, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);
                $proxy_password = openssl_decrypt($encrypted_password, 'aes-256-cbc', AUTH_SALT, 0, AUTH_SALT);

                $args['headers']['Proxy-Authorization'] = 'Basic ' . base64_encode("$proxy_username:$proxy_password");
            }
        }
    }

    return $args;
} 
