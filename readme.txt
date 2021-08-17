=== AntiSpam for Contact Form 7 ===
Contributors: codekraft
Tags: anti-spam, antispam, spam, bot, mail, blacklist, firewall, contact, form, security
Requires at least: 5.1
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: 0.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A trustworthy antispam plugin for Contact Form 7. Simple but effective.

== Description ==
Antispam for Contact Form 7 is an anti-bot plugin for your contact form, that without boring you with configurations, block bots from flood your email inbox.
We use several in-page and off-page bots traps and an auto-learning mechanism based on a statistical "Bayesian" spam filter called b8.

== SETUP ==
**Basic** - install & go! no action required to get the standard protection.
**Advanced** - For each contact form add 'flamingo_message: "[your-message]"' in the same way you do for [flamingo](https://contactform7.com/save-submitted-messages-with-flamingo/) to set the field to be checked as a text message.
This is required for advanced text statistical analysis, without this b8 will CANNOT be enabled.

**Please install also Flamingo to unlock the spam manager!**
Cf7A adds some functionalities to [Flamingo](https://wordpress.org/plugins/flamingo/): if both are installed Flamingo will be used as interface for the antispam system.
And if you already use Flamingo? Even better! But remember, to add 'flamingo_message: "[your-message]"' to advanced settings (as you do for the other flamingo labels) before activation.
So while activating CF7A all previous collected mail will be parsed and b8 will learn and build its vocabulary. In this way you will start with a pre-trained algorithm. Super cool!
Notes:
- On the right side of Flamingo inbound page i've added a new column that show the mail spamminess level
- if you unban an email in the flamingo "inbound" page the related ip will be removed from blacklist. But if you mark as spam the mail the ip will be not blacklisted again.
- Before activate this plugin please be sure to mark all spam mail as spam in flamingo inbound, in this way the b8 algorithm will be auto-trained

== Privacy Notices ==
AntiSpam for Contact Form 7 only process the ip but doesn't store any personal data, but anyway it creates a dictionary of spam and ham words in the wordpress database.
This database may contain words that are in the e-mail message, so can contain also personal data. This data can be "degenerated" that means the words that were in the e-mail might have been changed.
The purpose of this word collecting is to build a dictionary used for the spam detection.

== Installation ==
1. Upload the entire `contact-form-7-antispam` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress, you MUST have Contact Form 7 installed and enabled.
3. Setup advanced settings in Contact Form 7 in the same way you do for flamingo, but add also 'flamingo_message: "[your-message]"' - reference https://contactform7.com/save-submitted-messages-with-flamingo/
4. The configuration page for this plugin is located in the submenu "Antispam" under the Contact Form 7 menu

== Support==
Community support via the [support forums](https://wordpress.org/support/plugin/contact-form-7-antispam/) on wordpress.org
Open an issue on [GitHub](https://github.com/erikyo/contact-form-7-antispam)
also advice, reports, suggestions. Everyone can contribute, my intent is to keep it to be forever free but I ask for your support!

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
- Get the IP address and if it has already been blacklisted
- check if this mail was sent by a forbidden ip
- The bot fingerprinting is a way to check the mail was sent with a real browser
- Check the elapsed time to fill the form
- Check the message, the user agent or the email if it contains any forbidden words/strings.
- verify on dnsbl if the ip has already been reported
- if the spam score is above 1 the mail is proposed to b8 as spam, then b8 ranks it and learns the spam words.
- if the spam score is below 1 the mail will be passed to that b8 "decides" if it is spam or not.

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

= 0.2.4 =
* Solves installation failure (in very rare conditions) if Flamingo is installed and, while running additional installation scripts, characters that cannot be stored in the database (such as emoji in UTF8 databases charset) are found in the emails content.
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
1. the plugin options (1/3)
2. the plugin options (2/3)
3. the plugin options (3/3)

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
* Ban by geolocation
* Unban ip after x hours
* Configuration error detector (parse stored form ad return if the message field isn't found)
* CSV Export/import settings, banned ip
* Resend EMail if not were spam
* Optimise the the mail analysis (actually is a long script that execute sequentially checks, but rather a series of filters would be better)
* Selectable ciphers

== Resources ==
* Contact Form 7 and Flamingo © 2021 Takayuki Miyoshi,[LGPLv3 or later](https://it.wordpress.org/plugins/contact-form-7/)
* b8 https://nasauber.de/opensource/b8/, © 2021 Tobias Leupold, [LGPLv3 or later](https://gitlab.com/l3u/b8/-/tree/ab26daa6b293e6aa059d24ce7cf77af6c8b9b052/LICENSES)
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
