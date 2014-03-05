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

class Bloyal_CatalogIntegrator_Helper_Data extends Bloyal_Master_Helper_Data{
	
	/**
     * Get general configuration
     *
     * @param $field
     * @return string
     */

	public function getGeneralConfig($field){
	
		return Mage::getStoreConfig('bloyalcatalog/'.$field, Mage::app()->getStore()->getStoreId());
	}
	
	/**
     * Function to write log into log file
     *
     * @param String $field
     * @param String $file
     * @return boolean
     */
	
	public function log($data, $file){
		
		$arrData = explode(',', $data);
$strLogData = '';

		foreach ($arrData as $value) {
$strLogData .= '
- '.trim($value);
		}


		$strData = '
--------------------------------------------------------------------------------------------
'. $strLogData.'

--------------------------------------------------------------------------------------------

		';
		if((bool)$this->getGeneralConfig('general/logs')) Mage::log($strData,Zend_Log::DEBUG,$file);
		
		return true;
	}
	

	/**
     * Function to write log exception into log Exception file
     *
     * @param Object $e
     * @param String $field
     * @return boolean
     */

	public function logException($e, $file){
	
		if((bool)$this->getGeneralConfig('general/logs')) Mage::log("\n" . $e->__toString(), Zend_Log::ERR, $file);
	
		return true;
	}


	/**
     * Function to create or get bloyal categories
     *
     * @param String $strCatName
     * @param Int $intParentCatId
     * @return Int $intCatId
     */
	public function getBloyalCatalogCategories($strCatName, $intParentCatId = ''){
		$catId = $this->addNewBloyalCategory($strCatName, $intParentCatId);
		/*$catId = $this->getCategoryIdByName($strCatName); 

		if(!$catId){
			$catId = $this->addNewBloyalCategory($strCatName, $intParentCatId);
		}*/

		return $catId;
	}


	/**
     * Function to get Catalog categories
     *
     * @param array $arrCatalog
     * @return array Category Ids
     */
	public function getCatalogCategories($arrCatalogSections, $arrCatalog){

		$arrCategories 	= array();
		$arrCatalog1 = $arrCatalog[0];
		$arrCatalog2 = array();

		// Get catalog helper
		$_helper = Mage::helper('bloyalCatalog');

		if(isset($arrCatalog[1]))
		{
			$arrCatalog2 = $arrCatalog[1];
		}

		if(isset($arrCatalogSections['ListEntry'][0]))
		{
			foreach ($arrCatalogSections['ListEntry'] as $key => $value) {
				if(isset($arrCatalog1[$value['Name']]))
				{
					$arrCategories[] = $arrCatalog1[$value['Name']];
					$arrCategories[] = $_helper->getParentCategoryId($arrCatalog1[$value['Name']]);
				}
				if(isset($arrCatalog2[$value['Name']]))
				{
					$arrCategories[] = $arrCatalog2[$value['Name']];
					$arrCategories[] = $_helper->getParentCategoryId($arrCatalog2[$value['Name']]);
				}
			}

		}
		else
		{
			if(isset($arrCatalog1[$arrCatalogSections['ListEntry']['Name']]))
			{
					$arrCategories[] = $arrCatalog1[$arrCatalogSections['ListEntry']['Name']];
					$arrCategories[] = $_helper->getParentCategoryId($arrCatalog1[$arrCatalogSections['ListEntry']['Name']]);
			}
			if(isset($arrCatalog2[$arrCatalogSections['ListEntry']['Name']]))
			{
					$arrCategories[] = $arrCatalog2[$arrCatalogSections['ListEntry']['Name']];
					$arrCategories[] = $_helper->getParentCategoryId($arrCatalog2[$arrCatalogSections['ListEntry']['Name']]);
			}


		}

		
		// return catalog / category ids for product data
		return $arrCategories;

	}


	/**
     * Function to add new categories into magento
     *
     * @param String $strCatName
     * @param Integer $parentId
     * @return Integer $intCatId
     */
	private function addNewBloyalCategory($strCatName, $parentId='')
	{
		if(!$parentId || $parentId == '')
			$parentId = 1;

		// Set parent category
		$parentCategory = Mage::getModel('catalog/category')->load($parentId);

		$category = Mage::getModel('catalog/category');
		$category->setName($strCatName)
		->setIsActive(1)                       //activate your category
		->setDisplayMode('PRODUCTS')
		->setIsAnchor(1)
		->setCustomDesignApply(1)
		->setAttributeSetId($category->getDefaultAttributeSetId());

		// Set category path
		$category->setPath($parentCategory->getPath());

		$category->save();

		$intCatId = $category->getId();
		unset($category);

		// Return newly created category id
		return $intCatId;
	}

	/**
     * Function to get category id by name
     *
     * @param String $strCatName
     * @param Integer $parentId
     * @return Integer $intCatId
     */
	private function getCategoryIdByName($strCatName){

		$_category = Mage::getModel('catalog/category')->loadByAttribute('name', $strCatName);

		if(!empty($_category))
			$intCatId = $_category->getId();
		else
			$intCatId = '';

		return $intCatId;
	}
	
}