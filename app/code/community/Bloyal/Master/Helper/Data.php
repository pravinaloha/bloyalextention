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
 * @package     Bloyal_Master
 * @copyright   Copyright (c) 2014 bLoyal Inc. (http://www.bloyal.com)
 * @license     http://www.bloyal.com
 */


/**
 * Captcha block
 *
 * @category   Community
 * @package    Bloyal_Master
 * @author     Bloyal Team
 */
class Bloyal_Master_Helper_Data extends Mage_Core_Helper_Abstract{
	
	/**
     * Base API Url for accessing products
     *
     */
	//public $apiUrl = 'https://api-staging.bloyal.com/';
	public $apiUrl = 'https://api1.bloyal.com/';

	/**
     * Return bloyal configuration 
     *
     * @return string
     */
	public function getBloyalConfig($field){

		return Mage::getStoreConfig('bloyalmaster/'.$field, Mage::app()->getStore()->getStoreId());
	}
	
	/**
     * Return bloyal email configuration
     *
     * @return string
     */
	public function getEmailConfig($field){
		
		return $this->getBloyalConfig('email/'.$field);
	}

	/**
     * Return bloyal stores configuration
     *
     * @return string
     */
	public function getStoresConfig($field){
		
		return $this->getBloyalConfig('stores/'.$field);
	}
	
	/**
     * Return last execution date for cron and manual as well
     *
     * @return date
     */
	public function getLastExecution($type,$code){
		
		$lastDate = Mage::getResourceModel('bloyalMaster/execution_collection')
												->addFieldToFilter('process_type',$type)
												->addFieldToFilter('code',$code)
												->addFieldToFilter('status',Bloyal_Master_Model_Execution_Attribute_Source_Status::COMPLETED)
												->setOrder('entity_id','desc')
												->getFirstItem()
												->getData('last_time_run');


		if($lastDate == '' || $lastDate == null)
		{
			// for first time fetch all products
			$lastDate = '1970-01-01 00:00:00';
		}

		return $lastDate;
	}
	
	/**
     * Create new row into database with updating status.
     *
     * @return object
     */
	public function createRow($type,$code, $source){
	
		//Get model source
		$mdlSource = Mage::getModel('bloyalMaster/execution_attribute_source_executionsource');
		
		// Get execution model
		$row = Mage::getModel('bloyalMaster/execution');

		$row->setLastTimeRun(Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'))
			->setProcessType($type)
			->setStatus(Bloyal_Master_Model_Execution_Attribute_Source_Status::RUNNING)
			->setCode($code)
			->setQty(0)
			->setExecutionSource(($source)? $mdlSource->getCronSource() : $mdlSource->getManualSource())
			->save();
		
		return $row;
	}
	
	/**
     * Update execution records which was completed.
     *
     * @return boolean
     */
	public function updateRow($row,$status,$qty = 0){
	
		$row->setFinishedAt(Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'))
			->setStatus($status)
			->setQty($qty)
			->save();
		
		return true;
	}
		
	/**
     * Parse xml response into object array
     *
     * @param String $xml
     *
     * @return Array
     */
	public function parseXmlToArray($xml){
	
		//return simplexml_load_string($xml);
		$array = json_decode(json_encode((array)simplexml_load_string($xml)),1);

		// return sorted array by updateddate
		return $array;
	}

	/**
     * Function to get email configuration and send notification 
     * email to assigned email(s)
     *
     * @return none
     */
	public function sendNotification($message, $html){
				
		// Get email configuration
		$emailToName	= $this->getEmailConfig('email_to_name');
		$emailTo		= $this->getEmailConfig('send_to');
		$copyMethod 	= $this->getEmailConfig('copy_method');
		$templateId 	= $this->getEmailConfig('email_template');
		$data 			= $this->getEmailConfig('copy_to');
		$identity		= $this->getEmailConfig('identity');
			
		// Check for copy to 
		$copyTo = (!empty($data)? explode(',',$data):false);
		
		// Set mail template
		$mailTemplate = Mage::getModel('core/email_template');
		
		try {
				
				if ($copyTo && $copyMethod == 'bcc') 
				{
					foreach ($copyTo as $email){

						// Add Bcc
						$mailTemplate->addBcc($email);
					} 
				}
			
				// Send to email and name
				$sendTo = array(
							array('email' => $emailTo,
								  'name'  => $emailToName)
							);
			
				if ($copyTo && $copyMethod == 'copy') {
					foreach ($copyTo as $email) {
						$sendTo[] = array('email' => $email,
										  'name'  => null);
					}
				}
				
				// Get store id
				$storeId = Mage::app()->getStore()->getId();
				
				// Send email one by one
				foreach ($sendTo as $recipient) {
					$mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$storeId))
								 ->sendTransactional($templateId,
													 $identity,
													 $recipient['email'],
													 $recipient['name'],
													 array('html_message'=> $message,
														   'html_content'=> $html)
													);
			}
		} catch(Exception $e){
			throw $e;
		}
	}

	/**
     * Function to get date format specified to API
     *
     * @return date
     */
	public function toApiDate($date)
	{
		$timestamp = strtotime($date);
	    $seconds = strtotime($timestamp) / 1000;
	    $remainder = round($seconds - ($seconds >> 0), 3) * 1000;

	    $newDate = date('Y-m-d H:i:s.', $timestamp).$remainder;
	    return str_replace(' ','T',$newDate).'Z'; ;
	}
	

	
	/**
     * Function to get category id by store name 
     *
     * @return id
     */
	public function getCategoryIdByStoreName($strStoreName)
	{
		$storeData = $this->getWebsiteIdByStoreName($strStoreName);
		return Mage::app()->getStore($storeData['store_id'])->getRootCategoryId(); 
	}

	/**
     * Function to get parent category id
     *
     * @return id
     */
	public function getParentCategoryId($intCatId)
	{
		$cat = Mage::getModel('catalog/category')->load($intCatId);
		return $cat->getParentId();
	}



	/**
     * Function to get all subcategory by root category
     *
     * @return array()
     */
	public function getSubCategoryIdByRootCategory($intRootCategory)
	{
		$category_model = Mage::getModel('catalog/category');
		$_category = $category_model->load($intRootCategory); //$categoryid for which the child categories to be found       
		$all_child_categories = $category_model->getResource()->getAllChildren($_category); //array consisting of all child categories id

		foreach($all_child_categories as $subCatid)
		{
		  	$_category 					= Mage::getModel('catalog/category')->load($subCatid);
		    $catname     				= $_category->getName();
		  	$arrCategories[$catname] 	= $_category->getId();
		}

		return $arrCategories;
	}


	
	/**
     * Function to get wesite id 
     *
     * @return id
     */
	public function getWebsiteIdByStoreName($strStoreName)
	{

		$allStores = Mage::app()->getStores();
		foreach ($allStores as $_eachStoreId => $val)
		{           
			//echo $_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
			$_storeName = Mage::app()->getStore($_eachStoreId)->getGroup()->getName();
			$_storeId = Mage::app()->getStore($_eachStoreId)->getId();
			$_websiteId =  Mage::getModel('core/store')->load($_storeId)->getWebsiteId();

			//echo $website = Mage::app()->getWebsite($_websiteId)->getName();
	
			if(trim(strtolower($_storeName)) === trim(strtolower($strStoreName)))
			{
				return array('website_id' => Mage::getModel('core/store')->load($_storeId)->getWebsiteId(), 'store_id' => $_storeId);
				exit;
			}
		}

		// if no store found
		return 0;
	}




}