<?php

namespace DNADesign\RESTfulAPI\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

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
class ApiTest_Library extends DataObject
{
    private static $db = array(
        'Name' => 'Varchar(255)',
    );

    private static $many_many = array(
        'Books' => 'ApiTest_Book',
    );

    public function canView($member = null)
    {
        return Permission::check('RESTfulAPI_VIEW', 'any', $member);
    }

    public function canEdit($member = null)
    {
        return Permission::check('RESTfulAPI_EDIT', 'any', $member);
    }

    public function canCreate($member = null)
    {
        return Permission::check('RESTfulAPI_CREATE', 'any', $member);
    }

    public function canDelete($member = null)
    {
        return Permission::check('RESTfulAPI_DELETE', 'any', $member);
    }
}
