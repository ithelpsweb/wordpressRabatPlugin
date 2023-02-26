<?php
/*
Plugin Name: IT Helps Custom rabats
Description: Plugin for custom rabats, 10+1, 20+2 free product
Version: 1.0
Author: Janypka
Author URI: https://www.janypka.com
*/


function my_woocommerce_price_increase( $cart_object ) {
    //change price of free added products
    foreach ( $cart_object->cart_contents as $cart_item_key => $cart_item ) {
      if(isset($cart_item['treat_as_fee'])){
        $price = $cart_item['data']->get_price();
        $new_price = 0.01;
        $cart_item['data']->set_price( $new_price );
      }

    }
}

add_action( 'woocommerce_before_calculate_totals', 'my_woocommerce_price_increase', 99, 1 );

//add_action( 'woocommerce_calculate_totals', 'addFreeProducts',10,1 );

function addFreeProducts($cart_object){

	$max_levels = 20;

  if ( is_admin() && ! defined( 'DOING_AJAX' ) )
    return;

  if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
    return;


  //delete free added products
  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    if(isset($cart_item['treat_as_fee'])){
      WC()->cart->remove_cart_item($cart_item['key']);
    }
  }
  //end delete free added products


  foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

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

              $product_ids = (get_post_meta($rule->ID, 'rabat_products', true));
              $product_ids = explode(',',$product_ids);

              if(in_array($parent_id,$product_ids)){
                $amount_to_free_final = 0;
                $free_products_final=0;

								foreach (range(0, $max_levels) as $i){
									$free_products = (get_post_meta($rule->ID, 'free-products'.$i, true));
		              $amount_to_free = (get_post_meta($rule->ID, 'amount-to-free'.$i, true));

	                if(isset($free_products)
	                    && isset($amount_to_free)
	                    && !empty($free_products)
	                    && !empty($amount_to_free)
	                    && $free_products!=0
	                    && $amount_to_free!=0

	                ){
	                  if($cartAmount >= $amount_to_free) {
	                      $amount_to_free_final = $amount_to_free;
	                      $free_products_final=$free_products;
	                  }
	                }
								}


                if($cartAmount >= $amount_to_free_final){
                  $freeItem  = WC()->cart->add_to_cart($product->get_id(), $free_products_final, 0, array(), array('treat_as_fee' => true,'disable_qty' => true));
                }
              }
          }

        }
  }

  WC()->cart->calculate_totals();

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
	$max_levels = 20;
  wp_nonce_field( basename( __FILE__ ), 'rabat_nonce' );
  $rabat_stored_meta = get_post_meta( $post->ID );

  $products = get_posts(array(
        'post_type' => 'product',
        'numberposts' => -1
    ));

  $rules = get_rabats_posts();
  $allAlreadySelectedProductsIDs = [];
  $productIdRuleName = [];
  foreach ($rules as $rule) {
    if($rule->ID!=$post->ID){
      $product_ids = (get_post_meta($rule->ID, 'rabat_products', true));
      $product_ids = explode(',',$product_ids);
      foreach ($product_ids as $prid) {
        $allAlreadySelectedProductsIDs[]= $prid;
        $productIdRuleName[$prid] = get_the_title( $rule->ID );

      }
    }

  }

  ?>

	<?php foreach (range(0, $max_levels) as $i): ?>
		<b>Level <?=$i?></b>
		<p>

			<input type="text" name="amount-to-free<?=$i?>" id="amount-to-free<?=$i?>" value="<?php if ( isset ( $rabat_stored_meta['amount-to-free'.$i] ) ) echo $rabat_stored_meta['amount-to-free'.$i][0]; ?>" />
			<label for="amount-to-free<?=$i?>" class="rabat-row-title"><?php _e( 'Amount of products to add Free products', 'ith-custom-rabats' )?></label>
		</p>
		<p>
			<input type="text" name="free-products<?=$i?>" id="free-products<?=$i?>" value="<?php if ( isset ( $rabat_stored_meta['free-products'.$i] ) ) echo $rabat_stored_meta['free-products'.$i][0]; ?>" />
			<label for="free-products<?=$i?>" class="rabat-row-title"><?php _e( 'Number of Free Products added', 'ith-custom-rabats' )?></label>
		</p>

	<?php endforeach; ?>



  <b><?php _e( 'Select products', 'ith-custom-rabats' )?></b>
  <p>
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
            $sufix = '';
            $state = in_array($product->ID,$productsArr) ? 'checked': '';
            if(in_array($product->ID,$allAlreadySelectedProductsIDs)){
              $state .= 'disabled';
              if(isset($productIdRuleName[$product->ID])){
                $sufix = '<span style="font-size:12px"> Used in "'.$productIdRuleName[$product->ID].'"</span>';
              }
            }
            echo '<input type="checkbox" name="rabat_products[]" '.$state.' value="' . $product->ID . '">' .'<b>'. $product->post_title.'</b>' .$sufix. '<br>';
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
	$max_levels = 20;
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

	foreach (range(0, $max_levels) as $i){
	  // Save "Free Products" option

	  if ( isset( $_POST['free-products'.$i] ) ) {
	    update_post_meta( $post_id, 'free-products'.$i, sanitize_text_field( $_POST['free-products'.$i] ) );
	  }

	  // Save "Amount to Free" option
	  if ( isset( $_POST['amount-to-free'.$i] ) ) {
	    update_post_meta( $post_id, 'amount-to-free'.$i, sanitize_text_field( $_POST['amount-to-free'.$i] ) );
	  }
	}


  if ( isset( $_POST['rabat_products'] ) ) {
    update_post_meta( $post_id, 'rabat_products', sanitize_text_field( implode(',', $_POST['rabat_products']) ) );
  }
  else{
    update_post_meta( $post_id, 'rabat_products', sanitize_text_field( '' ) );
  }
}

add_action( 'save_post', 'save_rabat_options' );
