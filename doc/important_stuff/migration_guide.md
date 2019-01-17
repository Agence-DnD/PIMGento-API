# How to migrate from PIMGento CSV version to API version

### First of all, you will need to remove all PIMGento modules references:

* Remove everything in app/code/community/Pimgento
* Remove any override you may have in you app/code/local directory
    * Note that previous PIMGento overrides will now be ignored as import model names have changed
* Remove app/etc/modules/Pimgento_All.xml file
* Remove shell/pimgento.php file
    * This file will be overwritten after PIMGento API installation

### Now, clean up your Database:

As PIMGento permanent database tables have evolved, columns have changed or been added, and some tables were renamed.<br/>
In order to clean up your database, old PIMGento tables can be deleted.
*Before taking any action, do not forget to backup your database, or at least the tables you will alter/delete.*<br/>
**You especially need to remove old `pimgento_family_attribute_relations` table so these changes are taken into account**, in order for the extension to work properly.<br/>
Here are the main changes:
* `pimgento_asset`: this table is no longer needed and will not be replaced
* `pimgento_code`: has been renamed `pimgento_entities` and columns were modified
* `pimgento_family_attribute_relations`: columns have been renamed and one column added
* `pimgento_variant`: has been renamed `pimgento_product_model̀` and a lot of columns added

You may as well clean PIMGento related lines in tables `core_config_data` and `core_resource`.

### Then, flush cache:

Use the 'Flush cache' button in your Magento Back-Office, or delete the magento/var/cache directory on your website server.

### Install new PIMGento (API) Extension:

1. Download and copy PIMGento (API) source code from our [GitHub repository](https://github.com/Agence-DnD/PIMGento-API) into your Magento project.
2. You will need [Composer](https://getcomposer.org/) in order to proceed ot next step: [getcomposer.org](https://getcomposer.org/)
3. Install Akeneo API on your project. Please, check the ['How to...'](doc/important_stuff/how_to.md) section: How to install PIMGento (API)?

### Flush cache again:

Use the 'Flush cache' button in your Magento Back-Office, or delete the magento/var/cache directory on your website server.

### Configure PIMGento (API) in Magento backend:

Please, follow our configuration guide: [How to configure PIMGento (API)?](doc/important_stuff/how_to.md)

### Re-import all entities:
    
Please, follow our configuration guide: [How to import my data into PIMGento (API)?](doc/important_stuff/how_to.md)

### Important notes:

* It is important to know that the table named "pimgento_variant" in the CSV version is now called "pimgento_product_model", this is why you need to re-import all entities after migrating to be sure that all data will be set correctly.
* All custom rewrites of the previous PIMGento extension will be obsolete when migrating to the API version.

### Be happy:
You just installed the new PIMGento (API) Magento extension!