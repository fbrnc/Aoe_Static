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

    /**
     * Delete custom urls with given ids
     *
     * @param array $customUrlIds
     * @return $this
     */
    public function deleteCustomUrls(array $customUrlIds)
    {
        $this->getResource()->deleteCustomUrls($customUrlIds);
    }

    /**
     * purge URL from static cache to make sure the max-age is correct set for the cache server
     *
     * @return $this
     */
    protected function _afterSave()
    {
        $url = $this->getRequestPath();
        if (Mage::getStoreConfig('web/url/use_store')) {
            $url = Mage::app()->getStore($this->getStoreId())->getCode() . '/' . $url;
        }

        Mage::helper('aoestatic')->purge(array($url));

        return $this;
    }
}
