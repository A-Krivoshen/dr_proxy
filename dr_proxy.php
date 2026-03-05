<?php
/*
Plugin Name: Proxy Server Helper
Description: Настраивайте и используйте прокси-серверы с интерфейсом администратора.
Version: 1.6.1
Author: Aleksey Krivoshein
Author URI: https://krivoshein.site
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!extension_loaded('openssl')) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>OpenSSL extension is not enabled. Proxy Server Helper requires it to function.</p></div>';
    });
    return;
}

// Регистрация настроек
add_action('admin_init', 'register_proxy_settings');
function register_proxy_settings() {
    register_setting('proxy_settings_group', 'proxy_address', 'sanitize_proxy_address');
    register_setting('proxy_settings_group', 'proxy_port', 'sanitize_proxy_port');
    register_setting('proxy_settings_group', 'proxy_protocol', 'sanitize_proxy_protocol');
    register_setting('proxy_settings_group', 'proxy_username', 'sanitize_and_encrypt_proxy_data');
    register_setting('proxy_settings_group', 'proxy_password', 'sanitize_and_encrypt_proxy_data');
}

function sanitize_proxy_address($value) {
    return sanitize_text_field((string) $value);
}

function sanitize_proxy_port($value) {
    $port = absint($value);
    if ($port < 1 || $port > 65535) {
        return '';
    }

    return (string) $port;
}

function sanitize_proxy_protocol($value) {
    $allowed_protocols = array('http', 'https');
    $protocol = strtolower(sanitize_text_field((string) $value));

    return in_array($protocol, $allowed_protocols, true) ? $protocol : 'http';
}

function sanitize_and_encrypt_proxy_data($value) {
    $value = sanitize_text_field((string) $value);
    if ($value === '') {
        return '';
    }

    return encrypt_proxy_data($value);
}

function encrypt_proxy_data($value) {
    if ($value === '') {
        return '';
    }

    $key = AUTH_SALT;
    $iv = random_bytes(16);
    $encrypted_value = openssl_encrypt($value, 'aes-256-cbc', $key, 0, $iv);

    if ($encrypted_value === false) {
        return '';
    }

    return base64_encode($iv . $encrypted_value);
}

function decrypt_proxy_data($value) {
    if ($value === '') {
        return '';
    }

    $key = AUTH_SALT;
    $data = base64_decode($value, true);

    if ($data === false || strlen($data) <= 16) {
        // Поддержка ранее сохранённых или незашифрованных значений.
        return sanitize_text_field((string) $value);
    }

    $iv = substr($data, 0, 16);
    $encrypted_value = substr($data, 16);
    $decrypted_value = openssl_decrypt($encrypted_value, 'aes-256-cbc', $key, 0, $iv);

    return $decrypted_value === false ? '' : $decrypted_value;
}

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
    if (!current_user_can('manage_options')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1>Настройки прокси-сервера</h1>
        <form method="post" action="options.php">
            <?php settings_fields('proxy_settings_group'); ?>
            <div style="margin-bottom: 20px;">
                <label for="proxy_address">Адрес прокси:</label>
                <input type="text" name="proxy_address" value="<?php echo esc_attr(get_option('proxy_address', '')); ?>" />
            </div>

            <div style="margin-bottom: 20px;">
                <label for="proxy_port">Порт прокси:</label>
                <input type="text" name="proxy_port" value="<?php echo esc_attr(get_option('proxy_port', '')); ?>" />
            </div>

            <div style="margin-bottom: 20px;">
                <label for="proxy_protocol">Протокол:</label>
                <select name="proxy_protocol">
                    <option value="http" <?php selected(get_option('proxy_protocol', ''), 'http'); ?>>HTTP</option>
                    <option value="https" <?php selected(get_option('proxy_protocol', ''), 'https'); ?>>HTTPS</option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="proxy_username">Имя пользователя:</label>
                <input type="text" name="proxy_username" value="<?php echo esc_attr(decrypt_proxy_data(get_option('proxy_username', ''))); ?>" />
            </div>

            <div style="margin-bottom: 20px;">
                <label for="proxy_password">Пароль:</label>
                <input type="password" name="proxy_password" value="<?php echo esc_attr(decrypt_proxy_data(get_option('proxy_password', ''))); ?>" />
            </div>

            <input type="submit" class="button button-primary" value="Сохранить настройки" />
        </form>
    </div>
    <?php
}

add_filter('http_request_args', 'proxy_http_request_args', 10, 1);
function proxy_http_request_args($args) {
    $proxy_address = get_option('proxy_address', '');
    $proxy_port = get_option('proxy_port', '');
    $proxy_protocol = get_option('proxy_protocol', '');
    $username = decrypt_proxy_data(get_option('proxy_username', ''));
    $password = decrypt_proxy_data(get_option('proxy_password', ''));

    if (!empty($proxy_address) && !empty($proxy_port)) {
        $proxy_url = sprintf('%s://%s:%s', $proxy_protocol, $proxy_address, $proxy_port);
        $args['proxy'] = $proxy_url;

        if (!empty($username) && !empty($password)) {
            if (!isset($args['headers']) || !is_array($args['headers'])) {
                $args['headers'] = array();
            }

            $args['headers']['Proxy-Authorization'] = 'Basic ' . base64_encode("$username:$password");
        }
    }

    return $args;
}
?>
