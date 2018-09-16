<?php

namespace colymba\RESTfulAPI\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;

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

class ApiTest_Book extends DataObject
{
    private static $db = array(
        'Title' => 'Varchar(255)',
        'Pages' => 'Int',
    );

    private static $has_one = array(
        'Author' => 'ApiTest_Author',
    );

    private static $belongs_many_many = array(
        'Libraries' => 'ApiTest_Library',
    );

    public function validate()
    {
        if ($this->pages > 100) {
            $result = ValidationResult::create(false, 'Too many pages');
        } else {
            $result = ValidationResult::create(true);
        }

        return $result;
    }
}
