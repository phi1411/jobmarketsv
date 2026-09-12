<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\ValueObjects;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\Support\ValidationGuard;
use JsonSerializable;

final readonly class ScheduleRequirementValue implements JsonSerializable
{
    public const ALLOWED_KEYS = [
        'state',
        'shift_type',
        'slots',
        'minimum_shifts_per_week',
        'evidence',
        'confidence',
    ];

    /**
     * @param list<ScheduleSlot> $slots
     */
    public function __construct(
        public DataState $state = DataState::UNKNOWN,
        public ?ShiftType $shiftType = null,
        public array $slots = [],
        public ?int $minimumShiftsPerWeek = null,
        public ?string $evidence = null,
        public float $confidence = 1.0
    ) {
        if ($this->state === DataState::AVAILABLE && $this->shiftType === null && empty($this->slots) && empty($this->evidence)) {
            throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                ['schedule_requirement' => 'Trạng thái AVAILABLE yêu cầu phải có shift_type, slots hoặc evidence.'],
                'Trạng thái AVAILABLE thiếu dữ liệu lịch làm việc thực tế'
            );
        }
        if ($this->state !== DataState::AVAILABLE) {
            if ($this->shiftType !== null) {
                throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                    ['shift_type' => "Trạng thái {$this->state->value} không được mang shift_type."],
                    "Mâu thuẫn dữ liệu lịch làm việc khi trạng thái là {$this->state->value}"
                );
            }
            if (!empty($this->slots)) {
                throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                    ['slots' => "Trạng thái {$this->state->value} không được mang danh sách slots."],
                    "Mâu thuẫn dữ liệu lịch làm việc khi trạng thái là {$this->state->value}"
                );
            }
        }

        ValidationGuard::assertArrayLimit($this->slots, 'slots', 100);
        ValidationGuard::assertStringLength($this->evidence, 'evidence', 1000, true);
        ValidationGuard::assertConfidence($this->confidence, 'confidence');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, bool $strict = true): self
    {
        ValidationGuard::assertNoPii($data, 'schedule_requirement');
        if ($strict) {
            ValidationGuard::assertStrictKeys($data, self::ALLOWED_KEYS, 'schedule_requirement');
        }

        /** @var DataState $state */
        $state = ValidationGuard::assertEnum($data['state'] ?? DataState::UNKNOWN->value, DataState::class, 'state');

        $shiftType = null;
        if (isset($data['shift_type']) && $data['shift_type'] !== null) {
            /** @var ShiftType $shiftType */
            $shiftType = ValidationGuard::assertEnum($data['shift_type'], ShiftType::class, 'shift_type');
        }

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

        $minShifts = null;
        if (isset($data['minimum_shifts_per_week']) && $data['minimum_shifts_per_week'] !== null) {
            if (!is_int($data['minimum_shifts_per_week'])) {
                throw new \JobMarket\Domain\Matching\Exceptions\MatchingContractValidationException(
                    ['minimum_shifts_per_week' => 'minimum_shifts_per_week phải là số nguyên (int).'],
                    "Sai kiểu dữ liệu cho 'minimum_shifts_per_week'"
                );
            }
            $minShifts = $data['minimum_shifts_per_week'];
        }

        $evidence = ValidationGuard::assertStringLength($data['evidence'] ?? null, 'evidence', 1000, true);
        $confidence = isset($data['confidence'])
            ? ValidationGuard::assertConfidence($data['confidence'], 'confidence')
            : 1.0;

        return new self(
            state: $state,
            shiftType: $shiftType,
            slots: $slots,
            minimumShiftsPerWeek: $minShifts,
            evidence: $evidence,
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
            'shift_type' => $this->shiftType?->value,
            'slots' => array_map(fn(ScheduleSlot $slot) => $slot->toArray(), $this->slots),
            'minimum_shifts_per_week' => $this->minimumShiftsPerWeek,
            'evidence' => $this->evidence,
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
