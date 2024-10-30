=== Checkout Field Visibility for WooCommerce ===
Contributors: zamartz
Donate link: https://bt.zamartz.com/WooChecFielVis
Tags: checkout field customizer, woocommerce checkout fields, ecommerce, billing, shipping, zamartz
Requires at least: 5.0.0
Tested up to: 6.5.5
Requires WooCommerce at least: 5.0.0
Tested WooCommerce up to: 9.0.0
Stable Tag: trunk
Requires PHP: 7.0
License: GPLv3
Allows for the hiding of WooCommerce billing and shipping fields, based on the relevant conditional rule set(s) defined.

== Description ==

The Biggest update to the Checkout Field Visibility Settings yet.

The plugin adds administrative functionality to the WooCommerce checkout allowing for conditional logic to unset Billing and Shipping Fields. Multiple rule sets can be applied to the same checkout allowing one condition or many to unset a single field or multiple fields at the same time.

This no-coding approach helps achieve removing checkout features not needed for country specific checkouts or if trying to get email acquisitions with a freemium model. 

NEW, you can now update the "Requred" stats of fields and show WARNING, ERROR messages at chekout based on the RuleSet

This adds additional functionality and support to the legacy plugin [WooCommerce Hide Bulling Fields](https://zamartz.com/product/woocommerce-hide-billing-fields/). Users of the legacy plugin that have a paid version of this extension will have a one click option to import their previous rules.

**Unset Conditions base on:**

(both shipping and billing)

* Order Total Value
* Order Sub-Total Value
* Order Shipping Amount
* Order Tax Amount
* User or Admin Roles
* Product(s) in Cart
* Product Variant(s) in Cart
* Product Category(ies) in Cart
* Coupon is Applied

== Installation ==

* Maual Upload to Server - the entire ‘woocommerce-disqus-comments-and-ratings’ folder to the '/wp-content/plugins/' directory 
* Manual Upload throug Worpdress - Install by droping .zip throug Plugins -> Add New -> Upload Plugin -> Select File
* Auto Install through Wordpress Plugin Directory

== Activation ==

1. Install and Activate Plugin through the 'Plugins' menu in WordPress
1. Goto Settings in  YourSiteDomain/wp-admin/admin.php?page=wc-settings&tab=products&section=disqus_comments_and_ratings
1. Free - Use Select Option and Save
1. Advanced - Add API Cridentials and Save
1. Advanced - Activate API
1. Advanced - Choose Setting for Both Reviews and Comments and Save

== Frequently Asked Questions ==

= What is included in the FREE version? =

* RuleSet Condition = Order Total Value

= What is included in the PAID version? =

* Free Upgrades
* Priority Support
* Feedback request for improvements/enhancements
* RuleSet Condition = Order Sub-Total Value
* RuleSet Condition = Order Shipping Amount
* RuleSet Condition = RuleSet Condition = Order Tax Amount
* RuleSet Condition = User or Admin Roles
* RuleSet Condition = Product(s) in Cart
* RuleSet Condition = Product Variant(s) in Cart
* RuleSet Condition = Product Category(ies) in Cart
* RuleSet Condition = Coupon is Applied
* ReulSet Condition = Terms and Conditions Checkbox
* RuelSet Condition = User to be Logged In
* RuleSet Condition = Create an Account Required
* Ability to set or unset Required Fields
* Ability to show Warning and Error Messages at checkout based on RuleSet

= How do I remove the Dashboard Ads/Affiliates/Partners? =

For now, buy any extension, including this one, and upon activation of the API, all ads will be deactivated. It is pretty simple.

== Screenshots ==

1. Bumber screenshot- for Checkout Field Visabilty for WooComerce `/assets/screenshot-1.png`
2. Free  Site Settings AddOn Functionality `/assets/screenshot-2.png`
3. Free Site Settings AddOn Activation `/assets/screenshot-3.png`
4. Free Site Dashboard `/assets/screenshot-4.png`
5. Free Site Status `/assets/screenshot-5.png`
6. Paid Site Settings AddOn Activation `/assets/screenshot-6.png`
7. Paid Site Settings AddOn Advanced `/assets/screenshot-7.png`
8. Paid Site Settings AddOn Debug `/assets/screenshot-8.png`
9. Paid Site Dashboard `/assets/screenshot-9.png`
10. FreeWooCommerce Shipping `/assets/screenshot-10.png`
11. Free WooCommerce Billing `/assets/screenshot-11.png`
12. Paid WooCommerce Billing RuleSets `/assets/screenshot-12.png`
13. Paid Network Settings AddOn `/assets/screenshot-13.png`
14. Free Network Dashboard `/assets/screenshot-14.png`
15. Paid Network Dashboard `/assets/screenshot-15.png`

== Changelog ==

= 1.0.0 =

* Original version introduced. Replacing legacy plugin [WooCommerce Hide Bulling Fields](https://zamartz.com/product/woocommerce-hide-billing-fields/)

= 1.0.1 =

* plugin directory mis-match error

= 1.0.2 =

* plugin directory mis-match error paid version only

= 1.1.0 =

* major updates to core zamartz admin for future stability and network program

= 1.1.1 =

* Bugix for set_plugin_api_data() error

= 1.1.1 =

* Bugix for set_plugin_api_data() error

= 1.1.2 =

* NEW, Show Messages at Checkout based on the RuleSet
* NEW, ReulSet Condition = Terms and Conditions Checkbox
* NEW, RuelSet Condition = User to be Logged In
* NEW, RuleSet Condition = Create an Account Required
* NEW, Ability to set or unset Required Fields
* Updates to design and functionality

= 1.2.1 =

* Updated to correct minor update number
* patch for loop that could reprot errors in log

= 1.2.3 =

* core ZAMARTZ update

* general admin bug edgecase fixes

== Upgrade Notice ==

NEW Features added to premium version, while still at the same price!

== Buy Updgrade ==

Purchase the Advanced option to allow additional rules and logic to be applied to both Shipping and Billing fields = [WooCommerce Checkout Field Visability](https://zamartz.com/product/woocommerce-checkout-field-visibility/)