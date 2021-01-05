<?php
/**
 * @package    PrestashopSellersApi
 * @author     Zoltan Szanto <mrbig00@gmail.com>
 * @copyright  2021 Zoltán Szántó
 */


namespace PrestaChamps\PrestaShop\SellersApi;

use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\Deserializer as GuzzleDeserializer;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Command\ResultInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Deserializer
 *
 * @package PrestaChamps\PrestaShop\SellersApi
 */
final class Deserializer
{
    /**
     * @var GuzzleDeserializer
     */
    private $guzzleDeserializer;

    /**
     * @var Description
     */
    private $serviceDescription;

    /**
     * @param GuzzleDeserializer $guzzleDeserializer
     * @param Description        $serviceDescription
     */
    public function __construct(GuzzleDeserializer $guzzleDeserializer, Description $serviceDescription)
    {
        $this->guzzleDeserializer = $guzzleDeserializer;
        $this->serviceDescription = $serviceDescription;
    }

    /**
     * @param ResponseInterface $response
     * @param RequestInterface  $request
     * @param CommandInterface  $command
     *
     * @return ResultInterface|ResponseInterface
     */
    public function __invoke(ResponseInterface $response, RequestInterface $request, CommandInterface $command)
    {
        $result = ($this->guzzleDeserializer)($response, $request, $command);

        if (!$result instanceof ResultInterface) {
            return $result;
        }

        $operation = $this->serviceDescription->getOperation($command->getName());
        $rootKey = $operation->getData('root_key');
        $isCollection = $operation->getData('is_collection');

        // In Lightspeed Retail API, all responses wrap the data by the resource name.
        // For instance, using the customers endpoint will wrap the data by the "Customer" key.
        // This is a bit inconvenient to use in userland. As a consequence, we always "unwrap" the result.
        if (null !== $rootKey) {
            $result = new Result($result[$rootKey] ?? []);
        }

        // When a collection contains a single item in Lightspeed,
        // they return the item directly instead of an array containing a single item.
        // In these cases we "wrap" the item in an array to make sure that collections are always arrays of items.
        if (true === $isCollection) {
//            $result = new Result(Filter::normalizeCollection($result->toArray()));
        }

        return $result;
    }
}
