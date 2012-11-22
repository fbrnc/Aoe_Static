<?php

/**
 * Data helper
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Jakub Cegielka <j.cegielka@macopedia.pl>
 */
class Aoe_Static_Helper_Url extends Mage_Core_Helper_Url
{
    public function getEncodedUrl($url=null)
    {
        $sessionUrl = Mage::getSingleton('core/session')->getReturnUrl();
        if ($sessionUrl != NULL) {
            return $this->urlEncode($sessionUrl);
        }
        if (!$url) {
            $url = $this->getCurrentUrl();
        }
        return $this->urlEncode($url);
    }
}