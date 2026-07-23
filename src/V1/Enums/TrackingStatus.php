<?php

namespace SmartDato\MondialRelay\V1\Enums;

enum TrackingStatus: int
{
    case Registered = 80;
    case Processing = 81;
    case Delivered = 82;
    case Anomaly = 83;
}
