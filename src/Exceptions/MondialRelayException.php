<?php

namespace SmartDato\MondialRelay\Exceptions;

use Exception;

class MondialRelayException extends Exception
{
    public ?string $rawRequest = null;

    public ?string $rawResponse = null;

    public function withRawExchange(?string $rawRequest, ?string $rawResponse): static
    {
        $this->rawRequest = $rawRequest;
        $this->rawResponse = $rawResponse;

        return $this;
    }
}
