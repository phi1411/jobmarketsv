<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class AvailabilityValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'slots',
        'preferred_shift_for_application',
        'confidence',
    ];

    /**
     * @param list<ScheduleSlot> $slots
     */
    public function __construct(
        public DataState $state = DataState::AVAILABLE,
        public array $slots = [],
        public ?ShiftType $preferredShiftForApplication = null,
        public float $confidence = 1.0
    ) {
        if ($this->state === DataState::AVAILABLE && empty($this->slots) && $this->preferredShiftForApplication === null) {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['availability' => "AvailabilityValue ở trạng thái 'AVAILABLE' không được rỗng cả slots và preferredShiftForApplication."],
                "Dữ liệu lịch rảnh không được rỗng khi trạng thái là AVAILABLE"
            );
        }
        ValidationGuard::assertArrayLimit($this->slots, 'slots', 100);
        ValidationGuard::assertConfidence($this->confidence, 'confidence');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'availability');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'availability');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::AVAILABLE->value, DataState::class, 'state');

        $rawSlots = ValidationGuard::assertArrayLimit($data['slots'] ?? [], 'slots', 100);
        $slots = [];
        foreach ($rawSlots as $slotData) {
            if ($slotData instanceof ScheduleSlot) {
                $slots[] = $slotData;
            } elseif (is_array($slotData)) {
                $slots[] = ScheduleSlot::fromArray($slotData, $strict);
            } else {
                ValidationGuard::assertArrayLimit($slotData, 'slot_item');
            }
        }

        $preferredShift = null;
        if (isset($data['preferred_shift_for_application']) && $data['preferred_shift_for_application'] !== null) {
            /** @var ShiftType $preferredShift */
            $preferredShift = ValidationGuard::assertEnum(
                $data['preferred_shift_for_application'],
                ShiftType::class,
                'preferred_shift_for_application'
            );
        }

        $confidence = isset($data['confidence'])
            ? ValidationGuard::assertConfidence($data['confidence'], 'confidence')
            : 1.0;

        return new self(
            state: $state,
            slots: $slots,
            preferredShiftForApplication: $preferredShift,
            confidence: $confidence
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'slots' => array_map(fn(ScheduleSlot $slot) => $slot->toArray(), $this->slots),
            'preferred_shift_for_application' => $this->preferredShiftForApplication?->value,
            'confidence' => $this->confidence,
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
