=== AntiSpam for Contact Form 7 ===
Contributors: Codekraft
Tags: anti-spam, antispam, spam, bot, mail, blacklist, firewall, contact, form, security
Requires at least: 5.1
Tested up to: 5.7.1
Requires PHP: 5.6
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A trustworthy antispam plugin for Contact Form 7. Simple but effective.

== Description ==
Contact Form 7 Antispam - is an Anti-spam plugin that without boring you with configurations, filters out spam-bots with an auto-learning ai mechanism.
What the plugin does in detail:
- for first the plugin perform a quick, trasparent but effective check on the humanity of the sender (without i'm not a robot checkbox or something like that)
- Then the IP is checked to see if it is on any spammer blocklists
- After that it is analysed by a predictive algorithm that learns what is spam and what is not, so that as you receive spam your site learns to protect itself!

By the way nothing is perfect so if you don't want to loose any submitted mail you may want to install also [flamingo] (https://wordpress.org/plugins/flamingo/)
Flamingo also is integrated with AntiSpam for Contact Form 7 and when you mark an email as spam (or ham) the intelligent algorithm learns again!

== Privacy Notices ==
AntiSpam for Contact Form 7 only process the ip but doesn't store any personal data, but anyway it creates a dictionary of spam and ham words in the wordpress database.
This database may contain words that are in the e-mail message, so can contain also personal data.
The purpose of this word collecting is to build a dictionary used for the spam detection.

== Installation ==
1. Upload the entire `contact-form-7-antispam` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress, you MUST have Contact Form 7 installed and enabled.
3. Setup advanced settings in Contact Form 7 in the same way you do for flamingo, but add also 'flamingo_message: "[your-message]"' - reference https://contactform7.com/save-submitted-messages-with-flamingo/
4. You can tune the options of this plugin, check the page "Antispam" under the Contact Form 7 menu

== Support==
Community support via the [support forums](https://wordpress.org/support/plugin/contact-form-7-antispam/) on wordpress.org
Open an issue on [GitHub](https://github.com/erikyo/contact-form-7-antispam)

== Upgrade Notice ==

== Changelog ==

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
* b8 https://nasauber.de/opensource/b8/, © 2021 Tobias Leupold, [LGPLv3 or later](https://gitlab.com/l3u/b8/-/tree/ab26daa6b293e6aa059d24ce7cf77af6c8b9b052/LICENSES)
* Sudden Shower in the Summer, Public domain, Wikimedia Commons https://commons.wikimedia.org/wiki/File:Sudden_Shower_in_the_Summer_(5759500422).jpg

== Inspirations ==
Alexander Romanov [Bot detection page](bot.sannysoft.com)
Nikolai Tschacher [incolumitas.com](https://incolumitas.com/pages/BotOrNot/)