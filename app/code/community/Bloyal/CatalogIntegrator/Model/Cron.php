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
class Bloyal_CatalogIntegrator_Model_Cron {

    
    private $oldMemoryLimit;

    private $oldExecutionTime;

    /**
     * Product collection
     *
     * @var array
     */
    protected $_productsCollection = array();

    /**
     * root category
     *
     * @var string
     */
    protected $_rootCateogryId1;

    /**
     * root category for store 1
     *
     * @var int
     */
    protected $_rootCateogryId2;

    /**
     * product active status
     *
     * @var string
     */
    protected $_productActive = '';

    /**
     * catalog model resource
     *
     * @var string
     */
    protected $_catalogModel = '';

    /**
     * store1 name
     *
     * @var string
     */
    protected $_store1Name = '';

    /**
     * store1 device key
     *
     * @var string
     */
    protected $_store1DeviceKey = '';

    /**
     * store2 name
     *
     * @var string
     */
    protected $_store2Name = '';

    /**
     * store2 device key
     *
     * @var string
     */
    protected $_store2DeviceKey = '';

    /**
     * attribute set resource
     *
     * @var string
     */
    protected $_attributeSet = '';

    /**
     * helper resource
     *
     * @var string
     */
    protected $_helper = '';

    /**
     * website id
     *
     * @var integer
     */
    protected $_websiteId = array();

    /**
     * catalogs associative array
     *
     * @var array
     */
    protected $_catalogs = array();

    /**
     * function call at init stage
     *
     * @param none
     * @return boolean
     */
    private function init() {

        // Get catalog helper
        $this->_helper = Mage::helper('bloyalCatalog');

        // Check for catalog/product is enabled or not
        if (!(int) $this->_helper->getGeneralConfig('general/active')) {

            // Send notification for Catalog module is disabled
            $this->_helper->sendNotification('The Catalog Integrator Module is Off', '');

            // Write into log file for that
            $this->_helper->log('The Catalog Integrator Module is Off', Bloyal_CatalogIntegrator_Model_Catalog::EXCEPTION_FILE);

            return false;
        }

        // Set Class members with general config values.
        $this->_catalogModel = Mage::getModel('bloyalCatalog/catalog');

        // Get root category by default
        $this->_productActive   = $this->_helper->getGeneralConfig('general/product_active');
        $this->_attributeSet    = $this->_helper->getGeneralConfig('general/product_attributeset');

        $this->_store1Name      = $this->_helper->getStoresConfig('store1_name');
        $this->_store1DeviceKey = $this->_helper->getStoresConfig('device1_key');
        $this->_store2Name      = $this->_helper->getStoresConfig('store2_name');
        $this->_store2DeviceKey = $this->_helper->getStoresConfig('device2_key');
        return true;
    }

    /**
     * function to update products
     *
     * @param $source (Manual or Cron)
     * @return unknown
     */
    public function productsUpdater($source) {

        if (!$this->init())
            return $this;

        $this->oldMemoryLimit   = ini_get("memory_limit");
        ini_set("memory_limit", "512M");
        
        $this->oldExecutionTime = ini_get("max_execution_time");
        ini_set('max_execution_time', 36000);

        $type = Bloyal_CatalogIntegrator_Model_Catalog::TYPE;

        // Create row for start execution
        $row = $this->_helper->createRow($type, 'productsUpdater', $source);

        if (trim($this->_store1DeviceKey) !== '' && trim($this->_store1Name) !== '') {

            // Set store 1 info
            $this->setStoreInfo($this->_store1Name, 1);

            // Fetching catalogs for store1
            $store1Catalogs = $this->_catalogModel->setCatalogFromBloyal($this->_store1DeviceKey, $this->_store1Name);
           
            $this->syncProductsByCatalog($store1Catalogs);
            $this->_helper->log('Store ' . $this->_store1Name . ' Products Updation Completed', Bloyal_CatalogIntegrator_Model_Catalog::REGULAR_FILE);
        }


        if (trim($this->_store2DeviceKey) !== '' && trim($this->_store2Name) !== '') {

            // Set store 2 info
            $this->setStoreInfo($this->_store2Name, 2);

            // Fetching catalogs for store2
            $store2Catalogs = $this->_catalogModel->setCatalogFromBloyal($this->_store2DeviceKey, $this->_store2Name);

            $this->syncProductsByCatalog($store2Catalogs);
            $this->_helper->log('Store ' . $this->_store2Name . ' Products Updation Completed', Bloyal_CatalogIntegrator_Model_Catalog::REGULAR_FILE);
        }

        // Update total execuation time requited
        $this->_helper->updateRow($row, Bloyal_Master_Model_Execution_Attribute_Source_Status::COMPLETED);

        ini_set("memory_limit", $this->oldMemoryLimit);     
        ini_set('max_execution_time', $this->oldExecutionTime);

        return $this;
    }

    private function setStoreInfo($strStoreName, $intStore) {

        $storeInfo = $this->_helper->getWebsiteIdByStoreName($strStoreName);
        if ($storeInfo['website_id']) {
            $this->_websiteId[] = $storeInfo['website_id'];

            if ($intStore === 1)
                $this->_rootCateogryId1 = $this->_helper->getCategoryIdByStoreName($strStoreName);
            elseif ($intStore === 2)
                $this->_rootCateogryId2 = $this->_helper->getCategoryIdByStoreName($strStoreName);
        } else {

            // Send notification for No Store Found
            $this->_helper->sendNotification('Store ' . $intStore . ' with name "' . $strStoreName . '" is not found. Please check spelling or create new store with specified name', '');

            // Write into log file for that
            $this->_helper->log('Store ' . $intStore . ' with name "' . $strStoreName . '" is not found. Please check spelling or create new store with specified name', Bloyal_CatalogIntegrator_Model_Catalog::EXCEPTION_FILE);
            $this->_helper->log('Store ' . $intStore . ' with name "' . $strStoreName . '" is not found. Please check spelling or create new store with specified name', Bloyal_CatalogIntegrator_Model_Catalog::REGULAR_FILE);
        }

        return;
    }

    /**
     * function to sync products according to catalog 
     *
     * @param $arrCatalogs
     * @return unknown
     */
    protected function syncProductsByCatalog($arrCatalogs) {

        $type = Bloyal_CatalogIntegrator_Model_Catalog::TYPE;

        $lastExecutionDate = $this->_helper->getLastExecution($type, 'productsUpdater');

        try {

            if (isset($arrCatalogs['catalogs']))
                $this->_catalogs[] = $arrCatalogs['catalogs'];

            //Fetch all products according to catalog(s)
            if (isset($arrCatalogs['uri']))
                foreach ($arrCatalogs['uri'] as $catName => $uri) {

                    $this->_helper->log('Products For Catalog => ' . $catName . ' : Started.=========>', Bloyal_CatalogIntegrator_Model_Catalog::REGULAR_FILE);

                    // Get all products updated since last execuation
                    $this->_catalogModel->getProductChanges($uri, $lastExecutionDate);

                    // Get all updated products
                    $arrProductData = $this->_catalogModel->getProductsChanged();

                    // If product(s) found and product details are set
                    if (count($arrProductData) > 0 && is_array($arrProductData['ProductDetail'])) {

                        if (isset($arrProductData['ProductDetail'][0])) {
                            foreach ($arrProductData['ProductDetail'] as $k1 => $product) {

                                // Process each produts for create or update
                                $this->processSingleProduct($product);
                            }
                        } else {
                            $this->processSingleProduct($arrProductData['ProductDetail']);
                        }
                    }

                    $this->_helper->log('Products For Catalog =>' . $catName . ' :=========> Finished.', Bloyal_CatalogIntegrator_Model_Catalog::REGULAR_FILE);
                }
        } catch (Exception $e) {

            $this->_helper->logException($e, Bloyal_CatalogIntegrator_Model_Catalog::EXCEPTION_FILE);
        }
    }

    /**
     * function to processing single product
     *
     * @param $products
     * @return unknown
     */
    protected function processSingleProduct($productDetail) {

        try {

            // Get product_id by sku
            $idMagento = Mage::getModel('catalog/product')->getIdBySku($productDetail['Inventory']['LookupCode']);

            // Parse product data
            $newProductData = $this->parseProductData($productDetail);

            // If products already exists into magento update it
            if ($idMagento) {

                $newProductData['status'] = ((bool) $this->_productActive == false) ? '2' : '1';
                $this->_catalogModel->updateProductsToMagento($idMagento, $newProductData);
                $this->_helper->log('Product data updated correctly, Magento Id: ' . $idMagento . ', SKU:' . $newProductData['sku'], Bloyal_CatalogIntegrator_Model_Catalog::REGULAR_FILE);
            } else {

                // Create new products into magento
                $newProductData['status'] = ((bool) $this->_productActive == false) ? '2' : '1';
                $idMagento = $this->_catalogModel->addProductsToMagento($newProductData);
                $this->_helper->log('Product added correctly, Magento Id: ' . $idMagento . ', SKU:' . $newProductData['sku'], Bloyal_CatalogIntegrator_Model_Catalog::REGULAR_FILE);
            }
        } catch (Exception $e) {

            $this->_helper->logException($e, Bloyal_CatalogIntegrator_Model_Catalog::EXCEPTION_FILE);
        }

        return $this;
    }

    /**
     * function to parse product data
     *
     * @param $product
     * @return array
     */
    private function parseProductData($product) {

        // Set product data
        $productArray = array(
            'name' => $product['Name'],
            'websites' => $this->_websiteId,
            'sku' => $product['Inventory']['LookupCode'],
            'qty' => $product['Inventory']['Available'],
            'description' => $product['Description'],
            'short_description' => $product['Description'],
            'price' => $product['Price'],
            'Weight' => $product['Weight'],
            'tax_class_id' => 0,
            'meta_description' => $product['MetaDescription']
        );

        // Set product categories
        $productArray['categories'] = $this->_helper->getCatalogCategories($product['CatalogSections'], $this->_catalogs);

        if ($this->_rootCateogryId1)
            array_push($productArray['categories'], $this->_rootCateogryId1);
        if ($this->_rootCateogryId2)
            array_push($productArray['categories'], $this->_rootCateogryId2);

        return $productArray;
    }

}
