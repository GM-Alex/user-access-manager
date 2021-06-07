=== User Access Manager ===
Contributors: GM_Alex
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1947052
Tags: admin, access, member area, members, member, member access, page, pages, post, posts, private, privacy, restrict, user, user access manager, user management
Requires at least: 4.7
Tested up to: 5.7
Stable tag: 2.2.15

With the "User Access Manager"-plugin you can manage the access to your posts, pages and files.

== Description ==

The “User Access Manager”-plugin for Wordpress allows you to manage the access of your content. This is useful if you need a member area, a private section at your blog or you want that other people can write at your blog but not everywhere. Including all post type (post, pages etc.), taxonomies (categories etc.) and files by creating user groups. Just assign the content you want to restrict und and your registered users which should have a access to a group. From now on the content is only accessible and writable for the specified group.

<em>Feature list</em>

* User groups
* Set separate access for readers and editors
* Set access by user groups
* Set default user groups
* Set time based access
* User-defined post type (posts, pages etc.) title (if no access)
* User-defined post type (posts, pages etc.) text (if no access)
* Optional login form (if no access)
* User-defined comment text (if no access)
* Hide complete post types (posts, pages etc.)
* Hide elements in the navigation
* Redirecting users to other pages (if no access)
* Recursive locking of content
* Limited access to uploaded files
* Full integrated at the admin panel
* Multilingual support
* Also protect your rss feeds
* Give access by IP-address
* Plugin-Api to use the User Access Manager in your on plugins or extend other plugins
* [UAMPPE](https://wordpress.org/plugins/user-access-manager-private-public-extension/) like behaviour is now build in (Expect negation like !groupName and showprivate and shownotauthorized parameter)

<em>Included languages</em>

See [https://translate.wordpress.org/projects/wp-plugins/user-access-manager](https://translate.wordpress.org/projects/wp-plugins/user-access-manager)

The documentation can be found here: [https://github.com/GM-Alex/user-access-manager/wiki](https://github.com/GM-Alex/user-access-manager/wiki)
Please report bugs and feature requests here: [https://github.com/GM-Alex/user-access-manager/issues](https://github.com/GM-Alex/user-access-manager/issues)
If you are a developer and want to contribute please visit [https://github.com/GM-Alex/user-access-manager](https://github.com/GM-Alex/user-access-manager)
For general questions, like how to setup, best practice and so on please use the support thread here (don't post issues here): [https://wordpress.org/support/plugin/user-access-manager](https://wordpress.org/support/plugin/user-access-manager)
To stay up-to-date follow me on twitter: [GMAlex on Twitter](http://twitter.com/GM_Alex)


== Installation ==

1. Upload the full directory, with the folder, into your wp-content/plugins directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enjoy


== Changelog ==

Here you found the changes in each version.

    Version     Date        Changes

    2.2.15      2021/06/07  Fix possible type issues.

    2.2.14      2021/04/21  Fix bulk edit.
                            Fix permission issue.
                            Fix redirect typing issue.

    2.2.13      2021/04/15  Fix not logged in user handling.
                            Type fix for showEditLink.

    2.2.12      2021/04/14  Fix warning.
                            Fix jquery deprecation warning.

    2.2.11      2021/03/15  Type fix for showEditLink.
                            Set compatibility tag for wordpress 5.7.

    2.2.10      2021/02/26  Lower minimum php version to 7.2.

    2.2.9       2021/02/24  Type fixes for Yoast seo plugin.
                            Fix some other type issues.

    2.2.8       2021/02/21  Some cleanup.
                            Type fixes for the short controller.
                            Fix some possible php notices and warnings.

    2.2.7       2021/02/14  Fix issue with isAdmin.

    2.2.6       2021/02/14  Fix more possible type errors.

    2.2.5       2021/02/14  Fix more possible type errors if wordpress switches int to string.

    2.2.4       2021/02/13  Fix type error for user object controller.
                            Fix type error for the wordpress wrapper.

    2.2.3       2021/02/13  Fix warning at settings page.
                            Fix type error for user group handler.

    2.2.2       2021/02/13  Fix type error with login form.

    2.2.1       2021/02/13  Fix type error with registeredTaxonomy.

    2.2.0       2021/02/11  Improve and update code base.
                            Fix possible update bug.

    2.1.12      2019/01/08  Fix multi site file handling issue. #222

    2.1.11      2018/03/16  Fix missing got_mod_rewrite function. #212
                            Fix wrong media urls. #207
                            Fix wrong post counts at the admin panel.
                            Improve database updates/repairs.


    2.1.10      2017/12/15  Fix Posts removed from WP_Query results in Ajax requests for unprivileged users #176
                            Improve partly download handler
                            Suppress errors on file delivery
                            Improve error handling
                            Change blog switch handling. fixes #194
                            Fix wrong parameter name. fixes #191
                            Improve user group sorting. closes #180
                            Add new redirect type to the login page. closes #155
                            Add dynamic user groups for exclude object calculation. closes #181
                            Add filter for getUserUserGroupsForObjectAccess. closes #157
                            Make it possible to set a default group for new media files.

    2.1.9       2017/11/18  Add show content before <!--more--> tag option for all post types
                            Fix js time input issue
                            Fix small prepare query issue
                            Don't switch to edit mode after user group creation #171
                            Add sortable columns for user groups list #172
                            Show no access image again instead of broken image
                            Fix double user group form for media files
                            Add option to hide the edit page option if the user doesn't have the right to edit this page #174

    2.1.8       2017/11/07  Add getter for the user group handler fixes #160
                            Fix multi site file handling issue #159

    2.1.7       2017/11/01  Improve database update prompt fixes #153
                            Fix user group selection not saved when adding a new user issue fixes #154
                            Add none read access option for user groups closes #144
                            Extend file settings
                            Add xsendfile support

    2.1.6       2017/10/20  Fix "Inherited group membership for media attachments" issue #133
                            Fix traits strict warning #145

    2.1.5       2017/10/12  Refactor traits
                            Fix datetime issue with not supported browsers

    2.1.4       2017/10/10  Fix strict warnings #134
                            Fix "Unable to select default user group for both page and post" issue #126
                            Add NGINX reverse proxy handling #117
                            Improve code
                            Fix bulk edit issue #135
                            Fix array filly issue #138
                            Add database repair tool

    2.1.3       2017/09/18  Possible fix for custom post types / taxonomies issues
                            Fix .htaccess deletion issue

    2.1.2       2017/09/17  Fix issue with not handled taxonomies #123

    2.1.1       2017/09/16  Fix issue for users with small innodb page size #121

    2.1.0       2017/09/15  Refactor code
                            Implement content tags for partly restriction like UAMPPE #84
                            Add default user groups
                            Add time based access
                            Add dynamic user groups #64
                            Implement caching #75
                            Allow html as no access content #63 #93
                            Optimize settings screen #61
                            Support of partial download #118
                            Add option to toggle visibility of assigned groups text next to the edit link #111
                            Fix incorrectly retrieving of a ID for attachments/media #116

    2.0.13      2017/07/26  Add ipv6 support - Fix issue #97
                            Change "Hide Comments" logic - Fix issue #100
                            Fix rewrite base issue - Fix issue #107 and #103
                            Fix vsprintf warning - Fix issue #105
                            Fix Relevanssi compatibility issue
                            Fix missing feature picture if file locking is disabled

    2.0.12      2017/06/16  Fix media file group issue #74
                            Fix broken file includes / excludes
                            Fix wordpress filter issue
                            Fix access to own post #95

    2.0.11      2017/05/18  Fix FrontendController::postsPreQuery() expected to be a reference, value given error

    2.0.10      2017/05/17  Fix issue of not saved groups #74
                            Adjust password lost link #79
                            Improve attachment access checks #78
                            Fix not working redirect

    2.0.9       2017/05/13  Improve security #76

    2.0.8       2017/05/08  Fix missing rss feed if protection is disabled #68

    2.0.7       2017/05/07  Fix category tree issues #59
                            Fix mysql database errors on strict mode #60
                            Fix possible path issue
                            Remove settings for post_format post type (which is used for internal propose)
                            Fix wrong database update prompt
                            Fix issue with asgaros from #66

    2.0.6       2017/04/28  Improve http error codes #24
                            Fix wrong url on windows servers #53
                            Fix missing install routine #51

    2.0.5       2017/04/27  Fix switch_to_blog issue
                            Fix file handling issues

    2.0.4       2017/04/26  Adjust code so that php version is checked before throwing a fatal error
                            Improve login form #26
                            Fix soliloquy slider incompatibility #31
                            Improve performance #41

    2.0.3       2017/04/25  Fix compatibility issue with CMS Tree Page View plugin #37
                            Fix compatibility issue with SEO Redirection plugin #38
                            Fix Error thrown: Class 'WP_Site_Query' not found issue #36

    2.0.2       2017/04/24  Fix wp_get_current_user() error #34
                            Fix custom post save issue after database update #33

    2.0.1       2017/04/23  Fix dbDelta issue on activation

    2.0.0       2017/04/23  Refactoring of the module
                            Improve performance
                            Full support for custom post and taxonomies.
                            Use translate.wordpress.org for translations
                            Many improvements and fixes

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

See: [https://github.com/GM-Alex/user-access-manager/wiki/FAQ](https://github.com/GM-Alex/user-access-manager/wiki/FAQ)

== Screenshots ==

1. The group manger.
2. The setting screen
3. The setup screen.
4. Integration into post overview.
