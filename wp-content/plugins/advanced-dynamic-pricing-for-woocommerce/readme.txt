=== Advanced Dynamic Pricing for WooCommerce ===
Contributors: algolplus
Donate link: https://algolplus.com/plugins/
Tags: woocommerce, discounts, deals, dynamic pricing, pricing deals, bulk discount, pricing rule
Requires PHP: 5.4.0
Requires at least: 4.8
Tested up to: 5.0
Stable tag: 1.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

All discount types. WordPress Customizer supported.

== Description ==

This plugin helps you  quickly set discounts and pricing rules for your WooCommerce store.

Set up any kind of discount or dynamic pricing you like, and activate/deactivate rules as needed.

Configure fixed dollar amount adjustments, percentage adjustments, or set fixed price for the product or group of products.

Also supports role-based prices & bulk pricing. **Bulk tables can be designed with Customizer.**

The applied discounts can be viewed  inside the order.

= Some Examples  = 

* Category-level discounts - discount products and provide free shipping
* Buy 4(or more) items on Friday and get 20% off 
* Buy product X and get product Y for free - immediately added and visible in cart
* Buy a package -  discount it (each item separately), and also get a free product
* Apply bulk discount for selected items, available only to wholesale buyers
* Give a 10% discount to all "Accessories"(Category) if a product X is present in the cart

Check more examples [on our website](https://algolplus.com/plugins/category/advanced-dynamic-pricing/).

= Pricing Rules can  =
* Filter cart items by products, categories, tags or custom fields
* Adjust each product prices
* Set total price for packages
* Apply cart discounts and fees
* Add free products on fly
* Use tables to get bulk rates
* Validate conditions for cart items, user roles or dates
* Track limits (only "max usage" supported currently)

= Allowed settings = 
* Show/hide original prices 
* Show/hide bulk discount table on the product page
* Set rule for  products which already on sale
* Combine multiple discounts
* Combine multiple fees
* Setup default discount name ( we add cart discount as coupon )
* Setup default fee name

[Pro version](https://algolplus.com/plugins/downloads/advanced-dynamic-pricing-woocommerce-pro/) can [adjust product price onfly](https://algolplus.com/plugins/pro-features-in-action/), adds **extra conditions, exclusive rules, export/import and statistics** (which rules really work, which products are involved and how much does it cost for you).

Have an idea or feature request?
Please create a topic in the "Support" section with any ideas or suggestions for new features.

== Installation ==

= Automatic Installation =
Go to Wordpress dashboard, click  Plugins / Add New  , type 'Advanced Dynamic Pricing for WooCommerce' and hit Enter.
Install and activate plugin, visit WooCommerce > Pricing Rules.

= Manual Installation =
[Please, visit the link and follow the instructions](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)

== Frequently Asked Questions ==

= Compatibility with Currency Switcher =
The pricing in the bulk table will change automatically if the client changes the currency.

== Screenshots ==
1. List of pricing rules
2. Simple rule -  discount for category
3. More complex rule 
4. Complex rule was applied to the cart
5. The applied discounts can be viewed  inside the order
6. Settings page


== Changelog ==

= 1.5.2 - 2019-01-10 =
* Added operation "not in list" to product filters
* Added two modes for cart conditions:  AND (all conditions must be valid) and OR (any condition must be valid)
* Apply pricing rules to [Phone Orders](https://wordpress.org/plugins/phone-orders-for-woocommerce/). You must turn on "Apply pricing rules to backend orders" in >Settings>System.
* Fixed bug - showed SALE badge for all products
* Fixed bug - date range didn't work for some locales
* Fixed bug - didn't show price suffix in modified price
* Speeded up calculations for ajax requests

= 1.5.1 - 2018-11-26 =
* "Role discounts" and "Bulk discounts" can be used together (drag them to set priority, added mode "Skip bulk rules if role rule was applied")
* Correctly works with sold individually products
* New tab "Settings"
* Show bulk range as a single number, if "beginning of range" is equivalent to "end of range"
* Allow negative discounts (for price increase!)
* Speeded up calculations if there are many active rules
* Update price when user increases quantity on product page (pro version only), [see it in action](https://algolplus.com/plugins/pro-features-in-action/)
* Update price for cross-sells in the cart (pro version only)

= 1.5.0 - 2018-10-30 =
* Bulk tables can be tweaked using Customizer (visit tab "Settings" and click "Customize")
* Added new mode for on-sale products - "Best between discounted regular price and sale price"
* Fixed bug: "Free shipping" stayed in the cart if you delete products

= 1.4.4 - 2018-10-10 =
* Added mode "quantity based on" for bulk rules (default - all products)
* Added option to show discounted price in bulk table
* Display bulk table for selected variation
* Allow translate custom messages for bulk table (via WPML)
* Added new filter - category slug
* Speeded up calculations for category pages
* Speeded up calculations for cart having many units of same product, finally

= 1.4.3 - 2018-07-26 =
* Added new filter - product SKU 
* Added option to show "On Sale" badge if product price was modified by pricing rules
* Speeded up calculations for cart having many units of same product
* Fixed display bugs for variable products

= 1.4.2 - 2018-06-04 =
* Added ability to select position for table with bulk rules (thanks to @nessunluogo)
* Added shorcodes  [adp_category_bulk_rules_table] and [adp_product_bulk_rules_table] to use in category/product pages
* Fixed critical bug: product filter by attributes didn't work for some setups
* Fixed bug:  "on sale" badge was hidden
* Allow to customize bulk tables, you should copy files from folder "templates" to folder "advanced-dynamic-pricing-for-woocommerce" (create it in active theme)

= 1.4.1 - 2018-04-09 =
* Added ability to show bulk table at category page
* Fixed critical bug: product filter by category/tags/custom fields didn't work for variable products

= 1.4.0 - 2018-02-19 =
* New condition "Active subscriptions"
* New condition "Customer order count"
* New setting "Override cents" (round discounted prices  to xxx.99)
* Updated buttons in UI
* Preserve external coupons in cart 
* Show total discount amount in cart and checkout
* Show applied discounts in order popup (WooCommerce 3.3.0 functionality)

= 1.3 - 2017-12-20 =
* Fixed critical bug: now  we don't rebuild the cart if no rules were applied
* Added the message on activation

= 1.2 - 2017-12-08 =
* Support taxes for items and shipping
* Added condition "Product custom fields"
* Added tab "Help"
* Fixed some minor bugs

= 1.1 - 2017-11-21 =
* Added condition "Customer Role"
* Added documentation link

= 1.0 - 2017-11-10 =
* First release.