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

/**
 * Interface for request functions.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
interface NicRuRequestInterface
{
    /**
     * Build a NIC.ru request from tool credentials and operation arguments.
     *
     * @param array $data
     * @param array $args
     */
    public function __construct(array $data, $args);

    /**
     * Render the request as a NIC.ru SimpleRequest payload.
     *
     * @return string
     */
    public function __toString();

    /**
     * Return parser rules describing response blocks and fields for this request.
     *
     * @return array
     */
    public function getParserAnswerRules() : array;
    /**
     * Return the response block used to read search pagination metadata.
     *
     * @return string|null
     */
    public function getParserSearchDelimiter() : ?string;

    /**
     * Check whether the response should be treated as a search result list.
     *
     * @return bool
     */
    public function isSearchRequest() : bool;

    /**
     * Check whether parser must collect additional subinfo blocks.
     *
     * @return bool
     */
    public function isSubInfoQueried() : bool;
}
