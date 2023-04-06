=== AntiSpam for Contact Form 7 ===
Contributors: codekraft
Tags: antispam, blacklist, honeypot, geoip, security, contact form 7
Requires at least: 5.4
Tested up to: 6.2
Requires PHP: 5.6
Stable tag: 0.4.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A trustworthy antispam plugin for Contact Form 7. Simple but effective.

== Description ==
The antispam you're using isn't working well, is it? Maybe because it's not using the correct method*** to stop the type of bot that's attacking you, but I think I have a solution!
Antispam for Contact Form 7 is a free plugin for Contact Form 7 that blocks bots from flooding your mailbox, without tedious configuration and without captcha (which usually causes loss of conversions and sometimes are blocking for real users).
To do this we use different in and off page bots traps and an auto-learning mechanism based on a statistical "Bayesian" spam filter called B8.
CF7-AntiSpam works well and adds some functionalities to [Flamingo](https://wordpress.org/plugins/flamingo/). If both are installed Flamingo will get some additional controls and an additional dashboard widget will be enabled.

== SETUP ==
**Basic** - install & go! No Configuration / keys / registrations required to get the antispam protection. In this case only some protections may be enabled like fingerprinting, language checks and honeypots.
**Advanced** - CF7A needs to parse the input message field of your form to analyze properly the email content with its dictionary.
So you need to add a "marker" to "notify" the antispam to check this field (you need to do this for each contact form of your website)
so you need to add 'flamingo_message: "[your-message]"' for each additional settings panel of each contact form you need to secure. The method is the same as you use with [Flamingo](https://contactform7.com/save-submitted-messages-with-flamingo/).
I know, this is boring but is **required for advanced text statistical analysis**, without this B8 filter will couldn't be enabled.
**GeoIP** - (optional) Enable this functionality if you need to restrict which countries (or languages) can email you and which cannot.
In order to enable GeoIp you need to agree GeoLite2 End User License Agreement and sign up GeoLite2 Downloadable Databases, in this way you will obtain the key requested to download the database.
To find out more, read the information in the dedicated section of the cf7-antispam plugin settings and follow the steps.

== Antispam Available Tests ==
✅ Browser Fingerprinting
✅ Language checks (Geo-ip, http headers and browser - cross-checked)
✅ Honeypot
⚠️Honeyform*
✅ DNS Blacklists
✅ Blacklists (with automatic ban after N failed attempts, user defined ip exclusion list)
✅ Hidden fields with encrypted unique hash
✅ Time elapsed (with min/max values)
✅ Prohibited words in message/email and user agent
✅ B8 statistical "Bayesian" spam filter
✅ Identity protection

== Extends Flamingo and turns it into a spam manager! ==
In this way you will be able to review emails and "teach" to B8 what is spam and what is not (might be useful in the first times if some mail spam pass through).
And if you already use Flamingo? Even better! But remember, to add 'flamingo_message: "[your-message]"' to advanced settings (as you do for the other flamingo labels) before activation (or checkuot advanced options "rebuild dictionary").
While activating CF7A all previous collected mail will be parsed and B8 will learn and build its vocabulary. In this way you will start with a pre-trained algorithm. Super cool!
Notes:
- On the right side of Flamingo inbound page I've added a new column that show the mail spamminess level
- if you unban an email in the flamingo "inbound" page the related ip will be removed from blacklist. But if you mark as spam the mail the ip will be not blacklisted again.
- Before activate this plugin please be sure to mark all spam mail as spam in flamingo inbound, in this way the B8 algorithm will be auto-trained
- Don't delete a spam message from ham if you receive it, rather put it in spam to teach B8 how to recognise the difference!

== B8 statistical "Bayesian" Filter ==
Originally created by [Gary Robinson](https://en.wikipedia.org/wiki/Gary_Robinson) [b8 is a statistical "Bayesian"](https://www.linuxjournal.com/article/6467) spam filter implemented in PHP.
The filter tells you whether a text is spam or not, using statistical text analysis. What it does is: you give b8 a text and it returns a value between 0 and 1, saying it's ham when it's near 0 and saying it's spam when it's near 1. See [How does it work?](https://nasauber.de/opensource/b8/readme.html#how-does-it-work) for details about this.
To be able to distinguish spam and ham (non-spam), b8 first has to learn some spam and some ham texts. If it makes mistakes when classifying unknown texts or the result is not distinct enough, b8 can be told what the text actually is, getting better with each learned text.
This takes place on your own server without relying on third-party services.
More info: [nasauber.de](https://nasauber.de/opensource/b8/)

== Identity protection ==
To fully protect the forms, it may be necessary to enable a couple of additional controls, because bots use the public data of the website to spam on it.
- The first is user related and denies those who are not logged in the possibility of asking (sensitive) information about the user via wp-api and the protection for the xmlrpc exploit wordpress.
- The second one is the WordPress protection that will obfuscate sensitive WordPress and server data, adding some headers in order to enhance security against xss and so on.
Will be hidden the WordPress and WooCommerce version (wp_generator, woo_version), pingback (X-Pingback), server (nginx|apache|...) and php version (X-Powered-By), enabled xss protection headers (X-XSS-Protection), removes rest api link from header (but it will only continue to work if the link is not made public).

== Privacy Notices ==
AntiSpam for Contact Form 7 only process the ip but doesn't store any personal data, but anyway it creates a dictionary of spam and ham words in the wordpress database.
This database may contain words that are in the e-mail message, so can contain also personal data. This data can be "degenerated" that means the words that were in the e-mail might have been changed.
The purpose of this word collecting is to build a dictionary used for the spam detection.

== Installation ==
1. Upload the entire `cf7-antispam` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress, you MUST have Contact Form 7 installed and enabled.
3. Setup advanced settings in Contact Form 7 in the same way you do for flamingo, but add also 'flamingo_message: "[your-message]"' - reference https://contactform7.com/save-submitted-messages-with-flamingo/
4. The configuration page for this plugin is located in the submenu "Antispam" under the Contact Form 7 menu

== Support ==
Community support: via the [support forums](https://wordpress.org/support/plugin/contact-form-7-antispam/) on wordpress.org
Bug reporting (preferred): file an issue on [GitHub](https://github.com/erikyo/contact-form-7-antispam)

= Contribute =
We love your input! We want to make contributing to this project as easy and transparent as possible, whether it's:

* Reporting a bug
* Testing the plugin with different user agent and report fingerprinting failures
* Discussing the current state, features, improvements
* Submitting a fix or a new feature

We use GitHub to host code, to track issues and feature requests, as well as accept pull requests.
By contributing, you agree that your contributions will be licensed under its GPLv2 License.

My goal is to create an antispam that protects cf7 definitively without relying on external services. And free for everyone.
if you want to help me, [GitHub](https://github.com/erikyo/contact-form-7-antispam) is the right place 😉

== Frequently Asked Questions ==

=Will I finally be 100% protected from spam?=

NO, nobody can guarantee that, and anyone who tells you that is lying. But luckily, bots are limited by the fact that they don't use a real browser and they use fairly repetitive routes which can be recognised.

=Why I need to install Flamingo to get the full AntiSpam manager functionalities?=

Contact form 7 is made this way, the main plugin is made to be extended with other modules and this has resulted in many 3rd party plugins like mine! There is already a module for handling received emails, why should I redo it? And, in this way I can focus on my plugin, I believe the "power" of cf7 is just that and I invite you to check how many other nice and free extensions there are!

=Why are there so many antispam-tests?=

Because there are so many types of bots in this way detect them all!

Phantom-based bots fail with fingerprinting but are proficient with honeypots, while bots written in python fail with honeypots but are proficient with metadata forgery!

=How spam score works=

The system used to evaluate the e-mail is a non-proportional scoring system and each test have a different score (and can be customised with the advanced settings). When the mail score is equal to or greater than 1 it is considered spam.

=What do you mean by Standard Spam Filters=

Some standard test are Elapsed time, Auto-Blacklisting, Prohibited IP/strings and, in addition, we got some advanced test like HoneyPots, HoneyForms and the browser FingerPrinting.

=*HoneyForm, or you mean Honeypot?=

No, I mean HoneyForm! This is a hidden, bogus form that bots will fill, as it is part of the page code for them and they rarely check the visibility of an element. While honeypots can be easily spotted by some bots, these forms are not because they have the same characteristics as a 'normal' form, and it is impossible to distinguish them without truly visiting the page.

This is the first time they have been used, at the moment they seem to work and be effective, but consider this an experimental feature! (ps let me know your feedback about)

=But the standard Honeypot?=

We also have honeypots, to activate them just click on a checkbox, and they will be generated automatically for each text field. The only thing you need to check in the CF7A options page is the name of the fields used that need to differ with the names used in contact form 7.

=DNSBL... What?=

After that the sender ip will be searched into *DNS-based Black-hole server* to found if that ip is delisted for spam. 10 server are already set as default, but you can add or remove as you like, there are 50 server available (list below).

=What is B8? How it works?=

B8 cuts the text to classify to pieces, extracting stuff like email addresses, links and HTML tags and of course normal words. For each such token, it calculates a single probability for a text containing it being spam, based on what the filter has learned so far. B8 is a free software form Tobias Leupold, who I thank for making it available to everyone.

=Filters=

Before processing the email - `add_filter('cf7a_message_before_processing', 'my_message_before_processing', 10, 2 );`

Before processing the email with bayesian filter `add_filter('cf7a_before_b8', 'my_before_b8', 10, 3 );`

Add your own spam filter `add_filter('cf7a_additional_spam_filters', 'my_additional_spam_filters', 10, 3 );`

Add some content when resending a mail (useful to add a message like "this was spammed" or the original mail date/time) `add_filter('cf7a_before_resend_email', 'my_before_resend_email', 10, 3 );`

=DEBUG=

`define( 'CF7ANTISPAM_DEBUG', true);`

Enables **debug mode** (wp-debug has to be enabled) - verbose mode, prints email analysis results into wp-content/debug.log

`define( 'CF7ANTISPAM_DEBUG_EXTENDED', true);`

Enable **extended debug mode** ("CF7ANTISPAM_DEBUG" has to be enabled) - disable autoban, enable advanced logging, when you uninstall the plugin, the word database, blacklist and options are not deleted.


== Changelog ==

= 0.4.5 =
* Enhanced language detection using the http headers accepted language (bug report, thanks @senjoralfonso #33)
* Multisite compatibility #34 (bug report, thanks @pluspol #34)
* Replaced domDocument with a regexp for more reliability (bug report, thanks @jensdiep and @georgr #35)
* Whitelist Feature request: whitelisting (feature requests, thanks @jensdiep #36)
* Settings page card style (enhancement, thanks @emilycestmoi)
* Fix for automatic unban initial settings, in some cases it might not have been "disabled"

= 0.4.4 =
* Adds the @mirekdlugosz fix for flamingo metadata regex
* Better Honeypot default input name field handling
* Fixed 'ban forever' that was replacing the list of banned IPs instead of adding the selected one
* Add a new check in oder to verify the http protocol since bots usually connects with HTTP/1.X

= 0.4.3 =
* Fixes an issue with honeypot placeholder (thanks to @ardsoms and @edodemo for the report)
* User enumeration protection
* Xmlrpc bruteforce protection
* Http headers obfuscation
* Add a new filter (cf7a_additional_max_honeypots) to limit the number of automatic honeypots (default: 5)

= 0.4.2 =
* Dashboard widget updated (adds a new filter 'cf7a_dashboard_max_mail_count' to limit the maximum value of displayed mail, default 25)
* UI enhancements - labels in the flamingo inbound page and the blacklist table
* Displays a random security tip at the top of cf7-antispam settings
* Standalone geoip check (previously it was mandatory to enable the language checks in order to enable geo-ip)
* Under certain conditions an automatic ban is carried out and the e-mail is not processed to avoid unnecessary consumption of resources
* German translation - thanks to @fhwebdesign and @senjoralfonso

= 0.4.1 =
* Honeyform updated and enhanced
* updated dnsbl servers (removed spfbl.net, bogons.cymru.com - added spamrats.com)
* improved iOS detection

= 0.4.0 =
* Adds geoip antispam filter
* Updated dashboard widget
* Updated settings and frontend scripts
* Improved honeypot (thanks to @theadam123 for feedbacks/testing)

= 0.3.0 =
* Dashboard widget to display the email received of the last week
* Resend email from Flamingo UI (works with mail received after this update)
* CF7-AntiSpam version check enhanced (but you will probably have to flush cache anyway when you update this plugin)
* Honeyform enhancements
* Enhanced activation script
* Adds an option to set the number of attempt before ban
* Cron unban fix
* Referrer verify (under bad ip checks)

= 0.2.7 =
* avoid to parse multiple times the stored flamingo messages
* added under "advanced options" a button to full reset cf7-antispam stored data
* language check (allowed/disallowed) based on browser language

= 0.2.6 =
* New option under "Enable advanced settings -> Severity of anti-spam control" with some prebuilt presets (weak, standard, secure)
* Fix install script that in some edge case can fail
* Backend script update
* Improved Javascript support for older browsers and ios (safari > 9 and internet explorer)
* jquery is no longer needed

= 0.2.5 =
* Bugfix the additional data in the email related to flamingo may not be parsed correctly
* New option to disable cf7 reload (/refill) when caching is enabled
* Enhanced fingerprint support for chrome on ios

= 0.2.4 =
* A new section "Advanced Section" that can be unlocked at the end of cf7-antispam options. I will put the more complex options there to make the interface easier.
* Improved spam management with flamingo
* New automatic options update handler
* Selectable encryption cypher
* Improved browser detection
* Fix installation failure (in very rare conditions) when Flamingo is installed and in mail message there are some non-utf8 characters.
* Documentation Update

= 0.2.3 =
* enhanced fingerprint scripts performance
* improving debugging output
* solved an issue with some plugins like conditional forms for cf7
* improved mobile fingerprinting

= 0.2.2 =
* fix safari (macos/ios) detection (with a new custom check)
* fix max time elepsed check
* countermeasures to avoid bayesian poisoning
* fix encoding with some languages for generated honeyform/honeypot
* reviewed scoring for fingerprinting and dnsbl

= 0.2.1 =
* enhanced honeyform and honeypot style
* fix dnsbl report message
* enhanced hidden fields "append on submit" option
* with the "extended debug option" on deactivate resets the B8 db

= 0.2.0 =
* adds HoneyForm to antispam checks
* a new option (under fingerprinting) to add the hidden fields with javascript only while submitting
* add a options section where the user can define the score of tests
* some admin UI cosmetical changes

= 0.1.1 =
* user customizable scoring options
* fix some installation issues on mysql < 5.6

= 0.1.0 =
* AntiSpam for Contact Form 7 published into WordPress Plugin Directory
* Compared to the very early version, I've added honeypot, fingerprint bots and automated ip bans (but I need to provide a way to unban even without flamingo).
* Documentation

= 0.0.1 =
* This is the first release

== Screenshot ==
1. Plugin options (1/4)
2. Plugin options (2/4)
3. Flamingo customizations (3/4)
3. Dashboard widget (4/4)

== copyright ==
AntiSpam for Contact Form 7, Copyright 2021 Codekraft Studio
AntiSpam for Contact Form 7 is distributed under the terms of the GNU GPL

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the LICENSE file for more details.

= Resources =
* Contact Form 7 and Flamingo © 2021 Takayuki Miyoshi,[LGPLv3 or later](https://it.wordpress.org/plugins/contact-form-7/)
* B8 https://nasauber.de/opensource/b8/, © 2021 Tobias Leupold, [LGPLv3 or later](https://gitlab.com/l3u/b8/-/tree/ab26daa6b293e6aa059d24ce7cf77af6c8b9b052/LICENSES)
* GeoLite2 [license](https://www.maxmind.com/en/geolite2/eula)
* GeoIP2 PHP API [GeoIP2-php](https://github.com/maxmind/GeoIP2-php)
* chart.js https://www.chartjs.org/, © 2021 Chart.js [contributors](https://github.com/chartjs/Chart.js/graphs/contributors), [MIT](https://github.com/chartjs/Chart.js/blob/master/LICENSE.md)
* Sudden Shower in the Summer, Public domain, Wikimedia Commons https://commons.wikimedia.org/wiki/File:Sudden_Shower_in_the_Summer_(5759500422).jpg

== Contibutions ==
Mirek Długosz - [#30](https://github.com/erikyo/cf7-antispam/pull/30) fixes a crash that occurred when analysing flamingo metadata

== Special thanks ==
This project is tested with BrowserStack. [Browserstack](https://www.browserstack.com/)

== MaxMind GeoIP2 ==
This plugin on demand can enable GeoLite2 created by MaxMind, available from [https://www.maxmind.com](https://www.maxmind.com)
While enabled you may **have to mention it in the privacy policy** of your site, depending on the law regulating privacy in your state!
* GeoIP2 databases [GeoLite2 Country](https://www.maxmind.com/en/accounts/current/geoip/downloads)

== DNSBL servers privacy policies ==
* dnsbl-1.uceprotect.net [www.uceprotect.net license](http://www.uceprotect.net/en/index.php?m=13&s=0)
* dnsbl-2.uceprotect.net [www.uceprotect.net license](http://www.uceprotect.net/en/index.php?m=13&s=0)
* dnsbl-3.uceprotect.net [www.uceprotect.net license](http://www.uceprotect.net/en/index.php?m=13&s=0)
* dnsbl.sorbs.net [sorbs.net license](http://www.sorbs.net/information/faq/)
* zen.spamhaus.org [spamhaus.org license](https://www.spamhaus.org/organization/dnsblusage/)
* bl.spamcop.net [spamcop.net license](https://www.spamcop.net/fom-serve/cache/297.html)
* b.barracudacentral.org [barracudacentral.org privacy-policy](https://www.barracuda.com/company/legal/trust-center/data-privacy/privacy-policy)
* dnsbl.dronebl.org [dronebl.org](https://dronebl.org/docs/faq)
* all.spamrats.com [spamrats.com tos](https://spamrats.com/tos.php)
* bl.ipv6.spameatingmonkey.net [spameatingmonkey.net](https://spameatingmonkey.com/faq)

== Inspirations, links ==
* Nikolai Tschacher [incolumitas.com](https://incolumitas.com/pages/BotOrNot/)
* Antoine Vastel [fp-scanner](https://github.com/antoinevastel/fpscanner)/[fp-collect](https://github.com/antoinevastel/fp-collect)
* Niespodd [niespodd](https://github.com/niespodd/browser-fingerprinting)
* Thomas Breuss [tbreuss](https://gist.github.com/tbreuss/74da96ff5f976ce770e6628badbd7dfc)
* Domain Name System-based blackhole list [wiki](https://en.wikipedia.org/wiki/Domain_Name_System-based_blackhole_list)
* dnsbl list [wiki](https://en.wikipedia.org/wiki/Comparison_of_DNS_blacklists)
