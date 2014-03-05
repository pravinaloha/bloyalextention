<?php
/**
 * bLoyal
 *
 * NOTICE OF LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade bLoyal to newer
 * versions in the future. If you wish to customize bLoyal for your
 * needs please refer to http://www.bloyal.com for more information.
 *
 * @category    Bloyal
 * @package     Bloyal_CatalogIntegrator
 * @copyright   Copyright (c) 2014 bLoyal Inc. (http://www.bloyal.com)
 * @license     http://www.bloyal.com
 */


/**
 * Captcha block
 *
 * @category   Community
 * @package    Bloyal_CatalogIntegrator
 * @author     Bloyal Team
 */


class Bloyal_CatalogIntegrator_Model_System_Config_Source_Attributeset {

	/**
     * Function to get source attribute set
     *
     * @param none
     * @return array
     */
	public function toOptionArray(){

		$collection = Mage::getModel('eav/entity_attribute_set')->getCollection();
		$array = array();
		foreach($collection as $col){
			if($col->getEntityTypeId() == 4) $array[$col->getAttributeSetId()] = $col->getAttributeSetName();
		}
		return $array;
	}

}