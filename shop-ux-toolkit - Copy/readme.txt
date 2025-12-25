=== Shop UX Toolkit ===
Contributors: smokingblends
Tags: woocommerce, accessibility, ux, reviews, category-order
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.smokingblends.com

Enhances WooCommerce UX: link styling, hover shadows, keyboard focus, blog excerpts(Storefront theme only), reviews page, category ordering, Facebook cart handling.

== Description ==
Shop UX Toolkit improves WooCommerce and WordPress UX/accessibility. Designed and tested exclusively for the Storefront theme with WooCommerce. CSS targets WooCommerce classes for potential compatibility with other themes. Test thoroughly; disable features if conflicts occur. Toggle features in Settings > Shop UX Toolkit.

= Features =
- **Link Styling**: Underlines links in shop/category descriptions; removes on hover. Fixes accessibility/SEO issues.
- **Hover Effect**: Adds soft shadow on hover for product/category boxes. Signals clickability.
- **Keyboard Focus**: Shows focus outlines only for keyboard navigation.
- **Blog Excerpts**: Displays excerpts on blog/archives with "Continue Reading" button. Customizes length/ellipsis. (Available only for Storefront theme.)
- **Facebook Cart Integration**: Handles Meta (Facebook/Instagram) checkout redirects on secure-checkout page (auto-created).
- **Reviews Page**: Enables page for submitting/displaying reviews. Uses shortcodes [submit_review_form], [display_all_reviews]. Auto-creates page; customize slug. Note: Slowdown possible over 5000 products.
- **Captcha for Reviews**: Enables Turnstile/Google Captcha (requires Login Security Pro plugin).
- **Category Ordering**: Adds order field to product categories; sorts shop page by custom order (lower numbers first).
- **Other**: Stops empty paginated shop pages; centers modal image titles.

Live demo: https://www.smokingblends.com — hover product boxes, check description links, and Tab to see the focus option.

== Installation ==
1. Upload to `/wp-content/plugins/` or install via **Plugins → Add New**.
2. Activate the plugin.
3. Go to **Plugins → Installed Plugins → Shop UX Toolkit → Settings** to enable/disable features.
4. Clear page cache or CDN after changing settings.

== Usage ==
- Toggle features in settings.
- For reviews: Add shortcodes to page; manage displayed reviews in settings (up to 10).
- Category order: Edit categories, set number; auto-sorts shop.

== Frequently Asked Questions ==
= What does the plugin do? =
It enhances WooCommerce shop/product pages with better links, hover effects, centered image titles, and keyboard-only focus outlines, improving UX, accessibility, and engagement.
= Will it work with my theme? =
Designed for Storefront; may work with others but test for conflicts. CSS uses WooCommerce classes, but other themes' custom styles could override or cause issues.
= Can I customize features? =
Yes, enable/disable link underlines, hover effects, or focus outlines in the settings.
= Is it lightweight? =
Yes, CSS-based for minimal impact on site speed, ideal for using just one feature.
= How does it improve accessibility and SEO? =
Underlined links and keyboard-only focus outlines meet accessibility standards for visual impairments. Fixing PageSpeed Insights errors (e.g., color-only links) may improve SEO.
= Do I need coding skills? =
No, adjust settings via the WordPress dashboard. A “Settings” link appears on the Plugins page.
= How do I verify it’s working? =
Check shop/product pages for underlined links, hover effects, and focus outlines. Test accessibility with tools like Google PageSpeed Insights.
= How to use category order? =
Edit product category > set order number; lower first on shop.
= Reviews slowdown? =
Over 5000 products may slow dropdown; caching helps.
= Customize reviews page? =
Change slug in settings; auto-recreates if missing.

== Screenshots ==
1. Description links underlined by default, removed on hover. [screenshot-1.png]
2. Product/category boxes with soft shadow hover. [screenshot-2.png]
3. Reviews submission form. [screenshot-3.png]
4. Category order field in admin. [screenshot-4.png]

== Changelog ==
= 0.3.0 =
* Added blog excerpts feature (Storefront only).
* Added Facebook cart integration with customizable slug.
* Added reviews page with shortcodes, management, and captcha option.
* Optimized reviews with caching.
* Updated function prefixes for consistency.
* Added category ordering.
= 0.2.2 =
* Refined hover-lift to clickable elements in category/product boxes.
* Fixed lower-left and lower-right corner hover effect coverage.
* Centered image titles in single-product large image modal.
= 0.2.1 =
* Added link behavior for single-product description tabs.
* Clarified Storefront-first testing and support.
= 0.2.0 =
* CSS-only simplification; focused UX tweaks on archive pages.

== Upgrade Notice ==
= 0.3.0 =
This is a major update introducing new features like blog excerpts, Facebook cart, reviews page, and category ordering. Settings may reset due to internal changes like updated function prefixes—reconfigure as needed. Clear cache after upgrading.
= 0.2.2 =
Refined hover, fixed corners, centered modal titles. Clear cache after upgrading.
= 0.2.1 =
Added description tab link behavior. Clear cache to apply changes.

== Contributing ==
Free plugin, Storefront-first. Suggest changes via WordPress.org Support forum with WP, Woo, theme versions, steps, and minimal code.

== Privacy ==
This plugin does not collect, store, or transmit personal data, and makes no external requests.