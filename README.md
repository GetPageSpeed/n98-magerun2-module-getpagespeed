# n98-magerun2-modules

This is a small (hopefully expanding in the future) collection of commands for `n98-magerun2`.

## Synopsys

```bash
sudo yum install https://extras.getpagespeed.com/release-el$(rpm -E %{rhel})-latest.rpm
sudo yum install n98-magerun2-module-getpagespeed
cd /path/to/magento2
# Get tuned Varnish parameters specifc to your Magento 2 instance
n98-magerun2 varnish:tuned 
# Get active themes
n98-magerun2 dev:theme:active
```

## Commands available

### `n98-magerun2 varnish:tuned`

Gets tuned Varnish parameters specifc to *your* Magento 2 instance. It will find the category with largest number of products and provide Varnish parameters that will help you to avoid "Backend Fetch Failed" error as detailed in [documentation](https://devdocs.magento.com/guides/v2.2/config-guide/varnish/tshoot-varnish-503.html). Example output:

```
Largest product category has this number of products: 1715
+-------------------+----------------+-------------------+
| http_resp_hdr_len | http_resp_size | workspace_backend |
+-------------------+----------------+-------------------+
| 36015             | 60591          | 93359             |
+-------------------+----------------+-------------------+
```

### `n98-magerun2 dev:theme:active`

Allows to get list of used themes. Example output (suitable for deploy static command):

    --theme Swissup/argento-pure2

This is useful for scripts to facilitate faster builds.

Many Magento 2 themes come bundled with many actual themes, and deploying static assets takes huge time.
All because you unnecessarily minify a ton of CSS and Javascript files for themes which are not even in use!

You can deploy just the used themes with:

    bin/magento setup:static-content:deploy --theme Magento/backend $(n98-magerun2 dev:theme:active)

## Installation

### Quick install for CentOS 6+ (7, 8, ...)

This will install our [RPM repository](https://www.getpagespeed.com/redhat), `n98-magerun2` and the module:

    sudo yum install https://extras.getpagespeed.com/release-el$(rpm -E %{rhel})-latest.rpm
    sudo yum install n98-magerun2-module-getpagespeed

### Other platforms

Just place the files over to `/usr/local/share/n98-magerun2`.


