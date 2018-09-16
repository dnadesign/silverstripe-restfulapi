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

class ApiTest_Author extends DataObject
{
    private static $db = array(
        'Name' => 'Varchar(255)',
        'IsMan' => 'Boolean',
    );

    private static $has_many = array(
        'Books' => 'ApiTest_Book',
    );
}
