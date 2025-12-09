### Update checklist

- [ ] Update changelog
- [ ] Update [readme.txt](readme.txt) version (Tested up to -> last wordpress version)
- [ ] Update the plugin version ([readme.txt](readme.txt) - Stable tag)
- [ ] Update [cf7-antispam.php](cf7-antispam.php) the plugin version (plugin main file)
- [ ] Update CF7ANTISPAM_VERSION constant (just below)
- [ ] Update [package.json](package.json) version
- [ ] Update [composer.json](composer.json) version
- [ ] run `composer install --no-dev`
- [ ] run `composer dump-autoload --optimize`
- [ ] run `npm run plugin-zip`

🎉 🎉 🎉
