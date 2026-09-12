<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class JobLocationValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'location_id',
        'city',
        'district',
        'work_mode',
    ];

    public function __construct(
        public DataState $state = DataState::UNKNOWN,
        public ?string $locationId = null,
        public ?string $city = null,
        public ?string $district = null,
        public ?string $workMode = 'onsite'
    ) {
        if ($this->state === DataState::AVAILABLE && $this->locationId === null && $this->city === null && $this->district === null && $this->workMode !== 'remote') {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['job_location' => 'Trạng thái AVAILABLE yêu cầu phải có location_id, city, district hoặc work_mode=remote.'],
                'Trạng thái AVAILABLE thiếu dữ liệu địa điểm thực tế'
            );
        }
        if ($this->state !== DataState::AVAILABLE) {
            if ($this->locationId !== null || $this->city !== null || $this->district !== null) {
                throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                    ['location' => "Trạng thái {$this->state->value} không được mang thông tin chi tiết địa điểm."],
                    "Mâu thuẫn dữ liệu địa điểm khi trạng thái là {$this->state->value}"
                );
            }
        }

        ValidationGuard::assertStringLength($this->locationId, 'location_id', 64, true);
        ValidationGuard::assertStringLength($this->city, 'city', 100, true);
        ValidationGuard::assertStringLength($this->district, 'district', 100, true);
        ValidationGuard::assertStringLength($this->workMode, 'work_mode', 50, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'job_location');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'job_location');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::UNKNOWN->value, DataState::class, 'state');
        $locationId = ValidationGuard::assertStringLength($data['location_id'] ?? null, 'location_id', 64, true);
        $city = ValidationGuard::assertStringLength($data['city'] ?? null, 'city', 100, true);
        $district = ValidationGuard::assertStringLength($data['district'] ?? null, 'district', 100, true);
        $workMode = ValidationGuard::assertStringLength($data['work_mode'] ?? null, 'work_mode', 50, true);

        return new self(
            state: $state,
            locationId: $locationId,
            city: $city,
            district: $district,
            workMode: $workMode
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'location_id' => $this->locationId,
            'city' => $this->city,
            'district' => $this->district,
            'work_mode' => $this->workMode,
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
