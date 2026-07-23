<?php

namespace SmartDato\MondialRelay\Exceptions;

use Throwable;

class MondialRelayConnectionException extends MondialRelayException
{
    public static function fromException(Throwable $exception): self
    {
        return new self("Could not connect to the Mondial Relay API: {$exception->getMessage()}", previous: $exception);
    }

    public static function fromResponseStatus(int $status): self
    {
        return new self("The Mondial Relay API responded with HTTP status {$status}.");
    }

    public static function invalidResponse(): self
    {
        return new self('The Mondial Relay API returned a response that could not be parsed.');
    }
}
