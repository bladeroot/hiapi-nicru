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

use hiapi\legacy\lib\deps\err;


/**
 * Poll operations.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class PollModule extends AbstractModule implements ObjectModuleInterface
{
    /**
     * Build hiAPI poll messages from domains that changed transfer or deletion state.
     *
     * @param array|null $data
     * @return array|bool
     */
    public function pollsGetNew($data = null)
    {
        foreach (['ok', 'expired', 'outgoing'] as $state) {
            $domains = $this->base->domainsSearchForPolls([
                'status' => $state,
                'access_id' => $this->tool->getRequestData()['id'],
            ]);

            if (empty($domains)) {
                continue;
            }

            $polls = call_user_func_array([$this, "_pollsGet" . ucfirst($state) . "Message"], [$polls, $domains]);
        }

        return empty($polls) ? true : $polls;
    }

    /**
     * Detect domains that disappeared from NIC.ru while locally marked as active.
     *
     * @param array $polls
     * @param array $domains
     * @return array
     */
    protected function _pollsGetOkMessage($polls = [], $domains = [])
    {
        if (empty($domains)) {
            return $polls;
        }

        foreach ($domains as $domain) {
            $data = $this->base->domainInfo($domain);

            if (err::not($data)) {
                continue;
            }

            if (strpos(err::get($data), self::ERROR_OBJECT_DOES_NOT_EXIST) !== false) {
                $polls[] = $this->_pollBuild($domain, [
                    'type' => 'pendingTransfer',
                    'message' => 'Transfer requested',
                ], true);
            }
        }

        return $polls;
    }

    /**
     * Detect expired domains deleted at NIC.ru and mark them as deleting locally.
     *
     * @param array $polls
     * @param array $domains
     * @return array
     */
    protected function _pollsGetExpiredMessage($polls = [], $domains = [])
    {
        if (empty($domains)) {
            return $polls;
        }

        foreach ($domains as $domain) {
            $data = $this->base->domainInfo($domain);

            if (err::not($data)) {
                continue;
            }

            if (strpos(err::get($data), self::ERROR_OBJECT_DOES_NOT_EXIST) !== false) {
                $this->base->domainSetStateInDb(array_merge($domain, ['state' => 'deleting']));
                $polls[] = $this->_pollBuild($domain, [
                    'type' => 'pendingDelete',
                    'message' => 'domain deleted',
                ], false);
            }
        }

        return $polls;
    }

    /**
     * Detect outgoing transfers approved by NIC.ru.
     *
     * @param array $polls
     * @param array $domains
     * @return array
     */
    protected function _pollsGetOutgoingMessage($polls = [], $domains = []) : array
    {
        if (empty($domains)) {
            return $polls;
        }

        foreach ($domains as $id => $domain) {
            $info = $this->base->domainInfo($domain);

            if (err::is($info) && strpos(err::get($info), self::ERROR_OBJECT_DOES_NOT_EXIST) !== false) {
                $polls[] = $this->_pollBuild($domain, [
                    'type' => 'serverApproved',
                    'message' => 'Transfer approved',
                ], true);
            }
        }

        return $polls;
    }

    /**
     * Build a hiAPI poll message payload from a domain row and event data.
     *
     * @param array $row
     * @param array $data
     * @param bool $outgoing
     * @return array
     */
    private function _pollBuild($row, $data, $outgoing = false) : array
    {
        return array_merge([
            'class' => 'domain',
            'name' => $row['domain'],
            'request_client' => $this->tool->getRequestData()['name'],
            'request_date' => date("Y-m-d H:i:s"),
            'action_date' => date("Y-m-d H:i:s"),
            'action_client' => $this->tool->getRequestData()['name'],
            'outgoing' => $outgoing,
        ], $data);
    }
}
