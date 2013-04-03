<?php
/**
 * Custom url model class
 *
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoemedia.de>
 *
 * @method Aoe_Static_Model_Resource_CustomUrl _getResource()
 * @method Aoe_Static_Model_Resource_CustomUrl getResource()
 * @method int getStoreId()
 * @method Aoe_Static_Model_CustomUrl setStoreId(int $value)
 * @method string getRequestPath()
 * @method Aoe_Static_Model_CustomUrl setRequestPath(string $value)
 * @method int getMaxAge()
 * @method Aoe_Static_Model_CustomUrl setMaxAge(int $value)
 */
class Aoe_Static_Model_CustomUrl extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/customUrl');
    }

    /**
     * Load url max-age information for request
     * If $path is array - we must load possible records and choose one matching earlier record in array
     *
     * @param  array|string $path
     * @return $this
     */
    public function loadByRequestPath($path)
    {
        $this->setId(null);
        $this->_getResource()->loadByRequestPath($this, $path);
        $this->_afterLoad();
        $this->setOrigData();
        $this->_hasDataChanges = false;
        return $this;
    }
}
