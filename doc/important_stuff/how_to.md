How to...
========
### Q:  How to install PIMGento (API)?
**A:**
* Copy last release content in Magento root
* Use composer in Magento root to install required library:

```shell
composer require php-http/guzzle6-adapter:^1.1
composer require akeneo/api-php-client-ee:^3.0
```

* Clear the cache (System > Cache Management)
* Disconnect / reconnect to the Back Office
* Refresh Magento compilation if needed (System > Tools > Compilation)

### Q: How to configure PIMGento (API)?
**A:** Before starting to use PIMGento (API), few steps are required to set it right:

* Configure your store language and currency before import
* Configure your API settings in "Pimgento > Configuration > API Credentials"
* Launch import from admin panel in "Pimgento > Imports"
* After category import, set the "Root Category" for store in "Stores > Settings > All Stores" ...and you are good to go! Just check the configuration to be ready to import your data the right way!

### Q: How to import my data into PIMGento (API)?

**A:** You can import your data using two different ways:

* Using the [interface](../features/pimgento_interface.md)
* Using cron [tasks](../features/pimgento_cron.md)

But before using one of these methods be sure to read this [quick guide](../features/pimgento_import.md) about the import system.

### Q: How to customize PIMGento (API)?

**A:** If even the multiple configuration of PIMGento (API) doesn't suit your business logic, or if you want to have other possibilities in import, you can always override PIMGento (API) as it is completely Open Source. Just keep in mind a few things before beginning to develop your own logic:

* Observers define each task for a given import, if you want to add a task you should declaring a new method in the corresponding Import class and adding to the Observer.
* One method in Import class = One task
* There is no data transfer between tasks

Note that if you judge your feature can be used by others, and if you respect this logic, we will be glad to add it to PIMGento (API): just make us a PR!

### Q: How to contribute to PIMGento (API)?

**A:** You can contribute to PIMGento (API) by submitting PR on Github. However, you need to respect a few criteria:

* Respect PIMGento (API) logic and architecture
* Be sure to not break others features
* Submit a clean code
* Always update the documentation if you submit a new feature

### Q: How to set product name as URL

**A:** Add pim attribute mapping that points to the field "url_key"

* Go to Pimgento > Configuration -> Product Settings >  Attributes Mapping
* Add attribute mapping
* Set Pim Attribute to "akeneo_product_name_field"
* Set Magento Attribute to "url_key"
* Save
