<?php
/*
Plugin Name: IT Helps custom rabats
Description: Plugin for custom configurations rabats
Version: 1.0
Author: IT Helps
*/

add_action( 'woocommerce_before_calculate_totals', 'addFreeProducts' );




function addFreeProducts(){


  if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
        return;


  //delete free added products
  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    if(isset($cart_item['treat_as_fee'])){
      WC()->cart->remove_cart_item($cart_item['key']);
    }
  }
  //end delete free added products

  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        if(!isset($cart_item['treat_as_fee'])){
          $product = $cart_item['data'];
          $cartAmount = $cart_item['quantity'];

          //get product parent id if have if no use product id
          $parent_id = wp_get_post_parent_id($product->get_id());

          if (!$parent_id) {
            $parent_id = $product->get_id();
          }



          $rules = get_rabats_posts();
          foreach ($rules as $rule) {
              $free_products = (get_post_meta($rule->ID, 'free-products', true));
              $amount_to_free = (get_post_meta($rule->ID, 'amount-to-free', true));

              $free_products2 = (get_post_meta($rule->ID, 'free-products2', true));
              $amount_to_free2 = (get_post_meta($rule->ID, 'amount-to-free2', true));

              $free_products3 = (get_post_meta($rule->ID, 'free-products3', true));
              $amount_to_free3 = (get_post_meta($rule->ID, 'amount-to-free3', true));


              $product_ids = (get_post_meta($rule->ID, 'rabat_products', true));
              $product_ids = explode(',',$product_ids);





              if(in_array($parent_id,$product_ids)){
                $amount_to_free_final = 0;
                $free_products_final=0;

                if(isset($free_products)
                    && isset($amount_to_free)
                    && !empty($free_products)
                    && !empty($amount_to_free)
                    && $free_products!=0
                    && $amount_to_free!=0

                ){
                  if($cartAmount >$amount_to_free) {
                      $amount_to_free_final = $amount_to_free;
                      $free_products_final=$free_products;
                  }
                }

                if(isset($free_products2)
                    && isset($amount_to_free2)
                    && !empty($free_products2)
                    && !empty($amount_to_free2)
                    && $free_products2!=0
                    && $amount_to_free2!=0

                ){
                  if($cartAmount >$amount_to_free2) {
                      $amount_to_free_final = $amount_to_free2;
                      $free_products_final=$free_products2;
                  }
                }

                if(isset($free_products3)
                    && isset($amount_to_free3)
                    && !empty($free_products3)
                    && !empty($amount_to_free3)
                    && $free_products2!=0
                    && $amount_to_free2!=0

                ){
                  if($cartAmount >$amount_to_free3) {
                      $amount_to_free_final = $amount_to_free3;
                      $free_products_final=$free_products3;
                  }
                }

                if($cartAmount >= $amount_to_free_final){
                  $freeItem  = WC()->cart->add_to_cart($product->get_id(), $free_products_final, 0, array(), array('treat_as_fee' => true,'disable_qty' => true));
                }
              }

          }





        }
  }

  //change price of free added products
  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    if(isset($cart_item['treat_as_fee'])){
      $cart_item['data']->set_price( 0.01 );
      $cart_item['data']->set_name($cart_item['data']->get_title().' FREE');
      $cart_item['disable_qty'] = true;
    }
  }
  //end change price of free added products


}


function get_rabats_posts() {
  $args = array(
    'post_type' => 'rabat',
    'post_status' => 'publish',
    'posts_per_page' => -1
  );

  $rabats_posts = get_posts($args);

  return $rabats_posts;
}




// Register custom post type "Rabat"
function rabat_post_type() {
  $labels = array(
    'name' => 'Rabats',
    'singular_name' => 'Rabat',
    'add_new' => 'Add New',
    'add_new_item' => 'Add New Rabat',
    'edit_item' => 'Edit Rabat',
    'new_item' => 'New Rabat',
    'all_items' => 'All Rabats',
    'view_item' => 'View Rabat',
    'search_items' => 'Search Rabats',
    'not_found' => 'No Rabats found',
    'not_found_in_trash' => 'No Rabats found in Trash',
    'parent_item_colon' => '',
    'menu_name' => 'Rabats'
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'rabat' ),
    'capability_type' => 'post',
    'has_archive' => true,
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'author' )
  );

  register_post_type( 'rabat', $args );
}
add_action( 'init', 'rabat_post_type' );

// Add options "Free Products" and "Amount to Free" to Rabat custom post
function add_rabat_options() {
  add_meta_box( 'rabat_options', 'Rabat Options', 'rabat_options_callback', 'rabat', 'normal', 'high' );
}

function rabat_options_callback( $post ) {
  wp_nonce_field( basename( __FILE__ ), 'rabat_nonce' );
  $rabat_stored_meta = get_post_meta( $post->ID );

  $products = get_posts(array(
        'post_type' => 'product',
        'numberposts' => -1
    ));

  ?>


  <b>Level 1</b>
  <p>

    <input type="text" name="amount-to-free" id="amount-to-free" value="<?php if ( isset ( $rabat_stored_meta['amount-to-free'] ) ) echo $rabat_stored_meta['amount-to-free'][0]; ?>" />
    <label for="amount-to-free" class="rabat-row-title"><?php _e( 'Amount of products to add Free products', 'rabat' )?></label>
  </p>
  <p>

    <input type="text" name="free-products" id="free-products" value="<?php if ( isset ( $rabat_stored_meta['free-products'] ) ) echo $rabat_stored_meta['free-products'][0]; ?>" />
    <label for="free-products" class="rabat-row-title"><?php _e( 'Number of Free Products added', 'rabat' )?></label>
  </p>

  <b>Level 2</b>
  <p>

    <input type="text" name="amount-to-free2" id="amount-to-free2" value="<?php if ( isset ( $rabat_stored_meta['amount-to-free2'] ) ) echo $rabat_stored_meta['amount-to-free2'][0]; ?>" />
    <label for="amount-to-free2" class="rabat-row-title"><?php _e( 'Amount of products to add Free products', 'rabat' )?></label>
  </p>
  <p>

    <input type="text" name="free-products2" id="free-products2" value="<?php if ( isset ( $rabat_stored_meta['free-products2'] ) ) echo $rabat_stored_meta['free-products2'][0]; ?>" />
    <label for="free-products2" class="rabat-row-title"><?php _e( 'Number of Free Products added', 'rabat' )?></label>
  </p>

  <b>Level 3</b>
  <p>

    <input type="text" name="amount-to-free3" id="amount-to-free3" value="<?php if ( isset ( $rabat_stored_meta['amount-to-free3'] ) ) echo $rabat_stored_meta['amount-to-free3'][0]; ?>" />
    <label for="amount-to-free3" class="rabat-row-title"><?php _e( 'Amount of products to add Free products', 'rabat' )?></label>
  </p>
  <p>

    <input type="text" name="free-products3" id="free-products3" value="<?php if ( isset ( $rabat_stored_meta['free-products3'] ) ) echo $rabat_stored_meta['free-products3'][0]; ?>" />
    <label for="free-products3" class="rabat-row-title"><?php _e( 'Number of Free Products added', 'rabat' )?></label>
  </p>


  <p>
    <label for="rabat_products" class="rabat-row-title"><?php _e( 'Select products', 'rabat' )?></label><br>
    <?php
    if ( isset ( $rabat_stored_meta['rabat_products'][0] ) ){
      $productsArr = explode(',',$rabat_stored_meta['rabat_products'][0]);
    }
    else{
      $productsArr = '';
    }


    if (!empty($products)) {
        // Loop through all products
        foreach ($products as $product) {
            $checked = in_array($product->ID,$productsArr) ? 'checked': '';
            echo '<input type="checkbox" name="rabat_products[]" '.$checked.' value="' . $product->ID . '">' . $product->post_title . '<br>';
        }
    } else {
        // Output a message if there are no products
        echo 'No WooCommerce products found.';
    }

    ?>


  </p>
  <?php
}

add_action( 'add_meta_boxes', 'add_rabat_options' );

// Save options "Free Products" and "Amount to Free" to Rabat custom post
function save_rabat_options( $post_id ) {
  // Check if nonce is set
  if ( !isset( $_POST['rabat_nonce'] ) ) {
    return;
  }

  // Verify nonce
  if ( !wp_verify_nonce( $_POST['rabat_nonce'], basename( __FILE__ ) ) ) {
    return;
  }

  // Check if user has permission to save
  if ( !current_user_can( 'edit_post', $post_id ) ) {
    return;
  }

  // Save "Free Products" option
  if ( isset( $_POST['free-products'] ) ) {
    update_post_meta( $post_id, 'free-products', sanitize_text_field( $_POST['free-products'] ) );
  }

  // Save "Amount to Free" option
  if ( isset( $_POST['amount-to-free'] ) ) {
    update_post_meta( $post_id, 'amount-to-free', sanitize_text_field( $_POST['amount-to-free'] ) );
  }

  // Save "Free Products" option
  if ( isset( $_POST['free-products2'] ) ) {
    update_post_meta( $post_id, 'free-products2', sanitize_text_field( $_POST['free-products2'] ) );
  }

  // Save "Amount to Free" option
  if ( isset( $_POST['amount-to-free2'] ) ) {
    update_post_meta( $post_id, 'amount-to-free2', sanitize_text_field( $_POST['amount-to-free2'] ) );
  }

  // Save "Free Products" option
  if ( isset( $_POST['free-products3'] ) ) {
    update_post_meta( $post_id, 'free-products3', sanitize_text_field( $_POST['free-products3'] ) );
  }

  // Save "Amount to Free" option
  if ( isset( $_POST['amount-to-free3'] ) ) {
    update_post_meta( $post_id, 'amount-to-free3', sanitize_text_field( $_POST['amount-to-free3'] ) );
  }

  if ( isset( $_POST['rabat_products'] ) ) {
    update_post_meta( $post_id, 'rabat_products', sanitize_text_field( implode(',', $_POST['rabat_products']) ) );
  }
  else{
    update_post_meta( $post_id, 'rabat_products', sanitize_text_field( '' ) );
  }
}

add_action( 'save_post', 'save_rabat_options' );
