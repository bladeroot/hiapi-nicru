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

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use hiapi\nicru\requests\AbstractRequest;
use hiapi\nicru\parsers\NicRuResponseParser;

/**
 * Perform GuzzleHttp request and return parsed response
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class HttpClient
{
    /** @var Client */
    protected $client;

    /**
     * Store the configured Guzzle client used for all NIC.ru calls.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Send a composed NIC.ru request and parse the NIC.ru response body.
     *
     * @param string $httpMethod
     * @param AbstractRequest $request
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    public function performRequest (string  $httpMethod, AbstractRequest $request) : array
    {
        $guzzleResponse = $this->request($httpMethod, $request);
        $response = $this->parseGuzzleResponse($guzzleResponse, $request);
        return $response;
    }

    /**
     * Send the request as a URL query string.
     *
     * @param AbstractRequest $request
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchGet (AbstractRequest $request): Response
    {
        $query = '?' . $this->prepareQuery($request);
        return $this->client->request('GET', $query);
    }

    /**
     * Send the request as an x-www-form-urlencoded POST body.
     *
     * @param AbstractRequest $request
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchPost (AbstractRequest $request): Response
    {
        $query = $this->prepareQuery($request);
        return $this->client->request('POST', '', [
            'body' => $query,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
    }

    /**
     * Encode the textual NIC.ru SimpleRequest payload for transport.
     *
     * @param AbstractRequest $request
     * @return string
     */
    private function prepareQuery(AbstractRequest $request): string
    {
        return "SimpleRequest=" . urlencode(sprintf("%s", $request));
    }

    /**
     * Dispatch the request to the transport method selected by caller.
     *
     * @param string $httpMethod
     * @param AbstractRequest $request
     * @return Response|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request (string $httpMethod, AbstractRequest $request): ?Response
    {
        if (!strcasecmp($httpMethod, 'GET')) {
            return $this->fetchGet($request);
        }

        if (!strcasecmp($httpMethod, 'POST')) {
            return $this->fetchPost($request);
        }

        return null;
    }

    /**
     * Convert a successful KOI8-R NIC.ru response to UTF-8 and parse it.
     *
     * @param Response $guzzleResponse
     * @param AbstractRequest $request
     * @return array
     * @throws \hiapi\nicru\exceptions\NicRuException
     */
    private function parseGuzzleResponse(Response $guzzleResponse, AbstractRequest $request)
    {
        if ($guzzleResponse->getStatusCode() !== 200) {
            throw new \Exception(trim($guzzleResponse->getReasonPhrase()));
        }

        $response = trim(mb_convert_encoding($guzzleResponse->getBody()->getContents(), 'UTF-8', 'KOI8-R'));
        return NicRuResponseParser::parse($response, $request);
    }
}
