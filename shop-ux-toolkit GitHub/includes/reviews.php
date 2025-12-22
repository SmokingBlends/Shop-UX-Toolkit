<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Check if WooCommerce is active
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return; // Exit if WooCommerce is not active
}
// Shortcode for review submission form
add_shortcode('submit_review_form', 'gpt_sb_sux_review_form_shortcode');
function gpt_sb_sux_review_form_shortcode() {
    // Define current URL early for use in processing/redirect
    global $post;
    $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
    $current_url = is_a($post, 'WP_Post') ? get_permalink($post->ID) : home_url($request_uri);
    // Check only for POST with submit button (prevents Gutenberg preview issues)
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
        // Early nonce verification
        if (!isset($_POST['_wpnonce'])) {
            wc_add_notice(__('Security check failed. Please try again.', 'shop-ux-toolkit'), 'error');
            wp_safe_redirect($current_url);
            exit;
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce']));
        if (!wp_verify_nonce($nonce, 'submit_review_nonce')) {
            wc_add_notice(__('Security check failed. Please try again.', 'shop-ux-toolkit'), 'error');
            wp_safe_redirect($current_url);
            exit;
        }
        // Honey pot check for basic spam prevention
        if (!empty($_POST['hp_email'])) {
            wc_add_notice(__('Error submitting review. Please try again.', 'shop-ux-toolkit'), 'error'); // Generic error to not tip off bots
            wp_safe_redirect($current_url);
            exit;
        }
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $review_content = isset($_POST['comment']) ? sanitize_textarea_field(wp_unslash($_POST['comment'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $author_name = isset($_POST['author']) ? sanitize_text_field(wp_unslash($_POST['author'])) : '';
        if (!$product_id || !$review_content || (!$email && !is_user_logged_in()) || (wc_review_ratings_enabled() && !$rating)) {
            wc_add_notice(__('Please fill out all required fields.', 'shop-ux-toolkit'), 'error');
            wp_safe_redirect($current_url);
            exit;
        }
        if (wc_review_ratings_enabled() && ($rating < 1 || $rating > 5)) {
            wc_add_notice(__('Rating must be between 1 and 5.', 'shop-ux-toolkit'), 'error');
            wp_safe_redirect($current_url);
            exit;
        }
        if ($email && !is_email($email)) {
            wc_add_notice(__('Please provide a valid email address.', 'shop-ux-toolkit'), 'error');
            wp_safe_redirect($current_url);
            exit;
        }
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $submit_email = is_user_logged_in() ? wp_get_current_user()->user_email : $email;
        $submit_author = is_user_logged_in() ? wp_get_current_user()->display_name : ($author_name ?: 'Guest');
        $existing_args = array(
            'post_id' => $product_id,
            'type' => 'review',
            'status' => 'all', // Changed to 'all' to prevent duplicates even if pending
            'number' => 1,
            'fields' => 'ids',
        );
        $existing_args['author_email'] = $submit_email;
        if ($user_id) {
            $existing_args['user_id'] = $user_id;
        }
        $existing_reviews = get_comments($existing_args);
        if (!empty($existing_reviews)) {
            wc_add_notice(__('You have already reviewed this product.', 'shop-ux-toolkit'), 'error');
            wp_safe_redirect($current_url);
            exit;
        }
        $comment_approved = get_option('comment_moderation') ? 0 : 1; // Respect admin moderation setting
        $data = array(
            'comment_post_ID' => $product_id,
            'comment_author' => $submit_author,
            'comment_author_email' => $submit_email,
            'comment_author_url' => '',
            'comment_content' => $review_content,
            'comment_type' => 'review',
            'comment_parent' => 0,
            'user_ID' => $user_id,
            'comment_approved' => $comment_approved,
        );
        // NEW: Integrate Login Security Pro Turnstile verification via WP's preprocess_comment filter
        // This triggers the plugin's token check; returns WP_Error on failure (e.g., invalid captcha)
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $data = apply_filters( 'preprocess_comment', $data );
        if ( is_wp_error( $data ) ) {
            wc_add_notice( $data->get_error_message(), 'error' );
            wp_safe_redirect( $current_url );
            exit;
        }
        $comment_id = wp_insert_comment($data);
        if (!$comment_id) {
            wc_add_notice(__('Error submitting review. Please try again.', 'shop-ux-toolkit'), 'error');
            wp_safe_redirect($current_url);
            exit;
        }
        if ($rating) {
            add_comment_meta($comment_id, 'rating', $rating);
        }
        $verified = 0;
        if ($user_id && wc_customer_bought_product($submit_email, $user_id, $product_id)) {
            $verified = 1;
        } elseif (!$user_id) {
            $orders = wc_get_orders(array(
                'billing_email' => $submit_email,
                'status' => array('wc-completed', 'wc-processing'),
                'limit' => -1, // Changed to -1 for full accuracy (no perf hit expected for reviews)
                'date_created' => '>=' . (time() - YEAR_IN_SECONDS * 2), // Last 2 years
            ));
            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    if ($item->get_product_id() == $product_id) {
                        $verified = 1;
                        break 2;
                    }
                }
            }
        }
        add_comment_meta($comment_id, 'verified', $verified);
        // If auto-approved, update product rating caches (matches WC core)
        if (1 === $comment_approved) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            do_action('woocommerce_new_product_review', $comment_id);
        }
        // Dynamic notice based on approval status
        $notice = (1 === $comment_approved) ? __('Review published successfully!', 'shop-ux-toolkit') : __('Review submitted successfully! It will be reviewed before publishing.', 'shop-ux-toolkit');
        wc_add_notice($notice, 'success');
        // Redirect after processing to prevent resubmission on refresh (PRG pattern)
        // Notices will show on the redirected page load
        wp_safe_redirect($current_url);
        exit;
    }
    return gpt_sb_sux_render_review_form();
}
// Render the review form
function gpt_sb_sux_render_review_form() {
    ob_start();
    global $post;
    $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
    $current_url = is_a($post, 'WP_Post') ? get_permalink($post->ID) : home_url($request_uri);
    echo '<div class="woocommerce"><div id="reviews" class="woocommerce-Reviews">';
    echo '<h2 class="woocommerce-Reviews-title">' . esc_html__('Leave a Review', 'shop-ux-toolkit') . '</h2>';
    echo '<div id="review_form_wrapper"><div id="review_form">';
    echo '<div id="respond" class="comment-respond">'; // Added to match WC JS selector (#respond p.stars a)
    echo '<form action="' . esc_url($current_url) . '" method="post" id="commentform" class="comment-form">';
    $nonce_html = wp_nonce_field('submit_review_nonce', '_wpnonce', true, false);
    echo wp_kses($nonce_html, array('input' => array('type' => array(), 'name' => array(), 'value' => array(), 'id' => array())));
    // Honey pot field (hidden; bots might fill it)
    echo '<input type="text" name="hp_email" value="" style="display:none;" tabindex="-1" autocomplete="off">';
    ?>
    <p class="comment-form-product form-row form-row-wide">
        <label for="product_id"><?php esc_html_e('Select Product', 'shop-ux-toolkit'); ?> <span class="required">*</span></label>
        <select name="product_id" id="product_id" class="input-text" required>
            <option value=""><?php esc_html_e('Choose a product...', 'shop-ux-toolkit'); ?></option>
            <?php
            $products = wc_get_products(array(
                'limit' => 100,
                'status' => 'publish',
                'orderby' => 'name',
                'order' => 'ASC',
            ));
            if (empty($products)) {
                echo '<option value="" disabled>' . esc_html__('No products available', 'shop-ux-toolkit') . '</option>';
            } else {
                foreach ($products as $product) {
                    echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                }
            }
            ?>
        </select>
    </p>
    <?php
    $commenter = wp_get_current_commenter();
    $req = get_option('require_name_email');
    $aria_req = ($req ? ' aria-required="true"' : '');
    $html5 = current_theme_supports('html5', 'comment-form') ? 'html5' : '';
    if (!is_user_logged_in()) {
        ?>
        <p class="comment-form-author form-row form-row-first">
            <label for="author"><?php esc_html_e('Name', 'shop-ux-toolkit'); ?><?php echo $req ? ' <span class="required">*</span>' : ''; ?></label>
            <input id="author" name="author" type="text" value="<?php echo esc_attr($commenter['comment_author']); ?>" size="30" class="input-text"<?php echo esc_html($aria_req); ?> />
        </p>
        <p class="comment-form-email form-row form-row-last">
            <label for="email"><?php esc_html_e('Email', 'shop-ux-toolkit'); ?> <span class="required">*</span></label>
            <input id="email" name="email" <?php echo $html5 ? 'type="email"' : 'type="text"'; ?> value="<?php echo esc_attr($commenter['comment_author_email']); ?>" size="30" class="input-text"<?php echo esc_html($aria_req); ?> />
        </p>
        <?php
    }
    if (wc_review_ratings_enabled()) {
        ?>
        <p class="comment-form-rating form-row form-row-wide">
            <label for="rating"><?php esc_html_e('Your rating', 'shop-ux-toolkit'); ?> <span class="required">*</span></label>
            <select name="rating" id="rating" required>
                <option value=""><?php esc_html_e('Rate&hellip;', 'shop-ux-toolkit'); ?></option>
                <option value="5"><?php esc_html_e('Perfect', 'shop-ux-toolkit'); ?></option>
                <option value="4"><?php esc_html_e('Good', 'shop-ux-toolkit'); ?></option>
                <option value="3"><?php esc_html_e('Average', 'shop-ux-toolkit'); ?></option>
                <option value="2"><?php esc_html_e('Not that bad', 'shop-ux-toolkit'); ?></option>
                <option value="1"><?php esc_html_e('Very poor', 'shop-ux-toolkit'); ?></option>
            </select>
        </p>
        <?php
    }
    ?>
    <p class="comment-form-comment form-row form-row-wide">
        <label for="comment"><?php esc_html_e('Your review', 'shop-ux-toolkit'); ?> <span class="required">*</span></label>
        <textarea id="comment" name="comment" cols="45" rows="8" class="input-text" required></textarea>
    </p>
    <?php
    // NEW: Display Turnstile captcha markup via plugin hooks
    // Place after fields (before submit); uses correct hook based on login status
    if ( is_user_logged_in() ) {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        do_action( 'comment_form_logged_in_after' );
    } else {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        do_action( 'comment_form_after_fields' );
    }
    ?>
    <p class="form-submit form-row form-row-wide">
        <input name="submit_review" type="submit" id="submit" class="submit button" value="<?php esc_attr_e('Submit', 'shop-ux-toolkit'); ?>" />
    </p>
    <?php
    echo '</form>';
    echo '</div>'; // Close #respond
    echo '</div></div></div></div>';
    return ob_get_clean();
}
// Display selected reviews as static HTML
add_shortcode('display_all_reviews', 'gpt_sb_sux_all_reviews_shortcode');
function gpt_sb_sux_all_reviews_shortcode($atts) {
    $html = '<div class="reviews-grid" style="margin-bottom: 50px; grid-template-columns: 1fr; font-size: 1.1em;">
  <div class="review-card">
    <div class="stars">★★★★★</div>
    <blockquote>"I quit smoking cigarettes on January 10th, 2003, using an herbal product called Smoke Away or something like that. I quit smoking p*t in the mid 80s.I also work all day, and then take care of my 89 year old dad in the evenings when I get in from work. (I’m 56 years young.). I have hired someone to stay with him during the day while I’m at work. But, working a full time job, and taking care of my dad, gets really stressful. I was looking for an alternative to nights of no sleep. believe that Cloudbreak takes care of all of my problems. I don’t have health insurance. All I have is prayer to keep me well. I recommend your product to anyone who has been in my situation. This product is really, really, really, good. And so is this company."</blockquote>
    <p class="author">- youngtimer</p>
  </div>
  <div class="review-card">
    <div class="stars">★★★★★</div>
    <blockquote>"Good Afternoon! I would like to eat my words! The package just came in, mix up with the mailwoman. I am very pleased. Packed a bowl of the Mellow Yellow, extremely satisfactory. Will be purchasing more herbs from you in the future. Thanks again"</blockquote>
    <p class="author">- warren.zed</p>
  </div>
  <div class="review-card">
    <div class="stars">★★★★★</div>
    <blockquote>"I had to quit smoking after 20+ years due to employment reasons. I had done some research at various web sites and they kept referring back to “Another Site” saying it was the #1 place to go. I had such a bad experience with their customer service just trying to get my order which I had paid a “rush” charge. Once I received it, I was disappointed with the product. It had mold growing on it and it was so harsh with very enjoyment. To make a long story short, I was very disappointed and discouraged. I decided that I needed to have something to compare it to so I went on-line. After 2 hours of searching, I finally found a company that wasn’t an affiliate of the 1st company or a company in — ——. I placed an order with your company and your response was immediate. I had it shipped Express and it arrived on time. Once I opened the package and tried the product Mystical Journeys, I was shocked at how mild the smoke was and very enjoyable. Thanks for the professional and prompt service I received. With the great products and customer service I experienced, you have earned a customer for life."</blockquote>
    <p class="author">- phillywilly</p>
  </div>
  <div class="review-card">
    <div class="stars">★★★★★</div>
    <blockquote>"Have tried numerous smoke from smoking blends. I figured I would try this new product. Very nice smoke and I enjoyed Equilibrium. Good feeling after smoking. Good combo with Paragon with a unique taste. I give Equilibrium an A+. Try it you won’t be disappointed."</blockquote>
    <p class="author">- Michael Connolly</p>
  </div>
  <div class="review-card">
    <div class="stars">★★★★★</div>
    <blockquote>"Hi I got the Emberleaf at the post office today. Very cool!I will continue ordering from your company thanks. I never leave feedback for products I buy online but I am so impressed with your products that I absolutely had to drop you a note. I bought some Cloudbreak and Sunset and ordered express delivery. I was amazed to have the products arrive after only 1 day. Typically even the best sites take two days for delivery. So thanks for that!!! I really wanted an alternative to the addictive stuff and I wanted it fast so I’m happy for the prompt service. As for the actual products I immediately lit some up and was shocked to enjoy an immediate smoke. It was great!!!!!! I can not tell you how happy I am. I’ve been a regular smoker for years. Having your company as a natural alternative is an unbelievable treat. I eventually found that Cloudbreak when smoke in a pipe with a small air tip produced AMAZING smokes. The smoke is great!!!!!! Anyone who wants an alternative try Cloudbreak!!!! You will be amazed. And it’s natural!!!!!! Thank you, Thank you!!!!!!"</blockquote>
    <p class="author">- ShironB</p>
  </div>
  <div class="review-card">
    <div class="stars">★★★★★</div>
    <blockquote>"Impressive! I’ve been quitting Tobacco for years and this blend calms those nerves better than nicotine cessation products! I bought a pipe to smoke with this. Either is great. Reminds me of pipe tobacco in flavor. I will be buying more products to keep me nicotine free!"</blockquote>
    <p class="author">- stevenoloughlin83</p>
  </div>
  <div class="review-card">
    <div class="stars">★★★★★</div>
    <blockquote>"I recently ordered some Sunset and also some Luna. I have to say, I’m very impressed. Both are great, but I’m kind of partial to the Luna, it’s just so… green. Actually, it’s nice to alternate between the two. You can be sure I’ll be back to try some other products."</blockquote>
    <p class="author">- BethanyA </p>
  </div>
  <div class="review-card">
    <div class="stars">★★★★★</div>
    <blockquote>"I would like to thank you for Priority sending my order after a slight delay. The delay being for security reasons just serves to prove that you WILL NOT and DO NOT sell to minors and I as a parent appreciate that; thank you! On the matter of my order; WOW!! Your sample was so much bigger than the last place I ordered I thought perhaps I bought it and had to check my receipt! The Sunset is awesome!! Didn’t really expect much since the last place I ordered from was a waste of time and money on their herbal products! WOW! yours really is good! As Arnold would say; “I’LL BE BACK!” Thanks again!"</blockquote>
    <p class="author">- darlalouise</p>
  </div>
  <div class="review-card">
    <div class="stars">★★★★★</div>
    <blockquote>"Incredible, in-tact whole dried blue lotus/lily – huge and beautiful, just as described! These are some of the largest and most pigmented blue lotus flowers I’ve ever seen and I’m amazed by the quality of the cultivation. I’m tincturing them and can’t wait to see the results. Smoking Blends is my new go-to for blue lotus. Thank you! 🖤"</blockquote>
    <p class="author">- Tigerlily</p>
  </div>
</div>';
    return $html;
}