---
# You don't need to edit this file, it's empty on purpose.
# Edit theme's home layout instead if you wanna make some changes
# See: https://jekyllrb.com/docs/themes/#overriding-theme-defaults
layout: default
---

omeka-cli allows to interact with an Omeka installation using a command-line
interface.

## Download omeka-cli

Run this in your terminal to get the latest version of omeka-cli:

```sh
php -r 'copy("{{ '/installer' | absolute_url }}", "omeka-cli-installer.php");'
php omeka-cli-installer.php
php -r 'unlink("omeka-cli-installer.php");'
```

This will download the latest `omeka-cli.phar` in the current directory.

You can make it globally available by running the following commands:

```sh
chmod +x omeka-cli.phar
sudo mv omeka-cli.phar /usr/local/bin/omeka-cli
```

## Documentation

View [README.md on GitHub]({{ site.github.repository_url | append: '/blob/' | append: site.github.latest_release.tag_name | append: '/README.md' }})
