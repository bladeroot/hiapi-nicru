<?php
/**
 * hiAPI NIC.ru plugin
 *
 * @link      https://github.com/hiqdev/hiapi-nicru
 * @package   hiapi-nicru
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\nicru;

use hiapi\nicru\modules\ObjectModuleInterface;
use hiapi\nicru\modules\AbstractModule;
use hiapi\nicru\modules\DomainModule;
use hiapi\nicru\modules\HostModule;
use hiapi\nicru\modules\PollModule;
use hiapi\nicru\modules\ContactModule;
use hiapi\nicru\requests\AbstractRequest;
use hiapi\nicru\exceptions\InvalidCallException;
use hiapi\nicru\exceptions\RequiredParamMissingException;

/**
 * NIC.ru tool.
 */
class NicRuTool extends \hiapi\components\AbstractTool
{
    private const READABLE_PROPERTIES = ['url'];

    /** @var string */
    protected $url;

    /** @var string */
    protected $login;

    /** @var string */
    protected $password;

    /** @var HttpClient|null */
    protected $httpClient = null;

    /** @var array<string, string|ObjectModuleInterface> */
    protected $modules = [
        'domain'    => DomainModule::class,
        'domains'   => DomainModule::class,
        'host'      => HostModule::class,
        'hosts'     => HostModule::class,
        'poll'      => PollModule::class,
        'polls'     => PollModule::class,
        'contact'   => ContactModule::class,
        'contacts'  => ContactModule::class,
    ];

    /**
     * Initialize the tool with NIC.ru endpoint credentials.
     *
     * @param mixed $base
     * @param array|null $data
     * @throws RequiredParamMissingException
     */
    public function __construct($base = null, $data = null)
    {
        parent::__construct($base, $data);
        foreach (['url','login','password'] as $key) {
            if (empty($data[$key])) {
                throw new RequiredParamMissingException("`$key` must be given for NicRuTool");
            }
            $this->{$key} = $data[$key];
        }
    }

    /**
     * Route dynamic hiAPI commands to the module named by the command prefix.
     *
     * @param string $command
     * @param array $args
     * @return mixed
     * @throws InvalidCallException
     */
    public function __call($command, $args)
    {
        $parts = preg_split('/(?=[A-Z])/', $command);
        $entity = reset($parts);
        $module = $this->getModule($entity);

        return call_user_func_array([$module, $command], $args);
    }

    /**
     * Expose only non-sensitive configuration fields.
     *
     * @param string $name
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function __get($name)
    {
        if (in_array($name, self::READABLE_PROPERTIES, true) && property_exists($this, $name)) {
            return $this->{$name};
        }

        throw new \OutOfBoundsException("Property `$name` is not readable");
    }

    /**
     * Return NIC.ru request credentials for internal request builders.
     *
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->data;
    }

    /**
     * Resolve and lazily instantiate a module by entity name.
     *
     * @param string $name
     * @return ObjectModuleInterface
     * @throws InvalidCallException
     */
    public function getModule($name) : ObjectModuleInterface
    {
        if (empty($this->modules[$name])) {
            throw new InvalidCallException("module `$name` not found");
        }
        $module = $this->modules[$name];
        if (!is_object($module)) {
            $this->modules[$name] = $this->createModule($module);
        }

        return $this->modules[$name];
    }

    /**
     * Replace a module instance, primarily for tests.
     *
     * @param string $name
     * @param AbstractModule $module
     * @return self
     * @throws \hiapi\nicru\exceptions\InvalidCallException
     */
    public function setModule(string $name, AbstractModule $module): self
    {
        if (empty($this->modules[$name])) {
            throw new InvalidCallException("module `$name` not found");
        }
        $this->modules[$name] = $module;

        return $this;
    }

    /**
     * Create a module instance bound to this tool.
     *
     * @param string $class
     * @return AbstractModule
     */
    public function createModule($class) : AbstractModule
    {
        return new $class($this);
    }

    /**
     * Return the configured HTTP client, creating the default Guzzle-backed one when needed.
     *
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        if ($this->httpClient === null) {
            $guzzle = new \GuzzleHttp\Client(['base_uri' => $this->url]);
            $this->httpClient = new HttpClient($guzzle);
        }
        return $this->httpClient;
    }

    /**
     * Replace the HTTP client used by the tool.
     *
     * @param HttpClient $httpClient
     * @return self
     */
    public function setHttpClient(HttpClient $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * Perform an HTTP request with the specified method.
     *
     * Direct usage is deprecated
     *
     * @param string $method
     * @param AbstractRequest $request
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function request(string $method, AbstractRequest $request)
    {
        return $this->getHttpClient()->performRequest($method, $request);
    }
}
