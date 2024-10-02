=== VK Google Job Posting Manager ===
Contributors: vektor-inc,kurudrive,naoki0h,una9,rickaddison7634
Donate link:
Tags: Google Job Posting, Recruitment, Gutenberg.
Requires at least: 6.3
Tested up to: 6.6
Stable tag: 1.2.18
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin generates JSON-LD of your recruitment info which required to register Google Job Posting.

== Description ==

This is the job posting manager plugin designed to work with Google Job Posting.
It mainly has tow functions.

[ Generation of JSON-LD ]

This plugin generates JSON-LD of your recruitment info to register Google Job Posting.
While this plugin will generate JSON-LD, it doesn't guarantee your recruitment info will display on Google Job Posting.
Because the Google Job Posting algorithm is not public.

[ Blocks ]
You can also display your recruitment information by using Gutenberg custom block.
We prepare some styles, you can choose what you prefer to.

[ Custom Fields to enter recruitment info ]
You can enter your recruitment info via each post's custom fields, or you can use common fields in 'Settings' > 'VK Job Posting Settings'.
Once you fill out the common fields, you don't need to fill duplicated info in each post such as company name, logo, and website.
You can overwrite common fields value by fill out each post's custom fields.

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. You can configure settings by 'Settings' > 'VK Job Posting Settings' in WordPress

== Screenshots ==

1. Enter your recruitment information via custom fields.
2. Choose custom block to display the information.
3. Choose table styles that you prefer to.
4. You can use common fields to omit to enter duplicated information.

== Changelog ==

= 1.2.18 =
* [ Outer ] Add PHP Require 7.4

= 1.2.17 =
* [ Bug fix ] Update Custom Field Builder 0.2.3

= 1.2.16 =
* [ Bug fix ] WordPress 6.6 Compatible

= 1.2.15 =
* [ Other ] Update custom field builder 0.2.2

= 1.2.14 =
* [ Bug fix ] fix json error in case of empty salayy fields

= 1.2.13 =
* [ Bug fix ] fix json error in case of no Direct Apply

= 1.2.12 =
* [ Bug fix ] fix json error in case of no Direct Apply

= 1.2.11 =
* [ Bug fix ] Fix Syntax Error

= 1.2.1 =
* [ Bug fix ] Fix Syntax Error

= 1.2.0 =
* Add applicantLocationRequirements ( Country Only ) & directApply Setting

= 1.1.9 =
* version only

= 1.1.8 =
* [ Bug fix ] JSON LD

= 1.1.7 =
* [ Bug fix ] Cope with WordPress 5.8

= 1.1.2 =
* [ Bug fix ] Description html escaped

= 1.1.1 =
* [ Bug fix ] Translation first aid

= 1.1.0 =
* Add Field ( Per Day / Per Week )
* [ Bug fix ] php7.4 error

= 1.0.0 =
* Add the identifier input form.
* Removed Incentive Compensation, Salary Raise, Work Hours, Experience Requirements, and Special Commitment input form for optimization.
* Fix bugs.

= 0.6.0 =
[ Add filter ] Add Job info table html tag filter and more...

= 0.5.3 =
* Change $prefix #9

= 0.5.2 =
* [ bugfix ][ common setting ] specialCommitments form don't saved fix

= 0.5.1 =
* [ bugfix ][ common setting ] specialCommitments form

= 0.4.0 =
* Add remote work support
* Add currency
* Add description
* Change screenshot_

= 0.3.1 =
Add language file

= 0.3.0 =
All Update

= 0.2.0 =
[ bug fix ] single page custom field the_content filter.
[ Design tuning ] Setting Page design tuning.

= 0.1.0 =
First release
