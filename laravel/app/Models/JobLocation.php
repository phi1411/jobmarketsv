<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobLocation extends Model
{
    protected $table = 'job_locations';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_primary' => 'boolean',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
