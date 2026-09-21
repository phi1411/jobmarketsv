<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use SoftDeletes;

    protected $table = 'jobs';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'application_deadline' => 'date',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(JobLocation::class)->orderByDesc('is_primary');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $deadline): void {
                $deadline
                    ->whereNull('application_deadline')
                    ->orWhereDate('application_deadline', '>=', today());
            });
    }

    public function salaryLabel(): string
    {
        if ($this->salary_type === 'negotiable' || (!$this->salary_min && !$this->salary_max)) {
            return 'Thỏa thuận';
        }

        $suffix = match ($this->salary_type) {
            'daily' => '/ngày',
            'monthly' => '/tháng',
            default => '/giờ',
        };
        $min = $this->salary_min ? number_format((int) $this->salary_min, 0, ',', '.') . ' đ' : null;
        $max = $this->salary_max ? number_format((int) $this->salary_max, 0, ',', '.') . ' đ' : null;

        return trim(implode(' - ', array_filter([$min, $max]))) . $suffix;
    }

    public function shiftLabel(): string
    {
        return match ($this->shift_type) {
            'morning' => 'Ca sáng',
            'afternoon' => 'Ca chiều',
            'evening' => 'Ca tối',
            'night' => 'Ca đêm',
            'weekend' => 'Cuối tuần',
            'flexible' => 'Linh hoạt',
            'rotating' => 'Xoay ca',
            default => 'Theo thỏa thuận',
        };
    }

    public function locationLabel(): string
    {
        $primary = $this->locations->first();

        return $primary?->province ?? $this->city ?? $this->location ?? 'Toàn quốc';
    }
}
