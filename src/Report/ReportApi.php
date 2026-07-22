<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Report;

use Ages\SklikGateway\Enum\ReportFileFormat;
use Ages\SklikGateway\Http\SklikClient;
use Ages\SklikGateway\Report\Response\ReportItem;
use Ages\SklikGateway\Report\Response\StreamedReport;

/**
 * Statistical report endpoints (/sklik/reports/).
 *
 * Reports (including Seznam Nákupy statistics reports) are generated
 * asynchronously. Create one via {@see \Ages\SklikGateway\Nakupy\NakupyApi},
 * then list/download it here once its status is ready.
 */
class ReportApi
{
    public function __construct(private readonly SklikClient $client)
    {
    }

    /**
     * List reports for the user (GET /sklik/reports/).
     *
     * @param list<int>|null $ids Restrict to specific report ids.
     * @return list<ReportItem>
     */
    public function listReports(
        ?string $order = null,
        ?int $offset = null,
        ?int $limit = null,
        ?string $type = null,
        ?array $ids = null,
    ): array {
        $data = $this->client->get('sklik/reports/', array_filter([
            'order' => $order,
            'offset' => $offset,
            'limit' => $limit,
            'type' => $type,
            'id' => $ids,
        ], static fn (mixed $v): bool => $v !== null));

        $out = [];
        foreach ((array) ($data['items'] ?? []) as $item) {
            if (is_array($item)) {
                $out[] = ReportItem::fromArray($item);
            }
        }

        return $out;
    }

    /**
     * Download the contents of a generated report as JSON (GET /sklik/reports/{reportId}).
     */
    public function getReport(int $reportId, ReportFileFormat $format = ReportFileFormat::Json): StreamedReport
    {
        $data = $this->client->get("sklik/reports/{$reportId}", ['format' => $format->value]);

        return StreamedReport::fromArray($data);
    }
}
