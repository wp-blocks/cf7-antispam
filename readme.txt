=== AntiSpam for Contact Form 7 ===
Contributors: codekraft
Tags: anti-spam, antispam, spam, bot, mail, blacklist, firewall, contact, form, security
Requires at least: 5.1
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: 0.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A trustworthy antispam plugin for Contact Form 7. Simple but effective.

== Description ==
Antispam for Contact Form 7 is a free plugin for Contact Form 7, that without boring you with configurations, block bots from flood your email inbox.
CF7A use several in and off page bots traps and an auto-learning mechanism based on a statistical "Bayesian" spam filter called b8.
CF7-AntiSpam adds some functionalities also to [Flamingo](https://wordpress.org/plugins/flamingo/): if both are installed Flamingo will be used as interface for the antispam system and some convenient features will be added, such a dashboard widget or a function to resend emails.

== SETUP ==
**Basic** - install & go! no action required to get the standard protection.
**Advanced** - CF7A needs to parse the input message field of your form to analyze properly the email content with its dictionary.
So the only thing you need to do is add to (for each contact form) 'flamingo_message: "[your-message]"' in the same way you do for [flamingo](https://contactform7.com/save-submitted-messages-with-flamingo/).
This is **required for advanced text statistical analysis**, without this B8 filter will couldn't be enabled.

==Antispam Available Test==
- It is verified that the mail is sent through the cf7 module protecting the form with a encrypted unique hash
- Browser Fingerprinting (check if is a real browser or a bot that camouflaging as such)
- Blacklist bots after a customizable number of attempts (with the possibility to schedule unban)
- Time elapsed (a form is not filled out in a few seconds as bots do)
- IP check (you set a list of banned IP's, ipv6 compatible)
- Prohibited words in message/email and user agent
- DNS Blacklists
- Honeypot
- Honeyform
- B8 statistical "Bayesian" spam filter

==Install Flamingo to unlock the spam manager!==
Not using Flamingo? well i suggest you to install it, even if it is not essential. In this way from your wordpress installation you will be able to review emails and "re-teach" b8 what is spam and what is not (might be useful in the first times if some mail spam pass through).
And if you already use Flamingo? Even better! But remember, to add 'flamingo_message: "[your-message]"' to advanced settings (as you do for the other flamingo labels) before activation.
While activating CF7A all previous collected mail will be parsed and b8 will learn and build its vocabulary. In this way you will start with a pre-trained algorithm. Super cool!
Notes:
- On the right side of Flamingo inbound page i've added a new column that show the mail spamminess level
- if you unban an email in the flamingo "inbound" page the related ip will be removed from blacklist. But if you mark as spam the mail the ip will be not blacklisted again.
- Before activate this plugin please be sure to mark all spam mail as spam in flamingo inbound, in this way the b8 algorithm will be auto-trained
- Don't delete a spam message from ham if you receive it, rather put it in spam to teach b8 how to recognise the difference!

== Privacy Notices ==
AntiSpam for Contact Form 7 only process the ip but doesn't store any personal data, but anyway it creates a dictionary of spam and ham words in the wordpress database.
This database may contain words that are in the e-mail message, so can contain also personal data. This data can be "degenerated" that means the words that were in the e-mail might have been changed.
The purpose of this word collecting is to build a dictionary used for the spam detection.

== Installation ==
1. Upload the entire `contact-form-7-antispam` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress, you MUST have Contact Form 7 installed and enabled.
3. Setup advanced settings in Contact Form 7 in the same way you do for flamingo, but add also 'flamingo_message: "[your-message]"' - reference https://contactform7.com/save-submitted-messages-with-flamingo/
4. The configuration page for this plugin is located in the submenu "Antispam" under the Contact Form 7 menu

==Support==
Community support via the [support forums](https://wordpress.org/support/plugin/contact-form-7-antispam/) on wordpress.org
Open an issue on [GitHub](https://github.com/erikyo/contact-form-7-antispam)

= Contribute =
We love your input! We want to make contributing to this project as easy and transparent as possible, whether it's:

* Reporting a bug
* Testing the plugin with different user agent and report fingerprinting failures
* Discussing the current state, features, improvements
* Submitting a fix or a new feature

We use github to host code, to track issues and feature requests, as well as accept pull requests.
By contributing, you agree that your contributions will be licensed under its GPLv2 License.

My goal is to create an antispam that protects cf7 definitively without relying on external services. And free for everyone.
if you want to help me, [GitHub](https://github.com/erikyo/contact-form-7-antispam) is the right place ;)

== Debug / Plugin PHP Constants ==

Enable **debug mode** (verbose mode - need wp debug to be enabled and prints analysis results into log)
`define( 'CF7ANTISPAM_DEBUG', true);`

Enable **extended debug mode** (CF7ANTISPAM_DEBUG needs to be enabled, disable autoban and enable advanced logging).
if you uninstall this plugin with this option is enabled options and b8 words database will not be deleted. (Use it if you know what you are doing, because this way you do not delete/reset options and vocabulary)
`define( 'CF7ANTISPAM_DEBUG_EXTENDED', true);`

**Dnsbl benchmark**
if the mail takes so long to be sent, maybe it is a dnsbl that is taking so long to reply. with this option active, the time that each dns took to reply is printed in the log.
`define( 'CF7ANTISPAM_DNSBL_BENCHMARK', true);`


== Frequently Asked Questions ==

=Will I finally be 100% protected from spam?=

NO, nobody can guarantee that, and anyone who tells you that is lying. But luckily, bots are limited by the fact that they don't use a real browser and they use fairly repetitive routes which can be recognised.

=Mail spam test sequence explained=

Different checks are made to recognize different type of bots. This is a short list of the antispam tests
* Get the IP address and if it has already been blacklisted
* check if this mail was sent by a forbidden ip
* The bot fingerprinting is a way to check the mail was sent with a real browser
* Check the elapsed time to fill the form
* Check the message, the user agent or the email if it contains any forbidden words/strings.
* verify on dnsbl if the ip has already been reported
* if the spam score is above 1 the mail is proposed to b8 as spam, then b8 ranks it and learns the spam words.
* if the spam score is below 1 the mail will be passed to that b8 "decides" if it is spam or not.

=What do you mean by Standard Spam Filters=

Some of the standard test are Elapsed time, Auto-Blacklisting, Prohibited IP/strings and, in addition, we got some advanced test like HoneyPots, HoneyForms and the browser FingerPrinting.

=HoneyForm, or you mean Honeypot?=

No I mean HoneyForm! It's a hidden and fake form that bots can't resist filling in, after all it's part of the page code for them and they rarely check the visibility of an element. This form is completely a trap and when the bot fills it he will be banned.

=But the standard Honeypot?=

We also have honeypots, to activate them just click on a checkbox and they will be generated automatically for each text field. The only thing you need to check in the CF7A options page is the name of the fields used that need to differ with the names used in contact form 7.

=DNSBL... What?=

After that the sender ip will be searched into *DNS-based Blackhole server* to found if that ip is delisted for spam. 10 server already set as default but you can add or remove as you like, there are 50 server available (list below).

=What is b8? How it works?=

b8 cuts the text to classify to pieces, extracting stuff like email addresses, links and HTML tags and of course normal words. For each such token, it calculates a single probability for a text containing it being spam, based on what the filter has learned so far. b8 is a free software form Tobias Leupold, who I thank for making it available to everyone.

== Changelog ==

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
* A new section "Advanced Section" that can be unlocked at the end of cf7a options. I will put the more complex options there to make the interface easier.
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
* with the "extended debug option" on deactivate resets the b8 db

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

== TODOs ==
* Geoip ban
* Enhance user agent specific checks (especially for apple devices, lately bots are using this user agent to try to escape the scans)
* Banned ip and dictionary CSV Export/import settings
* Remove mail duplicates if users sent multiple
* Optimise the the mail analysis function using filters (actually is a long script that execute sequentially checks)
* Cover with the antispam also the wordpress comment form and the login panel

== Resources ==
* Contact Form 7 and Flamingo © 2021 Takayuki Miyoshi,[LGPLv3 or later](https://it.wordpress.org/plugins/contact-form-7/)
* b8 https://nasauber.de/opensource/b8/, © 2021 Tobias Leupold, [LGPLv3 or later](https://gitlab.com/l3u/b8/-/tree/ab26daa6b293e6aa059d24ce7cf77af6c8b9b052/LICENSES)
* chart.js https://www.chartjs.org/, © 2021 Chart.js [contributors](https://github.com/chartjs/Chart.js/graphs/contributors), [MIT](https://github.com/chartjs/Chart.js/blob/master/LICENSE.md)
* Sudden Shower in the Summer, Public domain, Wikimedia Commons https://commons.wikimedia.org/wiki/File:Sudden_Shower_in_the_Summer_(5759500422).jpg

== DNSBL servers privacy policies ==
* dnsbl-1.uceprotect.net [www.uceprotect.net license](http://www.uceprotect.net/en/index.php?m=13&s=0)
* dnsbl-2.uceprotect.net [www.uceprotect.net license](http://www.uceprotect.net/en/index.php?m=13&s=0)
* dnsbl-3.uceprotect.net [www.uceprotect.net license](http://www.uceprotect.net/en/index.php?m=13&s=0)
* dnsbl.sorbs.net [sorbs.net license](http://www.sorbs.net/information/faq/)
* zen.spamhaus.org [spamhaus.org license](https://www.spamhaus.org/organization/dnsblusage/)
* bl.spamcop.net [spamcop.net license](https://www.spamcop.net/fom-serve/cache/297.html)
* b.barracudacentral.org [barracudacentral.org privacy-policy](https://www.barracuda.com/company/legal/trust-center/data-privacy/privacy-policy)
* dnsbl.dronebl.org [dronebl.org](https://dronebl.org/docs/faq)
* dns.spfbl.net [spfbl.net](https://spfbl.net/dnsbl/)
* bogons.cymru.com [cymru.com privacy](https://team-cymru.com/privacy/)
* bl.ipv6.spameatingmonkey.net [spameatingmonkey.net](https://spameatingmonkey.com/faq)

== Inspirations, links ==
* Alexander Romanov [Bot detection page](bot.sannysoft.com)
* Nikolai Tschacher [incolumitas.com](https://incolumitas.com/pages/BotOrNot/)
* Niespodd [niespodd](https://github.com/niespodd/browser-fingerprinting)
* Thomas Breuss [tbreuss](https://gist.github.com/tbreuss/74da96ff5f976ce770e6628badbd7dfc)
* Domain Name System-based blackhole list [wiki](https://en.wikipedia.org/wiki/Domain_Name_System-based_blackhole_list)
* dnsbl list [wiki](https://en.wikipedia.org/wiki/Comparison_of_DNS_blacklists)
