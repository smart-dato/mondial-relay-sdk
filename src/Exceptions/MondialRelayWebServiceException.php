<?php

namespace SmartDato\MondialRelay\Exceptions;

class MondialRelayWebServiceException extends MondialRelayException
{
    protected const array MESSAGES = [
        1 => 'Invalid merchant account (Enseigne)',
        2 => 'Merchant account number is empty or unknown',
        9 => 'Unknown or ambiguous city',
        11 => 'Invalid pickup point number',
        12 => 'Invalid pickup point country',
        24 => 'Invalid shipment or tracking number',
        94 => 'Unknown parcel',
        95 => 'Merchant account is not activated',
        97 => 'Invalid security key',
        98 => 'Generic error: invalid parameters',
        99 => 'Generic system error',
    ];

    public function __construct(public readonly int $statusCode, string $message)
    {
        parent::__construct($message, $statusCode);
    }

    public static function fromStatusCode(int $statusCode): self
    {
        $message = self::MESSAGES[$statusCode] ?? 'Unknown error';

        return new self($statusCode, "Mondial Relay API error {$statusCode}: {$message}");
    }
}
