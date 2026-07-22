<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Enum;

/**
 * Downloadable report file format types.
 */
enum ReportFileFormat: string
{
    case Json = 'json';
    case Csv = 'csv';
    case Tsv = 'tsv';
    case Xml = 'xml';
    case Html = 'html';
    case Xlsx = 'xlsx';
    case JsonWebview = 'jsonWebview';
}
