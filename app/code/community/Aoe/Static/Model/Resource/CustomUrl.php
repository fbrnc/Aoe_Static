<?php
/**
 * Custom url resource model class
 *
 * @author Dmytro Zavalkin
 */
class Aoe_Static_Model_Resource_CustomUrl extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/custom_url', 'custom_url_id');
    }

    /**
     * Initialize array fields
     *
     * @return $this
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = array(
            array(
                 'field' => array('request_path','store_id'),
                 'title' => Mage::helper('aoestatic')->__('Request Path for Specified Store'),
            )
        );
        return $this;
    }

    /**
     * Retrieve select object for load object data
     *
     * @param string $field
     * @param mixed $value
     * @param Aoe_Static_Model_CustomUrl $object
     * @return Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        /** @var $select Varien_Db_Select */
        $select = parent::_getLoadSelect($field, $value, $object);

        if (!is_null($object->getStoreId())) {
            $select->where('store_id IN(?)', array(Mage_Core_Model_App::ADMIN_STORE_ID, $object->getStoreId()));
            $select->order('store_id ' . Varien_Db_Select::SQL_DESC);
            $select->limit(1);
        }

        return $select;
    }

    /**
     * Load max-age information for given custom url
     * If $path is array - we must load all possible records and choose one matching earlier record in array
     *
     * @param  Aoe_Static_Model_CustomUrl $object
     * @param  array|string $path
     * @return $this
     */
    public function loadByRequestPath(Aoe_Static_Model_CustomUrl $object, $path)
    {
        if (!is_array($path)) {
            $path = array($path);
        }

        $pathBind = array();
        foreach ($path as $key => $url) {
            $pathBind['path' . $key] = $url;
        }
        // Form select
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getMainTable())
            ->where('request_path IN (:' . implode(', :', array_flip($pathBind)) . ')')
            ->where('store_id IN(?)', array(Mage_Core_Model_App::ADMIN_STORE_ID, (int)$object->getStoreId()));

        $items = $adapter->fetchAll($select, $pathBind);

        // Go through all found records and choose one with lowest penalty - earlier path in array, concrete store
        $mapPenalty = array_flip(array_values($path)); // we got mapping array(path => index), lower index - better
        $currentPenalty = null;
        $foundItem = null;
        foreach ($items as $item) {
            if (!array_key_exists($item['request_path'], $mapPenalty)) {
                continue;
            }
            $penalty = $mapPenalty[$item['request_path']] << 1 + ($item['store_id'] ? 0 : 1);
            if (!$foundItem || $currentPenalty > $penalty) {
                $foundItem = $item;
                $currentPenalty = $penalty;
                if (!$currentPenalty) {
                    break; // Found best matching item with zero penalty, no reason to continue
                }
            }
        }

        // Set data and finish loading
        if ($foundItem) {
            $object->setData($foundItem);
        }

        // Finish
        $this->unserializeFields($object);
        $this->_afterLoad($object);

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
        $this->_getWriteAdapter()->delete($this->getMainTable(),
            array('custom_url_id IN(?)' => $customUrlIds)
        );

        return $this;
    }
}
