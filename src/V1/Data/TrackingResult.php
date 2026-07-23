<?php

namespace SmartDato\MondialRelay\V1\Data;

use SimpleXMLElement;
use SmartDato\MondialRelay\V1\Enums\TrackingStatus;

final readonly class TrackingResult
{
    /** @param array<int, TrackingEvent> $events */
    public function __construct(
        public ?TrackingStatus $status,
        public string $statusLabel,
        public string $relayLabel,
        public string $relayNumber,
        public string $additionalInformation,
        public array $events,
    ) {}

    public static function fromXml(SimpleXMLElement $node): self
    {
        $events = [];

        if (isset($node->Tracing->ret_WSI2_sub_TracingColisDetaille)) {
            foreach ($node->Tracing->ret_WSI2_sub_TracingColisDetaille as $event) {
                $events[] = TrackingEvent::fromXml($event);
            }
        }

        return new self(
            status: TrackingStatus::tryFrom((int) $node->STAT),
            statusLabel: trim((string) $node->Libelle01),
            relayLabel: trim((string) $node->Relais_Libelle),
            relayNumber: trim((string) $node->Relais_Num),
            additionalInformation: trim((string) $node->Libelle02),
            events: $events,
        );
    }

    public function isDelivered(): bool
    {
        return $this->status === TrackingStatus::Delivered;
    }
}
