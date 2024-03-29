# `n98-magerun2-modules`

This is our collection of commands for `n98-magerun2`.
The commands allow to speed up compilation and deployment of static assets, as well
 as few other helper commands for Magento 2.

## Synopsys

```bash
sudo yum -y install https://extras.getpagespeed.com/release-latest.rpm
sudo yum -y install n98-magerun2-module-getpagespeed

cd /path/to/magento2

# Get tuned Varnish parameters specifc to your Magento 2 instance
n98-magerun2 varnish:tuned 

# Get active themes
n98-magerun2 dev:theme:active
```

## Commands available

### `n98-magerun2 varnish:tuned`

Gets tuned Varnish parameters specifc to *your* Magento 2 instance. It will find the category with 
largest number of products and provide Varnish parameters that will help you to avoid 
"Backend Fetch Failed" error as detailed in 
[documentation](https://devdocs.magento.com/guides/v2.2/config-guide/varnish/tshoot-varnish-503.html). 
Example output:

<pre>
Varnish params: <b>-p http_resp_hdr_len=54684 -p http_resp_size=79260 -p workspace_backend=112028</b>
Largest product category has this number of products: <b>2604</b>
</pre>

Simply copy-paste the suggested configuration bits to e.g. `/etc/varnish/varnish.params` or otherwise
specify as startup parameters to Varnish.

### `n98-magerun2 dev:theme:active`

Allows to get list of used themes. Example output (suitable for deploy static command):

    --theme Swissup/argento-pure2

This is useful for scripts to facilitate faster builds.

Many Magento 2 themes come bundled with many actual themes, and deploying static assets takes huge time.
All because you unnecessarily minify a ton of CSS and Javascript files for themes which are not even in use!

You can deploy just the used themes with:

    bin/magento setup:static-content:deploy $(n98-magerun2 dev:theme:active)
    
This is used in our zero-downtime deployment program, 
[`clearmage2`](https://www.getpagespeed.com/clearmage2).

### `n98-magerun2 deploy:locale:active`

Another way to speed up deployment of static files is by generating them only for actually used
locales. I truly believe that Magento 2 is dumb as f* of not doing this by default.

Only if you use `n98-magerun2 deploy:mode:set production` command, it is smart enough to generate
statics only for used locales. But this command is dumb, because when you use it, you cannot parallelize
deployment, and then again, you cannot deploy for only active themes, as above allows.

So you can get list of actively used locales with `n98-magerun2 deploy:locale:active` and pass it
over to deployment command:

<pre>
<em>#></em> n98-magerun2 deploy:locale:active
<em>#></em> <b>en_US en_GB</b>
</pre>

Combining all the things together for a no-dumb and much quicker static deployment:

```bash
THEMES=$(n98-magerun2 dev:theme:active)
LOCALES=$(n98-magerun2 deploy:locale:active)
php ./bin/magento setup:static-content:deploy --jobs=$(nproc) ${THEMES} ${LOCALES}
```

This is also used in our zero-downtime deployment program, 
[`clearmage2`](https://www.getpagespeed.com/clearmage2).

### `n98-magerun2 db:maintain:search-query`

This command trims entries for the `search_query` for queries which produce no results and were
made more than a month ago. Helps to prevent too much unnecessary database bloating and thus
improves performance. 

There is no output on success. It is suitable to be set up into a `@monthly` cron, e.g.:

    @monthly n98-magerun2 db:maintain:search-query --root-dir=/path/to/magento2  
    
### TODO

Extra switch for trimming based on popularity, e.g. remove all with popularity < 25    

## Installation

### Quick install for CentOS 6+ (7, 8, ...)

This will install our [RPM repository](https://www.getpagespeed.com/redhat), `n98-magerun2` and the module:

    sudo yum install https://extras.getpagespeed.com/release-latest.rpm
    sudo yum install n98-magerun2-module-getpagespeed

### Other platforms

Just place the files over to `/usr/local/share/n98-magerun2`.


