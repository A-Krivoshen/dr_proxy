<?php
/*
Plugin Name: Proxy Server Helper
Description: Настраивайте и используйте прокси-серверы с интерфейсом администратора.
Version: 1.6.0
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
    register_setting('proxy_settings_group', 'proxy_address');
    register_setting('proxy_settings_group', 'proxy_port');
    register_setting('proxy_settings_group', 'proxy_protocol');
    register_setting('proxy_settings_group', 'proxy_username', 'encrypt_proxy_data');
    register_setting('proxy_settings_group', 'proxy_password', 'encrypt_proxy_data');
}

function encrypt_proxy_data($value) {
    $key = AUTH_SALT;
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted_value = openssl_encrypt($value, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted_value);
}

function decrypt_proxy_data($value) {
    $key = AUTH_SALT;
    $data = base64_decode($value);
    $iv = substr($data, 0, 16);
    $encrypted_value = substr($data, 16);
    return openssl_decrypt($encrypted_value, 'aes-256-cbc', $key, 0, $iv);
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
            $args['headers']['Proxy-Authorization'] = 'Basic ' . base64_encode("$username:$password");
        }
    }

    return $args;
}
?>
