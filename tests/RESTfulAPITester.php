<?php

namespace DNADesign\RESTfulAPI\Tests;

use DNADesign\RESTfulAPI\Extensions\RESTfulAPIGroupExtension;
use DNADesign\RESTfulAPI\QueryHandlers\RESTfulAPIDefaultQueryHandler;
use DNADesign\RESTfulAPI\RESTfulAPI;
use DNADesign\RESTfulAPI\Tests\ApiTest_Author;
use DNADesign\RESTfulAPI\Tests\ApiTest_Book;
use DNADesign\RESTfulAPI\Tests\ApiTest_Library;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * RESTfulAPI Test suite common methods and tools
 *
 * @author  Thierry Francois @colymba thierry@colymba.com
 * @copyright Copyright (c) 2013, Thierry Francois
 *
 * @license http://opensource.org/licenses/BSD-3-Clause BSD Simplified
 *
 * @package RESTfulAPI
 * @subpackage Tests
 */
class RESTfulAPITester extends SapphireTest
{
    public function generateDBEntries()
    {
        $peter = ApiTest_Author::create(array(
            'Name' => 'Peter',
            'IsMan' => true,
        ));
        $marie = ApiTest_Author::create(array(
            'Name' => 'Marie',
            'IsMan' => false,
        ));

        $bible = ApiTest_Book::create(array(
            'Title' => 'The Bible',
            'Pages' => 2000,
        ));
        $kamasutra = ApiTest_Book::create(array(
            'Title' => 'Kama Sutra',
            'Pages' => 1000,
        ));

        $helsinki = ApiTest_Library::create(array(
            'Name' => 'Helsinki',
        ));
        $paris = ApiTest_Library::create(array(
            'Name' => 'Paris',
        ));

        // write to DB
        $peter->write();
        $marie->write();
        $bible->write();
        $kamasutra->write();
        $helsinki->write();
        $paris->write();

        // relations
        $peter->Books()->add($bible);
        $marie->Books()->add($kamasutra);

        $helsinki->Books()->add($bible);
        $helsinki->Books()->add($kamasutra);
        $paris->Books()->add($kamasutra);

        // since it doesn't seem to be called automatically
        $ext = new RESTfulAPIGroupExtension();
        $ext->requireDefaultRecords();
    }

    public function setDefaultApiConfig()
    {
        Config::inst()->update(RESTfulAPI::class, 'access_control_policy', 'ACL_CHECK_CONFIG_ONLY');

        Config::inst()->update(RESTfulAPI::class, 'dependencies', array(
            'authenticator' => '%$RESTfulAPITokenAuthenticator',
            'authority' => '%$RESTfulAPIDefaultPermissionManager',
            'queryHandler' => '%$RESTfulAPIDefaultQueryHandler',
            'serializer' => '%$RESTfulAPIBasicSerializer',
        ));

        Config::inst()->update(RESTfulAPI::class, 'cors', array(
            'Enabled' => true,
            'Allow-Origin' => '*',
            'Allow-Headers' => '*',
            'Allow-Methods' => 'OPTIONS, POST, GET, PUT, DELETE',
            'Max-Age' => 86400,
        ));

        Config::inst()->update(RESTfulAPIDefaultQueryHandler::class, 'dependencies', array(
            'deSerializer' => '%$RESTfulAPIBasicDeSerializer',
        ));
    }

    public function getOPTIONSHeaders($method = 'GET', $site = null)
    {
        if (!$site) {
            $site = Director::absoluteBaseURL();
        }
        $host = parse_url($site, PHP_URL_HOST);

        return array(
            'Accept' => '*/*',
            'Accept-Encoding' => 'gzip,deflate,sdch',
            'Accept-Language' => 'en-GB,fr;q=0.8,en-US;q=0.6,en;q=0.4',
            'Access-Control-Request-Headers' => 'accept, x-silverstripe-apitoken',
            'Access-Control-Request-Method' => $method,
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Host' => $host,
            'Origin' => 'http://' . $host,
            'Pragma' => 'no-cache',
            'Referer' => 'http://' . $host . '/',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36',
        );
    }

    public function getRequestHeaders($site = null)
    {
        if (!$site) {
            $site = Director::absoluteBaseURL();
        }
        $host = parse_url($site, PHP_URL_HOST);

        return array(
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding' => 'gzip,deflate,sdch',
            'Accept-Language' => 'en-GB,fr;q=0.8,en-US;q=0.6,en;q=0.4',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Host' => $host,
            'Origin' => 'http://' . $host,
            'Pragma' => 'no-cache',
            'Referer' => 'http://' . $host . '/',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36',
            'X-Silverstripe-Apitoken' => 'secret key',
        );
    }

    public function setUpOnce()
    {
        parent::setUpOnce();

        if ($this->extraDataObjects) {
            $this->generateDBEntries();
        }

        Config::inst()->update(Director::class, 'alternate_base_url', 'http://mysite.com/');
    }

    public function setUp()
    {
        parent::setUp();

        $this->setDefaultApiConfig();
    }
}
