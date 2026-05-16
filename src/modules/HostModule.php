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

use hiapi\nicru\requests\host\HostInfoRequest;
use hiapi\nicru\requests\host\HostCreateRequest;
use hiapi\nicru\requests\host\HostUpdateRequest;
use hiapi\nicru\requests\host\HostDeleteRequest;
use hiapi\nicru\requests\host\HostsSearchRequest;
use hiapi\nicru\exceptions\RequiredParamMissingException;

/**
 * Host operations.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class HostModule extends AbstractModule implements ObjectModuleInterface
{
    /** @var array */
    protected $ruZones = ['ru', 'su', 'рф', 'xn--p1ai'];

    /**
     * Resolve the owning domain for host operations before normal module dispatch.
     *
     * @param string $method
     * @param array $args
     * @throws \hiapi\nicru\exceptions\RequiredParamMissingException
     */
    public function __call(string $method, array $args)
    {
        $data = array_shift($args);
        if (empty($data['domain'])) {
            $hostInfo = $this->base->hostGetInfo($data);
            if (empty($hostInfo) || empty($hostInfo['domain'])) {
                throw new RequiredParamMissingException("`domain` is required by module Host");
            }
            $data['domain'] = $hostInfo['domain'];
        }

        array_unshift($args, $data);
        return parent::__call($method, $args);
    }

    /**
     * Delete multiple NIC.ru host objects and keep the input array keys.
     *
     * @param array $rows
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function hostsDelete(array $rows) : array
    {
        foreach ($rows as $id => $row) {
            $host = new HostModule($this->tool);
            $res[$id] = $host->hostDelete($row);
        }

        return $res;
    }

    /**
     * Request NIC.ru host details and annotate whether the host exists.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function hostInfo(array $row): array
    {
        $request = new HostInfoRequest($this->tool->getRequestData(), $row);
        $res = $this->post($request);
        $res['exists'] = !empty($res);
        return $res;
    }

    /**
     * Create or update a host using native server requests or domain update fallback.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function hostSet(array $row) : array
    {
        $parts = explode(".", $row['domain']);
        if (count($parts) > 2) {
            return $this->hostSetViaDomain($row);
        }
        $zone = array_pop($parts);
        if (in_array($zone, $this->ruZones, true)) {
            return $this->hostSetViaDomain($row);
        }
        $row['ips'] = implode(",", $row['ips']);

        return $this->hostSetNative($row);
    }

    /**
     * Delete a single NIC.ru host object.
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function hostDelete(array $row) : array
    {
        $request = new HostDeleteRequest($this->tool->getRequestData(), $row);
        return $this->post($request);
    }

    /**
     * Create or update a NIC.ru host object using native server operations.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function hostSetNative(array $row) : array
    {
        $info = $this->hostInfo($row);
        if ($info['exists']) {
            $request = new HostUpdateRequest($this->tool->getRequestData(), $row);
        } else {
            $request = new HostCreateRequest($this->tool->getRequestData(), $row);
        }

        return reset($this->post($request));
    }

    /**
     * Temporarily attach glue data through domain nameservers for zones without native host updates.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function hostSetViaDomain(array $row) : array
    {
        $domain = new DomainModule($this->tool);
        $info = $domain->domainInfo($row);
        $oldNSs = $info['nss'];
        $row['nss'] = [
            'ns1.topdns.me' => 'ns1.topdns.me',
            $row['host'] => "{$row['host']} " . ($row['ip'] ? : implode(",", $row['ips'])),
        ];
        $res = $domain->domainSetNSs($row);
        return $domain->domainSetNSs(array_merge($row, ['nss' => $oldNSs]));
    }

}
