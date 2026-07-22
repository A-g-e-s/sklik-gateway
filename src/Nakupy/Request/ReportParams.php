<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Nakupy\Request;

use Ages\SklikGateway\Enum\ReportFileFormat;
use Ages\SklikGateway\Enum\ReportStatisticsGranularity;
use Ages\SklikGateway\Enum\ReportStatisticsSplit;
use DateTimeInterface;

/**
 * Parameters for requesting a Seznam Nákupy statistics report.
 *
 * Both `from` and `to` are required. The report is generated asynchronously –
 * the create call returns a report id which is then downloaded via ReportApi.
 */
readonly class ReportParams
{
    /**
     * @param list<ReportFileFormat>       $format     Requested output formats.
     * @param list<ReportStatisticsSplit>  $splitStats Split options (combinable).
     * @param list<int>|null               $conversionIds Restrict to given conversion IDs.
     */
    public function __construct(
        public DateTimeInterface $from,
        public DateTimeInterface $to,
        public array $format = [ReportFileFormat::Json],
        public array $splitStats = [],
        public ?array $conversionIds = null,
        public ReportStatisticsGranularity $granularity = ReportStatisticsGranularity::None,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'from' => $this->from->format(DateTimeInterface::ATOM),
            'to' => $this->to->format(DateTimeInterface::ATOM),
            'format' => array_map(static fn (ReportFileFormat $f): string => $f->value, $this->format),
            'splitStats' => array_map(static fn (ReportStatisticsSplit $s): string => $s->value, $this->splitStats),
            'granularity' => $this->granularity->value,
        ];

        if ($this->conversionIds !== null) {
            $data['conversionIds'] = $this->conversionIds;
        }

        return $data;
    }
}
