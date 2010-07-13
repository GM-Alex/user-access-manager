=== User Access Manager ===
Contributors: GM_Alex
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1947052
Tags: admin, access, member area, members, member, member access, page, pages, post, posts, private, privacy, restrict, user, user access manager, user management
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 1.0 Beta 2

With the "User Access Manager"-plugin you can manage the access to your posts, pages and files.

== Description ==

With the "User Access Manager"-plugin you can manage the access to your posts, pages and files. You only create a user group, put registered users to this and set up the rights for this group. From now on the post/page is only accessible and writable for the specified group. This plugin is useful if you need a member area or a private section at your blog or you want that other people can write at your blog but not everywhere.

<strong>Note:</strong> This is the second public beta release of the User Access Manager 1.0. This version is a stable release, but there could be still some small bugs. I publish this version as beta because my aim is to release a bug free version 1.0. So please help me to find all bugs.

<em>Feature list</em>

* User groups
* Set seperate access for readers and editors
* Set access by user groups
* Set access by post categories
* User-defined post/page title (if no access)
* User-defined post/page text (if no access)
* Optional login form (if no access)
* User-defined comment text (if no access)
* Hide complete post/page
* Hide pages in navigation
* Redirecting users to other pages (if no access)
* Recursive locking of posts/pages
* Limited access to uploaded files
* Full integrated at the admin panel
* Multilanguage support
* Also proteced your rss feeds
* Give access by IP-address

<em>Included languages</em>

* English
* German
* Czech, based on 0.7.0.1. Thanks to Huska
* French, based on 0.8.0.2. Thanks to macbidule

For a German description visit my page at [GMAlex](http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/ "GMAlex - deviant design and development"). If you have any problem, suggestions or make a translation please [contact me](mailto:alexanderschneider85@googlemail.com). To stay up-to-date follow me on twitter: [GMAlex on Twitter](http://twitter.com/GM_Alex)


== Installation ==
 
1. Upload the full directory, with the folder, into your wp-content/plugins directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enjoy


== Changelog ==

Here you found the changes in each version.

    Version		Date      	Changes
    
    1.0			2010/07/13	Fix perforance issues
    						Fix media gallery issues
    						Fix category bug
    						Fix not loaded translation
    						Remove some unused functions
    
    1.0 Beta	2010/07/09	Complete rewrite
    						Some new options to improve the functions
    
    0.9.1.4 	2010/06/23  Hotfix for Wordpress 3.0
    
    0.9.1.3		2009/07/17	Fix "header already send"-Problem with Wordpress 2.8.1
    						Add option to set level with full access
    
    0.9.1.2		2009/03/29	Fix some path problems
    						Add a reset function
    						Rename menu at admin panel
    
    0.9.1.1		2009/03/26	Fix the empty category issue
    
    0.9.1		2009/03/26	Fix the database problem
    						Fix feed bug. Thanks to Markus Brinkmann
    						Fix wrong javascript path
    
    0.9			2009/03/24	Add login form
    						Add option to show text bevor <!--more--> tag
    						Fix write access issues
    						Fix file issues
    						Small fixes
    
    0.8.0.2		2009/03/09	Add French language file
    						Delete .htaccess files on deactivation/uninstall 
    
    0.8.0.1		2009/03/06	Small fix
    
    0.8			2009/03/05	Add write access control
    						Add support down to 2.6
    						Better file access control
    						Code optimization - Plugin became faster
    						Fix the category bug
    						Small fixes
    
    0.7.0.1		2009/02/13	Small fixes
    
    0.7			2009/02/13	Fix: All known Bugs of the beta
    						Add ajax for more comfort
    						Add language support
    
    0.7 Beta	2009/01/05	Fix: Problems with media at posts
    						Fix: Many other small fixes
    						Only support up to Wordpress 2.7
    						Better integration
    
    0.62		2008/12/18	Fix: Fatal error: Only variables can 
    						be passed by reference. Thanks to David Thompson
    
    0.61		2008/12/17	Fix: Wrong file id in Media Library.

    0.6			2007/12/14	First release.


== Frequently Asked Questions ==

<strong>How works the User Access Manager?</strong>

All posts/pages which are not in a user access group are accessable to all users. Posts/pages can put to groupes by themselfe, categories or recursive (most by pages).

<strong>How dose "Role affiliation" work?</strong>

This example will give you an overview:

- Posts: Post1, Post2
- Users: User1 (admin), User2 (subscriber), User3 (subscriber)

Groups:

- Group1: Users: User2, User3 | Posts: Post1 | Role affiliation: subscriber
- Group2: Users: User3 | Posts: Post2 | Role affiliation: -

Access:

- Post1: User1 because he is a admin, User2 because he is in the group and a subscriber, User3 he is a subscriber (to this group all subscribers has access!)
- Post2: User1 because he is a admin, User3 because he is in the group (only group members has access because there is no role affiliation!) 

<strong>I get an login box "WP-Files", what can I do?</strong>

- You can deactivate the file locking at the UAM settings.
- You can replace your media files in your posts. Just delete the old link and insert it again. (A new working link will generated)


== Screenshots ==

1. The group manger.
2. The setting screen - Part 1.
3. The setting screen - Part 2.
4. The setting screen - Part 3.
5. The setup screen.
6. Integration into post overview.
7. Integration into the post page.
8. Admin hint at the blog frontend.