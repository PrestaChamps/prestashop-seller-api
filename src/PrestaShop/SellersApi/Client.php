<?php
/**
 * @package    PrestashopSellersApi
 * @author     Zoltan Szanto <mrbig00@gmail.com>
 * @copyright  2021 Zoltán Szántó
 */

namespace PrestaChamps\PrestaShop\SellersApi;

use Monolog\Logger;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Command\Result;
use GuzzleHttp\MessageFormatter;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Handler\CurlHandler;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;

/**
 * Class Client
 *
 * @package PrestaChamps\PrestaShop\SellersApi
 *
 * @method array getThread(array $params) Params: integer $threadId
 * @method array getThreads(array $params) Params: string $sort = "ASC", int $limit = 100, int $page = 1
 * @method array getMessages(array $params) Params: $seen = 0, string $sort = "ASC", int $limit = 100, int $page = 1
 * @method array getThreadMessages(array $params) Params: integer $threadId, $seen = 0, string $sort = "ASC", int
 *         $limit = 100, int $page = 1
 */
class Client
{
    /**
     * @var \GuzzleHttp\Client
     */
    public $client;
    /**
     * @var GuzzleClient
     */
    public $guzzleClient;

    protected $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->initClient();
    }

    protected function initClient()
    {
        $stack = HandlerStack::create();

        $stack->setHandler(new CurlHandler());

        $stack->unshift(Middleware::mapRequest(function(RequestInterface $request) {
            return $request->withUri(Uri::withQueryValue($request->getUri(), 'api_key', $this->apiKey));
        }));

        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.addons.prestashop.com/',
            'handler' => $stack,
        ]);

        $description = new Description(json_decode(file_get_contents(__DIR__ . '/service_description.json'), true));

        $this->guzzleClient = new GuzzleClient(
            $this->client,
            $description,
            null,
            new Deserializer(
                new \GuzzleHttp\Command\Guzzle\Deserializer($description, true),
                $description
            )
        );
    }


    /**
     * Directly call a specific endpoint by creating the command and executing it
     *
     * Using __call magic methods is equivalent to creating and executing a single command.
     * It also supports using optimized iterator requests by adding "Iterator" suffix to the command
     *
     * @param string $method
     * @param array  $args
     *
     * @return Result
     */
    public function __call(string $method, array $args = [])
    {
        $params = $args[0] ?? [];

        return $this->guzzleClient->$method($params);
    }
}
