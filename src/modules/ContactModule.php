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

/**
 * Contact operations.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class ContactModule extends AbstractModule implements ObjectModuleInterface
{
    /**
     * Return contact data unchanged because NIC.ru contacts are represented as contracts.
     *
     * @param array $row
     * @return array
     */
    public function contactInfo(array $row) : array
    {
        return $row;
    }

    /**
     * Return the given contact list unchanged.
     *
     * @param array $rows
     * @return array
     */
    public function contactsSearch($rows = []) : array
    {
        return $rows;
    }

    /**
     * Build a hiAPI contact identifier from the selected NIC.ru contract.
     *
     * @param array $row
     * @return array
     */
    protected function contactSet(array $row): array
    {
        return [
            'id' => $row['contract'],
        ];
    }
}
