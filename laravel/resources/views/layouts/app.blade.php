<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'JobMarketSV')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/vendor/remixicon/remixicon.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <style>
        body{font-family:"Plus Jakarta Sans",sans-serif;background:#f7faf9}.migration-banner{background:#052e24;color:#d1fae5;padding:.65rem 1rem;text-align:center;font-size:.82rem}.migration-shell{max-width:1200px;margin:0 auto;padding:0 1.25rem}.migration-header{background:#fff;border-bottom:1px solid #dce7e2}.migration-nav{min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:1rem}.migration-logo{color:#071f18;font-weight:800;font-size:1.35rem;text-decoration:none}.migration-logo span{color:#059669}.migration-page{padding:2rem 0 4rem}.migration-filter{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.75rem;background:#fff;padding:1rem;border:1px solid #dce7e2;border-radius:18px;box-shadow:0 12px 35px rgba(5,46,36,.06)}.migration-filter input,.migration-filter select{width:100%;min-height:46px;border:1px solid #cbdad4;border-radius:12px;padding:0 .9rem;background:#fff}.migration-filter button{border:0;border-radius:12px;padding:0 1.25rem;font-weight:700;background:#059669;color:#fff;cursor:pointer}.migration-heading{display:flex;align-items:end;justify-content:space-between;gap:1rem;margin:2rem 0 1rem}.migration-heading h1{margin:0;color:#071f18;font-size:1.65rem}.migration-heading p{margin:.35rem 0 0;color:#64748b}.migration-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.migration-card{background:#fff;border:1px solid #dce7e2;border-radius:16px;padding:1.15rem;transition:.2s ease}.migration-card:hover{border-color:#6ee7b7;transform:translateY(-2px);box-shadow:0 14px 28px rgba(5,46,36,.08)}.migration-card__company{display:flex;gap:.75rem;align-items:center;color:#64748b;font-size:.86rem}.migration-card__logo{width:42px;height:42px;border-radius:11px;background:#ecfdf5;color:#047857;display:grid;place-items:center;font-weight:800;overflow:hidden}.migration-card__logo img{width:100%;height:100%;object-fit:cover}.migration-card h2{font-size:1rem;line-height:1.45;min-height:2.9em;margin:1rem 0;color:#0f172a}.migration-card__meta{display:flex;flex-wrap:wrap;gap:.5rem}.migration-pill{padding:.35rem .6rem;border-radius:999px;background:#f0fdf4;color:#047857;font-size:.78rem;font-weight:600}.migration-pagination{margin-top:1.5rem}.migration-pagination nav>div:first-child{display:none}@media(max-width:900px){.migration-filter{grid-template-columns:1fr 1fr}.migration-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.migration-filter,.migration-grid{grid-template-columns:1fr}.migration-heading{align-items:start;flex-direction:column}}
    </style>
</head>
<body>
    <header class="migration-header">
        <div class="migration-shell migration-nav">
            <a class="migration-logo" href="{{ route('jobs.index') }}"><i class="ri-briefcase-4-line"></i> JobMarket<span>SV</span></a>
        </div>
    </header>
    <main class="migration-shell migration-page">@yield('content')</main>
    <script src="/assets/js/custom_select.js"></script>
</body>
</html>
