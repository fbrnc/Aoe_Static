<?php
/**
 * Custom url resource model class
 *
 * @author Dmytro Zavalkin <dmytro.zavalkin@aoemedia.de>
 */
class Aoe_Static_Model_Resource_BlacklistUrlPattern extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/blacklist_url_pattern', 'pattern_id');
    }

    /**
     * Initialize unique field
     *
     * @return $this
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = array(
            array(
                'field' => array('pattern'),
                'title' => Mage::helper('aoestatic')->__('Blacklist Url Pattern'),
            )
        );

        return $this;
    }

    /**
     * Delete url patterns with given ids
     *
     * @param array $patternIds
     * @return $this
     */
    public function deleteBlacklistUrlPatterns(array $patternIds)
    {
        $this->_getWriteAdapter()->delete($this->getMainTable(),
            array('pattern_id IN(?)' => $patternIds)
        );

        return $this;
    }
}
