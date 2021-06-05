=== AntiSpam for Contact Form 7 ===
Contributors: codekraft
Tags: anti-spam, antispam, spam, bot, mail, blacklist, firewall, contact, form, security
Requires at least: 5.1
Tested up to: 5.7.1
Requires PHP: 5.6
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A trustworthy antispam plugin for Contact Form 7. Simple but effective.

== Description ==
Antispam for Contact Form 7 is an anti-bot plugin for your contact form, that without boring you with configurations, block bots from flood your email inbox.
We use several methods to detect bots (always updated) and an auto-learning mechanism based on a statistical "Bayesian" spam filter.
So as your site receives spam and real emails, learn to distinguish between them!

== SETUP ==
**Basic** - install & go! no action required to get the standard protection.
**Advanced** - For each contact form (in the same way you do for flamingo) add 'flamingo_message: "[your-message]"' - [reference](https://contactform7.com/save-submitted-messages-with-flamingo/).
Without this d8 cannot know what the message is and therefore is deactivated.

**Standard Spam Filters:**
We have several types of bot detection for many type of bots! Auto-blacklisting (in fail2ban style), HoneyPots, HoneyForms, Bot FingerPrinting, Elapsed time checks, IP address exclusion, prohibited strings in email and in user agent.
In Addition we will check on 10 preconfigured *DNS-based Blackhole server* to found ip delisted for spam!
And besides the most famous Uceprotect, Spamhouse, Barracuda, Sorbs, Spamcop you can add or remove other servers as you like, there are [50 server around the world](https://en.wikipedia.org/wiki/Domain_Name_System-based_blackhole_list).

**B8:**
In principle, b8 uses the math and technique described in Gary Robinson's articles "A Statistical Approach to the Spam Problem" and "Spam Detection".
The "degeneration" method Paul Graham proposed in "Better Bayesian Filtering" has also been implemented.
b8 cuts the text to classify to pieces, extracting stuff like email addresses, links and HTML tags and of course normal words.
For each such token, it calculates a single probability for a text containing it being spam, based on what the filter has learned so far.

*Will I finally be 100% protected from spam?*
Absolutely NO, nobody can guarantee that, and anyone who tells you that is lying.
But luckily, bots are limited by the fact that they don't use a real browser, they aren't stupid at all because the scammers who designed them aren't. Understanding how a bot "think" is the key to fool them!
Scammers know very well how we defend our forms, they can even see the source code of the various plugins, so I ask you to report if they have found a way bypass this plugin filters on the WordPress forum.


You can use [flamingo](https://wordpress.org/plugins/flamingo/) as spam manager and my personal advice to do it!
Cf7a add some functionalities to Flamingo: it can be used as manager for the antispam system and when you mark an email as spam (or ham) this action will be submitted also to the d8 dictionary!
And if you already use flamingo? Even better! But before the activation of "Antispam for Contact Form 7" remember to add 'flamingo_message: "[your-message]"' to advanced settings (as you do for the other fields).
in this way while activating this plugin activation all collected mail will be parsed and d8 will learn what is spam or not. So in this way you will start with a pre-trained algorithm. super cool!
- On the right side of the flamingo inbound table there is a new column that show the level of spamminess
- if you unban an email in the flamingo "inbound" page the related ip will be removed from blacklist. But if you mark as spam the mail the ip will be not blacklisted again.

== Privacy Notices ==
AntiSpam for Contact Form 7 only process the ip but doesn't store any personal data, but anyway it creates a dictionary of spam and ham words in the wordpress database.
This database may contain words that are in the e-mail message, so can contain also personal data. This data can be "degenerated" that means the words that were in the e-mail might have been changed.
The purpose of this word collecting is to build a dictionary used for the spam detection.

== Installation ==
1. Upload the entire `contact-form-7-antispam` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress, you MUST have Contact Form 7 installed and enabled.
3. Setup advanced settings in Contact Form 7 in the same way you do for flamingo, but add also 'flamingo_message: "[your-message]"' - reference https://contactform7.com/save-submitted-messages-with-flamingo/
4. You can tune the options of this plugin, check the page "Antispam" under the Contact Form 7 menu

== Support==
Community support via the [support forums](https://wordpress.org/support/plugin/contact-form-7-antispam/) on wordpress.org
Open an issue on [GitHub](https://github.com/erikyo/contact-form-7-antispam)
also advice, reports, suggestions. Everyone can contribute, my intent is to keep it to be forever free but I ask for your support!

== Spam-bot detection sequence ==
- Checks IP address and if it has already been blacklisted, marks the e-mail as spam immediately.
- Checks if the ip is valid and looks through the list of forbidden strings for ip's, to check if match the one of the mail sender. You can ban an ip (ipv4 or ipv6) by typing its entire address or if you want to ban all addresses that contain "192.168.1" you can just type in part of it.
- The bot fingerprinting is a way to check the mail was sent with a real browser, we have two sets of tests: the first "passive" one where the keyboard, the time zone, the computer hardware, the browser extensions are checked. the second is an "active" test that perform a computer hardware check
- Check the elapsed time to fill the form, which has to be more than a few seconds (the amount of time it usually takes a bot).
- Then (in order) we check the message, the user agent or the email if it contains any forbidden words/strings. It is useful to ban your own domain from the forms (unless you want to write your own) because a scammer's trick is get your domain and use it as an email (info@yourdomain)in order to bypass the classic anti-spam checks (server and client side)
- Check on dnsbl if the ip has already been reported
- if the spam score is above 1 the mail is proposed to d8 as spam, then d8 ranks it and learns the spam words.
- if the spam score is below 1 the mail will be passed to that d8 "decides" if it is spam or not. You have to wait until d8 has a good dictionary, usually 40-50 emails are a good starting point, until then it is better to keep the tolerance up (0.95 can be a good starting point)

== Constants ==
Enable **debug mode**
`define( 'CF7ANTISPAM_DEBUG', false);`

Enable **extended debug mode** (disable-autoban, prints fingerprinting results and dnsbl benchmark)
`define( 'CF7ANTISPAM_DEBUG_EXTENDED', false);`

== TODOs ==
* Ban by geolocation
* Test based on device type (mobiles, desktops etc)
* Unban ip after x hours
* Configuration error detector (parse stored form ad return if the message field isn't found)
* Export/import settings, banned ip

== Changelog ==

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
Modul R, Copyright 2021 Codekraft Studio
Modul R is distributed under the terms of the GNU GPL

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the LICENSE file for more details.

== Resources ==
* Contact Form 7, Flamingo © 2021 Takayuki Miyoshi,[LGPLv3 or later](https://it.wordpress.org/plugins/contact-form-7/)
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
