<?php

namespace colymba\RESTfulAPI\Tests;

use colymba\RESTfulAPI\Inflector;
use colymba\RESTfulAPI\Serializers\EmberData\RESTfulAPIEmberDataSerializer;
use colymba\RESTfulAPI\Tests\ApiTest_Author;
use colymba\RESTfulAPI\Tests\ApiTest_Book;
use colymba\RESTfulAPI\Tests\ApiTest_Library;
use colymba\RESTfulAPI\Tests\RESTfulAPITester;
use SilverStripe\Core\Injector\Injector;

/**
 * EmberData Serializer Test suite
 *
 * @author  Thierry Francois @colymba thierry@colymba.com
 * @copyright Copyright (c) 2013, Thierry Francois
 *
 * @license http://opensource.org/licenses/BSD-3-Clause BSD Simplified
 *
 * @package RESTfulAPI
 * @subpackage Tests
 */
class RESTfulAPIEmberDataSerializer_Test extends RESTfulAPITester
{
    protected $extraDataObjects = array(
        'ApiTest_Author',
        'ApiTest_Book',
        'ApiTest_Library',
    );

    protected function getSerializer()
    {
        $injector = new Injector();
        $serializer = new RESTfulAPIEmberDataSerializer();

        $injector->inject($serializer);

        return $serializer;
    }

    /* **********************************************************
     * TESTS
     * */

    /**
     * Checks serializer content type access
     */
    public function testContentType()
    {
        $serializer = $this->getSerializer();
        $contentType = $serializer->getcontentType();

        $this->assertTrue(
            is_string($contentType),
            'EmberData Serializer getcontentType() should return string'
        );
    }

    /**
     * Checks data serialization
     */
    public function testSerialize()
    {
        $serializer = $this->getSerializer();

        // test single dataObject serialization
        $dataObject = ApiTest_Author::get()->filter(array('Name' => 'Peter'))->first();
        $jsonString = $serializer->serialize($dataObject);
        $jsonObject = json_decode($jsonString);

        $this->assertEquals(
            1,
            $jsonObject->apiTest_Author->id,
            "EmberData Serialize should wrap result in an object in JSON root"
        );
    }

    /**
     * Checks sideloading records config
     */
    public function testSideloadedRecords()
    {
        Config::inst()->update(RESTfulAPIEmberDataSerializer::class, 'sideloaded_records', array(
            'ApiTest_Library' => array('Books'),
        ));

        Config::inst()->update(ApiTest_Book::class, 'api_access', true);

        $serializer = $this->getSerializer();
        $dataObject = ApiTest_Library::get()->filter(array('Name' => 'Helsinki'))->first();

        $jsonString = $serializer->serialize($dataObject);
        $jsonObject = json_decode($jsonString);

        $booksRoot = $serializer->formatName('ApiTest_Book');
        $booksRoot = Inflector::pluralize($booksRoot);

        $this->assertFalse(
            is_null($jsonObject->$booksRoot),
            "EmberData Serialize should sideload records in an object in JSON root"
        );

        $this->assertTrue(
            is_array($jsonObject->$booksRoot),
            "EmberData Serialize should sideload records as array"
        );
    }

    /**
     * Checks column name formatting
     */
    public function testFormatName()
    {
        $serializer = $this->getSerializer();

        $column = 'UpperCamelCase';
        $class = 'ApiTest_Library';

        $this->assertEquals(
            'upperCamelCase',
            $serializer->formatName($column),
            "EmberData Serializer should return lowerCamel case columns"
        );

        $this->assertEquals(
            'apiTest_Library',
            $serializer->formatName($class),
            "EmberData Serializer should return lowerCamel case class"
        );
    }
}
