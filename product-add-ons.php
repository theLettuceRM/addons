<?php

/**
 * Plugin Name: Product Add Ons
 * Description: Display products as add ons
 * Version: 1.0
 * Author: TheLettuce
 * Author URI: https://skylerstyron.com/
 */

// -------------------- GET COMPRESSOR & BRACKET PRODUCTS --------------------
function get_compressor_products(){
	global $post;
    $domain = 'woocommerce';

    // Initialize array for product categories
    $current_cats = array();
    $cat_terms = get_the_terms( $post->ID, 'product_cat' );

	// Get main product category names
    foreach ( $cat_terms as $term ) {
        $product_cat_name = $term->name;
        // Assign category names to array
        $current_cats[] = $product_cat_name;
        // echo "<script>console.log('$product_cat_name')</script>";
    }
    
    // Show drop down if Current product has category 'Complete A/C Systems'
    if ( in_array( 'Complete A/C Systems', $current_cats  )) {

        // create array of category products
        $args = array(
            'category'      => 'compressor-kits', 
            'limit'         => -1,
        );
        
        // Retrieve products
        $comp_product_array = wc_get_products( $args );
        
        // Check if category products array is not empty 
        if ( ! empty( $comp_product_array ) ) {

            // Loop thru product array
            foreach ( $comp_product_array as $products ) {
                
                // Initialize array for product tags
                $tags_array =  array();
                $product_tags = wp_get_post_terms( $products->get_id(), 'product_tag', array( 'fields' => 'names' ) ); // Get array of Product tags 
                $tags_array = $product_tags; // Assign product tags to array    

                // echo "<script>console.log('" . json_encode($tags_array) . "');</script>";
                
                if ( array_intersect( $tags_array, $current_cats ) ) {
                    
                    $product_id = $products->get_id(); // Get product name
                    $product_price = " +$" . $products->get_regular_price(); // Get product price
                    $options[$product_id] = $products->get_name() . $product_price; // Display product as name + price
    
                } 
            }
                        
            // Add additional items to array 
            $options = array( 
                0 => __( 'Select an option...', 'woocommerce' ),
                1 => __( 'No Compressor & Bracket ', 'woocommerce') 
                ) + $options;
                
            // Add select field
            woocommerce_form_field( 'compressor-options', array(
                'type'          => 'select',
                'label'         => __( 'Add Compressor & Bracket', $domain ),
                'required'      => false,
                'options'       => $options,
            ),'' );
            echo '<p>Number of Compressor & Bracket options: ' . count( $options ) . '</p>';

            ?>
                <script type="text/javascript">
                    jQuery( function($) {
                    // On change
                        $( document ).on( 'change', '#compressor-options', function () {
                            var suffix = $( 'option:selected', this ).text();

                            // Append
                            $( '.my-product-sku .sku-suffix' ).text( suffix );
                        });
                    });
                </script>
            <?php
        }
    } else {
        echo 'Complete A/C System not found';
        
    }
}
add_action( 'woocommerce_before_add_to_cart_button', 'get_compressor_products' );

// -------------------- ADD COMPRESSOR & BRACKET PRODUCT SELECTION TO CART --------------------
function add_additional_product_to_cart() {
    if ( isset( $_POST['compressor-options'] ) ) {
        // Get product ID
        $the_product_id = sanitize_text_field( $_POST['compressor-options'] );
        
        // WC Cart
        if ( WC()->cart ) {
            // Get cart
            $cart = WC()->cart;
            
            // If cart is NOT empty
            if ( ! $cart->is_empty() ) {
                // Cart id
                $product_cart_id = $cart->generate_cart_id( $the_product_id );
                
                // Find product in cart
                $in_cart = $cart->find_product_in_cart( $product_cart_id );
                
                // If product NOT in cart
                if ( ! $in_cart ) {
                    $cart->add_to_cart( $the_product_id );
                }
            } else {
                $cart->add_to_cart( $the_product_id );
            }
        }
    }
}
add_action( 'woocommerce_add_to_cart', 'add_additional_product_to_cart', 10, 6 );

function change_current_sku() {
    global $product;
    
    // Is a WC product
    if ( is_a( $product, 'WC_Product' ) ) {
        echo '<div><br></div>';
        echo '<p class="my-product-sku">' . $product->get_sku() . ': ' . '<span class="sku-suffix"></p>';
    }
}
add_action( 'woocommerce_after_add_to_cart_button', 'change_current_sku' );


// -------------------- DISPLAY SKU ON CART AND CHECKOUT PAGES --------------------

add_filter( 'woocommerce_cart_item_name', 'display_sku_after_item_name', 5, 3 );
function display_sku_after_item_name( $item_name, $cart_item, $cart_item_key ) {
    $product = $cart_item['data']; // The WC_Product Object
    
    if( is_cart() && $product->get_sku() ) {
        $sku_name = $product->get_sku();
        $item_name .= "<p>SKU: $sku_name</p>";
    }
    return $item_name;
}

// -------------------- DISPLAY SKU BELOW CART ITEM NAME IN CHECKOUT --------------------

add_filter( 'woocommerce_checkout_cart_item_quantity', 'display_sku_after_item_qty', 5, 3 );  
function display_sku_after_item_qty( $item_quantity, $cart_item, $cart_item_key ) {
    $product = $cart_item['data']; // The WC_Product Object
    
    if( $product->get_sku() ) {
        $sku_name = $product->get_sku();
        $item_quantity .= "<p>SKU: $sku_name</p>";
    }
    return $item_quantity;
}