# Products

Configuration is available in Magento back-office under:
* System > Configuration > Catalog > Pimgento > Products settings

| Configuration          | Usage                                                                                                                                                                                  |
|------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Attribute Mapping      | Mapping to import the value of an Akeneo attribute to an other attribute in Magento with a different code (leave empty to map with attribute code)                                     |
| Default tax class      | Set up default tax class for websites                                                                                                                                                  |
| Configurable           | Use the value column to force data for specific attribute. Leave empty to retrieve the value of the first simple product. Value must be a chain between quotes or an SQL statement.    |
| Import media files     | Set to "Yes" to import media files                                                                                                                                                     |
| PIM Images Attributes  | Declare Akeneo attributes to be imported as image attribute                                                                                                                            |
| Product Images Mapping | Map Akeneo image attribute with Magento small image, thumbnail and swatch image                                                                                                        |
| Import Asset Files     | Set to "Yes" to import assets                                                                                                                                                          |
| PIM Asset Attributes   | Declare Akeneo attributes to be imported as asset attribute                                                                                                                            |
| Reindex Data           | Trigger Magento reindex after import                                                                                                                                                   |
| Clear cache            | Trigger cache clear after import                                                                                                                                                       |
| Cache Selection        | Select which cache to flush                                                                                                                                                            |