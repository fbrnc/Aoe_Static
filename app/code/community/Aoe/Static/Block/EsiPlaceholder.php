<?php

/**
 * Placeholder for ESI
 *
 * @author Fabrizio Branca
 * @author https://github.com/maggedotno
 */
class Aoe_Static_Block_EsiPlaceholder extends Mage_Core_Block_Template
{
    /**
     * @var string default template of this block
     */
    protected $_template = 'aoestatic/placeholder_esi.phtml';

    public function getUrl()
    {
        $url = parent::getUrl('aoestatic/esi/index', array(
            'block' => $this->getPlaceholderBlockname(),
        ));
        # make url relative (TODO: is there a smarter way of doing this?)
        return str_replace(Mage::getBaseUrl(), '/', $url);
    }
}
