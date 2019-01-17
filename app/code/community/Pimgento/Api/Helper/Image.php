<?php

/**
 * Class Pimgento_Api_Helper_Image
 *
 * @category  Class
 * @package   Pimgento_Api_Helper_Image
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Helper_Image extends Mage_Core_Helper_Data
{
    /**
     * Retrieve product base media path
     *
     * @return string
     */
    public function getBaseMediaPath()
    {
        /** @var Mage_Catalog_Model_Product_Media_Config $media */
        $media = Mage::getSingleton('catalog/product_media_config');

        return $media->getBaseMediaPath();
    }

    /**
     * Retrieve media file path
     *
     * @param string $filename
     *
     * @return string
     */
    public function getMediaFilePath($filename)
    {
        /** @var string $dispretionPath */
        $dispretionPath = Mage_Core_Model_File_Uploader::getDispretionPath($filename);
        /** @var string $correctFileName */
        $correctFileName = Mage_Core_Model_File_Uploader::getCorrectFileName($filename);

        return $dispretionPath . DS . $correctFileName;
    }

    /**
     * Check if media file exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function mediaFileExists($name)
    {
        /** @var string $path */
        $path = $this->getBaseMediaPath() . $this->getMediaFilePath($name);
        /** @var Varien_Io_File $ioAdapter */
        $ioAdapter = new Varien_Io_File();

        return $ioAdapter->fileExists($path);
    }

    /**
     * Save media file
     *
     * @param string $name
     * @param string $binary
     *
     * @return boolean
     */
    public function saveMediaFile($name, $binary)
    {
        /** @var string $path */
        $path = $this->getBaseMediaPath() . $this->getMediaFilePath($name);
        /** @var Varien_Io_File $ioAdapter */
        $ioAdapter = new Varien_Io_File();
        $ioAdapter->setAllowCreateFolders(true);
        $ioAdapter->open(['path' => dirname($path)]);
        $ioAdapter->streamOpen(basename($path));

        return $ioAdapter->streamWrite($binary);
    }
}
