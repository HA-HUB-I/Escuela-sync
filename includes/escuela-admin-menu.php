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
        <h1>Синхронизация на Продукти</h1>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('escuela_store_sync', 'escuela_store_sync_nonce'); ?>
            <input type="hidden" name="action" value="escuela_store_sync">
            <p>
                <input type="submit" name="submit" class="button-primary" value="Синхронизирай Продукти">
            </p>
        </form>
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
        'URL на Escuela Store API',
        'escuela_api_url_callback',
        'escuela_store_sync',
        'escuela_store_sync_settings_section'
    );

//     register_setting('escuela_store_sync_settings', 'escuela_api_url');
 }

// function escuela_store_sync_settings_section_callback() {
//     echo '<p>Настройки за синхронизация на Escuela Store Sync.</p>';
// }

// function escuela_api_url_callback() {
//     $api_url = get_option('escuela_api_url');
//     echo '<input type="text" name="escuela_api_url" value="' . esc_attr($api_url) . '" class="regular-text">';
// }
?>
