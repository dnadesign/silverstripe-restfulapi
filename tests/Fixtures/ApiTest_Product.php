<?php

namespace colymba\RESTfulAPI\Tests;

use SilverStripe\ORM\DataObject;

/**
 * RESTfulAPI Test suite DataObjects
 *
 * @author  Thierry Francois @colymba thierry@colymba.com
 * @copyright Copyright (c) 2013, Thierry Francois
 *
 * @license http://opensource.org/licenses/BSD-3-Clause BSD Simplified
 *
 * @package RESTfulAPI
 * @subpackage Tests
 */
class ApiTest_Product extends DataObject
{
    public static $rawJSON;

    private static $db = array(
        'Title' => 'Varchar(64)',
        'Soldout' => 'Boolean',
    );

    private static $api_access = true;

    public function onAfterDeserialize(&$payload)
    {
        // don't allow setting `Soldout` via REST API
        unset($payload['Soldout']);
    }

    public function onBeforeDeserialize(&$rawJson)
    {
        self::$rawJSON = $rawJson;
    }
}
