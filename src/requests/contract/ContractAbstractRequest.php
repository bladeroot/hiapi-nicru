<?php

/**
 * hiAPI NicRu plugin
 *
 * @link      https://github.com/hiqdev/hiapi-nicru
 * @package   hiapi-nicru
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\nicru\requests\contract;

use hiapi\nicru\requests\AbstractRequest;
/**
 * Contract main request composer.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class ContractAbstractRequest extends AbstractRequest
{
    /* {@inheritdoc} */
    protected $operation = 'search';
    protected $request = 'contract';
    protected $header = 'contract';
    protected $bodyStatic = [
        'contracts-limit' => 1,
        'contracts-first' => 1,
    ];
    protected $answer = [
        'delimiter' => 'contract',
        'fields' => [
            'contract-num' => 'contract',
            'phone' => 'phone',
            'email' => 'email',
            'person' => 'name',
            'passport' => 'passport',
            'org' => 'organization',
            'code' => 'inn',
        ],
    ];
    protected $search = [
        'delimiter' => 'contracts-list',
        'fields' => [
            'contracts-found',
            'contracts-limit',
            'contracts-first',
        ],
    ];

}
