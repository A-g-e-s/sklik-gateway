<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Enum;

/**
 * Statistical split options for a report.
 *
 * The `conversionId` split is only available for periods starting on or after 2026-01-01.
 */
enum ReportStatisticsSplit: string
{
    case DeviceType = 'deviceType';
    case WebType = 'webType';
    case ProductType = 'productType';
    case ConversionId = 'conversionId';
}
