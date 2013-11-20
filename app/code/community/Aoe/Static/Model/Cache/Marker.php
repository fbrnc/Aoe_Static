<?php

class Aoe_Static_Model_Cache_Marker
{
    /**
     * @var Aoe_Static_Model_Config
     */
    protected $_config;

    /**
     * Local cache for calculated values of markers
     * @var null | array
     */
    protected $_markersValues = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_config = Mage::getSingleton('aoestatic/config');
    }

    public function addMarkerValues($markers)
    {
        if (!is_array($markers)) {
            $markers = array($markers);
        }

        $this->_markersValues = array_merge($this->_markersValues, $markers);
    }

    /**
     * Replaces markers in the $value, saves calculated value to the local cache
     *
     * @param string $value
     * @return string
     */
    public function replaceMarkers($value)
    {
        $matches = array();
        preg_match_all('|###[^#]+###|', $value, $matches);
        $markersWithoutValues = array_diff($matches[0], array_keys($this->markersValues));
        foreach($markersWithoutValues as $marker) {
            $this->markersValues[$marker] = $this->getMarkerValue($marker);
        }
        $value = str_replace(array_keys($this->markersValues), array_values($this->markersValues), $value);
        return $value;
    }

    /**
     * Returns value of a given marker
     *
     * @param string $marker
     * @return string
     */
    public function getMarkerValue($marker)
    {
        $markerValue = $marker;
        if (isset($this->markersValues[$marker]) && $this->markersValues[$marker] !== NULL) {
            $markerValue = $this->markersValues[$marker];
        } elseif ($this->_config->getMarkerCallback($marker)) {
            $markerValue = $this->executeCallback($this->_config->getMarkerCallback($marker));
        }
        return (string)$markerValue;
    }

    /**
     * Executes method defined in the marker callback configuration and returns the result
     *
     * @param string $callbackString
     * @return mixed
     */
    public function executeCallback($callbackString)
    {
        $result = "";
        try {
            if ($callbackString) {
                if (!preg_match(Mage_Cron_Model_Observer::REGEX_RUN_MODEL, (string)$callbackString, $run)) {
                    Mage::throwException('Invalid model/method definition, expecting "model/class::method".');
                }
                if (!($model = Mage::getModel($run[1])) || !method_exists($model, $run[2])) {
                    Mage::throwException('Invalid callback: %s::%s does not exist', $run[1], $run[2]);
                }
                $callback = array($model, $run[2]);
                $arguments = array();
            }
            if (empty($callback)) {
                Mage::throwException(Mage::helper('cron')->__('No callbacks found for marker'));
            }
            $result = call_user_func_array($callback, $arguments);
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $result;
    }

}
