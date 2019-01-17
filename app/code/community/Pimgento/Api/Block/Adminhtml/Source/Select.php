<?php

/**
 * Class Pimgento_Api_Block_Adminhtml_Source_Select
 *
 * @category  Class
 * @package   Pimgento_Api_Block_Adminhtml_Source_Select
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Block_Adminhtml_Source_Select extends Mage_Adminhtml_Block_Abstract
{
    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        /** @var string $html */
        $html = sprintf(
            '<select name="%s" id="%s" class="%s" title="%s" %s>',
            $this->getInputName(),
            $this->getId(),
            $this->getClass(),
            $this->getTitle(),
            $this->getStyle()
        );

        $html .= $this->getSelectedOptionHtml();

        /** @var mixed[] $options */
        $options = $this->getOptions();
        /** @var string[] $option */
        foreach ($options as $option) {
            if (empty($option['label']) || empty($option['value'])) {
                continue;
            }
            $html .= $this->getOptionHtml($option);
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Return selected option as first html
     *
     * @return string
     */
    public function getSelectedOptionHtml()
    {
        /** @var string $columnName */
        $columnName = $this->getColumnName();

        return sprintf('<option value="#{%s}">#{%s}</option>', $columnName, $columnName);
    }

    /**
     * Return option html
     *
     * @param string $option
     *
     * @return string
     */
    public function getOptionHtml($option)
    {
        return sprintf(
            '<option value="%s">%s</option>',
            $this->escapeHtml($option['value']),
            $this->escapeHtml($option['label'])
        );
    }
}
