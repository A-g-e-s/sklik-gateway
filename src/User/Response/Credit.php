<?php

declare(strict_types=1);

namespace Ages\SklikGateway\User\Response;

/**
 * Current Sklik account credit (GET /user/me/credit). Amounts in CZK.
 */
readonly class Credit
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        /** Credit without VAT. */
        public float $credit,
        public float $creditWithVat,
        public bool $verifiedInWallet,
        public ?string $agencyStatus,
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            credit: (float) ($data['credit'] ?? 0),
            creditWithVat: (float) ($data['creditWithVat'] ?? 0),
            verifiedInWallet: (bool) ($data['verifiedInWallet'] ?? false),
            agencyStatus: isset($data['agencyStatus']) && is_scalar($data['agencyStatus']) ? (string) $data['agencyStatus'] : null,
            raw: $data,
        );
    }
}
