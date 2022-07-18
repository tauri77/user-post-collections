# User Post Collections

Work in progress. Do not use in production!

This plugin allows users to create lists of different types (simple, numbered, cart and poll) and share them.
The items of these lists are the posts of the types to configure (Ex: posts, pages, products, other CPT, etc)
Create classic lists like Favorites, Bookmarks, Wish List. Or poll lists like "Which one should I buy?", or shopping cart lists of every month, etc.
It is flexible and extensible.

The plugin add custom endpoints in the wordpress REST API.
It also includes a client for the api developed in preact.

You can add a link, for example to the menu, with the hash "#my-lists", when clicked the client appears on the scene as a modal.


== Description ==

The plugin adds custom endpoints to the wordpress REST API and includes a client that will display operations on user lists in a modal.

### Features

* Roles that can create lists is configurable (for each type of list).
* Post type that can be added to lists is configurable (per list type).
* Title and description of the lists can be editable (configurable in each type of list)
* List items can be saved with a comment (configurable in each type of list)
* The lists can be private or public (configurable options in each type of list)
* Max items per list (configurable in each type of list)

### Default lists types

The plugin comes with 6 types of lists:
* **Simple:** Simple list sorted according to their items added
* **Numbered:** List with your numbered items. You can edit the order in which the items will be displayed.
* **Poll:** You can ask others for their opinion
* **Shopping Cart:** List to add items to a virtual cart ( only on Woocommerce )
* **Favorites** This type of list conceptually always exists for users, that is, the user does not create them but simply adds items.
* **Bookmarks** Equivalent to favorites.

If you are a developer and you are making a theme you can register your own list types.

## Reinventing the wheel

The plugin is being developed in an existing database, but the structure is such that it will be simple to pass the lists to a CPT.

## Screenshots

![Screenshot 1](screenshot-1.png?raw=true)
![Screenshot 2](screenshot-2.png?raw=true)
![Screenshot 3](screenshot-3.png?raw=true)
![Screenshot 4](screenshot-4.png?raw=true)
![Screenshot 5](screenshot-5.png?raw=true)
