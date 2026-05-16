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

use hiapi\nicru\requests\contract\ContractInfoRequest;
use hiapi\nicru\requests\contract\ContractsSearchRequest;

/**
 * Contract operations.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class ContractModule extends AbstractModule implements ObjectModuleInterface
{
    /**
     * Request NIC.ru contract details for the given contract search data.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function contractInfo(array $row) : array
    {
        unset($row['contract']);
        $request = new ContractInfoRequest($this->tool->data, $row);
        return $this->post($request);
    }

    /**
     * Search NIC.ru contracts and return parsed contract rows.
     *
     * @param array $rows
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function contractsSearch($rows = []) : array
    {
        unset($rows['contract']);
        $request = new ContractsSearchRequest($this->tool->data, $rows);
        return $this->post($request);
    }
}
