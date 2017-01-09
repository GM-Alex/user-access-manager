=== User Access Manager ===
Contributors: GM_Alex
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1947052
Tags: admin, access, member area, members, member, member access, page, pages, post, posts, private, privacy, restrict, user, user access manager, user management
Requires at least: 3.4.0
Tested up to: 4.7.0
Stable tag: 1.2.14

With the "User Access Manager"-plugin you can manage the access to your posts, pages and files.

== Description ==

With the "User Access Manager"-plugin you can manage the access to your posts, pages and files. You only create a user group, put registered users to this and set up the rights for this group. From now on the post/page is only accessible and writable for the specified group. This plugin is useful if you need a member area or a private section at your blog or you want that other people can write at your blog but not everywhere.

Appeal: If it works click the "Works" button, if it don't works click the "Broken" button and write me. That is the only way to find bugs and see if the plugin works proper. Thanks.

<em>Feature list</em>

* User groups
* Set separate access for readers and editors
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
* Multilingual support
* Also protect your rss feeds
* Give access by IP-address
* Plugin-Api to use the User Access Manager in your on plugins or extend other plugins

<em>Included languages</em>

* Czech, based on 1.1.1.2. Thanks to Jan
* Danish, based on 1.1.1.2. Thanks to GeorgWP
* English
* Farsi, based on 1.1.2. Thanks to Hadi Mostafapour
* French, based on 0.8.0.2. Thanks to macbidule
* German
* Hungary, based on 1.0.2. Thanks to Zina
* Italian. Thanks to Diego Valobra
* Russian, based on 1.1.2. Thanks to PoleeK
* Spanish, based on 1.1.2. Thanks to Juan Rodriguez
* Swedish, based on 1.1. Thanks to Konsult
* Turkish, based on 1.1.2. Thanks to Mesut Soylu
* Polish, based on 1.2. Thanks to Piotr Kaczynski


Some language files are really old, if you are a native speaker it would be nice if you update a language file or make one for a language which is not translated yet.

To stay up-to-date follow me on twitter: [GMAlex on Twitter](http://twitter.com/GM_Alex)


== Installation ==
 
1. Upload the full directory, with the folder, into your wp-content/plugins directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enjoy


== Changelog ==

Here you found the changes in each version.

    Version     Date        Changes

    1.2.14      2017/01/07  Fix IP access issue.
                            Some small optimization.

    1.2.13      2017/01/04  Fix tag count issue.

    1.2.12      2017/01/03  Fix tag issue.
                            Improve usability of the setup page.

    1.2.11      2017/01/02  Restore hide categories behaviour.

    1.2.10      2017/01/02  Improve mysql query to prevent against possible collations issue.

    1.2.9       2017/01/01  Fix wrong category count.
                            Hide pages without access at the administration panel.
                            Fix redirect problems.

    1.2.8       2016/12/31  Fix wrong term behavior for the backend.
                            Require at least PHP 5.3

    1.2.7.6     2016/12/28  Fix ip restriction issue.
                            Fix missing categories issue.

    1.2.7.5     2016/12/27  Improve taxonomy handling.
                            Improve performance.
                            Add missing wp-cli.php file.
                            Refactor config handling.
                            Fix Yoast SEO Plugin issue.
                            Some other small fixes.

    1.2.7.4     2016/12/08  Fix some issues related to Wordpress 4.7

    1.2.7.3     2016/12/08  Fix empty category issue.

    1.2.7.2     2016/12/07  Hot fix for Wordpress 4.7

    1.2.7.1     2016/12/04  Remove deprecated function get_currentuserinfo

    1.2.7.0     2016/11/30  Add experimental nginx support
                            Fix redirect issue (Thanks to Laurence Shaw for the hint and James Riordon for the fix)

    1.2.6.10    2016/10/07  Again a security fix

    1.2.6.9     2016/10/06  Security fix

    1.2.6.8     2016/08/15  Security fix
                            Add Yoast SEO Plugin filter. Thanks to Christian Werner

    1.2.6.7     2016/03/17  Security fix

    1.2.6.6     2015/03/09  Hide UAM at the backend from normal users.
                            Change IP range logic. Thanks to Takanashi
                            Fix issue with roles.

    1.2.6.5     2015/03/02  Fix install/update missing table issue.

    1.2.6.4     2015/02/26  Fix database update issue.

    1.2.6.3     2015/02/25  Fix database update for multi sites.
                            Fix array_keys warning.
                            Fix redirect loop.

    1.2.6.2     2015/02/24  Fix not removable role affiliation issue.

    1.2.6.1     2015/02/24  Remove deprecated php function mysql_get_server_info().
                            Change object_id from varchar 11 to varchar 255.
                            Fix backslashes issue.

    1.2.6.0     2015/02/11  Remove unnecessary js library.
                            Merge changes from https://github.com/nwoetzel/user-access-manager. Thanks for contribution.

    1.2.5.0     2013/12/13  Improve performance. Thanks to all testers.
                            Add bulk edit feature.

    1.2.4.3     2013/05/08  Fix redirect if page is hidden and permalink is active issue.
                            Fix no access issue.

    1.2.4.2     2013/05/08  Fix pagination issue. Thanks to arjenbreur.
                            Fix comment issue. Thanks to jpr105.
                            Redirect if post/page is hidden and permalink is active.
                            Change icon.

    1.2.4.1     2013/05/06  Fix broken images issue.
                            Fix duplicated key issue.
                            Filter file types.
                            Use wordpress mime types instead of the config array.

    1.2.4       2013/05/01  Fix add column issue.
                            Fix install bug.
                            Fix error if no user is logged in. Thanks to Robert Egger and akiko.pusu.
                            Fix media file issue.
                            Optimize code.

    1.2.3.1     2013/05/01  Fix terms issue.

    1.2.3       2013/04/30  Fix Fatal 'error: Call-time pass-by-reference' error.
                            Fix 'get_userdata() is not defined' error. Thanks to ranwaldo.
                            Refactor many variable names to fit new coding style.

    1.2.2       2011/04/03  Speed improvements.
                            Fix get_term bug.
                            Fix category bug
    
    1.2.1       2011/10/11  Fix uninstall bug.
                            Add capability 'manage_user_groups'. Thanks to Tim Okrongli
                            Some small improvements.
    
    1.2         2011/06/19  Add custom post types.
                            Fix CSRF issue. Thanks to Luke Crouch
    
    1.1.4       2010/10/19  Fix error on custom post types
                            Add warning for PHP version lower than 5.0
    
    1.1.3       2010/10/09  Add tag filter
                            Fix media file bug for multisites
                            Fix ip access bug
                            Improve redirecting code
                            Speed up
    
    1.1.2       2010/10/04  Fix read/write access bug
                            Add recursive looking for posts page option
                            Improve network activation/update
                            Add hooks for login bar
                            Prepare for NextGEN Gallery extension
    
    1.1.1.2     2010/09/29  Fix bug that a usergroup for a user wasn't saved
    
    1.1.1.1     2010/09/29  Fix T_DNUMBER bug
    
    1.1.1       2010/09/29  Fix custom menu bug
                            Fix quick edit bug
                            Fix undefined object bug
                            Extend admin hint
    
    1.1         2010/09/28  Add plugin api
                            Optimized code
                            Improve content filtering
                            Fix bug that user groups can't removed from element
                            Fix empty category bug
                            Fix pre/next post bug
                            Fix login form bug
                            Fix redirect bug
                            Fix some other small bugs
    
    1.0.2       2010/07/24  Remove debugging code
                            Fix file download bug
    
    1.0.1       2010/07/24  Fix bug that only one user can be a member of a user group
                            Fix bug for recursive locking for categories
    
    1.0         2010/07/22  Fix array_merge bug at media page
                            Reorder some admin options
                            Add some new admin functions
                            Speed it up
                            Disable file locking by default
    
    1.0 Beta 2  2010/07/13  Fix performance issues
                            Fix media gallery issues
                            Fix category bug
                            Fix not loaded translation
                            Remove some unused functions
    
    1.0 Beta    2010/07/09  Complete rewrite
                            Some new options to improve the functions
    
    0.9.1.4     2010/06/23  Hotfix for Wordpress 3.0
    
    0.9.1.3     2009/07/17  Fix "header already send"-Problem with Wordpress 2.8.1
                            Add option to set level with full access
    
    0.9.1.2     2009/03/29  Fix some path problems
                            Add a reset function
                            Rename menu at admin panel
    
    0.9.1.1     2009/03/26  Fix the empty category issue
    
    0.9.1       2009/03/26  Fix the database problem
                            Fix feed bug. Thanks to Markus Brinkmann
                            Fix wrong javascript path
    
    0.9         2009/03/24  Add login form
                            Add option to show text bevor <!--more--> tag
                            Fix write access issues
                            Fix file issues
                            Small fixes
    
    0.8.0.2     2009/03/09  Add French language file
                            Delete .htaccess files on deactivation/uninstall 
    
    0.8.0.1     2009/03/06  Small fix
    
    0.8         2009/03/05  Add write access control
                            Add support down to 2.6
                            Better file access control
                            Code optimization - Plugin became faster
                            Fix the category bug
                            Small fixes
    
    0.7.0.1     2009/02/13  Small fixes

    0.7         2009/02/13  Fix: All known Bugs of the beta
                            Add ajax for more comfort
                            Add language support
    
    0.7 Beta    2009/01/05  Fix: Problems with media at posts
                            Fix: Many other small fixes
                            Only support up to Wordpress 2.7
                            Better integration
    
    0.62        2008/12/18  Fix: Fatal error: Only variables can
                            be passed by reference. Thanks to David Thompson

    0.61        2008/12/17  Fix: Wrong file id in Media Library

    0.6         2007/12/14  First release.


== Frequently Asked Questions ==

<strong>How works the User Access Manager?</strong>

All posts/pages which are not in a user access group are accessible to all users. Posts/pages can put to groups by themselves, categories or recursive (most by pages).

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
2. The setting screen
3. The setup screen.
4. Integration into post overview.
