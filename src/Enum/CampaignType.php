<?php

declare(strict_types=1);

namespace Ages\SklikGateway\Enum;

/**
 * Sklik campaign type (CampaignType).
 */
enum CampaignType: string
{
    case Combined = 'combined';
    case Video = 'video';
    case Context = 'context';
    case Fulltext = 'fulltext';
    case Product = 'product';
    /** Seznam Nákupy / Zboží.cz campaign – the same spend as the Nákupy statistics reports. */
    case Zbozi = 'zbozi';
    case Social = 'social';
    case Simple = 'simple';
    case Smart = 'smart';
    case Audio = 'audio';
}
