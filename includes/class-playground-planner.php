<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Playground_Planner {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_shortcode( 'playground_planner', array( $this, 'shortcode' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    public function register_assets() {
        wp_register_style(
            'pp-planner',
            PP_URL . 'assets/css/planner.css',
            array(), PP_VERSION
        );
        wp_register_script(
            'pp-planner',
            PP_URL . 'assets/js/planner.js',
            array(), PP_VERSION, true
        );
    }

    public function shortcode( $atts = array() ) {
        wp_enqueue_style( 'pp-planner' );
        wp_enqueue_script( 'pp-planner' );
        wp_localize_script( 'pp-planner', 'PPPlanner', array(
            'restUrl' => esc_url_raw( rest_url( 'playground-planner/v1/' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ) );

        ob_start(); ?>
        <div class="pp-planner" data-pp-planner>
            <div class="pp-header">
                <div>
                    <span class="pp-eyebrow">PLAYGROUND DESIGNER</span>
                    <h2>Design your playground</h2>
                    <p>Explore products or add your space details for a more relevant concept.</p>
                </div>
                <button class="pp-button pp-button-secondary" type="button" data-pp-reset>Reset</button>
            </div>

            <div class="pp-notice">
                <strong>Planning tool — rough guide only.</strong>
                This tool helps you explore ideas and understand how products may work within your available space. It is not a final design, safety assessment or installation recommendation. Requirements should be confirmed with our design team.
            </div>

            <div class="pp-start-grid" data-pp-start>
                <button class="pp-start-card" type="button" data-pp-mode="plan">
                    <span>PLAN MY PLAYGROUND</span>
                    <small>Add optional space and setting information.</small>
                </button>
                <button class="pp-start-card" type="button" data-pp-mode="browse">
                    <span>BROWSE ALL PRODUCTS</span>
                    <small>Explore the existing WordPress product catalogue.</small>
                </button>
            </div>

            <div class="pp-planner-controls" data-pp-controls hidden>
                <div class="pp-field">
                    <label for="pp-width">Site width (m) <span>optional</span></label>
                    <input id="pp-width" type="number" min="0" step="0.1" data-pp-width placeholder="e.g. 20">
                </div>
                <div class="pp-field">
                    <label for="pp-length">Site length (m) <span>optional</span></label>
                    <input id="pp-length" type="number" min="0" step="0.1" data-pp-length placeholder="e.g. 30">
                </div>
                <div class="pp-field">
                    <label for="pp-setting">Setting</label>
                    <select id="pp-setting" data-pp-setting>
                        <option value="">Any setting</option>
                        <option value="EYFS">EYFS</option>
                        <option value="Primary">Primary</option>
                        <option value="SEN">SEN / Inclusive</option>
                        <option value="Public">Public</option>
                        <option value="Leisure">Leisure</option>
                    </select>
                </div>
                <div class="pp-field">
                    <label for="pp-age">Age range</label>
                    <select id="pp-age" data-pp-age>
                        <option value="">Any age</option>
                        <option value="2-4">2–4</option>
                        <option value="3-5">3–5</option>
                        <option value="5-8">5–8</option>
                        <option value="8-12">8–12</option>
                        <option value="Mixed">Mixed</option>
                    </select>
                </div>
                <div class="pp-field pp-field-wide">
                    <label>Site photo <span>optional</span></label>
                    <input type="file" accept="image/*" data-pp-photo>
                </div>
                <button class="pp-button" type="button" data-pp-load>Start designing</button>
            </div>

            <div class="pp-workspace" data-pp-workspace hidden>
                <aside class="pp-sidebar">
                    <div class="pp-sidebar-title">Products</div>
                    <div class="pp-filters">
                        <input type="search" placeholder="Search products…" data-pp-search>
                        <select data-pp-category><option value="">All categories</option></select>
                    </div>
                    <div class="pp-product-list" data-pp-products></div>
                </aside>

                <main class="pp-canvas-wrap">
                    <div class="pp-canvas" data-pp-canvas>
                        <div class="pp-canvas-empty">
                            <strong>Your playground concept</strong>
                            <span>Add products from the left.</span>
                        </div>
                    </div>
                    <div class="pp-selected" data-pp-selected hidden></div>
                </main>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function register_rest_routes() {
        register_rest_route( 'playground-planner/v1', '/products', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array( $this, 'products' ),
            'permission_callback' => '__return_true',
        ) );
    }

    private function product_post_types() {
        $types = get_option( 'pp_product_post_types', array( 'product' ) );
        return apply_filters( 'pp_product_post_types', $types );
    }

    private function meta( $post_id, $keys, $default = '' ) {
        foreach ( (array) $keys as $key ) {
            $value = get_post_meta( $post_id, $key, true );
            if ( '' !== $value && null !== $value ) return $value;
        }
        return $default;
    }

    public function products() {
        $posts = get_posts( array(
            'post_type'      => $this->product_post_types(),
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $data = array();
        foreach ( $posts as $post ) {
            $thumb = get_the_post_thumbnail_url( $post, 'large' );
            $data[] = array(
                'id'              => $post->ID,
                'name'            => get_the_title( $post ),
                'url'             => get_permalink( $post ),
                'image'           => $thumb ?: '',
                'category'        => $this->meta( $post->ID, array( 'planner_category', 'category' ) ),
                'subcategory'     => $this->meta( $post->ID, array( 'planner_subcategory', 'subcategory' ) ),
                'age_from'        => $this->meta( $post->ID, array( 'age_from', 'planner_age_from' ) ),
                'age_to'          => $this->meta( $post->ID, array( 'age_to', 'planner_age_to' ) ),
                'eyfs'            => $this->meta( $post->ID, array( 'suitable_for_eyfs', 'planner_eyfs' ) ),
                'sen'             => $this->meta( $post->ID, array( 'suitable_for_sen', 'planner_sen' ) ),
                'setting'         => $this->meta( $post->ID, array( 'suitable_settings', 'planner_settings' ) ),
                'width'           => $this->meta( $post->ID, array( 'product_width', 'width' ) ),
                'length'          => $this->meta( $post->ID, array( 'product_length', 'length' ) ),
                'height'          => $this->meta( $post->ID, array( 'product_height', 'height' ) ),
                'fall_height'     => $this->meta( $post->ID, array( 'critical_fall_height', 'critical_all_height', 'fall_height' ) ),
                'planning_width'  => $this->meta( $post->ID, array( 'planning_width', 'use_zone_width' ) ),
                'planning_length' => $this->meta( $post->ID, array( 'planning_length', 'use_zone_length' ) ),
                'surfacing'       => $this->meta( $post->ID, array( 'surfacing_required', 'planner_surfacing' ) ),
                'model'           => $this->meta( $post->ID, array( '3d_model', 'model_3d', 'glb_url', 'gltf_url' ) ),
                'features'        => $this->meta( $post->ID, array( 'play_value_features', 'planner_features' ) ),
                'recommended'     => $this->meta( $post->ID, array( 'recommended_with', 'planner_recommended_with' ) ),
            );
        }
        return rest_ensure_response( $data );
    }

    public function admin_menu() {
        add_options_page(
            'Playground Planner',
            'Playground Planner',
            'manage_options',
            'playground-planner',
            array( $this, 'settings_page' )
        );
    }

    public function settings_page() {
        if ( isset( $_POST['pp_save'] ) && check_admin_referer( 'pp_settings' ) ) {
            $raw = isset( $_POST['pp_post_types'] ) ? sanitize_text_field( wp_unslash( $_POST['pp_post_types'] ) ) : 'product';
            $types = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
            update_option( 'pp_product_post_types', $types ?: array( 'product' ) );
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        $types = get_option( 'pp_product_post_types', array( 'product' ) );
        ?>
        <div class="wrap">
            <h1>Playground Planner</h1>
            <p>This starter plugin reads existing WordPress product posts. We will map your actual product fields once your spreadsheet is completed.</p>
            <form method="post">
                <?php wp_nonce_field( 'pp_settings' ); ?>
                <table class="form-table"><tr>
                    <th scope="row"><label for="pp_post_types">Product post types</label></th>
                    <td><input class="regular-text" id="pp_post_types" name="pp_post_types" value="<?php echo esc_attr( implode( ',', $types ) ); ?>">
                    <p class="description">Comma-separated. Defaults to <code>product</code> for WooCommerce.</p></td>
                </tr></table>
                <p><button class="button button-primary" name="pp_save" value="1">Save settings</button></p>
            </form>
            <h2>Shortcode</h2>
            <p>Put <code>[playground_planner]</code> on a page to display the planner.</p>
        </div>
        <?php
    }
}
