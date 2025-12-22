<?php
/**
 * Settings for Shop UX Toolkit
 */

if (!defined('ABSPATH')) exit;

/** ===============================
 * Settings page (hidden from Settings menu; Plugins row link opens it)
 * =============================== */
add_action('admin_menu', function () {
  add_options_page(
    __('Shop UX Toolkit', 'shop-ux-toolkit'),
    __('Shop UX Toolkit', 'shop-ux-toolkit'),
    'manage_options',
    'gpt_sb_sux',
    'gpt_sb_sux_render_settings_page'
  );
});

// Hide from Settings menu; keep URL working for Plugins-row link
add_action('admin_menu', function () {
  remove_submenu_page('options-general.php', 'gpt_sb_sux');
}, 99);

/** ===============================
 * Save handler (manual form)
 * =============================== */
add_action('admin_post_gpt_sb_sux_save', function () {
  if (!current_user_can('manage_options')) {
    wp_die(
      esc_html__('Unauthorized', 'shop-ux-toolkit'),
      esc_html__('Error', 'shop-ux-toolkit'),
      ['response' => 403]
    );
  }
  check_admin_referer('gpt_sb_sux_save');

  $keys = array_keys(GPT_SB_SUX_DEFAULTS);
  $new  = [];
  foreach ($keys as $k) {
    $new[$k] = isset($_POST[$k]) ? true : false;
  }
  update_option('gpt_sb_sux_options', $new);

  wp_safe_redirect(add_query_arg(
    ['page' => 'gpt_sb_sux', 'settings-updated' => '1'],
    admin_url('options-general.php')
  ));
  exit;
});

/** ===============================
 * Render settings page
 * =============================== */
function gpt_sb_sux_render_settings_page() {
  if (!current_user_can('manage_options')) return;
  $o = gpt_sb_sux_opts();
  $settings_updated = (bool) filter_input(INPUT_GET, 'settings-updated', FILTER_VALIDATE_BOOLEAN);
  ?>
  <div class="wrap">
    <h1><?php echo esc_html__('Shop UX Toolkit', 'shop-ux-toolkit'); ?></h1>

    <?php if ($settings_updated): ?>
      <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Settings saved.', 'shop-ux-toolkit'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <?php wp_nonce_field('gpt_sb_sux_save'); ?>
      <input type="hidden" name="action" value="gpt_sb_sux_save" />

      <table class="form-table" role="presentation">
        <tbody>
          <tr>
            <th scope="row"><?php echo esc_html__('Links in shop & category descriptions display properly', 'shop-ux-toolkit'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="load_links_css" value="1" <?php checked(!empty($o['load_links_css'])); ?>>
                <?php echo esc_html__('Links are underlined; underline not displayed on hover.', 'shop-ux-toolkit'); ?>
              </label>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php echo esc_html__('Hover effect on product & category boxes', 'shop-ux-toolkit'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="load_lift_css" value="1" <?php checked(!empty($o['load_lift_css'])); ?>>
                <?php echo esc_html__('Gives boxes a lift with a soft shadow on hover.', 'shop-ux-toolkit'); ?>
              </label>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php echo esc_html__('Keyboard-only focus outline (optional)', 'shop-ux-toolkit'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="keyboard_only_focus" value="1" <?php checked(!empty($o['keyboard_only_focus'])); ?>>
                <?php echo esc_html__('Visible focus ring for keyboard navigation; not shown for mouse/touch.', 'shop-ux-toolkit'); ?>
              </label>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php echo esc_html__('Blog excerpts with "Read More" button', 'shop-ux-toolkit'); ?></th>
            <td>
              <label>
                <input type="checkbox" name="blog_excerpts_enabled" value="1" <?php checked(!empty($o['blog_excerpts_enabled'])); ?>>
                <?php echo esc_html__('Show excerpts on blog and archive pages with a styled "Read More" link.', 'shop-ux-toolkit'); ?>
              </label>
            </td>
          </tr>
        </tbody>
      </table>

      <?php submit_button(__('Save Changes', 'shop-ux-toolkit')); ?>
    </form>

    <p style="margin-top:1rem;color:#555;">
      <?php echo esc_html__('Privacy: This plugin does not collect, store, or transmit any personal data and makes no external requests.', 'shop-ux-toolkit'); ?>
    </p>
  </div>
  <?php
}

/** ===============================
 * Plugins screen links
 * =============================== */
add_filter('plugin_action_links_' . plugin_basename(GPT_SB_SUX_PLUGIN_FILE), function ($links) {
  $url = admin_url('options-general.php?page=gpt_sb_sux');
  array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'shop-ux-toolkit') . '</a>');
  return $links;
});

add_filter('plugin_row_meta', function ($links, $file) {
  if ($file !== plugin_basename(GPT_SB_SUX_PLUGIN_FILE)) return $links;
  $links[] = '<a href="https://wordpress.org/plugins/shop-ux-toolkit/#faq" target="_blank" rel="noopener">' . esc_html__('FAQ', 'shop-ux-toolkit') . '</a>';
  $links[] = '<a href="https://wordpress.org/support/plugin/shop-ux-toolkit/" target="_blank" rel="noopener">' . esc_html__('Support', 'shop-ux-toolkit') . '</a>';
  return $links;
}, 10, 2);