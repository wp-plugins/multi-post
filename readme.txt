=== Multi Post ===
Contributors: yianniy,yitg
Donate link: http://example.com/
Tags: wpmu,author,post,cross post
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 1.1

Allow a user to author a post accross multiple blogs in the same Multi-Site install.

== Description ==

Multi Post allows you to post to other sites withing a single multi-site Wordpress environment. As an other, you can post to other sites that a) you can post to and b) has the Multi Post plugin turned on.

The plugin adds a new metabox to the post authoring page. It is a list of sites (blogs) that can be cross posted to. Simply select the site(s) you wish to also post to and when you publish your new post, it will automatically be added to the other sites as well. Your post's tags will exist on all of the sites it goes to. Categories will be added if they exist in the other sites.

The Multi Post plugin also respects post updates. When a post is updated in one site, the updates go to all other sites selected in the metabox (the sites it is already posted to are preselected). If one of the sites is deslected, you have the option of deleting the post in that site or orphan it.

If someone other than the author edits a cross posted post, changes only occur in that one site, but the author receives an e-mail stating that a change was made and a link to that post. This give the author to option to update the post again, letting its changes go to all the other sites.

== Installation ==

1. Upload `multi-post` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What sites can I post to? =

Multi Posts will allow you to post to other sites on the same Wordpress install that is configured to support multiple sites. It will not let you post to other sites or blogs.

= Why can't I cross post to all the sites I belong to? =

Multi Post only allows you to post to other sites that also have Multi Post turned on. As the site's administrator to turn it on.

= What happens if I edit a post? =

When you update a post, the changes will go to all other sites it was posted to. (They are selected in the Multi Post metabox on the right.)

= What happens if I deselect a site in the Multi Post metabox when I update a post =

When you deselect a site from the Multi Post metabox, you are given the option to delete or orphan the post in that site. When you update the post,

* delete - will remove the post from the deselected site.
* orphan - will leave the post on the site, but disconnect it from the Multi Post system.

== Changelog ==

= 2.1.1 = 

* Fixes but with redirection to Signup Page. (wp-signup.php)

= 2.1 =

* Fixed bug that prevented new users from registering.

= 1.1 =
* Fixed some PHP Warnings
* Updated manner user capabilities are checked

= 1.0 =
* This is the first version.

== Upgrade Notice ==

= 1.1 =
Corrected code to remove warning being thrown by PHP. Updated how user capabilities are check to comply with current version of Wordpress.

= 1.0 =
This is the first version.