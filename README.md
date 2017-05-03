Car-ads crawler API
==============
## Important
API is built on [auto_scrapy](https://github.com/DarkerTH/auto_scrapy) - car-ads websites crawler.

## Setup
* Run `composer install`
* Change API key `config/prod.php` (security reasons)

## Permissions
API needs permissions to run crawler in order to provide results. 
* Run `sudo visudo` to open `sudoers` file
* Go to the bottom of the file and add these lines. Make sure paths specified below are correct (modify them if wrong).
```
www-data ALL = NOPASSWD:/usr/bin/python2.7 /var/www/html/auto_api/vendor/darkerth/auto_scrapy/auto/spiders/*, \
                      ! /usr/bin/python2.7 /var/www/html/auto_api/vendor/darkerth/auto_scrapy/auto/spiders/*..*
```
> `www-data` may be changed to `apache` if you are running Apache on CentOS

## How to run
* Navigate to (e.g.) `http://localhost/cars/audi/a4`
* Specify parameters (GET) by adding `parameter=value`, e.g. `http://localhost/cars/audi/a4?year_from=2005&year_to=2010`

## Parameters
Here is a list of available parameters:
* year_from (default 1900)
* year_to (default 2018)
* price_from (default 0)
* price_to (default 200000)

## Cache
API query is being cached for X seconds (`config/prod.php` -> `api.cacheTime`). 
