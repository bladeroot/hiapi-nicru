<?php
/**
 * hiAPI NIC.ru plugin
 *
 * @link      https://github.com/hiqdev/hiapi-nicru
 * @package   hiapi-nicru
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\nicru\modules;

use hiapi\nicru\NicRuTool;
use hiapi\nicru\requests\AbstractRequest;
use hiapi\nicru\exceptions\InvalidCallException;
use hiapi\nicru\exceptions\InvalidObjectException;

/**
 * General module functions.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
abstract class AbstractModule implements ObjectModuleInterface
{
    const ERROR_OBJECT_DOES_NOT_EXIST = 'Object does not exist';

    /** @var NicRuTool */
    public $tool;
    /** @var object */
    public $base;

    /** @var array list of functions for emulation without errors */
    static protected $emulatedFuncs = [
//        'domainSaveContacts',
    ];

    /**
     * Keep references to the owning tool and the base hiAPI object.
     *
     * @param NicRuTool $tool
     */
    public function __construct(NicRuTool $tool)
    {
        $this->tool = $tool;
        $this->base = $tool->getBase();
    }

    /**
     * Dispatch dynamic module methods and inject contract data when it is missing.
     *
     * @param string $method
     * @param array $args
     * @throws \hiapi\nicru\exceptions\InvalidCallException|\hiapi\nicru\exceptions\InvalidObjectException
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function __call(string $method, array $args)
    {
        $data = array_shift($args);

        if (!method_exists($this, $method)) {
            if (in_array($method, self::$emulatedFuncs, true)) {
                return $data;
            }

            throw new InvalidCallException("{$method} is not available");
        }

        if (empty($data['contract'])) {
            $contract = new ContractModule($this->tool);
            $contractInfo = $contract->contractInfo($data);
            if (empty($contractInfo)) {
                throw new InvalidObjectException('Object does not exist');
            }
            $data = array_merge($contractInfo, $data);
        }

        return call_user_func([$this, $method], $data);

    }

    /**
     * Perform a raw HTTP GET request through the owning tool.
     *
     * @param array $data
     * @return array
     */
    public function get(array $data) : array
    {
        return $this->tool->request('GET', ['SimpleRequest' => sprintf("%s", $data)]);
    }

    /**
     * Perform a composed NIC.ru POST request through the owning tool.
     *
     * @param AbstractRequest $request
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function post(AbstractRequest $request) : array
    {
        return $this->tool->request('POST', $request);
    }
}
