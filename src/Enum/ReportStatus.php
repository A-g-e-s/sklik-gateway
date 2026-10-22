<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Enum;

/**
 * Processing status of a requested report.
 */
enum ReportStatus: string
{
    case New = 'new';
    case Processing = 'processing';
    case Finished = 'finished';
    case MaxRowError = 'maxRowError';
    case Error = 'error';

    public function isPending(): bool
    {
        return $this === self::New || $this === self::Processing;
    }
}
