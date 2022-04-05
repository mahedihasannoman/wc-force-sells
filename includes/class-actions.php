<?php 

/**
 * Class WC_Force_Sells_Actions
 */
class WC_Force_Sells_Actions{

    /**
     * Constructor
     * 
     * @since 1.0.0
     * 
     * @return null
     */
    public function __construct() {
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_data_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'product_write_panel' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'process_extra_product_meta' ), 1, 2 );
        
        add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'show_force_sell_products' ) );
        add_action( 'woocommerce_add_to_cart', array( $this, 'add_force_sell_items_to_cart' ), 11, 6 );
        add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'update_force_sell_quantity_in_cart' ), 1, 2 );
        add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'update_force_sell_quantity_in_cart' ), 1, 2 );
        // Keep force sell data in the cart.
        add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
        add_filter( 'woocommerce_get_item_data', array( $this, 'get_linked_to_product_data' ), 10, 2 );
        add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'remove_orphan_force_sells' ) );
        add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'maybe_remove_duplicate_force_sells' ) );
        // Don't allow synced force sells to be removed or change quantity.
        add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'cart_item_remove_link' ), 10, 2 );
        add_filter( 'woocommerce_cart_item_quantity', array( $this, 'cart_item_quantity' ), 10, 2 );
        // Sync with remove/restore link in cart.
        add_action( 'woocommerce_cart_item_removed', array( $this, 'cart_item_removed' ), 30 );
        add_action( 'woocommerce_cart_item_restored', array( $this, 'cart_item_restored' ), 30 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
    }
    
    /**
	 * Enqueue admin related assets
	 *
	 * @param $hook
	 *
	 * @since 1.2.0
	 */
    public function admin_scripts( $hook ) {
        wp_enqueue_script( 'wc-force-sells-admin', BT_WC_FORCE_SELLS_ASSETS_URL . '/js/wc-force-sells-admin.js', [ 'jquery' ], BT_WC_FORCE_SELLS_VERSION, true );
    }

    /**
	 * product tab
     * 
	 * since 1.0.0
	 */
	public static function product_data_tab( $tabs ) {
		$tabs['wc_force_sells'] = array(
			'label'    => __( 'WC Force Sells', 'wc-force-sells' ),
			'target'   => 'wc_force_sells_data',
			'class'    => array( 'show_if_simple' ),
			'priority' => 11
		);

		return $tabs;
    }
    
    /**
     * Render Force Sells fields in WC Force Sells tab.
     * 
     * @since 1.0.0
     * 
     * @return null
     */
    public function product_write_panel() {
        global $post;
        wp_localize_script( 'wc-force-sells-admin', 'wc_force_sells_admin_i10n', array(
			'i18n'    => array(
				'search_product' => __( 'Search for a product&hellip;', 'wc-force-sells' ),
			),
			'nonce'   => wp_create_nonce( 'wc_force_sells_admin_js_nonce' ),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'excludeid' => intval( $post->ID ),
        ) );
        $existing = get_post_meta($post->ID, '_wcfs_meta', true);
        
        ?>
        
        <div id="wc_force_sells_data" class="panel woocommerce_options_panel show_if_simple" style="padding-bottom: 50px;display: none;">
            <table class="striped widefat wp-list-table">
                <thead>
                    <tr>
                        <th><?php echo __('Product', 'wc-force-sells'); ?></th>
                        <th><?php echo __('Removable', 'wc-force-sells'); ?></th>
                        <th><?php echo __('Sync Quantity', 'wc-force-sells'); ?></th>
                        <th><?php echo __('Quantity Increment', 'wc-force-sells'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="wcfs_rows">
                    <?php  if( ! empty( $existing ) ): ?>
                    <?php foreach( $existing as $key=>$singledata ): ?>
                        <tr>
                            <td>
                                <select id="wc_force_sell_ids" class="wc-product-search" style="width: 100% !important;" name="_wcfs_meta[<?php echo $key; ?>][product]" data-name="product" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'wc-force-sells' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
                                    <?php if( isset( $singledata['product'] ) && $singledata['product']!='' ): ?>
                                        <?php $product = wc_get_product( $singledata['product'] ); ?>
                                        <option value="<?php echo esc_attr( $singledata['product'] ); ?>" selected="selected"><?php echo wp_kses_post( $product->get_formatted_name() ); ?></option>
                                    <?php endif; ?>
                                </select>
                            </td>
                            <td>
                                <input type="checkbox" name="_wcfs_meta[<?php echo $key; ?>][removable]" data-name="removable" <?php echo (isset( $singledata['removable'] )?checked( $singledata['removable'], 1 ):'');  ?> value="1" />
                            </td>
                            <td>
                                <input type="checkbox" name="_wcfs_meta[<?php echo $key; ?>][sync_quantity]" data-name="sync_quantity" value="1" <?php echo (isset( $singledata['sync_quantity'] )?checked( $singledata['sync_quantity'], 1 ):'');  ?> />
                            </td>
                            <td>
                                <input type="number" name="_wcfs_meta[<?php echo $key; ?>][base_quantity]" data-name="base_quantity" value="<?php echo (isset( $singledata['base_quantity'] )?$singledata['base_quantity']:'1'); ?>" />
                            </td>
                            <td>
                                <input data-repeater-delete type="button" value="<?php echo __('Delete', 'wc-force-sells'); ?>" class="button wcfs_delete" />
                            </td>
                        </tr>

                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td>
                                <select id="wc_force_sell_ids" class="wc-product-search" style="width: 100% !important;" name="_wcfs_meta[0][product]" data-name="product" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'wc-force-sells' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
                                </select>
                            </td>
                            <td>
                                <input type="checkbox" name="_wcfs_meta[0][removable]" data-name="removable" value="1" />
                            </td>
                            <td>
                                <input type="checkbox" name="_wcfs_meta[0][sync_quantity]" data-name="sync_quantity" value="1" />
                            </td>
                            <td>
                                <input type="number" name="_wcfs_meta[0][base_quantity]" data-name="base_quantity" value="1" />
                            </td>
                            <td>
                                <input data-repeater-delete type="button" value="<?php echo __('Delete', 'wc-force-sells'); ?>" class="button wcfs_delete" />
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <br>
            <button type="button" class="button wcfs_add"><?php echo __('Add New', 'wc-force-sells'); ?></button>
        </div>
        <?php
    }


    /**
     * Save Force Sell Ids into post meta when product is saved.
     * 
     * @since 1.0.0
     *
     * @param int     $post_id Post ID.
     * 
     * @param WP_Post $post    Post object.
     */
    public function process_extra_product_meta( $post_id, $post ) {

        $final_array = array();
        foreach($_POST['_wcfs_meta'] as $wcfs){
            if( isset( $wcfs['product'] ) && (int)$wcfs['product'] > 0 ){
                $final_array[] = $wcfs;
            }
        }
        if( ! empty( $final_array ) ){
            update_post_meta( $post_id, '_wcfs_meta', $final_array );
        }else{
            delete_post_meta( $post_id, '_wcfs_meta' );
        }
        
    }

    /**
     * Displays information of what linked products that will get added when current
     * product is added to cart.
     * 
     * @since 1.0.0
     * 
     * @return null
     */
    public function show_force_sell_products() {
        global $post;
        $force_sells = $this->get_post_meta($post->ID, '_wcfs_meta', true);
        $product_ids = array();
        foreach( $force_sells as $force_sell ){
            if( isset($force_sell['product']) && (int)$force_sell['product'] > 0 ){
                $product_ids[] = $force_sell['product'];
            }
        }
        $titles      = array();
        // Make sure the products still exist and don't display duplicates.
        foreach ( array_values( array_unique( $product_ids ) ) as $key => $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product && $product->exists() && 'trash' !== $product->get_status() ) {
                $titles[] = version_compare( WC_VERSION, '3.0', '>=' ) ? $product->get_title() : get_the_title( $product_id );
            }
        }
        if ( ! empty( $titles ) ) {
            echo '<div class="clear"></div>';
            echo '<div class="wc-force-sells">';
            echo '<p>' . esc_html__( 'This will also add the following products to your cart:', 'wc-force-sells' ) . '</p>';
            echo '<ul>';
            foreach ( $titles as $title ) {
                echo '<li>' . esc_html( $title ) . '</li>';
            }
            echo '</ul></div>';
        }
    }

    public function get_post_meta($postid, $key="_wcfs_meta", $single = true){
        return get_post_meta($postid, $key, $single)?get_post_meta($postid, $key, $single):array();
    }

    /**
     * Add linked products when current product is added to the cart.
     * 
     * @since 1.0.0
     *
     * @param string $cart_item_key  Cart item key.
     * @param int    $product_id     Product ID.
     * @param int    $quantity       Quantity added to cart.
     * @param int    $variation_id   Producat varation ID.
     * @param array  $variation      Attribute values.
     * @param array  $cart_item_data Extra cart item data.
     *
     * @throws Exception Notice message when the forced item is out of stock and parent isn't added.
     */
    public function add_force_sell_items_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        
        // Check if this product is forced in itself, so it can't force in others (to prevent adding in loops).
        if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['forced_by'] ) ) {
            $forced_by_key = WC()->cart->cart_contents[ $cart_item_key ]['forced_by'];
            if ( isset( WC()->cart->cart_contents[ $forced_by_key ] ) ) {
                return;
            }
        }

        $product = wc_get_product( $product_id );
        
        $forec_sells = $this->get_post_meta($product_id);

        foreach( $forec_sells as $force_sell ){
            if( ! isset( $force_sell['product'] ) || (int)$force_sell['product'] < 1 ){
                continue;
            }
            $cart_id = WC()->cart->generate_cart_id( $force_sell['product'], '', '', array( 'forced_by' => $cart_item_key ) );
            $key     = WC()->cart->find_product_in_cart( $cart_id );
            if ( ! empty( $key ) ) {
                WC()->cart->set_quantity( $key, WC()->cart->cart_contents[ $key ]['quantity'] );
            } else {
                $args = array();
                if( ! isset( $force_sell['removable'] ) ){
                    $args['forced_by'] = $cart_item_key;
                }
                $args['base_quantity'] = (isset( $force_sell['base_quantity'] ) && (int)$force_sell['base_quantity'] > 0 ? (int)$force_sell['base_quantity'] : 1);
                if( isset( $force_sell['sync_quantity'] ) ){
                    $args['sync_quantity'] = $cart_item_key;
                }
                if( isset( $force_sell['base_quantity'] ) && (int)$force_sell['base_quantity'] > 0 ){
                    $quantity = (int)$force_sell['base_quantity'];
                }
                $params = apply_filters( 'wc_force_sell_add_to_cart_product', array( 'id' => $force_sell['product'], 'quantity' => $quantity, 'variation_id' => '', 'variation' => '' ), WC()->cart->cart_contents[ $cart_item_key ] );
                $result = WC()->cart->add_to_cart( $params['id'], $params['quantity'], $params['variation_id'], $params['variation'], $args );
                // If the forced sell product was not able to be added, don't add the main product either. "Can be filtered".
                if ( empty( $result ) && apply_filters( 'wc_force_sell_disallow_no_stock', true ) ) {
                    WC()->cart->remove_cart_item( $cart_item_key );
                    /* translators: %s: Product title */
                    throw new Exception( sprintf( __( '%s will also be removed as they\'re sold together.', 'wc-force-sells' ), $product->get_title() ) );
                }
            }
        }

    }

    /**
     * Check if a given force sells ID is for a valid product.
     * 
     * @since 1.0.0
     *
     * @param int $force_sell_id Force Sell ID.
     * 
     * @return bool Whether the product is valid or not.
     */
    private function force_sell_is_valid( $force_sell_id ) {
        $product = wc_get_product( $force_sell_id );
        if ( ! $product || ! $product->exists() || 'trash' === $product->get_status() ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Update the forced product's quantity in the cart when the product that forcing
     * it got qty updated.
     * 
     * @since 1.0.0
     *
     * @param string $cart_item_key Cart item key.
     * @param int    $quantity      Quantity.
     * 
     * @return null
     */
    public function update_force_sell_quantity_in_cart( $cart_item_key, $quantity = 0 ) {

        if ( ! empty( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
            if ( 0 === $quantity || 0 > $quantity ) {
                $quantity = 0;
            } else {
                $quantity = WC()->cart->cart_contents[ $cart_item_key ]['quantity'];
            }
            
            foreach ( WC()->cart->cart_contents as $key => $value ) {

                if( isset( $value['sync_quantity'] ) && $cart_item_key === $value['sync_quantity'] ){
                    $final_quantity = $quantity * $value['base_quantity'];
                    $final_quantity = apply_filters( 'wc_force_sell_update_quantity', $final_quantity, WC()->cart->cart_contents[ $key ] );
                    WC()->cart->set_quantity( $key, $final_quantity );
                }
                
            }

        }

    }

    /**
     * Get forced product added again to cart when item is loaded from session.
     * 
     * @since 1.0.0
     *
     * @param array $cart_item Item in cart.
     * @param array $values    Item values.
     *
     * @return array Cart item.
     */
    public function get_cart_item_from_session( $cart_item, $values ) {

        if ( isset( $values['forced_by'] ) ) {
            $cart_item['forced_by'] = $values['forced_by'];
        }
        return $cart_item;
    }

    /**
     * Making sure linked products from an item is displayed in cart.
     * 
     * @since 1.0.0
     *
     * @param array $data      Data.
     * @param array $cart_item Cart item.
     *
     * @return array
     */
    public function get_linked_to_product_data( $data, $cart_item ) {
        if ( isset( $cart_item['forced_by'] ) ) {
            $product_key = WC()->cart->find_product_in_cart( $cart_item['forced_by'] );
            if ( ! empty( $product_key ) ) {
                $product_name = WC()->cart->cart_contents[ $product_key ]['data']->get_title();
                $data[]       = array(
                    'name'    => __( 'Linked to', 'wc-force-sells' ),
                    'display' => $product_name,
                );
            }
        }

        return $data;
    }

    /**
     * Looks to see if a product with the key of 'forced_by' actually exists and
     * deletes it if not.
     * 
     * @since 1.0.0
     * 
     * @return null
     */
    public function remove_orphan_force_sells() {
        $cart_contents = WC()->cart->get_cart();
        foreach ( $cart_contents as $key => $value ) {
            if ( isset( $value['forced_by'] ) ) {
                if ( ! array_key_exists( $value['forced_by'], $cart_contents ) ) {
                    WC()->cart->remove_cart_item( $key );
                }
            }
        }
    }

    /**
     * Checks the cart contents to make sure we don't
     * have duplicated force sell products.
     *
     * @since 1.0.0
     * 
     * @return null
     */
    public function maybe_remove_duplicate_force_sells() {
        $cart_contents = WC()->cart->get_cart();
        $product_ids   = array();
        foreach ( $cart_contents as $key => $value ) {
            if ( isset( $value['forced_by'] ) ) {
                $product_ids[] = $value['product_id'];
            }
        }
        foreach ( WC()->cart->get_cart() as $key => $value ) {
            if ( ! isset( $value['forced_by'] ) && in_array( $value['product_id'], $product_ids, true ) ) {
                WC()->cart->remove_cart_item( $key );
            }
        }
    }

    /**
     * Remove link in cart item for Synced Force Sells products.
     * 
     * @since 1.0.0
     *
     * @param string $link          Remove link.
     * @param string $cart_item_key Cart item key.
     *
     * @return string Link.
     */
    public function cart_item_remove_link( $link, $cart_item_key ) {
        if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['forced_by'] ) ) {
            return '';
        }
        return $link;
    }

    /**
     * Makes quantity cart item for Synced Force Sells products uneditable.
     * 
     * @since 1.0.0
     *
     * @param string $quantity      Quantity input.
     * @param string $cart_item_key Cart item key.
     *
     * @return string Quantity input or static text of quantity.
     */
    public function cart_item_quantity( $quantity, $cart_item_key ) {
        if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['forced_by'] ) ) {
            return WC()->cart->cart_contents[ $cart_item_key ]['quantity'];
        }
        return $quantity;
    }

    /**
     * When an item gets removed from the cart, do the same for forced sells.
     * 
     * @since 1.0.0
     *
     * @param string $cart_item_key Cart item key.
     */
    public function cart_item_removed( $cart_item_key ) {
        foreach ( WC()->cart->get_cart() as $key => $value ) {
            if ( isset( $value['forced_by'] ) && $cart_item_key === $value['forced_by'] ) {
                WC()->cart->remove_cart_item( $key );
            }
        }
    }

    /**
     * When an item gets removed from the cart, do the same for forced sells.
     * 
     * @since 1.0.0
     *
     * @param string $cart_item_key Cart item key.
     */
    public function cart_item_restored( $cart_item_key ) {
        foreach ( WC()->cart->removed_cart_contents as $key => $value ) {
            if ( isset( $value['forced_by'] ) && $cart_item_key === $value['forced_by'] ) {
                WC()->cart->restore_cart_item( $key );
            }
        }
    }

}

new WC_Force_Sells_Actions();