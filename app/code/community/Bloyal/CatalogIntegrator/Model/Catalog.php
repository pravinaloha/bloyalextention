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


class Bloyal_CatalogIntegrator_Model_Catalog extends Bloyal_Master_Model_Abstract {

    /**
     * Constant for Type
     *
     * @var string
     */

	const TYPE						= 'c';

    /**
     * Constant for regular  log file name
     *
     * @var string
     */

	const REGULAR_FILE 	 			= 'bloyal_catalogIntegrator.log';

    /**
     * Constant for exception log file
     *
     * @var string
     */
	const EXCEPTION_FILE 			= 'bloyal_catalogIntegrator_exceptions.log';
	
    /**
     * Products changed array
     *
     * @var array
     */

	private $_productsChanged	= array();

    /**
     * Inventory changed array
     *
     * @var array
     */

    private $_catalogNames   = array();

    /**
     * Catalog Names array
     *
     * @var array
     */

    private $_catalogUris   = array();
    
     /**
     * Subcategory Names => id array
     *
     * @var array
     */

    private $_subCategoryArray   = array();


    /**
     * Catalog Uris array
     *
     * @var array
     */

    /**
     * helper resource
     *
     * @var string
     */
    private $_helper              = '';



	public function getProductsChanged(){
		
		return $this->_productsChanged;
	}
	

     /**
     * Get products chnaged
     *
     * @param Date $lastDate 
     * @return unknown
     */

	public function getProductChanges($uri, $lastDate = ''){

    	try{

    		$params = array('credential' => $this->getCredentials());

            $helper = Mage::helper('bloyalMaster');

    		if($lastDate) 
                $lastDate =  $helper->toApiDate($lastDate); 
            else 
                $lastDate =  $helper->toApiDate(date("Y-m-d H:i:s",strtotime("-1 week")));	

       		$result = $this->getCurlResponse('Products/detail?updated-since='.$lastDate.'&catalog-section='.$uri);
    			
			$checkResult = (array)$result;

			if(!empty($checkResult)) 
                $this->_productsChanged = $result;	
            else 
                $this->_productsChanged = array();

                		
    	}catch (Exception $e){    	

    		Mage::helper('bloyalCatalog')->logException($e,self::EXCEPTION_FILE);
    	}

    	return $this;
    }


    /**
     * Get Product list SOAP API
     *
     *
     */

    public function getSoapProductList()
    {
        $this->SoapApi();
        return true;
    }
    
   
    /**
     * Function to add products into magento
     *
     * @param $newProductData array()
     * @return Integer Product Id
     */

    public function addProductsToMagento($newProductData)
    {
        return $this->createSoapProducts($newProductData);
    }

    /**
     * Function to update products into magento
     *
     * @param $idMagento Integer
     * @param $productData array()
     * @return boolean
     */
    public function updateProductsToMagento($idMagento, $productData)
    {
        return $this->updateSoapProducts($idMagento, $productData);
    }

    
    /**
     * Get product info from magento
     *
     * @param $strSku String
     * @return Integer 
     */
    public function getProductFromMagento($strSku)
    {
        return $this->getMagentoProductsInfo($strSku);
    }

    
    /**
     * Function to fetch all catalogs from bloyal to magento
     *
     * @param null
     * @return boolean
     */

    public function setCatalogFromBloyal($strDeviceKey ,$strStoreName )
    {
        $this->_helper = Mage::helper('bloyalCatalog');

        // If Device key or Store key empty return nothing. 
        if(trim($strDeviceKey) == '' || trim($strStoreName) == '')
        {
            $this->_helper->log('Not valid or empty device key/store key.', Bloyal_CatalogIntegrator_Model_Catalog::EXCEPTION_FILE);
            return array();
        }

        // Get Device key
        $section = 'Catalogs/detail?device-key='.$strDeviceKey;
        //$section = 'Catalogs/detail';

        // Get all catalogs as per device key
        $arrCatalogResult = $this->getCurlResponse($section);

         // Create/Fetch root category for store
        $intStoreCatalog = $this->_helper->getCategoryIdByStoreName($strStoreName);

        if(!$intStoreCatalog)
        {
           $this->_helper->log('Store with name "'.$strStoreName .'" is not found. Please check spelling or create new store with specified name', Bloyal_CatalogIntegrator_Model_Catalog::EXCEPTION_FILE);
           die('store '.$strStoreName.' not found.');
        }

      
        // Store catalog with id
        $this->_catalogNames[$strStoreName] = $intStoreCatalog;

        $this->_subCategoryArray = $this->_helper->getSubCategoryIdByRootCategory($intStoreCatalog);
       // print_r($this->_subCategoryArray); die;

        if(is_array($arrCatalogResult) && isset($arrCatalogResult['CatalogDetail']))
        {
            if(isset($arrCatalogResult['CatalogDetail'][0]))
            {
                foreach ($arrCatalogResult['CatalogDetail'] as $key => $arrCatalogData) {

                    $intCatId =  isset($this->_subCategoryArray[$arrCatalogData['Name']])?$this->_subCategoryArray[$arrCatalogData['Name']]:$this->_helper->getBloyalCatalogCategories($arrCatalogData['Name'], $intStoreCatalog);
                    $arrSubCatalog = $arrCatalogData['Sections'];

                    $this->_catalogNames[$arrCatalogData['Name']] = $intCatId;

                    $this->setCatalogNameUriArray($arrSubCatalog, $intCatId );
                } 

            }
            else
            {

                $arrCatalogData = $arrCatalogResult['CatalogDetail'];
                $intCatId =  isset($this->_subCategoryArray[$arrCatalogData['Name']])?$this->_subCategoryArray[$arrCatalogData['Name']]:$this->_helper->getBloyalCatalogCategories($arrCatalogData['Name'], $intStoreCatalog);
                $arrSubCatalog = $arrCatalogData['Sections'];

                $this->_catalogNames[$arrCatalogData['Name']] = $intCatId;

                $this->setCatalogNameUriArray($arrSubCatalog, $intCatId );
            }

            
        }

        return array('catalogs'=>$this->_catalogNames, 'uri'=>$this->_catalogUris);
    }


     /**
     * Function to set all catalogs and uri into array
     *
     * @param  array $arrSubCatalog
     * @param  Integer $intCatId
     */

    private function setCatalogNameUriArray($arrSubCatalog, $intCatId)
    {
        if(is_array($arrSubCatalog ))
        {
             foreach ($arrSubCatalog as $k => $arrSubCatalogData) {

                if(isset($arrSubCatalogData[0]))
                {
                   foreach ($arrSubCatalogData as $k1 => $arrData) {
                        $intSubCatId =  isset($this->_subCategoryArray[$arrData['Name']])?$this->_subCategoryArray[$arrData['Name']]:$this->_helper->getBloyalCatalogCategories($arrData['Name'], $intCatId);
                        $this->_catalogNames[$arrData['Name']]  =  $intSubCatId;
                        $this->_catalogUris[$arrData['Name']]  =  substr($arrData['Uri'], strpos($arrData['Uri'], '/Catalog/')+9);
                   }
                }
                else
                {                           
                    $intSubCatId =  isset($this->_subCategoryArray[$arrSubCatalogData['Name']])?$this->_subCategoryArray[$arrSubCatalogData['Name']]:$this->_helper->getBloyalCatalogCategories($arrSubCatalogData['Name'], $intCatId);
                    $this->_catalogNames[$arrSubCatalogData['Name']]  =  $intSubCatId;
                    $this->_catalogUris[$arrSubCatalogData['Name']] =  substr($arrSubCatalogData['Uri'], strpos($arrSubCatalogData['Uri'], '/Catalog/')+9);
                }                   

            }
        } 
    }

   
}
