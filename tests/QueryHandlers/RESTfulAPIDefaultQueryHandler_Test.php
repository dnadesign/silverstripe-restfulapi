<?php

namespace DNADesign\RESTfulAPI\Tests;

use DNADesign\RESTfulAPI\QueryHandlers\RESTfulAPIDefaultQueryHandler;
use DNADesign\RESTfulAPI\Tests\ApiTest_Book;
use DNADesign\RESTfulAPI\Tests\ApiTest_Product;
use ilverStripe\Core\Injector\Injector;
use NADesign\RESTfulAPI\RESTfulAPIError;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * Default Query Handler Test suite
 *
 * @author  Thierry Francois @colymba thierry@colymba.com
 * @copyright Copyright (c) 2013, Thierry Francois
 *
 * @license http://opensource.org/licenses/BSD-3-Clause BSD Simplified
 *
 * @package RESTfulAPI
 * @subpackage Tests
 */
class RESTfulAPIDefaultQueryHandler_Test extends RESTfulAPITester
{
    protected $extraDataObjects = array(
        'ApiTest_Author',
        'ApiTest_Book',
        'ApiTest_Library',
        'ApiTest_Product',
    );

    protected $url_pattern = 'api/$ClassName/$ID';

    protected function getHTTPRequest($method = 'GET', $class = 'ApiTest_Book', $id = '', $params = array())
    {
        $request = new HTTPRequest(
            $method,
            'api/' . $class . '/' . $id,
            $params
        );
        $request->match($this->url_pattern);
        $request->setRouteParams(array(
            'Controller' => 'RESTfulAPI',
        ));

        return $request;
    }

    protected function getQueryHandler()
    {
        $injector = new Injector();
        $qh = new RESTfulAPIDefaultQueryHandler();

        $injector->inject($qh);

        return $qh;
    }

    public function generateDBEntries()
    {
        parent::generateDBEntries();

        $product = ApiTest_Product::create(array(
            'Title' => 'Sold out product',
            'Soldout' => true,
        ));
        $product->write();
    }

    /* **********************************************************
     * TESTS
     * */

    /**
     * Checks that query parameters are parsed properly
     */
    public function testQueryParametersParsing()
    {
        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('GET', 'ApiTest_Book', '1', array('Title__StartsWith' => 'K'));
        $params = $qh->parseQueryParameters($request->getVars());
        $params = array_shift($params);

        $this->assertEquals(
            $params['Column'],
            'Title',
            'Column parameter name mismatch'
        );
        $this->assertEquals(
            $params['Value'],
            'K',
            'Value parameter mismatch'
        );
        $this->assertEquals(
            $params['Modifier'],
            'StartsWith',
            'Modifier parameter mismatch'
        );
    }

    /**
     * Checks that access to DataObject with api_access config disabled return error
     */
    public function testAPIDisabled()
    {
        Config::inst()->update(ApiTest_Book::class, 'api_access', false);

        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('GET', 'ApiTest_Book', '1');
        $result = $qh->handleQuery($request);

        $this->assertContainsOnlyInstancesOf(
            RESTfulAPIError::class,
            array($result),
            'Request for DataObject with api_access set to false should return a RESTfulAPIError'
        );
    }

    /**
     * Checks single record requests
     */
    public function testFindSingleModel()
    {
        Config::inst()->update(ApiTest_Book::class, 'api_access', true);

        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('GET', 'ApiTest_Book', '1');
        $result = $qh->handleQuery($request);

        $this->assertContainsOnlyInstancesOf(
            ApiTest_Book::class,
            array($result),
            'Single model request should return a DataObject of class model'
        );
        $this->assertEquals(
            1,
            $result->ID,
            'IDs mismatch. DataObject is not the record requested'
        );
    }

    /**
     * Checks multiple records requests
     */
    public function testFindMultipleModels()
    {
        Config::inst()->update(ApiTest_Book::class, 'api_access', true);

        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('GET', 'ApiTest_Book');
        $result = $qh->handleQuery($request);

        $this->assertContainsOnlyInstancesOf(
            DataList::class,
            array($result),
            'Request for multiple models should return a DataList'
        );

        $this->assertGreaterThan(
            1,
            $result->toArray(),
            'Request should return more than 1 result'
        );
    }

    /**
     * Checks max record limit config
     */
    public function testMaxRecordsLimit()
    {
        Config::inst()->update(ApiTest_Book::class, 'api_access', true);
        Config::inst()->update(RESTfulAPIDefaultQueryHandler::class, 'max_records_limit', 1);

        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('GET', 'ApiTest_Book');
        $result = $qh->handleQuery($request);

        $this->assertCount(
            1,
            $result->toArray(),
            'Request for multiple models should implement limit set by max_records_limit config'
        );
    }

    /**
     * Checks new record creation
     */
    public function testCreateModel()
    {
        $existingRecords = ApiTest_Book::get()->toArray();

        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('POST', 'ApiTest_Book');

        $body = json_encode(array('Title' => 'New Test Book'));
        $request->setBody($body);

        $result = $qh->createModel('ApiTest_Book', $request);
        $rewRecords = ApiTest_Book::get()->toArray();

        $this->assertContainsOnlyInstancesOf(
            DataObject::class,
            array($result),
            'Create model should return a DataObject'
        );

        $this->assertEquals(
            count($existingRecords) + 1,
            count($rewRecords),
            'Create model should create a database entry'
        );

        $this->assertEquals(
            'New Test Book',
            $result->Title,
            "Created model title doesn't match"
        );

        // failing tests return error?
    }

    /**
     * Checks new record creation
     */
    public function testModelValidation()
    {
        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('POST', 'ApiTest_Book');

        $body = json_encode(array('Title' => 'New Test Book', 'Pages' => 101));
        $request->setBody($body);

        $result = $qh->createModel('ApiTest_Book', $request);

        $this->assertEquals(
            'Too many pages',
            $result->message,
            "Model with validation error should return the validation error"
        );
    }

    /**
     * Checks record update
     */
    public function testUpdateModel()
    {
        $firstRecord = ApiTest_Book::get()->first();

        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('PUT', 'ApiTest_Book');

        $newTitle = $firstRecord->Title . ' UPDATED';
        $body = json_encode(array('Title' => $newTitle));
        $request->setBody($body);

        $result = $qh->updateModel(ApiTest_Book::class, $firstRecord->ID, $request);
        $updatedRecord = DataObject::get_by_id('ApiTest_Book', $firstRecord->ID);

        $this->assertContainsOnlyInstancesOf(
            DataObject::class,
            array($result),
            'Update model should return a DataObject'
        );

        $this->assertEquals(
            $newTitle,
            $updatedRecord->Title,
            "Update model didn't update database record"
        );

        // failing tests return error?
    }

    /**
     * Checks record deletion
     */
    public function testDeleteModel()
    {
        $firstRecord = ApiTest_Book::get()->first();

        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('DELETE', 'ApiTest_Book');
        $result = $qh->deleteModel('ApiTest_Book', $firstRecord->ID, $request);

        $deletedRecord = DataObject::get_by_id(ApiTest_Book::class, $firstRecord->ID);

        $this->assertFalse(
            $deletedRecord,
            'Delete model should delete a database record'
        );
    }

    public function testAfterDeserialize()
    {
        $product = ApiTest_Product::get()->first();
        $qh = $this->getQueryHandler();
        $request = $this->getHTTPRequest('PUT', 'ApiTest_Product', $product->ID);
        $body = json_encode(array(
            'Title' => 'Making product available',
            'Soldout' => false,
        ));
        $request->setBody($body);

        $updatedProduct = $qh->handleQuery($request);

        $this->assertContainsOnlyInstancesOf(
            DataObject::class,
            array($updatedProduct),
            'Update model should return a DataObject'
        );

        $this->assertEquals(
            ApiTest_Product::$rawJSON,
            $body,
            "Raw JSON passed into 'onBeforeDeserialize' should match request payload"
        );

        $this->assertTrue(
            $updatedProduct->Soldout == 1,
            "Product should still be sold out, because 'onAfterDeserialize' unset the data bafore writing"
        );
    }
}
