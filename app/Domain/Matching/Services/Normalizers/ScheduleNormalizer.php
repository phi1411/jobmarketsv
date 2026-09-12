<?php

declare(strict_types=1);

namespace JobMarket\Domain\Matching\Services\Normalizers;

use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Enums\DayOfWeek;
use JobMarket\Domain\Matching\Enums\ShiftType;
use JobMarket\Domain\Matching\ValueObjects\AvailabilityValue;
use JobMarket\Domain\Matching\ValueObjects\ScheduleRequirementValue;
use JobMarket\Domain\Matching\ValueObjects\ScheduleSlot;

final class ScheduleNormalizer
{
    /**
     * Normalizes candidate schedule from available_schedule (JSON or array)
     * and preferred_shift from applications table.
     */
    public function normalizeCandidateSchedule(mixed $rawSchedule, ?string $preferredShift = null): AvailabilityValue
    {
        $parsed = $this->parseScheduleData($rawSchedule);
        if ($parsed === null) {
            // Null or completely invalid format -> UNKNOWN
            return new AvailabilityValue(
                state: DataState::UNKNOWN,
                slots: [],
                preferredShiftForApplication: $this->parseShiftType($preferredShift),
                confidence: 0.0
            );
        }

        if (empty($parsed)) {
            // Empty schedule is UNKNOWN (not yet filled), not assumed "busy"
            return new AvailabilityValue(
                state: DataState::UNKNOWN,
                slots: [],
                preferredShiftForApplication: $this->parseShiftType($preferredShift),
                confidence: 0.5
            );
        }

        $slots = [];
        $hasInvalidDayOrShift = false;

        // Check if legacy format: { "shifts": ["evening", "weekend"] }
        if (isset($parsed['shifts']) && is_array($parsed['shifts'])) {
            // Legacy shape: associate with general week slots
            foreach ($parsed['shifts'] as $shiftRaw) {
                $shiftType = $this->parseShiftType($shiftRaw);
                if ($shiftType !== null) {
                    // Legacy maps to all standard weekdays or weekend
                    if ($shiftType === ShiftType::WEEKEND) {
                        $slots[] = new ScheduleSlot(DayOfWeek::SATURDAY, ShiftType::FLEXIBLE);
                        $slots[] = new ScheduleSlot(DayOfWeek::SUNDAY, ShiftType::FLEXIBLE);
                    } else {
                        // Monday to Friday
                        foreach ([DayOfWeek::MONDAY, DayOfWeek::TUESDAY, DayOfWeek::WEDNESDAY, DayOfWeek::THURSDAY, DayOfWeek::FRIDAY] as $d) {
                            $slots[] = new ScheduleSlot($d, $shiftType);
                        }
                    }
                } else {
                    $hasInvalidDayOrShift = true;
                }
            }
        } else {
            // Matrix format: { "monday": ["evening"], "saturday": ["morning", "afternoon"] }
            foreach ($parsed as $dayKey => $shifts) {
                $day = DayOfWeek::tryFrom(mb_strtolower(trim((string)$dayKey)));
                if ($day === null) {
                    $hasInvalidDayOrShift = true;
                    continue;
                }

                if (!is_array($shifts)) {
                    $shifts = [$shifts];
                }

                foreach ($shifts as $s) {
                    $shiftType = $this->parseShiftType($s);
                    if ($shiftType !== null) {
                        $slots[] = new ScheduleSlot($day, $shiftType);
                    } else {
                        $hasInvalidDayOrShift = true;
                    }
                }
            }
        }

        $state = !empty($slots) ? DataState::AVAILABLE : DataState::UNKNOWN;
        $confidence = $hasInvalidDayOrShift ? 0.75 : 1.0;

        return new AvailabilityValue(
            state: $state,
            slots: $slots,
            preferredShiftForApplication: $this->parseShiftType($preferredShift),
            confidence: $confidence
        );
    }

    /**
     * Normalizes job schedule requirements from shift_type, shift_information, working_schedule.
     */
    public function normalizeJobSchedule(
        ?string $shiftType = null,
        ?string $shiftInformation = null,
        ?string $workingSchedule = null
    ): ScheduleRequirementValue {
        $parsedShift = $this->parseShiftType($shiftType);

        if ($parsedShift === null && empty($shiftInformation) && empty($workingSchedule)) {
            return new ScheduleRequirementValue(
                state: DataState::UNKNOWN,
                shiftType: null,
                slots: [],
                minimumShiftsPerWeek: null,
                evidence: null,
                confidence: 0.0
            );
        }

        $evidenceParts = [];
        if ($parsedShift !== null) {
            $evidenceParts[] = "shift_type:{$parsedShift->value}";
        }
        if (!empty($shiftInformation)) {
            $evidenceParts[] = "shift_information: " . trim($shiftInformation);
        }
        if (!empty($workingSchedule)) {
            $evidenceParts[] = "working_schedule: " . trim($workingSchedule);
        }

        $evidence = !empty($evidenceParts) ? implode('; ', $evidenceParts) : null;
        if ($evidence !== null && mb_strlen($evidence) > 1000) {
            $evidence = mb_substr($evidence, 0, 997) . '...';
        }

        return new ScheduleRequirementValue(
            state: DataState::AVAILABLE,
            shiftType: $parsedShift,
            slots: [],
            minimumShiftsPerWeek: null,
            evidence: $evidence,
            confidence: 0.95
        );
    }

    private function parseShiftType(mixed $val): ?ShiftType
    {
        if ($val === null) {
            return null;
        }

        $str = mb_strtolower(trim((string)$val));
        if ($str === '') {
            return null;
        }

        // Exact match
        $case = ShiftType::tryFrom($str);
        if ($case !== null) {
            return $case;
        }

        // Common Vietnamese aliases
        return match ($str) {
            'sang', 'sáng', 'ca sang', 'ca sáng' => ShiftType::MORNING,
            'chieu', 'chiều', 'ca chieu', 'ca chiều' => ShiftType::AFTERNOON,
            'toi', 'tối', 'ca toi', 'ca tối' => ShiftType::EVENING,
            'dem', 'đêm', 'ca dem', 'ca đêm' => ShiftType::NIGHT,
            'cuoi tuan', 'cuối tuần' => ShiftType::WEEKEND,
            'linh hoat', 'linh hoạt' => ShiftType::FLEXIBLE,
            'xoay ca', 'xoay' => ShiftType::ROTATING,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseScheduleData(mixed $raw): ?array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '' || $trimmed === 'null') {
                return [];
            }
            if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            return null; // Malformed JSON string
        }

        return null;
    }
}
