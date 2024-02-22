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
        <form method="post" action="">
            <?php wp_nonce_field('save_proxy_settings', 'save_proxy_nonce'); ?>

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
        <form method="post" action="">
            <?php wp_nonce_field('save_proxy_server_list', 'save_proxy_server_nonce'); ?>
            <textarea name="proxy_server_list" id="proxy_server_list" rows="5"><?php echo esc_textarea(get_option('proxy_server_list', '')); ?></textarea>
            <p class="description">Введите прокси-серверы в формате JSON. Каждый сервер должен быть в формате {"address": "адрес", "port": порт, "protocol": "протокол", "username": "имя пользователя", "password": "пароль", "priority": приоритет}.</p>
            <input type="submit" class="button button-primary" value="Сохранить настройки" />
        </form>
    </div>
    <?php
}

add_action('admin_init', 'save_proxy_settings');

function save_proxy_settings() {
    if (isset($_POST['save_proxy_nonce']) && wp_verify_nonce($_POST['save_proxy_nonce'], 'save_proxy_settings')) {
        $proxy_address = sanitize_text_field($_POST['proxy_address']);
        $proxy_port = absint($_POST['proxy_port']);
        $proxy_protocol = sanitize_text_field($_POST['proxy_protocol']);
        $proxy_username = sanitize_text_field($_POST['proxy_username']);
        $proxy_password = sanitize_text_field($_POST['proxy_password']);
        $proxy_priority = absint($_POST['proxy_priority']);

        update_option('proxy_address', $proxy_address);
        update_option('proxy_port', $proxy_port);
        update_option('proxy_protocol', $proxy_protocol);
        update_option('proxy_username', $proxy_username);
        update_option('proxy_password', $proxy_password);
        update_option('proxy_priority', $proxy_priority);
    }

    if (isset($_POST['save_proxy_server_nonce']) && wp_verify_nonce($_POST['save_proxy_server_nonce'], 'save_proxy_server_list')) {
        $proxy_server_list = sanitize_textarea_field($_POST['proxy_server_list']);
        update_option('proxy_server_list', $proxy_server_list);
    }
}
