<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobBrowseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:100'],
            'shift_type' => ['nullable', 'in:morning,afternoon,evening,night,rotating,weekend,flexible'],
            'sort_by' => ['nullable', 'in:newest,salary_desc,salary_asc'],
        ]);

        $sort = $filters['sort_by'] ?? 'newest';
        $jobs = Job::query()
            ->with([
                'company:id,name,logo_url,verification_status',
                'locations:id,job_id,address_text,province,commune,latitude,longitude,is_primary',
            ])
            ->visible()
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword): void {
                $query->where(function (Builder $text) use ($keyword): void {
                    $text
                        ->where('title', 'like', "%{$keyword}%")
                        ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['city'] ?? null, function (Builder $query, string $city): void {
                $query->where(function (Builder $location) use ($city): void {
                    $location
                        ->where('city', 'like', "%{$city}%")
                        ->orWhereHas('locations', fn (Builder $jobLocation) => $jobLocation->where('province', 'like', "%{$city}%"));
                });
            })
            ->when($filters['shift_type'] ?? null, fn (Builder $query, string $shift) => $query->where('shift_type', $shift))
            ->when($sort === 'salary_desc', fn (Builder $query) => $query->orderByDesc('salary_max'))
            ->when($sort === 'salary_asc', fn (Builder $query) => $query->orderByRaw('salary_min IS NULL, salary_min ASC'))
            ->when($sort === 'newest', fn (Builder $query) => $query->orderByDesc('published_at')->orderByDesc('created_at'))
            ->paginate(15)
            ->withQueryString();

        return view('jobs.index', compact('jobs', 'filters'));
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'framework' => app()->version(),
            'mode' => 'legacy-database-read-only',
            'published_jobs' => Job::query()->visible()->count(),
        ]);
    }
}
