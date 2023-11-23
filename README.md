# Contact Form 7 - Antispam
A trustworthy anti-spam plugin for Contact Form 7 a WordPress, simple but effective!
AntiSpam for Contact Form 7 without boring you with configurations, filters out spam-bots with an auto-learning ai mechanism.

This is the GitHub repo of the plugin hosted into [WordPress.org Plugin Directory](https://wordpress.org/plugins/cf7-antispam/).

License
-------

This plugin is released under the GNU General Public License Version 2 (GPLv2). For details, see [license.txt](license.txt).


Getting started
---------------

Install the Contact Form 7 plugin through the **Add Plugins** screen (**Plugins > Add New**). After activating the plugin, the **Antispam** sub-menu will appear under the Contact Form 7 menu, in the left sidebar.


Support
-------

Support for this plugin is primarily provided within the volunteer-based WordPress.org support forums. The official website also provides custom development and professional support services.


Contributing to Contact Form 7 - Antispam
-----------------------------------------

There are two great way to contribute to the project, help improve the source code or broaden the number of translations.

### Preparing for development

If you would like to submit a pull request, please follow the steps below:

- Make sure you have a GitHub account
- Fork the repository on GitHub
- Make changes to your fork of the repository
- Ensure you stick to the WordPress Coding Standards
- When committing, reference your issue (if present) and include a note about the fix
- Push the changes to your fork and [submit a pull request](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request) to the 'main' branch of the 'erikyo/cf7-antispam' repository.

#### Clone the repository

In order to work with your copy of the repository, clone it locally.

```bash
$ git clone https://github.com/GITHUB_USERNAME_HERE/cf7-antispam.git
```

> [!NOTE]
> Make sure to replace `GITHUB_USERNAME_HERE` with your actual username.

#### Install dependencies

In order to setup the development environment, remember to install:

- NodeJS - https://nodejs.org/
- Composer - https://getcomposer.org/

Optionally, setup a local environment with `wp-env` and Docker.

- [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) - A development tool to set up a sandboxed development environment, specifically for WordPress.
- [Docker](https://www.docker.com/) - Optional, though required if using `wp-env`.

From inside the project directory, run the following commands to install dependencies:

```bash
$ npm install
$ composer install
```

##### Additional dependencies

This plugin extends the functionality of Contact Form 7 and Flamingo. Both plugins should also be installed in your development environment. If you are using `wp-env`, both plugins will be automatically installed.

#### Starting the local development environment

If you have chosen to use the develop environment, run the following `wp-env` command to start the local server.

```bash
$ wp-env start
```

Special Thanks
--------------

[Tobias Leupold](https://github.com/l3u) - [b8](https://gitlab.com/l3u/b8/) https://nasauber.de/opensource/b8/

[Takayuki Miyoshi](https://github.com/takayukister) - [Contact Form 7](https://wordpress.org/plugins/contact-form-7/), [Flamingo](https://wordpress.org/plugins/flamingo/)

This project is tested with BrowserStack. [Browserstack](https://www.browserstack.com/)
