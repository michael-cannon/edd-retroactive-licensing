=== Easy Digital Downloads - Retroactive Licensing ===

Contributors: comprock
Donate link: http://aihr.us/about-aihrus/donate/
Tags: EDD, easy digital downloads, license
Requires at least: 3.6
Tested up to: 3.8.0
Stable tag: 1.2.0RC1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send out license keys and activation reminders to users who bought products through Easy Digital Downloads before software licensing was enabled.


== Description ==

Send out license keys and activation reminders to users who bought products through Easy Digital Downloads before software licensing was enabled.

= Primary Features =

* API
* Ajax-based retroactive license processing screen
* Automatically selects unlicensed products needing licenses
* Ensures required software is installed and active before running
* License provisioning email and configuration options
* License activation reminder email and configuration options
* Optional retroactive license processing or not
* Sends email to product buyer and admin after license generation
* Select which products to process retroactive licensing for

= Settings Options =

**Extensions**

* Contact Page Link - This is a feedback page for users to contact you.
* Allowed Products - These products have licensing enabled. Check the products you want retroactive licensing to work with.

**License Provisioning**

* Enabled? - Check this to enable licensing provision.

**License Reminders**

* Enabled? - Check this to enable sending reminders to activate licenses.

**Emails**

* Disable Licensing Notifications - Check this box if you do not want to receive emails when sales recovery attempts are made.

**License Provisioning**

* Licensing Subject
* Licensing Content
* Licensing Notification Subject

**License Reminders**

* Reminder Subject
* Reminder Content
* Reminder Notification Subject


== Installation ==

= Requirements =

* Plugin "[Easy Digital Downloads](http://wordpress.org/plugins/easy-digital-downloads/)" is required to be installed and activated prior to activating "Easy Digital Downloads - Retroactive Licensing".

= Install Methods =

* Download `edd-retroactive-licensing.zip` locally
	* Through WordPress Admin > Plugins > Add New
	* Click Upload
	* "Choose File" `edd-retroactive-licensing.zip`
	* Click "Install Now"
* Download and unzip `edd-retroactive-licensing.zip` locally
	* Using FTP, upload directory `edd-retroactive-licensing` to your website's `/wp-content/plugins/` directory

= Activation Options =

* Activate the "Easy Digital Downloads - Retroactive Licensing" plugin after uploading
* Activate the "Easy Digital Downloads - Retroactive Licensing" plugin through WordPress Admin > Plugins

= License Activatation =

1. Set the license key through WordPress Admin > Products > Settings > Licenses tab, EDD - Retroactive Licensing License Key field
1. License key activation is automatic upon clicking "Save Changes"

= Usage =

1. Read [Setup Retroactive Licensing for Easy Digital Downloads](https://aihr.us/easy-digital-downloads-retroactive-licensing/setup/)
1. Visit WordPress Admin > Products > Settings
1. Configure and save via Emails' tab settings
1. Configure and save via Extensons' tab settings
1. Make sure to check allowed products and enable license provisioning
1. Visit WordPress Admin > Products > Retroactive Licensing
1. Click button "Perform EDD Retroactive Licensing"
1. Viola, all done!

= Upgrading =

* Through WordPress
	* Via WordPress Admin > Dashboard > Updates, click "Check Again"
	* Select plugins for update, click "Update Plugins"
* Using FTP
	* Download and unzip `edd-retroactive-licensing.zip` locally
	* Upload directory `edd-retroactive-licensing` to your website's `/wp-content/plugins/` directory
	* Be sure to overwrite your existing `edd-retroactive-licensing` folder contents


== Frequently Asked Questions ==

= Most Common Issues =

* [Setup Retroactive Licensing for Easy Digital Downloads](https://aihr.us/easy-digital-downloads-retroactive-licensing/setup/)
* Got `Parse error: syntax error, unexpected T_STATIC, expecting ')'`? Read [Most Aihrus Plugins Require PHP 5.3+](https://aihrus.zendesk.com/entries/30678006) for the fixes.
* [Debug common theme and plugin conflicts](https://aihrus.zendesk.com/entries/25119302)

= Still Stuck or Want Something Done? Get Support! =

1. [Easy Digital Downloads - Retroactive Licensing Knowledge Base](https://aihrus.zendesk.com/categories/20133716) - read and comment upon frequently asked questions
1. [Open Easy Digital Downloads - Retroactive Licensing Issues](https://github.com/michael-cannon/edd-retroactive-licensing/issues) - review and submit bug reports and enhancement requests
1. [Easy Digital Downloads - Retroactive Licensing Support Forum](TBD) - review responses and ask questions
1. [Contribute Code to Easy Digital Downloads - Retroactive Licensing](https://github.com/michael-cannon/edd-retroactive-licensing/blob/master/CONTRIBUTING.md) - [request access](http://aihr.us/contact-aihrus/)
1. [Beta Testers Needed](http://aihr.us/become-beta-tester/) - get the latest Easy Digital Downloads - Retroactive Licensing version


== Screenshots ==

1. Easy Digitial Downloads - Retroactive Licensing Processer
2. Retroactive license created and sent
3. Licensing provision enabled - WP Admin > Downloads > Settings, Extensions tab
4. Retroactive Licensing email
5. Email settings - Retroactive Licensing for Easy Digital Downloads
6. License entry - Retroactive Licensing for Easy Digital Downloads
7. Plugin entry - Retroactive Licensing for Easy Digital Downloads

[gallery]


== Changelog ==

See [Changelog](http://aihr.us/easy-digital-downloads-retroactive-licensing/changelog/)


== Upgrade Notice ==

= 1.0.0 =

* Initial release


== Notes ==

TBD


== API ==

* Read the [EDD Retroactive Licensing API](http://aihr.us/easy-digital-downloads-retroactive-licensing/api/).


== Localization ==

You can translate this plugin into your own language if it's not done so already. The localization file `edd-retroactive-licensing.pot` can be found in the `languages` folder of this plugin. After translation, please [send the localized file](http://aihr.us/contact-aihrus/) for plugin inclusion.

**[How do I localize?](https://aihrus.zendesk.com/entries/23691557)**
