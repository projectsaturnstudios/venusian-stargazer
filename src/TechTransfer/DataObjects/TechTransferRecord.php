<?php

namespace ProjectSaturnStudios\Stargazer\TechTransfer\DataObjects;

use ProjectSaturnStudios\Stargazer\Contracts\HydratesFromArray;

final readonly class TechTransferRecord implements HydratesFromArray
{
    public function __construct(
        public string $id,
        public string $caseNumber,
        public string $title,
        public string $description,
        public string $documentId,
        public string $category,
        public string $releaseType,
        public string $secondaryCategory,
        public string $tertiaryCategory,
        public string $center,
        public string $imageUrl,
        public string $detailUrl,
        public float $score,
    ) {}

    public static function fromArray(array $data): static
    {
        $row = array_is_list($data) ? $data : array_values($data);

        return new self(
            id: (string) ($row[0] ?? ''),
            caseNumber: (string) ($row[1] ?? ''),
            title: (string) ($row[2] ?? ''),
            description: (string) ($row[3] ?? ''),
            documentId: (string) ($row[4] ?? ''),
            category: (string) ($row[5] ?? ''),
            releaseType: (string) ($row[6] ?? ''),
            secondaryCategory: (string) ($row[7] ?? ''),
            tertiaryCategory: (string) ($row[8] ?? ''),
            center: (string) ($row[9] ?? ''),
            imageUrl: (string) ($row[10] ?? ''),
            detailUrl: (string) ($row[11] ?? ''),
            score: (float) ($row[12] ?? 0),
        );
    }
}
