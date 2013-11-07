<?php

/**
 * Data helper
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 */
class Aoe_Static_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Registry Key to store individual max-age times
     */
    const REGISTRY_MAX_AGE = 'aoe_static_max_age';

    /**
     * computes the minimum max-age timestamp, based on the given timestamps and a possible earlier-set timestamp
     *
     * @param array|int $timestamps
     * @return Aoe_Static_Helper_Data
     */
    public function computeRegistryMaxAge($timestamps)
    {
        $maxAge = -1;
        if (!is_array($timestamps)) {
            $timestamps = array($timestamps);
        }

        foreach ($timestamps as $timestamp) {
            if (($timestamp > 0) && (($timestamp < $maxAge) || ($maxAge < 0))) {
                $maxAge = $timestamp;
            }
        }
        if ($timestamp = Mage::registry(self::REGISTRY_MAX_AGE)) {
            if (($timestamp > 0) && (($timestamp < $maxAge) || ($timestamp < 0))) {
                $maxAge = $timestamp;
            }
            Mage::unregister(self::REGISTRY_MAX_AGE);
        }
        Mage::register(self::REGISTRY_MAX_AGE, $maxAge);

        return $this;
    }
}
