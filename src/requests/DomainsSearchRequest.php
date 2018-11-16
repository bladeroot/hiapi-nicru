<?php

/**
 * hiAPI NIC.ru plugin
 *
 * @link      https://github.com/hiqdev/hiapi-nicru
 * @package   hiapi-nicru
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\nicru\requests;

class DomainsSearchRequest extends DomainAbstractRequest
{
    protected $operation = 'search';
    protected $request = 'service-object';
    protected $header = 'service-object';
    protected $bodyStatic = [
        'service' => 'domain',
        'service-objects-limit' => 64000,
        'service-objects-first' => 1,
    ];
}
