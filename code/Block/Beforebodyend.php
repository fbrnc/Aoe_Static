<?php

/**
 * Beforebodyend block
 *
 * @author Fabrizio Branca
 */
class Aoe_Static_Block_Beforebodyend extends Mage_Core_Block_Template {

	/**
	 * @var Mage_Customer_Model_Session
	 */
	protected $session;

	/**
	 * Get session
	 *
	 * @return Mage_Customer_Model_Session
	 */
	public function getSession() {
		if (is_null($this->session)) {
			$this->session = Mage::getSingleton('customer/session');
		}
		return $this->session;
	}

	/**
	 * Check if there is a logged in customer
	 *
	 * @return bool
	 */
	public function isLoggedIn() {
		return $this->getSession()->isLoggedIn();
	}

	/**
	 * Get customer name
	 *
	 * @return bool|string
	 */
	public function getCustomerName() {
		if ($this->isLoggedIn()) {
			return $this->getSession()->getCustomer()->getName();
		} else {
			return false;
		}
	}

	/**
	 * Get cart summary count
	 *
	 * @return int
	 */
	public function getCartSummaryCount() {
		// return Mage::helper('checkout/cart')->getSummaryCount();
	}

}