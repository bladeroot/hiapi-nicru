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


/**
 * General module functions.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
interface ObjectModuleInterface
{
    /**
     * Keep references to the owning tool and the base hiAPI object.
     *
     * @param NicRuTool $tool
     */
    public function __construct(NicRuTool $tool);

    /**
     * Dispatch dynamic module methods and inject contract data when it is missing.
     *
     * @param string $method
     * @param array $args
     * @throws \hiapi\nicru\exceptions\InvalidCallException|\hiapi\nicru\exceptions\InvalidObjectException
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function __call(string $method, array $args);

    /**
     * Perform a raw HTTP GET request through the owning tool.
     *
     * @param array $data
     * @return array
     */
    public function get(array $data) : array;

    /**
     * Perform a composed NIC.ru POST request through the owning tool.
     *
     * @param AbstractRequest $request
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function post(AbstractRequest $request) : array;
}
