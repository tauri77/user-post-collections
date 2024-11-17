# User Post Collections

This plugin allows users to create lists of different types (simple, numbered, cart and poll) and share them.
The items of these lists are the posts of the types to configure (Ex: posts, pages, products, other CPT, etc)
Create classic lists like Favorites, Bookmarks, Wish List. Or poll lists like "Which one should I buy?", or shopping cart lists of every month, etc.
It is flexible and extensible.

The plugin add custom endpoints in the wordpress REST API.
It also includes a client for the api developed in preact.

You can add a link, for example to the menu, with the hash "#my-lists", when clicked the client appears on the scene as a modal.


## Description

The plugin adds custom endpoints to the wordpress REST API and includes a client that will display operations on user lists in a modal.

## Features

* Roles that can create lists is configurable (for each type of list).
* Post type that can be added to lists is configurable (per list type).
* Title and description of the lists can be editable (configurable in each type of list)
* List items can be saved with a comment (configurable in each type of list)
* The lists can be private or public (configurable options in each type of list)
* Max items per list (configurable in each type of list)

## Default lists types

The plugin comes with 6 types of lists:
* **Simple:** Simple list sorted according to their items added
* **Numbered:** List with your numbered items. You can edit the order in which the items will be displayed.
* **Poll:** You can ask others for their opinion
* **Shopping Cart:** List to add items to a virtual cart ( only on Woocommerce )
* **Favorites** This type of list conceptually always exists for users, that is, the user does not create them but simply adds items.
* **Bookmarks** Equivalent to favorites.

If you are a developer and you are making a theme you can register your own list types.


## Collections Archive

The plugin adds a new page to the site where all the collections of the users are shown. This page can be disabled/enabled on the plugin settings.
This page is also used as the basis for displaying each list, but disabling the archive page from the plugin settings does not disable the pages of each collection..
Archive URL example: https://domain.com/user-post-collection/
Collection URL example: https://domain.com/user-post-collection/list-x-by-tauri/

## Shortcode

You can use the shortcode [user_posts_collections] to show the lists. Example:

    [user_posts_collections type="vote" tpl-items="cards" exclude="32,45"]
    [user_posts_collections author="23" tpl-items="list"]
    [user_posts_collections limit="5" pagination="1" tpl-cols="4,4,4,3,2,1"]
    [user_posts_collections type="vote" author-name="tauri" orderby="title" order="ASC" limit=10 pagination=1]
    [user_posts_collections include="23,31,412"]

### Shortcode options

* __type:__ simple|numbered|vote|favorites|bookmarks|cart
* __author-name:__ Author username
* __author:__ Author ID
* __include:__ Lists ID to include (comma separated)
* __exclude:__ Lists ID to exclude (comma separated)
* __orderby:__ ID|views|vote_counter|count|created|modified|title
* __order:__ ASC|DESC
* __limit:__ Max lists to show
* __pagination:__ Show pagination. Set to 1 for enabled. Default: 0
* __id:__ Set unique string. Only letters, numbers and "-". Used for pagination.
* __tpl-items:__ (card|list) List type
* __tpl-cols:__ Number of columns, comma separated: xxl,xl,lg,md,sm,xs (for card list type) Default: 4,4,4,3,2,1
* __tpl-cols-(xs|sm|md|lg|xl|xxl):__ (1|2|3|4|5) Number of columns (for card list type)
* __tpl-thumbs:__ Default thumbnails layout. Set to "off" to not show
* __tpl-thumbs-(xs|sm|md|lg|xl|xxl):__ (0|2x2|2x3|3x2|4x1|1x3|...) Thumbnails layout [1-4]x[1-4]
* __tpl-desc:__ (on|off) Show description. Set to "off" to hide description
* __tpl-user:__ (on|off) Show author. Set to "off" to hide user
* __tpl-meta:__ (on|off) Show meta. Set to "off" to hide meta

## Reinventing the wheel

The plugin is being developed in an existing database, but the structure is such that it will be simple to pass the lists to a CPT.

## Screenshots

![Screenshot 1](.wordpress-org/screenshot-1.png?raw=true)
![Screenshot 2](.wordpress-org/screenshot-2.png?raw=true)
![Screenshot 3](.wordpress-org/screenshot-3.png?raw=true)
![Screenshot 4](.wordpress-org/screenshot-4.png?raw=true)
![Screenshot 5](.wordpress-org/screenshot-5.png?raw=true)
![Screenshot 6](.wordpress-org/screenshot-6.png?raw=true)
![Screenshot 7](.wordpress-org/screenshot-7.png?raw=true)
