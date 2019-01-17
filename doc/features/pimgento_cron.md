# Cron setup

### Schedule my tasks:

You can configure crontask through configuration for each import. 
To do so:

*  Set your cron expression in your crontab following this example:
```bash
0 22 * * * /usr/bin/php /path/to/magento/shell/pimgento.php --import import-type >> /path/to/magento/var/log/pimgento_import_type.cron.log`
```

### Command line:

You can also launch import directly with command line:

```bash
php /path/to/magento/shell/pimgento.php --import import-type
```

With **import-type**:

* category
* family
* attribute
* option
* product_model
* family_variant
* product
