<?php declare(strict_types=1);

namespace NoGlitchYo\DoDoh;

use Exception;
use InvalidArgumentException;
use NoGlitchYo\DoDoh\Factory\DnsMessageFactory;
use NoGlitchYo\DoDoh\Factory\DnsMessageFactoryInterface;
use NoGlitchYo\DoDoh\Factory\DohHttpMessageFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class HttpProxy
{
    /**
     * @var DnsPoolResolver
     */
    private $dnsResolver;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var DnsMessageFactory
     */
    private $dnsMessageFactory;
    /**
     * @var DohHttpMessageFactoryInterface
     */
    private $dohHttpMessageFactory;

    public function __construct(
        DnsResolverInterface $dnsResolver,
        DnsMessageFactoryInterface $dnsMessageFactory,
        DohHttpMessageFactoryInterface $dohHttpMessageFactory,
        LoggerInterface $logger = null
    )
    {
        $this->dnsResolver = $dnsResolver;
        $this->logger = $logger ?? new NullLogger();
        $this->dnsMessageFactory = $dnsMessageFactory;
        $this->dohHttpMessageFactory = $dohHttpMessageFactory;
    }

    public function forward(ServerRequestInterface $serverRequest): ResponseInterface
    {
        try {
            switch ($serverRequest->getMethod()) {
                case 'GET':
                    $dnsQuery = $serverRequest->getQueryParams()['dns'] ?? null;
                    if (!$dnsQuery) {
                        throw new InvalidArgumentException('Query parameter `dns` is mandatory.');
                    }
                    $dnsRequestMessage = $this->dnsMessageFactory->createMessageFromBase64($dnsQuery);
                    break;
                case 'POST':
                    $dnsRequestMessage = $this->dnsMessageFactory->createMessageFromDnsWireMessage((string)$serverRequest->getBody());
                    break;
                default:
                    throw new Exception('Request method is not supported.');
            }
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Failed to create DNS message: %s', $t->getMessage()));
            throw $t;
        }

        try {
            $dnsResponseMessage = $this->dnsResolver->resolve($dnsRequestMessage);
        } catch (Throwable $t) {
            $this->logger->error(sprintf('Failed to resolve DNS query: %s', $t->getMessage()), [
                'dnsRequestMessage' => $dnsRequestMessage,
            ]);
            throw $t;
        }

        $this->logger->info(
            sprintf("Resolved DNS query with method %s", $serverRequest->getMethod()),
            [
                'dnsRequestMessage' => $dnsRequestMessage,
                'dnsResponseMessage' => $dnsResponseMessage,
            ]
        );

        return $this->dohHttpMessageFactory->createResponseFromMessage($dnsResponseMessage);
    }
}