@extends('layouts.app')

@section('title', 'Việc Làm Part-Time | JobMarketSV Laravel')

@section('content')
    <form class="migration-filter" method="get" action="{{ route('jobs.index') }}">
        <input name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Tên công việc hoặc công ty">
        <input name="city" value="{{ $filters['city'] ?? '' }}" placeholder="Tỉnh / Thành phố">
        <select name="shift_type" aria-label="Ca làm việc">
            <option value="">Tất cả ca</option>
            @foreach(['morning' => 'Ca sáng', 'afternoon' => 'Ca chiều', 'evening' => 'Ca tối', 'night' => 'Ca đêm', 'weekend' => 'Cuối tuần', 'flexible' => 'Linh hoạt'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['shift_type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit"><i class="ri-search-line"></i> Tìm kiếm</button>
    </form>

    <div class="migration-heading">
        <div>
            <h1>Việc làm Part-time dành cho sinh viên</h1>
            <p>Tìm thấy {{ number_format($jobs->total(), 0, ',', '.') }} việc làm đang tuyển</p>
        </div>
        <form method="get" action="{{ route('jobs.index') }}">
            @foreach(request()->except('sort_by', 'page') as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <select name="sort_by" aria-label="Sắp xếp" onchange="this.form.submit()">
                <option value="newest" @selected(($filters['sort_by'] ?? 'newest') === 'newest')>Mới nhất</option>
                <option value="salary_desc" @selected(($filters['sort_by'] ?? '') === 'salary_desc')>Lương cao nhất</option>
                <option value="salary_asc" @selected(($filters['sort_by'] ?? '') === 'salary_asc')>Lương thấp nhất</option>
            </select>
        </form>
    </div>

    <section class="migration-grid">
        @forelse($jobs as $job)
            <article class="migration-card">
                <div class="migration-card__company">
                    <div class="migration-card__logo">
                        @if($job->company?->logo_url)
                            <img src="{{ $job->company->logo_url }}" alt="{{ $job->company->name }}">
                        @else
                            {{ mb_substr($job->company?->name ?? 'J', 0, 1) }}
                        @endif
                    </div>
                    <span>{{ $job->company?->name ?? 'Doanh nghiệp' }}</span>
                </div>
                <h2>{{ $job->title }}</h2>
                <div class="migration-card__meta">
                    <span class="migration-pill"><i class="ri-money-dollar-circle-line"></i> {{ $job->salaryLabel() }}</span>
                    <span class="migration-pill"><i class="ri-time-line"></i> {{ $job->shiftLabel() }}</span>
                    <span class="migration-pill"><i class="ri-map-pin-line"></i> {{ $job->locationLabel() }}</span>
                </div>
            </article>
        @empty
            <p>Không tìm thấy việc làm phù hợp.</p>
        @endforelse
    </section>

    <div class="migration-pagination">{{ $jobs->links() }}</div>
@endsection
