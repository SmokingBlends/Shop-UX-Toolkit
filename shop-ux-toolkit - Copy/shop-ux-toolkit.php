<?php
/*
Plugin Name: Shop UX Toolkit
Description: CSS-only tweaks: proper link styling, subtle hover on cards, and keyboard-only focus rings.
Version: 0.2.2
Author: Smoking Blends
Author URI: https://www.smokingblends.com/
Text Domain: shop-ux-toolkit
Requires at least: 6.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if (!defined('ABSPATH')) exit;
define('GPT_SB_SUX_VER', '0.2.2');
define('GPT_SB_SUX_PLUGIN_FILE', __FILE__);
/** ==============================
 * Options (defaults + helpers)
 * ============================== */
const GPT_SB_SUX_DEFAULTS = [
  'load_links_css'       => true,
  'load_lift_css'        => true,
  'keyboard_only_focus' => false,
  'blog_excerpts_enabled' => false,
  'enable_facebook_cart' => true,
  'enable_reviews' => true,
  'reviews' => [],
  'enable_turnstile' => false,
  'reviews_page_slug' => 'reviews',
];
function gpt_sb_sux_opts() {
  $saved = get_option('gpt_sb_sux_options');
  if (!is_array($saved)) $saved = [];
  return array_merge(GPT_SB_SUX_DEFAULTS, $saved);
}
function gpt_sb_sux_opt($key) {
  $o = gpt_sb_sux_opts();
  return isset($o[$key]) ? $o[$key] : (GPT_SB_SUX_DEFAULTS[$key] ?? null);
}
// Include the blog adjustments file (now after options are defined)
require_once plugin_dir_path(__FILE__) . 'includes/blog-adjustments.php';
// Include the settings file
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
// Include the reviews file conditionally
if (gpt_sb_sux_opt('enable_reviews')) {
  require_once plugin_dir_path(__FILE__) . 'includes/reviews.php';
}
// Include the category order file
require_once plugin_dir_path(__FILE__) . 'includes/category-order.php';
// Include the checkout-facebook file conditionally
if (gpt_sb_sux_opt('enable_facebook_cart')) {
  require_once plugin_dir_path(__FILE__) . 'includes/checkout-facebook.php';
}
/** ======================================
 * STOP empty paginated shop pages
 * ====================================== */
add_action( 'template_redirect', function() {
  if ( is_shop() && get_query_var( 'paged' ) > 1 && get_option( 'woocommerce_shop_page_display' ) === 'subcategories' ) {
      global $wp_query;
      $wp_query->set_404();
      status_header( 404 );
      nocache_headers();
  }
} );
/** ======================================
 * STOP empty paginated shop pages END
 * ====================================== */
// Enqueue WooCommerce styles and scripts (consider moving this add_action to your main plugin file for better organization)
add_action('wp_enqueue_scripts', 'gpt_sb_sux_enqueue_wc_styles_for_review_shortcode');
function gpt_sb_sux_enqueue_wc_styles_for_review_shortcode() {
    global $post;
    if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'submit_review_form') || has_shortcode($post->post_content, 'display_all_reviews'))) {
        wp_enqueue_style('woocommerce-layout');
        wp_enqueue_style('woocommerce-smallscreen');
        wp_enqueue_style('woocommerce-general');
        wp_enqueue_script('jquery'); // Ensure jQuery is loaded
        wp_enqueue_script('wc-single-product'); // Includes JS for star rating handling
      //  wp_enqueue_script('wc-add-to-cart');
    }
}
// Enqueue WooCommerce styles and scripts (consider moving this add_action to your main plugin file for better organization) END
/** ======================================
 * Enqueue CSS (each toggle loads a file)
 * ====================================== */
add_action('wp_enqueue_scripts', function () {
  $deps = [];
  if (wp_style_is('storefront-style', 'registered')) $deps[] = 'storefront-style';
  if (wp_style_is('wc-blocks-style',  'registered')) $deps[] = 'wc-blocks-style';
  // 1) Intro paragraph links
  if (gpt_sb_sux_opt('load_links_css')) {
    $rel  = 'assets/css/sb-links.css';
    $file = plugin_dir_path(__FILE__) . $rel;
    $url  = plugins_url($rel, __FILE__);
    $ver  = file_exists($file) ? (string) filemtime($file) : GPT_SB_SUX_VER;
    wp_enqueue_style('gpt-sb-links', $url, $deps, $ver);
  }
  // 2) Hover highlight on product & category boxes
  if (gpt_sb_sux_opt('load_lift_css')) {
    $rel  = 'assets/css/sb-lift.css';
    $file = plugin_dir_path(__FILE__) . $rel;
    $url  = plugins_url($rel, __FILE__);
    $ver  = file_exists($file) ? (string) filemtime($file) : GPT_SB_SUX_VER;
    wp_enqueue_style('gpt-sb-lift', $url, $deps, $ver);
  }
  // 3) Keyboard-only focus rings (CSS only)
  if (gpt_sb_sux_opt('keyboard_only_focus')) {
    $rel  = 'assets/css/sb-focus.css';
    $file = plugin_dir_path(__FILE__) . $rel;
    $url  = plugins_url($rel, __FILE__);
    $ver  = file_exists($file) ? (string) filemtime($file) : GPT_SB_SUX_VER;
    wp_enqueue_style('gpt-sb-focus', $url, [], $ver);
  }
  // 4) Blog excerpts "Read More" button (new feature)
  if (gpt_sb_sux_opt('blog_excerpts_enabled') && (is_home() || is_archive())) {
    $rel  = 'assets/css/sb-snippet-articles.css';
    $file = plugin_dir_path(__FILE__) . $rel;
    $url  = plugins_url($rel, __FILE__);
    $ver  = file_exists($file) ? (string) filemtime($file) : GPT_SB_SUX_VER;
    wp_enqueue_style('gpt-sb-blog-excerpts', $url, $deps, $ver);
  }
  // 5) Reviews-specific styles (conditionally if enabled)
  if (gpt_sb_sux_opt('enable_reviews')) {
    $rel  = 'assets/css/sb-reviews.css';
    $file = plugin_dir_path(__FILE__) . $rel;
    if (file_exists($file)) {
      $url  = plugins_url($rel, __FILE__);
      $ver  = (string) filemtime($file);
      wp_enqueue_style('gpt-sb-reviews', $url, $deps, $ver);
    }
  }
}, 999);
// Create secure-checkout page on activation if not exists
register_activation_hook(__FILE__, 'gpt_sb_sux_create_secure_checkout_page');
function gpt_sb_sux_create_secure_checkout_page() {
  if (!get_page_by_path('secure-checkout')) {
    $page = [
      'post_title'   => 'Secure Checkout',
      'post_name'    => 'secure-checkout',
      'post_status'  => 'publish',
      'post_type'    => 'page',
      'post_content' => '',
    ];
    wp_insert_post($page);
  }
}
// Create reviews page on activation if not exists
register_activation_hook(__FILE__, 'gpt_sb_sux_create_reviews_page');
function gpt_sb_sux_create_reviews_page() {
  $slug = gpt_sb_sux_opt('reviews_page_slug');
  if ($slug && !get_page_by_path($slug)) {
    $page = [
      'post_title'   => 'Reviews',
      'post_name'    => $slug,
      'post_status'  => 'publish',
      'post_type'    => 'page',
      'post_content' => '[display_all_reviews][submit_review_form]',
    ];
    wp_insert_post($page);
  }
}
// Enqueue admin CSS on settings page
add_action('admin_enqueue_scripts', 'gpt_sb_sux_admin_scripts');
function gpt_sb_sux_admin_scripts($hook) {
  if ($hook !== 'settings_page_gpt_sb_sux') return;
  wp_enqueue_style('gpt-sb-admin', plugins_url('assets/css/admin.css', __FILE__), [], GPT_SB_SUX_VER);
}