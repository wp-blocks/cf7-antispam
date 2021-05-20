=== AntiSpam for Contact Form 7 ===
Contributors: Codekraft
Tags: anti-spam, antispam, spam, bot, mail, blacklist, firewall, contact, form, security
Requires at least: 5.1
Tested up to: 5.7.1
Requires PHP: 5.6
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A trustworthy antispam plugin for Contact Form 7. Simple but effective.

== Description ==
Antispam for Contact Form 7 is an anti-bot plugin for your contact form, that without boring you with configurations, remove the spam from your mail.
We have serveral method to detect fake users and an auto-learning mechanism based on a statistical "Bayesian" spam filter.
So as your site receives spam and real emails, learn to distinguish between them!

Basic Spam Filters:
Before to tell to B8 what is spam and what is ham we do some basic checks like honeypot, bot fingerprinting, elapsed time checks, bad ip, prohibited strings in email and in user agent.
Then we check on DNS-based Blackhole List and check that ip is delisted for spam on the major 10 server like uceprotect, spamhouse, barracuda, sorbs, spamcop... and the dnsbl list configurable so you can add/remove servers as you want.
This way we can already tell if the e-mail they are about to send us is spam and, if so, we will teach d8 that it is spam (because it has not passed the checks).
Otherwise we will ask d8 to classify the mail and, based on what it has learned (if it exceeds a certain rating) it will be classified as spam.

B8:
In principle, b8 uses the math and technique described in Gary Robinson's articles "A Statistical Approach to the Spam Problem" and "Spam Detection".
The "degeneration" method Paul Graham proposed in "Better Bayesian Filtering" has also been implemented.
b8 cuts the text to classify to pieces, extracting stuff like email addresses, links and HTML tags and of course normal words.
For each such token, it calculates a single probability for a text containing it being spam, based on what the filter has learned so far.

What does the spam-bot detection sequence:
- Checks the sender IP address and if it has already been blacklisted, marks the e-mail as spam immediately.
- It checks if the ip is valid and looks through the list of forbidden strings for ip's, to check if match the one of the mail sender. You can ban an ip (ipv4 or ipv6) by typing its entire address or if you want to ban all addresses that contain "192.168.1" you can just type in part of it.
- The fingerprinting bot is a way to check if the sender of the mail is using a real browser, we have two sets of tests: the first "passive" one where the keyboard, the time zone, the computer hardware, the browser extensions are checked. the second is an "active" test that uses the computer hardware to make a short test of the graphic card or stuff like this
- We test the time to fill the form, which has to be more than a few seconds (the amount of time it usually takes a bot).
- Then (in order) we check the message, the user agent or the email if it contains any forbidden words/strings. It is useful to ban your own domain from the forms for example (unless you want to write your own) because a scammer's trick is get your domain and use it as an email (info@yourdomain)in order to bypass the classic anti-spam checks and those of your mail client
- Check on dnsbl to see if the ip has already been reported
- if the spam score is above 1 the mail is proposed to d8 as spam, then d8 ranks it and learns the spam words.
- if the spam score is below 1 the mail is read and d8 decides if it is spam or not (you can decide the tolerance so until it is familiar with the "right" words it should be kept high e.g. 0.95)

By the way nothing is perfect so if you don't want to loose any submitted mail you may want to install also [flamingo](https://wordpress.org/plugins/flamingo/)

Flamingo in addition can be used as manager for the antispam system and when you mark an email as spam (or ham) the intelligent algorithm learns again!
On plugin activation if you have flamingo the plugin will parse all the collected mail and teach to d8 what is spam or not so you will start with a good pre-trained algorithm.
Note: if you unban an email in the flamingo inbound page the related ip will be unbanned.

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

== How it Work ==

== TO DOs ==
Ban by geolocation
Unban ip after x hours

== Changelog ==

= 0.1.0 =
* ContactForm 7 AntiSpam published into WordPress Plugin Directory
* Compared to the very early version, I've added honeypot, fingerprint bots and automated ip bans (but I need to provide a way to unban even without flamingo).
* Documentation

= 0.0.1 =
* This is the first release

== Screenshot ==
1. the plugin options

== Copyright ==
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
dnsbl-1.uceprotect.net [www.uceprotect.net license](http://www.uceprotect.net/en/index.php?m=13&s=0)
dnsbl-2.uceprotect.net [www.uceprotect.net license](http://www.uceprotect.net/en/index.php?m=13&s=0)
dnsbl-3.uceprotect.net [www.uceprotect.net license](http://www.uceprotect.net/en/index.php?m=13&s=0)
dnsbl.sorbs.net [sorbs.net license](http://www.sorbs.net/information/faq/)
zen.spamhaus.org [spamhaus.org license](https://www.spamhaus.org/organization/dnsblusage/)
bl.spamcop.net [spamcop.net license](https://www.spamcop.net/fom-serve/cache/297.html)
b.barracudacentral.org [barracudacentral.org privacy-policy](https://www.barracuda.com/company/legal/trust-center/data-privacy/privacy-policy)
dnsbl.dronebl.org [dronebl.org](https://dronebl.org/docs/faq)
dns.spfbl.net [spfbl.net](https://spfbl.net/dnsbl/)
bogons.cymru.com [cymru.com privacy](https://team-cymru.com/privacy/)
bl.ipv6.spameatingmonkey.net [spameatingmonkey.net](https://spameatingmonkey.com/faq)

== Inspirations ==
Alexander Romanov [Bot detection page](bot.sannysoft.com)
Nikolai Tschacher [incolumitas.com](https://incolumitas.com/pages/BotOrNot/)
Thomas Breuss [tbreuss](https://gist.github.com/tbreuss/74da96ff5f976ce770e6628badbd7dfc)
Domain Name System-based blackhole list [wiki](https://en.wikipedia.org/wiki/Domain_Name_System-based_blackhole_list)
dnsbl list [wiki](https://en.wikipedia.org/wiki/Comparison_of_DNS_blacklists)
