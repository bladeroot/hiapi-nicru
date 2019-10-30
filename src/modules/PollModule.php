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

use hiapi\nicru\requests\domain\DomainInfoRequest;
use hiapi\nicru\requests\domain\DomainRenewRequest;
use hiapi\nicru\requests\domain\DomainUpdateRequest;
use hiapi\nicru\requests\domain\DomainsSearchRequest;
use hiapi\nicru\requests\service\ServicesSearchRequest;

/**
 * Domain operations.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class PollModule extends AbstractModule implements ObjectModuleInterface
{
    public function pollsGetNew($data = null)
    {
        foreach (['incoming', 'outgoing', 'expired'] as $state) {
            $domains = $this->base->domainsSearchForPolls([
                'status' => $state === 'expired' ? 'checked4deleting' : $state,
                'access_id' => $this->tool->data['id'],
            ]);

            if (empty($domains)) {
                continue;
            }

            $polls = call_user_func_array([$this, "_pollsGet" . ucfirst($state) . "Message"], [$polls, $domains]);
        }

        return empty($polls) ? true : $polls;

    }

    protected function _pollsGetDeleteMessage($polls = [], $domains = [])
    {
        if (empty($domains)) {
            return $polls;
        }

        foreach ($domains as $id => $domain) {
            $info = $this->base->domainInfo($domain);
            if (err::not($info)) {
                continue ;
            }

            if (err::is($info) && strpos(err::get($info), "Object does not exist") !== false) {
                // SET STATE
            }
        }

        return $polls;
    }
}
