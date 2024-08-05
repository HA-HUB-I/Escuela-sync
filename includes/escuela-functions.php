<?php
// Function to initialize the plugin
function escuela_store_sync_init() {
    // Initialization code can go here
}

// Function to handle activation
function escuela_store_sync_activate() {
    add_option('escuela_api_url', 'https://api.escuelajs.co/api/v1/products');
}

// Function to handle deactivation
function escuela_store_sync_deactivate() {
    delete_option('escuela_api_url');
}

add_action('admin_post_escuela_store_sync', 'escuela_store_sync_handle_submit');


// new
function escuela_store_sync_handle_submit() {
    // Verify nonce
    if (!isset($_POST['escuela_store_sync_nonce']) || !wp_verify_nonce($_POST['escuela_store_sync_nonce'], 'escuela_store_sync')) {
        wp_die(__('Nonce verification failed', 'escuela-store-sync'));
    }

    // Call the function to update products
    escuela_store_sync_update_products();

    // Redirect back to the settings page
    wp_redirect(admin_url('admin.php?page=escuela_store_sync'));
    exit;
}


function escuela_store_sync_update_products() {
    $response = wp_remote_get( 'https://api.escuelajs.co/api/v1/products');
    if (is_wp_error($response)) {
        // Handle error
        return;
    }

    $products = json_decode(wp_remote_retrieve_body($response));
    // $total_products = count($products);
    // $products_to_process = array_slice($products, $batch * $batch_size, $batch_size);

    foreach ($products as $product) {
        // Assume SKU is the product's ID from the Escuela API
        $sku = $product->id;

        // Check if a product with this SKU already exists
        $existing_product_id = get_page_by_title($product->title, OBJECT, 'product');
        if ($existing_product_id) {
            // Update existing product
            $product_data = array(
                'ID'           => $sku,
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
                // 'meta_input'   => array(
                //     '_sku' => $sku,
                // ),
            );
            $existing_product_id = wp_insert_post($product_data);
        }

        // Add/update product meta (price, category, image, etc.)
        update_post_meta($existing_product_id, '_price', $product->price);

        // Handle categories
        if (!empty($product->category)) {
            $category_id = escuela_store_sync_get_or_create_category($product->category->name);
            if ($category_id) {
                wp_set_post_terms($existing_product_id, array($category_id), 'product_cat');
            }
        }

        // Handle product image
        if (!empty($product->images)) {
            $image_id = escuela_store_sync_set_product_image($product->images[0], $existing_product_id);
            if ($image_id) {
                set_post_thumbnail($existing_product_id, $image_id);
            }
        }
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
