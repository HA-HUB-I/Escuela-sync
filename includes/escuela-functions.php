<?php
// Function to initialize the plugin
function escuela_store_sync_init() {
    // Initialization code can go here
}

// Function to handle activation
function escuela_store_sync_activate() {
    add_option('escuela_api_url', 'https://api.escuelajs.co/api/v1/products');
    add_option('escuela_default_image', ''); // Default image path option
    add_option('escuela_enable_default_image', 'no'); // Option to enable/disable default image
}

// Function to handle deactivation
function escuela_store_sync_deactivate() {
    delete_option('escuela_api_url');
    delete_option('escuela_default_image');
    delete_option('escuela_enable_default_image');
}

// Function to enqueue scripts
function escuela_store_sync_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('escuela-store-sync', plugin_dir_url(__FILE__) . 'escuela-store-sync.js', array('jquery'), '1.0', true);
    wp_localize_script('escuela-store-sync', 'escuelaStoreSync', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('escuela_store_sync_nonce')
    ));
}

// AJAX handler for batch processing
function escuela_store_sync_ajax_handler() {
    check_ajax_referer('escuela_store_sync_nonce', 'nonce');

    // Get the current batch from the request
    $batch = isset($_POST['batch']) ? intval($_POST['batch']) : 0;
    $batch_size = 5;

    // Get API URL from settings
    $api_url = get_option('escuela_api_url', 'https://api.escuelajs.co/api/v1/products');
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Error fetching products from API.'));
        return;
    }

    $products = json_decode(wp_remote_retrieve_body($response));
    $total_products = count($products);
    $products_to_process = array_slice($products, $batch * $batch_size, $batch_size);

    foreach ($products_to_process as $product) {
        // Assume SKU is the product's ID from the Escuela API
        $sku = $product->id;

        // Check if a product with this SKU already exists
        $existing_product_id = wc_get_product_id_by_sku($sku);

        if ($existing_product_id) {
            // Update existing product
            $product_data = array(
                'ID'           => $existing_product_id,
                'post_content' => $product->description,
            );
            wp_update_post($product_data);
        } else {
            // Create new product
            $product_data = array(
                'post_title'   => $product->title,
                'post_content' => $product->description,
                'post_status'  => 'publish',
                'post_type'    => 'product',
                'meta_input'   => array(
                    '_sku' => $sku,
                ),
            );
            $existing_product_id = wp_insert_post($product_data);
        }

        // Add/update product meta (price, category, image, etc.)
        update_post_meta($existing_product_id, '_price', $product->price);

        // Handle categories
        if (!empty($product->category->name)) {
            $category_id = escuela_store_sync_get_or_create_category($product->category->name);
            if ($category_id) {
                wp_set_post_terms($existing_product_id, array($category_id), 'product_cat');
            }
        }

        // Handle product image
        $image_url = !empty($product->images) ? $product->images[0] : '';
        $image_id = escuela_store_sync_set_product_image($image_url, $existing_product_id);

        if ($image_id) {
            set_post_thumbnail($existing_product_id, $image_id);
        } else {
            // Set default image if no product image found or image URL returns 404
            if (get_option('escuela_enable_default_image') === 'yes') {
                $default_image_url = get_option('escuela_default_image');
                if ($default_image_url) {
                    $image_id = escuela_store_sync_set_product_image($default_image_url, $existing_product_id);
                    if ($image_id) {
                        set_post_thumbnail($existing_product_id, $image_id);
                    }
                }
            }
        }
    }

    // Check if there are more products to process
    $next_batch = $batch + 1;
    if ($next_batch * $batch_size < $total_products) {
        wp_send_json_success(array(
            'message' => 'Batch processed successfully.',
            'next_batch' => $next_batch,
            'total_products' => $total_products
        ));
    } else {
        wp_send_json_success(array(
            'message' => 'All products synchronized successfully.',
            'next_batch' => -1,
            'total_products' => $total_products
        ));
    }
}

// Function to get or create category
function escuela_store_sync_get_or_create_category($category_name) {
    $term = get_term_by('name', $category_name, 'product_cat');
    if ($term) {
        return $term->term_id;
    } else {
        $new_term = wp_insert_term($category_name, 'product_cat');
        if (!is_wp_error($new_term)) {
            return $new_term['term_id'];
        }
    }
    return null;
}

// Function to set product image
function escuela_store_sync_set_product_image($image_url, $product_id) {
    if (empty($image_url)) {
        return false;
    }

    // Check if the image URL returns a 404 status
    $response = wp_remote_head($image_url);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) == 404) {
        return false;
    }

    // Check if the image size is 0 KB
    $image_size = wp_remote_retrieve_header($response, 'content-length');
    if ($image_size == 0) {
        return false;
    }

    // Download the image
    $image = wp_remote_get($image_url);

    if (is_wp_error($image)) {
        return false;
    }

    // Upload the image to the media library
    $upload = wp_upload_bits(basename($image_url), null, wp_remote_retrieve_body($image));

    if ($upload['error']) {
        return false;
    }

    // Check the type of the uploaded file
    $wp_filetype = wp_check_filetype($upload['file'], null);

    // Prepare an array of post data for the attachment
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name(basename($upload['file'])),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );

    // Insert the attachment
    $attach_id = wp_insert_attachment($attachment, $upload['file'], $product_id);

    // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Generate the metadata for the attachment, and update the database record
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}
?>
