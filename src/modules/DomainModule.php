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
use hiapi\nicru\requests\domain\DomainWPRequest;
use hiapi\nicru\requests\service\ServicesSearchRequest;

/**
 * Domain operations.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class DomainModule extends AbstractModule implements ObjectModuleInterface
{
    const ERROR_WP_IS_NOT_AVAILABLE = 'Errors in order item templates: For this TLD service is not available.';
    const ERROR_SIMILAR_OBJECT = 'Errors in order item templates: Similar object already exists';
    /** @var array<int, string> */
    protected $domainStatuses = [
        0 => 'ok',
    ];

    /**
     * Load detailed NIC.ru info for each domain row while preserving the input keys.
     *
     * @param array $rows
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function domainsGetInfo(array $rows) :array
    {
        foreach ($rows as $id => $row) {
            $tmp = new DomainModule($this->tool);
            $res[$id] = $tmp->domainInfo($row);
        }

        return $res;
    }

    /**
     * Keep domain password data unchanged because NIC.ru password changes are not implemented here.
     *
     * @param array $row
     * @return array
     */
    public function domainSetPassword(array $row) : array
    {
        return $row;
    }

    /**
     * Search all NIC.ru domain service objects and index the parsed result by domain name.
     *
     * @param array $_rows
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function domainsLoadNicRu($_rows = []) : array
    {
        $request = $this->tool->createRequest(ServicesSearchRequest::class, [
            'service' => 'domain',
        ]);
        $result = $this->post($request);
        $domains = [];

        foreach ($result as $info) {
            $info = $this->_domainPostParseRequest($info);
            $domains[$info['domain']] = $info;
        }

        return $domains;
    }

    /**
     * Return preloaded domain rows unchanged.
     *
     * @param array $rows
     * @return array
     */
    public function domainsLoadInfo(array $rows) : array
    {
        return $rows;
    }

    /**
     * Return domain contact rows unchanged because bulk contact saving is handled elsewhere.
     *
     * @param array $rows
     * @return array
     */
    public function domainsSaveContacts(array $rows) : array
    {
        return $rows;
    }

    /**
     * Request one NIC.ru domain object and merge parsed fields into the original row.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function domainInfo(array $row): array
    {
        $request = $this->tool->createRequest(DomainInfoRequest::class, $row);
        $res = $this->post($request);
        return array_merge($this->_domainPostParseRequest($res), $row);
    }

    /**
     * Update NIC.ru domain data, expanding glue nameservers with IPs when needed.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function domainUpdate(array $row) : array
    {
        $_row = $row;
        if ($_row['nss']) {
            foreach ($_row['nss'] as $key => &$value) {
                if (strpos($value, $_row['domain']) !== false) {
                    $host = $this->base->hostGetInfo(['host' => $value]);
                    $value = "{$value} " . ($host['ip'] ? : implode(",", $host['ips']));
                }
            }
        }

        $request = $this->tool->createRequest(DomainUpdateRequest::class, $_row);
        $res = $this->post($request);
        $order = new OrderModule($this->tool);
        $res = $order->orderInfo(['order_id' => $res['order_id']]);
        return $row;
    }

    /**
     * Update domain nameservers through the generic domain update flow.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function domainSetNSs(array $row) : array
    {
        return $this->domainUpdate($row);
    }

    /**
     * Create a NIC.ru renewal order and check the resulting order state.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function domainRenew(array $row) : array
    {
        $request = $this->tool->createRequest(DomainRenewRequest::class, $row);
        $res = $this->post($request);
        $order = new OrderModule($this->tool);
        $res = $order->orderInfo(['order_id' => $res['order_id']]);
        return $row;
    }

    /**
     * Synchronize contact privacy by delegating to WHOIS proxy switching.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function domainSetContacts(array $row): array
    {
        return $this->domainSetWhoisProtect($row);
    }

    /**
     * Save domain contacts through the base hiAPI helper without forcing remote sync.
     *
     * @param array $row
     * @return array
     */
    protected function domainSaveContacts(array $row): array
    {
        return $this->base->_simple_domainSaveContacts($row, false);
    }

    /**
     * Enable NIC.ru WHOIS proxy for a domain when the TLD supports it.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function domainEnableWhoisProtect(array $row): array
    {
        return $this->domainSetWhoisProtect($row, true);
    }

    /**
     * Disable NIC.ru WHOIS proxy for a domain when the TLD supports it.
     *
     * @param array $row
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function domainDisableWhoisProtect(array $row): array
    {
        return $this->domainSetWhoisProtect($row, false);
    }

    /**
     * Buy or update the NIC.ru WHOIS proxy service for supported TLDs.
     *
     * @param array $row
     * @param bool|null $enable
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    protected function domainSetWhoisProtect(array $row, bool $enable = null): array
    {
        if (
            preg_match('/\.[rs]{1}u$/ui', $row['domain'])
        ||  preg_match('/xn--p1ai$/ui', $row['domain'])
        ||  preg_match('/рф$/ui', $row['domain'])
        ||  preg_match('/fm$/ui', $row['domain'])
        ) {
            return $row;
        }

        $enable = $enable === null ? ($row['whois_protected'] ? true : false) : $enable;
        $enable = $enable === true ? 'ON' : 'OFF';
        $info = $this->domainInfo($row);

        foreach (['switch', 'admin-on', 'tech-on', 'bill-on'] as $key) {
            $row[$key] = $enable;
        }

        $row['action'] = 'update';

        if (empty($info['wp_purchased'])) {
            $row = array_merge($row, [
                'amount' => 1,
                'action' => 'new',
                'switch' => 'ON',
            ]);
        }

        $request = $this->tool->createRequest(DomainWPRequest::class, $row);
        try {
            $res = $this->post($request);
        } catch (\Exception $e) {
           if ($e->getMessage() === self::ERROR_WP_IS_NOT_AVAILABLE || strpos($e->getMessage(), self::ERROR_SIMILAR_OBJECT) !== false) {
               return $row;
           }

           throw new \Exception($e->getMessage());
        }
        $order = new OrderModule($this->tool);
        $res = $order->orderInfo(['order_id' => $res['order_id']]);
        return $row;
    }

    /**
     * Normalize parsed NIC.ru domain fields to the hiAPI domain schema.
     *
     * @param array $domain
     * @return array
     */
    protected function _domainPostParseRequest(array $domain) : array
    {
        if (empty($domain)) {
            return ['_error' => self::ERROR_OBJECT_DOES_NOT_EXIST];
        }

        $expires = $domain['expires'];
        unset($domain['expires']);

        if ($domain['nss']) {
            foreach($domain['nss'] as &$nss) {
                if (strpos($nss, ' ') !== false) {
                    [$nss, $ip ] = explode(" ", $nss, 2);
                    $nss = "{$nss}/{$ip}";
                }
            }
        }

        return array_merge($domain, [
            'domain' => strtolower($domain['domain']),
            'statuses' => implode(",", array_filter([
                'inactive' => $domain['status.state'] !== 'DELEGATED' && $domain['status.state'] !== 'LOCK' ? 'inactive' : null,
                'clientTransferProhibited' => (
                        (isset($domain['status.transfer']) && $domain['status.transfer'] === 'ON')
                    ||  (isset($domain['status.transfer']) && $domain['status.state'] === 'LOCK')
                ) ? 'clientTransferProhibited' : null,
                'autoprolong' => $domain['status.autoprolong'] == 1 ? 'autoprolong' : null,
            ])),
            'nameservers' => $domain['nss'] ? implode(',', $domain['nss']) : '',
            'expiration_date' => date("Y-m-d H:i:s", strtotime($expires)),
            'wp_enabled' => isset($domain['wp_enabled']) && $domain['wp_enabled'] === 'ON',
            'wp_purchased' => in_array($domain['wp_enabled'] ?? null, ['ON', 'OFF'], true),
        ]);
    }
}
