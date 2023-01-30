=== User Post Collections ===
Contributors: Mauricio Galetto
Donate link: https://www.paypal.com/donate/?hosted_button_id=XNASRT5UB7KBN
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.txt
Tags: User lists, Post Collections, Woocommerce Wishlist
Tested up to: 6.1
Stable tag: 0.8.32
Requires PHP: 7.0

This plugin allows users to create lists of different types (simple, numbered, cart and poll) and share them.
The items of these lists are the posts of the types to configure (Ex: post, page, product, other CPT, etc).
Create classic lists like Favorites, Bookmarks, Wish List. Or poll lists like "Which one should I buy?", or shopping cart lists of every month, etc.
It is flexible and extensible.

== Description ==

The plugin adds custom endpoints to the wordpress REST API and includes a client that will display operations on user lists in a modal.

### Default lists types

The plugin comes with 6 types of lists:
* **Simple:** Simple list sorted according to their items added.
* **Numbered:** List with your numbered items. You can edit the order in which the items will be displayed.
* **Poll:** You can ask others for their opinion.
* **Shopping Cart:** List to add items to a virtual cart ( only on Woocommerce ).
* **Favorites** This type of list conceptually always exists for users, that is, the user does not create them but simply adds items.
* **Bookmarks** Equivalent to favorites.

All list types can be disabled.

If you are a developer and you are making a theme you can register your own list types.

### Features

* Roles that can create lists is configurable (for each type of list).
* Post type that can be added to lists is configurable (per list type).
* Title and description of the lists can be editable (configurable in each type of list)
* List items can be saved with a comment (configurable in each type of list)
* The lists can be private or public (configurable options in each type of list)
* Max items per list (configurable in each type of list)
* Share buttons for public lists

== Screenshots ==

1. Add item button
2. Item comment
3. Select list
4. Select type of list to create
5. Edit list details
6. Share link for the list
7. Poll list page

== Frequently Asked Questions ==

= How to add an entry in the menu with the user lists? =

You can add a link, with the url "#my-lists". Then when the user clicks there will appear the modal with the user's lists.

= Can I disable a list type? =

Yes, all list types can be disabled from the plugin settings.

= I am a developer. Do you have api documentation? =

Yes, you can read the api documentation by visiting [https://tauri77.github.io/user-post-collections/api.html](https://tauri77.github.io/user-post-collections/api.html)

== Changelog ==

= 0.8.32 =
* Added show list helper

= 0.8.31 =
* Fix "Edit Comment" button
* Added to the configuration "Add to..." string
* Tested on WP 6.1

= 0.8.30 =
* Add some themes helper

= 0.8.29 =
* Update preact

= 0.8.28 =
* Fix some uninstall options
* Fix errors on plain permalinks

= 0.8.27 =
* Update author url

= 0.8.26 =
* Remove unsafe IP option and use rest_is_ip_address

= 0.8.25 =
* Sanitize client IP

= 0.8.24 =
* Validate client IP

= 0.8.23 =
* First version of the plugin
