# n98-magerun2-modules

This is a small (hopefully expanding in the future) collection of commands for `n98-magerun2`.

## Installation

### Quick install for CentOS 7

This will install our RPM repository, `n98-magerun2` and the module:

    yum install https://extras.getpagespeed.com/release-el7-latest.rpm
    yum install n98-magerun2-module-getpagespeed

### Other platforms

Just place the files over to `/usr/local/share/n98-magerun2`.

## Commands available

### `n98-magerun2 varnish:tuned`

Gets tuned Varnish params. Example output:

```
Largest product category has this number of products: 1715
+-------------------+----------------+-------------------+
| http_resp_hdr_len | http_resp_size | workspace_backend |
+-------------------+----------------+-------------------+
| 36015             | 60591          | 93359             |
+-------------------+----------------+-------------------+
```
