=== User Access Manager ===
Contributors: GM_Alex
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1947052
Tags: admin, access, member area, members, page, pages, post, posts, private, privacy, restrict, user, user access manager, user management
Requires at least: 2.6
Tested up to: 2.7.1
Stable tag: 0.8.0.2

With the "User Access Manager"-plugin you can manage the access to your posts, pages and files.

== Description ==

With the "User Access Manager"-plugin you can manage the access to your posts, pages and files. You only create a user group, put registered users to this and set up the rights for this group. From now on the post/page is only accessible and writable for the specified group. This plugin is useful if you need a member area or a private section at your blog or you want that other people can write at your blog but not everywhere.

<em>Feature list</em>

* User groups
* Set seperate access for readers and editors
* Set access by user groups
* Set access by post categories
* User-defined post/page title (if no access)
* User-defined post/page text (if no access)
* User-defined comment text (if no access)
* Hide complete post/page
* Hide pages in navigation
* Redirecting users to other pages (if no access)
* Recursive locking of posts/pages
* Limited access to uploaded files
* Full integrated at the admin panel
* Multilanguage support
* Also proteced your rss feeds

<em>Included languages</em>

* English
* German
* Czech, base on 0.7.0.1. Thanks to Huska
* French, thanks to macbidule

<strong>Note</strong>: At this release you can replace one core files, because I need some hooks which are not created by the Wordpress-Team for the better integration. I've send them a diff patch and this will add in Wordpress 2.8, so you can trust this modification (see <a href="http://trac.wordpress.org/changeset/10292">http://trac.wordpress.org/changeset/10292</a>). <strong>This core modification is not needed and only optional, but it gives you more comfort.</strong>

For a German description visit my page at [GMAlex](http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/ "GMAlex - deviant design and development"). If you have any problem, suggestions or make a translation please [contact me](mailto:alexanderschneider85@googlemail.com).
== Installation ==
 
1. Upload the full directory, with the folder, into your wp-content/plugins directory
1. Replace the following files in the wp-admin dir with the files at 'core_files' or use the patches: 'includes/template.php'
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enjoy


== Changelog ==

Here you found the changes in each version.

    Version		Date      	Changes
    
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





== Screenshots ==

1. The group manger.
2. The setting screen.
3. Integration at posts.
