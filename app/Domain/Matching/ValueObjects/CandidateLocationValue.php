<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class CandidateLocationValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'location_ids',
        'names',
    ];

    /**
     * @param list<string> $locationIds
     * @param list<string> $names
     */
    public function __construct(
        public DataState $state = DataState::AVAILABLE,
        public array $locationIds = [],
        public array $names = []
    ) {
        if ($this->state === DataState::AVAILABLE && empty($this->locationIds) && empty($this->names)) {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['locations' => "CandidateLocationValue ở trạng thái 'AVAILABLE' không được rỗng cả locationIds và names."],
                "Dữ liệu địa điểm không được rỗng khi trạng thái là AVAILABLE"
            );
        }
        ValidationGuard::assertArrayLimit($this->locationIds, 'location_ids', 20);
        ValidationGuard::assertArrayLimit($this->names, 'names', 20);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'candidate_location');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'candidate_location');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::AVAILABLE->value, DataState::class, 'state');

        $rawIds = ValidationGuard::assertArrayLimit($data['location_ids'] ?? [], 'location_ids', 20);
        $locationIds = [];
        foreach ($rawIds as $id) {
            $locationIds[] = ValidationGuard::assertStringLength($id, 'location_id', 64);
        }

        $rawNames = ValidationGuard::assertArrayLimit($data['names'] ?? [], 'names', 20);
        $names = [];
        foreach ($rawNames as $name) {
            $names[] = ValidationGuard::assertStringLength($name, 'location_name', 255);
        }

        return new self(
            state: $state,
            locationIds: $locationIds,
            names: $names
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'location_ids' => $this->locationIds,
            'names' => $this->names,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
