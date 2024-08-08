<?php
// Add menu item in the admin panel
function escuela_plugin_menu() {
    add_menu_page(
        'Escuela Синхронизация на Продукти',
        'Escuela Синхронизация',
        'manage_options',
        'escuela_store_sync',
        'escuela_plugin_settings_page'
    );
}
add_action('admin_menu', 'escuela_plugin_menu');

// Admin page content
function escuela_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>Escuela Синхронизация на Продукти</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('escuela_store_sync_settings');
            do_settings_sections('escuela_store_sync');
            submit_button();
            ?>
        </form>
        <p id="sync-status">Готови за синхронизация.</p>
        <form id="sync-form" method="post" action="">
            <?php wp_nonce_field('escuela_store_sync', 'escuela_store_sync_nonce'); ?>
            <p>
                <input type="button" id="start-sync" class="button-primary" value="Синхронизирай Продукти">
            </p>
        </form>
        <div id="progress-wrap" style="display: none;">
            <progress id="progress-bar" value="0" max="100"></progress>
            <p id="progress-text">Синхронизирани продукти: <span id="progress-count">0</span></p>
        </div>
    </div>
    <?php
}

add_action('admin_init', 'escuela_store_sync_settings_init');

function escuela_store_sync_settings_init() {
    add_settings_section(
        'escuela_store_sync_settings_section',
        'Настройки на Escuela Store Sync',
        'escuela_store_sync_settings_section_callback',
        'escuela_store_sync'
    );

    add_settings_field(
        'escuela_api_url',
        'API URL',
        'escuela_api_url_callback',
        'escuela_store_sync',
        'escuela_store_sync_settings_section'
    );
    register_setting('escuela_store_sync_settings', 'escuela_api_url');

    add_settings_field(
        'escuela_default_image',
        'Default Image URL <br>if not exist',
        'escuela_default_image_callback',
        'escuela_store_sync',
        'escuela_store_sync_settings_section'
    );
    register_setting('escuela_store_sync_settings', 'escuela_default_image');

    add_settings_field(
        'escuela_enable_default_image',
        'Enable Default Image',
        'escuela_enable_default_image_callback',
        'escuela_store_sync',
        'escuela_store_sync_settings_section'
    );
    register_setting('escuela_store_sync_settings', 'escuela_enable_default_image');
}

function escuela_store_sync_settings_section_callback() {
    echo 'Настройки за синхронизиране на продукти от Escuela API.';
}

function escuela_api_url_callback() {
    $escuela_api_url = get_option('escuela_api_url', '');
    echo "<input type='text' name='escuela_api_url' value='$escuela_api_url' class='regular-text' />";
}

function escuela_default_image_callback() {
    $escuela_default_image = get_option('escuela_default_image', '');
    echo "<input type='text' name='escuela_default_image' value='$escuela_default_image' class='regular-text' />";
}

function escuela_enable_default_image_callback() {
    $escuela_enable_default_image = get_option('escuela_enable_default_image', 'no');
    $checked = $escuela_enable_default_image === 'yes' ? 'checked' : '';
    echo "<input type='checkbox' name='escuela_enable_default_image' value='yes' $checked />";
}
?>
