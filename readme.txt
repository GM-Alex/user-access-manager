=== User Access Manager ===
Contributors: GM_Alex
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1947052
Tags: user access manager, access, member area, user management, private, privacy, admin
Requires at least: 2.7
Tested up to: 2.7
Stable tag: 0.7 Beta

With the "User Access Manager"-plugin you can manage the access to your posts, pages and files.

== Description ==

With the "User Access Manager"-plugin you can manage the access to your posts, pages and files. You only create a user group, put registered users to this and set up the rights for this group. From now on the post/page is only accessible for the specified group. This plugin is useful if you need a member area or a private section at your blog.

<strong style="color:red;">Important:</strong> This release is a beta release. If you want to help me or test the new version use this one. But I think it's much better than version 0.62. Please report me bugs if you found any.

<strong>Note:</strong> At this release you have to replace some core files, because I need some hooks which are not created by the Wordpress-Team for the better integration. I've send them a diff patch and this will add in Wordpress 2.8, so you can trust this modification (see <a href="http://trac.wordpress.org/changeset/10292">http://trac.wordpress.org/changeset/10292</a>).

<em>Feature list</em>
* User groups
* Set access by user groups
* Set access by post categories
* User-defined post/page title (if no access)
* User-defined post/page text (if no access)
* Hide complete post/page
* Hide pages in navigation
* Redirecting users to other pages (if no access)
* Recursive locking of posts/pages
* Limited access to uploaded files
* Full integrated at the admin panel

For a German description visit my page at [GMAlex](http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/ "GMAlex - deviant design and development"). If you have any problem or suggestions please [contact me](mailto:alexanderschneider85@googlemail.com).


== Installation ==
 
1. Upload the full directory into your wp-content/plugins directory
1. Replace the following files in the wp-admin dir with the files at 'core_files' or use the patches: 'categories.php', 'edit-category-form.php', 'includes/template.php'
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enjoy


== Contributors/Changelog ==

    Version	Date      	Changes
    0.7		2000/01/05	Fix: Problems with media at posts
    					Fix: Many other small fixes
    					Only support up to Wordpress 2.7
    					Better integration
    
    0.62	2008/12/18	Fix: Fatal error: Only variables can 
    					be passed by reference. Thanks to David Thompson
    
    0.61	2008/12/17	Fix: Wrong file id in Media Library.

    0.6		2007/12/14	First release.





== Screenshots ==

1. The group manger.
2. The setting screen.
3. Integration at posts.
