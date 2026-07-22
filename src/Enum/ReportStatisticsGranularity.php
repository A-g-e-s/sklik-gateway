<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Enum;

/**
 * Supported granularity of statistics in reports.
 */
enum ReportStatisticsGranularity: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
    case None = 'none';
}
