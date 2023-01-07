=== netvisor_connect ===
Contributors: 
Donate link: -
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Small plugin used to connect to netvisor through their REST API.

== Description ==

This plugin works in tandem with Wordpress site that runs woocommerce. Note that the customerinformation tables must not have been modified by their core rows.
netvisor_connect connects to Netvisor through a opened path, that must be done on the Netvisor side. Beyond this it uses the following interfaces:
Customer.nv, salesinvoice.nv, customerlist.nv, productlist.nv, product.nv.
With this plugin any new product, updated user and invoiced order will be transferred to the Netvisor account. If ay of these fail a log will be created/updated
that bears the name of this plugin. Also in the case of orders a order note is also added to that particular orders information in WooCommerce

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

1. Upload `netvisor_connect.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates
2. In Netvisor go to interfaces and create a new interfaceid
2.1 than go to the interface rights and allow the following rights: customerlist.nv, getcustomer.nv, customer.nv, salesinvoice.nv, productlist.nv, getproduct.nv, product.nv
3. change the header information at the function getHeaderInformation to your own. Function is at: admin/class-netvisor_connect-admin.php
4. change your time attribute at sendBillToNetvisor function
4. at the same function also change the first If-function to fit your software
5. at checkingNetvisorForCustomer and getCustomerIdentifier functions change from where the important customer data is gotten from to suit your software
6. Give products at WC the following attributes if you want them to update to netvisor as well
6. productGroup, countryOrigin, domesticA, EUA and outsideA
6. Note! DomesticA EUA and outsideA are account numbers respectively

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.



1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
* First version


== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

// some things

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`