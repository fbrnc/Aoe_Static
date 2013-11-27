<?php

/**
 * Purge adapters
 *
 * @author Fabrizio Branca
 * @since 2013-11-27
 */
class Aoe_Static_Model_Adminhtml_System_Config_Source_PurgeAdapters {

    /**
     * Get option array
     *
     * @return array
     */
    public function toOptionArray() {
        $options = array();

        $adapters = Mage::helper('aoestatic')->getConfig()->getAdapters();

        $options[] = array(
            'value'=> '',
            'label'=> Mage::helper('aoestatic')->__('(Purging disabled)')
        );

        foreach ($adapters as $key => $adapter) {
            $options[] = array(
                'value'=> $key,
                'label'=> Mage::helper('aoestatic')->__($key)
            );
        }

        Mage::dispatchEvent('aoestatic_purgeadapters_options', array('options' => &$options));

        return $options;
    }

}
