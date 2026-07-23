<?php

namespace SmartDato\MondialRelay\V1;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use SensitiveParameter;
use SimpleXMLElement;
use SmartDato\MondialRelay\Exceptions\MondialRelayConnectionException;
use SmartDato\MondialRelay\Exceptions\MondialRelayWebServiceException;
use SmartDato\MondialRelay\V1\Data\PickupPoint;
use SmartDato\MondialRelay\V1\Data\TrackingResult;
use SmartDato\MondialRelay\V1\Queries\PickupPointSearchQuery;

class Client
{
    public const string DEFAULT_URL = 'https://api.mondialrelay.com/Web_Services.asmx';

    protected const string XML_NAMESPACE = 'http://www.mondialrelay.fr/webservice/';

    protected const string SOAP_NAMESPACE = 'http://schemas.xmlsoap.org/soap/envelope/';

    protected ?string $lastRawRequest = null;

    protected ?string $lastRawResponse = null;

    public function __construct(
        protected string $enseigne,
        #[SensitiveParameter] protected string $privateKey,
        protected string $url = self::DEFAULT_URL,
    ) {}

    /** @return Collection<int, PickupPoint> */
    public function searchPickupPoints(PickupPointSearchQuery $query): Collection
    {
        $result = $this->call('WSI4_PointRelais_Recherche', $query->toParameters());

        $pickupPoints = collect();

        if (! isset($result->PointsRelais->PointRelais_Details)) {
            return $pickupPoints;
        }

        foreach ($result->PointsRelais->PointRelais_Details as $node) {
            $pickupPoints->push(PickupPoint::fromXml($node));
        }

        return $pickupPoints;
    }

    public function trackParcel(string $shipmentNumber, string $language = 'FR'): TrackingResult
    {
        $result = $this->call('WSI2_TracingColisDetaille', [
            'Expedition' => $shipmentNumber,
            'Langue' => $language,
        ], successStatuses: [0, 80, 81, 82, 83]);

        return TrackingResult::fromXml($result);
    }

    public function lastRawRequest(): ?string
    {
        return $this->lastRawRequest;
    }

    public function lastRawResponse(): ?string
    {
        return $this->lastRawResponse;
    }

    /**
     * @param  array<string, string|int>  $parameters
     * @param  array<int, int>  $successStatuses
     */
    protected function call(string $method, array $parameters, array $successStatuses = [0]): SimpleXMLElement
    {
        $parameters = ['Enseigne' => $this->enseigne, ...$parameters];
        $parameters['Security'] = $this->securityHash($parameters);

        $envelope = $this->buildEnvelope($method, $parameters);

        $this->lastRawRequest = $envelope;
        $this->lastRawResponse = null;

        try {
            $response = Http::withHeaders([
                'SOAPAction' => '"'.self::XML_NAMESPACE.$method.'"',
            ])
                ->withBody($envelope, 'text/xml; charset=utf-8')
                ->post($this->url);
        } catch (ConnectionException $exception) {
            throw MondialRelayConnectionException::fromException($exception)
                ->withRawExchange($envelope, null);
        }

        $this->lastRawResponse = $response->body();

        if ($response->failed()) {
            throw MondialRelayConnectionException::fromResponseStatus($response->status())
                ->withRawExchange($envelope, $response->body());
        }

        try {
            $result = $this->parseResult($method, $response->body());
        } catch (MondialRelayConnectionException $exception) {
            throw $exception->withRawExchange($envelope, $response->body());
        }

        $status = (int) $result->STAT;

        if (! in_array($status, $successStatuses, true)) {
            throw MondialRelayWebServiceException::fromStatusCode($status)
                ->withRawExchange($envelope, $response->body());
        }

        return $result;
    }

    /** @param array<string, string|int> $parameters */
    protected function securityHash(array $parameters): string
    {
        $concatenated = implode('', array_map(strval(...), array_values($parameters)));

        return strtoupper(md5($concatenated.$this->privateKey));
    }

    /** @param array<string, string|int> $parameters */
    protected function buildEnvelope(string $method, array $parameters): string
    {
        $elements = '';

        foreach ($parameters as $name => $value) {
            $elements .= sprintf(
                '<%1$s>%2$s</%1$s>',
                $name,
                htmlspecialchars((string) $value, ENT_XML1),
            );
        }

        return <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
              <soap:Body>
                <{$method} xmlns="http://www.mondialrelay.fr/webservice/">{$elements}</{$method}>
              </soap:Body>
            </soap:Envelope>
            XML;
    }

    protected function parseResult(string $method, string $body): SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($body);

        if ($xml === false) {
            throw MondialRelayConnectionException::invalidResponse();
        }

        $response = $xml
            ->children(self::SOAP_NAMESPACE)
            ->Body
            ->children(self::XML_NAMESPACE)
            ->{"{$method}Response"};

        if (! isset($response->{"{$method}Result"})) {
            throw MondialRelayConnectionException::invalidResponse();
        }

        return $response->{"{$method}Result"};
    }
}
