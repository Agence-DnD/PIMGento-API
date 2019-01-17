<?php

/**
 * Class Task_Log_Helper_Data
 *
 * @category  Class
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Log_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * Retrieve current admin user name
     *
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getCurrentAdminUser()
    {
        /** @var string $name */
        $name = '';

        if (Mage::app()->getStore()->isAdmin()) {
            /** @var Mage_Admin_Model_Session $session */
            $session = Mage::getSingleton('admin/session');

            /** @var Mage_Admin_Model_User $user */
            $user = $session->getUser();

            if ($user) {
                $name = $user->getFirstname() . ' ' . $user->getLastname();
            }
        }

        return $name;
    }
}
