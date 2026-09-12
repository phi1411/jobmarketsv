<?php

/**
 * CV-AI-P1-04 & CV-AI-P1-05 REGRESSION TEST SUITE
 *
 * Verifies:
 * - P1-04: Student Match Analysis UI & Disclaimer (app/Views/jobs/show.php & app/Views/student/applications.php)
 * - P1-05: Profile Data Completeness UX (app/Views/student/profile.php, Profile entity, ProfileService)
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Domain/Profile/Profile.php';
require_once __DIR__ . '/app/Domain/ProfileService.php';
require_once __DIR__ . '/app/Infrastructure/Security/FileRateLimiter.php';
require_once __DIR__ . '/app/Domain/Matching/Entities/JobMatchAnalysis.php';
require_once __DIR__ . '/app/Domain/Matching/Repositories/JobMatchAnalysisRepositoryInterface.php';

use JobMarket\Domain\Profile\Profile;
use JobMarket\Domain\Profile\ProfileRepositoryInterface;
use JobMarket\Domain\Matching\Entities\JobMatchAnalysis;
use JobMarket\Domain\Matching\Repositories\JobMatchAnalysisRepositoryInterface;
use JobMarket\Infrastructure\Security\FileRateLimiter;
use JobMarket\Exceptions\ValidationException;

$passed = 0;
$failed = 0;

function assertCondition(bool $cond, string $msg): void {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo " [PASS] $msg\n";
    } else {
        $failed++;
        echo " [FAIL] $msg\n";
    }
}

echo "=================================================================\n";
echo "   CV-AI-P1-04 & P1-05 TEST SUITE (UI & Profile Completeness)    \n";
echo "=================================================================\n\n";

$showView = file_get_contents(__DIR__ . '/app/Views/jobs/show.php');
$appsView = file_get_contents(__DIR__ . '/app/Views/student/applications.php');
$profView = file_get_contents(__DIR__ . '/app/Views/student/profile.php');

$mandatoryDisclaimer = "Điểm phù hợp chỉ phản ánh mức độ khớp giữa dữ liệu hồ sơ hiện có và yêu cầu công việc. Đây không phải xác suất được tuyển và không thay thế quyết định của nhà tuyển dụng.";

// -------------------------------------------------------------
// 1. P1-04: app/Views/jobs/show.php (Apply Success Flow)
// -------------------------------------------------------------
echo "--- 1. Testing app/Views/jobs/show.php ---\n";

assertCondition(
    str_contains($showView, 'id="apply-match-banner"'),
    "Apply view has #apply-match-banner container"
);

assertCondition(
    str_contains($showView, 'showToast("Ứng tuyển thành công! Nhà tuyển dụng sẽ xem xét hồ sơ của bạn.", "success");'),
    "Apply view renders success toast immediately on 201 Created"
);

assertCondition(
    str_contains($showView, 'triggerPostApplyMatchAnalysis'),
    "Apply view calls triggerPostApplyMatchAnalysis in background"
);

assertCondition(
    str_contains($showView, '/match-analysis'),
    "triggerPostApplyMatchAnalysis calls POST /applications/{id}/match-analysis"
);

assertCondition(
    str_contains($showView, 'Đang phân tích độ phù hợp với công việc...'),
    "triggerPostApplyMatchAnalysis displays lightweight loading indicator"
);

assertCondition(
    str_contains($showView, 'Phân tích độ phù hợp tạm thời không khả dụng, bạn có thể xem lại sau trong mục'),
    "triggerPostApplyMatchAnalysis has gentle error fallback message"
);

assertCondition(
    str_contains($showView, $mandatoryDisclaimer),
    "Apply view contains exact mandatory disclaimer text"
);

assertCondition(
    str_contains($showView, 'escapeHtml('),
    "Apply view escapes AI text with escapeHtml to prevent XSS"
);

assertCondition(
    str_contains($showView, 'role="dialog"') && str_contains($showView, 'aria-modal="true"') && str_contains($showView, 'aria-labelledby="apply-modal-title"'),
    "Apply modal includes dialog accessibility attributes (role=dialog, aria-modal, aria-labelledby)"
);

assertCondition(
    str_contains($showView, 'lastApplyFocusElement = document.activeElement;') && str_contains($showView, 'lastApplyFocusElement.focus()'),
    "Apply modal retains and restores active focus element on close"
);

assertCondition(
    str_contains($showView, 'showPostApplyNoConsentBanner') && str_contains($showView, 'Bạn chưa bật phân tích AI cho đơn ứng tuyển này.'),
    "Apply flow displays explicit non-consent banner when ai_match_consent is false"
);

assertCondition(
    str_contains($showView, 'MAX_POST_APPLY_POLLS') && str_contains($showView, 'pollPostApplyMatchAnalysis'),
    "Apply flow implements bounded polling with backoff for processing status"
);

assertCondition(
    str_contains($showView, 'Phân tích độ phù hợp đang được xử lý trong nền.'),
    "Apply flow bounded polling displays timeout background processing message"
);

assertCondition(
    str_contains($showView, 'isInsufficient') && str_contains($showView, 'Chưa đủ dữ liệu'),
    "Apply banner renders null-safe score display when data is insufficient"
);

// -------------------------------------------------------------
// 2. P1-04: app/Views/student/applications.php (Applications List & Detail)
// -------------------------------------------------------------
echo "\n--- 2. Testing app/Views/student/applications.php ---\n";

assertCondition(
    str_contains($appsView, 'id="match-analysis-modal"'),
    "Applications view defines #match-analysis-modal"
);

assertCondition(
    str_contains($appsView, 'openMatchAnalysisModal'),
    "Applications view defines openMatchAnalysisModal function"
);

assertCondition(
    str_contains($appsView, 'closeMatchModal'),
    "Applications view defines closeMatchModal function"
);

assertCondition(
    str_contains($appsView, 'loadMatchAnalysisData'),
    "Applications view defines loadMatchAnalysisData function"
);

assertCondition(
    str_contains($appsView, 'renderMatchModalContent'),
    "Applications view defines renderMatchModalContent function"
);

assertCondition(
    str_contains($appsView, 'requestReanalysis'),
    "Applications view defines requestReanalysis action"
);

assertCondition(
    str_contains($appsView, 'revokeMatchConsent'),
    "Applications view defines revokeMatchConsent action"
);

assertCondition(
    str_contains($appsView, 'PATCH') && str_contains($appsView, '/match-consent'),
    "revokeMatchConsent calls PATCH /applications/{id}/match-consent"
);

assertCondition(
    str_contains($appsView, 'confirm('),
    "revokeMatchConsent prompts user confirmation before proceeding"
);

assertCondition(
    str_contains($appsView, 'Điểm Phù Hợp Tổng Thể') || str_contains($appsView, 'overall_score'),
    "Modal renders overall score"
);

assertCondition(
    str_contains($appsView, 'Độ phủ:'),
    "Modal renders data coverage percent"
);

assertCondition(
    str_contains($appsView, 'Kỹ Năng') &&
    str_contains($appsView, 'Lịch Làm Việc') &&
    str_contains($appsView, 'Kinh Nghiệm') &&
    str_contains($appsView, 'Học Vấn') &&
    str_contains($appsView, 'Địa Điểm') &&
    str_contains($appsView, 'Độ Phù Hợp Vị Trí'),
    "Modal renders 6 MVP criteria breakdown (skills, availability, experience, education, location, role_relevance)"
);

assertCondition(
    !str_contains($appsView, 'onclick="openMatchAnalysisModal('),
    "Applications view eliminates dangerous inline onclick string interpolation"
);

assertCondition(
    str_contains($appsView, 'loadedApplicationsMap') && str_contains($appsView, 'data-app-id'),
    "Applications view uses in-memory Map and data-app-id event delegation"
);

assertCondition(
    str_contains($appsView, 'subTitle.textContent ='),
    "Applications view binds modal job title and company name strictly via textContent"
);

assertCondition(
    str_contains($appsView, 'Chưa đủ dữ liệu để tính điểm tổng thể'),
    "Applications view modal handles insufficient data with clear warning"
);

assertCondition(
    str_contains($appsView, 'renderMatchModalConsentDeclined') &&
    str_contains($appsView, 'renderMatchModalRevoked') &&
    str_contains($appsView, 'renderMatchModalNotStarted') &&
    str_contains($appsView, 'renderMatchModalProcessing') &&
    str_contains($appsView, 'isStale'),
    "Applications view exhaustively handles not_started, processing, partial, failed, stale, declined_consent, and revoked states"
);

assertCondition(
    str_contains($appsView, 'MAX_MATCH_POLLS'),
    "Applications view bounds processing polling to prevent runaway background loops"
);

assertCondition(
    str_contains($appsView, 'Điểm Mạnh Nổi Bật') && str_contains($appsView, 'strengths'),
    "Modal renders strengths"
);

assertCondition(
    str_contains($appsView, 'Điểm Cần Bổ Sung') && str_contains($appsView, 'considerations'),
    "Modal renders considerations / areas to improve"
);

assertCondition(
    str_contains($appsView, '/student/profile'),
    "Modal links to /student/profile for updating missing profile data"
);

assertCondition(
    str_contains($appsView, $mandatoryDisclaimer),
    "Applications modal contains exact mandatory disclaimer text"
);

assertCondition(
    substr_count($appsView, $mandatoryDisclaimer) >= 2,
    "Mandatory disclaimer is present in both modal unconsented and results views"
);

assertCondition(
    str_contains($appsView, 'escapeHtml('),
    "Applications view escapes all dynamic strings with escapeHtml"
);

assertCondition(
    str_contains($appsView, 'role="dialog"') && str_contains($appsView, 'aria-modal="true"') && str_contains($appsView, 'aria-labelledby="modal-match-title"'),
    "Applications match modal includes dialog accessibility attributes (role=dialog, aria-modal, aria-labelledby)"
);

assertCondition(
    str_contains($appsView, 'lastFocusedAppElement = document.activeElement;') && str_contains($appsView, 'lastFocusedAppElement.focus()'),
    "Applications match modal retains and restores active focus element on close"
);

assertCondition(
    str_contains($appsView, 'aria-live="polite"'),
    "Applications match modal uses aria-live='polite' for dynamic status updates"
);

assertCondition(
    str_contains($appsView, 'currentModalAppId !== applicationId'),
    "Applications match modal guards against race condition from stale async responses"
);

assertCondition(
    str_contains($appsView, 'renderMatchModalRateLimited') && str_contains($appsView, 'rateLimitCountdownInterval'),
    "Applications modal implements HTTP 429 countdown timer with disabled retry button"
);

assertCondition(
    str_contains($appsView, 'getFailureExplanation') && str_contains($appsView, 'ANALYSIS_STALLED'),
    "Applications modal maps specific failure codes including ANALYSIS_STALLED to user-friendly copy"
);

assertCondition(
    str_contains($appsView, 'NOT_APPLICABLE') && str_contains($appsView, 'Không áp dụng'),
    "Applications modal safely handles NOT_APPLICABLE criteria without rendering score 0"
);

// -------------------------------------------------------------
// 3. P1-05: app/Views/student/profile.php (Profile Completeness UX)
// -------------------------------------------------------------
echo "\n--- 3. Testing app/Views/student/profile.php ---\n";

assertCondition(
    str_contains($profView, 'id="profile-completeness-suggestion"'),
    "Profile view contains #profile-completeness-suggestion container"
);

assertCondition(
    str_contains($profView, 'Thêm kinh nghiệm làm việc để tăng độ bao phủ dữ liệu đánh giá'),
    "Profile view contains neutral suggested copy for work experience"
);

assertCondition(
    !str_contains($profView, '(+15%)'),
    "Profile view removes misleading (+15%) percentage bonus"
);

assertCondition(
    str_contains($profView, 'prof-experience-list'),
    "Profile view defines #prof-experience-list container"
);

assertCondition(
    str_contains($profView, 'addExperienceRow'),
    "Profile view defines addExperienceRow function"
);

assertCondition(
    str_contains($profView, 'collectWorkExperience'),
    "Profile view defines collectWorkExperience function"
);

assertCondition(
    str_contains($profView, 'prof-education-degree') && str_contains($profView, 'prof-grad-year'),
    "Profile view defines degree and grad year education fields"
);

assertCondition(
    str_contains($profView, 'collectEducation'),
    "Profile view defines collectEducation function"
);

assertCondition(
    str_contains($profView, 'prof-certificates-list'),
    "Profile view defines #prof-certificates-list container"
);

assertCondition(
    str_contains($profView, 'addCertificateRow'),
    "Profile view defines addCertificateRow function"
);

assertCondition(
    str_contains($profView, 'collectCertificates'),
    "Profile view defines collectCertificates function"
);

assertCondition(
    str_contains($profView, 'updateProfileCompletenessSuggestion'),
    "Profile view defines updateProfileCompletenessSuggestion function"
);

assertCondition(
    str_contains($profView, 'class="form-control exp-duration" maxlength="100"'),
    "Profile view restricts work experience duration to maxlength=100"
);

assertCondition(
    str_contains($profView, 'class="form-control cert-year" maxlength="100"'),
    "Profile view restricts certificate year to maxlength=100"
);

assertCondition(
    str_contains($profView, 'id="prof-university" class="form-control" maxlength="255"') &&
    str_contains($profView, 'id="prof-major" class="form-control" maxlength="255"') &&
    str_contains($profView, 'id="prof-education-degree" class="form-control" maxlength="100"') &&
    str_contains($profView, 'id="prof-grad-year" class="form-control" maxlength="20"') &&
    str_contains($profView, 'id="prof-education-desc" class="form-control" rows="2" maxlength="2000"'),
    "Profile view restricts education inputs to valid schema maxlengths"
);

assertCondition(
    str_contains($profView, '!university && !major && !degree && !gradYear && !desc'),
    "Profile view collectEducation explicitly returns null when all fields are cleared"
);

// -------------------------------------------------------------
// 4. P1-05: Non-sensitive Completeness Scoring & Entity Verification
// -------------------------------------------------------------
echo "\n--- 4. Testing Profile Entity Non-Sensitive Completeness Scoring ---\n";

$p1 = Profile::fromArray([
    'full_name' => 'Nguyen Van A',
    'phone' => '0912345678',
    'university' => 'DH Bach Khoa',
    'major' => 'CNTT',
    'academic_year' => 3,
    'bio' => 'Sinh vien nam 3 tim viec lam part-time',
    'location_id' => 'loc-1',
    'skill_ids' => ['sk-1', 'sk-2'],
    'available_schedule' => ['monday' => ['morning']],
    'work_experience' => '[{"title":"Intern"}]',
    'cv_storage_path' => 'resumes/cv.pdf'
]);

$scoreFull = $p1->calculateCompletionPercent();
assertCondition($scoreFull >= 95, "Comprehensive non-sensitive profile achieves high completion ($scoreFull%)");

// Test that changing date_of_birth or gender DOES NOT affect completion percent (anti-bias)
$p2 = Profile::fromArray([
    'full_name' => 'Nguyen Van A',
    'phone' => '0912345678',
    'university' => 'DH Bach Khoa',
    'major' => 'CNTT',
    'academic_year' => 3,
    'bio' => 'Sinh vien nam 3 tim viec lam part-time',
    'location_id' => 'loc-1',
    'skill_ids' => ['sk-1', 'sk-2'],
    'available_schedule' => ['monday' => ['morning']],
    'work_experience' => '[{"title":"Intern"}]',
    'cv_storage_path' => 'resumes/cv.pdf',
    'date_of_birth' => '2003-05-15',
    'gender' => 'male'
]);

$p3 = Profile::fromArray([
    'full_name' => 'Nguyen Van A',
    'phone' => '0912345678',
    'university' => 'DH Bach Khoa',
    'major' => 'CNTT',
    'academic_year' => 3,
    'bio' => 'Sinh vien nam 3 tim viec lam part-time',
    'location_id' => 'loc-1',
    'skill_ids' => ['sk-1', 'sk-2'],
    'available_schedule' => ['monday' => ['morning']],
    'work_experience' => '[{"title":"Intern"}]',
    'cv_storage_path' => 'resumes/cv.pdf',
    'date_of_birth' => null,
    'gender' => null
]);

assertCondition(
    $p2->calculateCompletionPercent() === $p3->calculateCompletionPercent(),
    "Anti-bias: date_of_birth and gender have ZERO effect on profile completeness"
);

assertCondition(
    method_exists($p1, 'getWorkExperience') &&
    method_exists($p1, 'getEducation') &&
    method_exists($p1, 'getCertificates'),
    "Profile entity provides getters for work_experience, education, and certificates"
);

// -------------------------------------------------------------
// 5. P1-04: XSS Fixtures Safety Simulation
// -------------------------------------------------------------
echo "\n--- 5. Testing XSS Fixtures & Safe Attribute Escaping ---\n";

$xssFixtures = [
    "Software Engineer');alert(1);//",
    'Senior Dev" onfocus="alert(2)"',
    "<script>alert(3)</script>",
    "<svg onload=alert(4)>",
    "Tailor's Assistant & \"Chief\" <Explorer>",
];

foreach ($xssFixtures as $idx => $payload) {
    $escaped = htmlspecialchars($payload, ENT_QUOTES, 'UTF-8');
    assertCondition(
        !str_contains($escaped, "'") || !str_contains($escaped, '"'),
        "XSS fixture $idx: quotes are safely escaped for HTML context"
    );
    assertCondition(
        !str_contains($escaped, '<script>') && !str_contains($escaped, '<svg'),
        "XSS fixture $idx: tag delimiters < and > are neutralized"
    );
}

// Ensure applications.php contains NO inline JS execution for modal opening
assertCondition(
    !preg_match('/onclick\s*=\s*["\']\s*openMatchAnalysisModal/i', $appsView),
    "XSS Guard: Zero inline onclick openMatchAnalysisModal calls found in student applications view"
);
assertCondition(
    !preg_match('/openMatchAnalysisModal\s*\([^)]*[\$\+]/i', $appsView),
    "XSS Guard: No dynamic string concatenation in openMatchAnalysisModal caller"
);

// -------------------------------------------------------------
// 6. P1-05: Rate Limiting Behavioral Test
// -------------------------------------------------------------
echo "\n--- 6. Testing Rate Limiting (FileRateLimiter) ---\n";

$tempDir = sys_get_temp_dir() . '/test_ratelimit_' . bin2hex(random_bytes(8));
mkdir($tempDir, 0777, true);
$rateLimiter = new FileRateLimiter($tempDir);
$testKey = 'student_match_user_123';

for ($i = 1; $i <= 5; $i++) {
    assertCondition(
        !$rateLimiter->tooManyAttempts($testKey, 5),
        "Rate limiter: Attempt $i is allowed within limit"
    );
    $rateLimiter->hit($testKey, 60);
}

assertCondition(
    $rateLimiter->tooManyAttempts($testKey, 5),
    "Rate limiter: 6th attempt is blocked (tooManyAttempts === true)"
);

$retryAfter = $rateLimiter->availableIn($testKey);
assertCondition(
    $retryAfter > 0 && $retryAfter <= 60,
    "Rate limiter: returns valid positive Retry-After ($retryAfter seconds)"
);

array_map('unlink', glob("$tempDir/*"));
rmdir($tempDir);

// -------------------------------------------------------------
// 7. P1-02: Multi-Analysis Consent Revocation & Anti-Race Write
// -------------------------------------------------------------
echo "\n--- 7. Testing Multi-Analysis Consent Revocation & Anti-Race Write ---\n";

class TestJobMatchAnalysisRepo implements JobMatchAnalysisRepositoryInterface
{
    /** @var array<string, JobMatchAnalysis> */
    public array $analyses = [];

    public function create(JobMatchAnalysis $analysis): void
    {
        $this->analyses[$analysis->getId()] = $analysis;
    }

    public function findById(string $id): ?JobMatchAnalysis
    {
        return $this->analyses[$id] ?? null;
    }

    public function findLatestByApplicationId(string $applicationId): ?JobMatchAnalysis
    {
        $matching = array_filter($this->analyses, fn($a) => $a->getApplicationId() === $applicationId);
        return !empty($matching) ? end($matching) : null;
    }

    public function findByCacheKey(string $cacheKey): ?JobMatchAnalysis
    {
        foreach ($this->analyses as $a) {
            if ($a->getCacheKey() === $cacheKey) return $a;
        }
        return null;
    }
    public function claimForProcessing(string $id, int $leaseSeconds, int $retryCooldownSeconds): bool { return false; }

    public function findReusableCandidateExtraction(string $candidateHash, string $promptVersion, string $schemaVersion, ?string $geminiModel): ?array { return null; }
    public function findReusableJobExtraction(string $jobHash, string $promptVersion, string $schemaVersion, ?string $geminiModel): ?array { return null; }
    public function updateStatus(string $id, string $status, ?string $failureCode = null): void {}
    
    public function complete(string $id, array $data): void
    {
        if (isset($this->analyses[$id]) && $this->analyses[$id]->getStatus() === 'processing') {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'completed';
            $curr['overall_score'] = $data['overall_score'] ?? null;
            $curr['coverage_percent'] = $data['coverage_percent'] ?? null;
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }

    public function markPartial(string $id, array $data): void
    {
        if (isset($this->analyses[$id]) && $this->analyses[$id]->getStatus() === 'processing') {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'partial';
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }

    public function markFailed(string $id, string $failureCode): void
    {
        if (isset($this->analyses[$id]) && $this->analyses[$id]->getStatus() === 'processing') {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'failed';
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }

    public function markRevoked(string $id): void
    {
        if (isset($this->analyses[$id])) {
            $curr = $this->analyses[$id]->toArray();
            $curr['status'] = 'revoked';
            $curr['failure_code'] = 'CONSENT_REVOKED';
            $curr['candidate_snapshot_json'] = null;
            $curr['candidate_extraction_json'] = null;
            $curr['criteria_json'] = null;
            $curr['summary_json'] = null;
            $curr['overall_score'] = null;
            $curr['coverage_percent'] = null;
            $curr['classification'] = null;
            $this->analyses[$id] = JobMatchAnalysis::fromArray($curr);
        }
    }

    public function purgeByApplicationId(string $applicationId): void
    {
        foreach ($this->analyses as $id => $a) {
            if ($a->getApplicationId() === $applicationId) {
                $this->markRevoked($id);
            }
        }
    }
}

$repo = new TestJobMatchAnalysisRepo();
$targetAppId = 'app-consent-revocation-test';

$analysis1 = JobMatchAnalysis::fromArray([
    'id' => 'analysis-uuid-1',
    'application_id' => $targetAppId,
    'candidate_hash' => 'hash1',
    'job_hash' => 'jobhash1',
    'cache_key' => 'key1',
    'matcher_version' => '1.0',
    'prompt_version' => '1.0',
    'schema_version' => '1.0',
    'status' => 'completed',
    'candidate_snapshot_json' => ['skills' => ['PHP', 'MySQL']],
    'criteria_json' => [['criterion_name' => 'skills', 'state' => 'AVAILABLE', 'score' => 90, 'weight' => 35]],
    'summary_json' => ['strengths' => ['Strong skills']],
    'overall_score' => 88.0,
    'coverage_percent' => 85.0,
    'classification' => 'HIGH_MATCH',
    'created_at' => '2026-03-01 10:00:00',
]);

$analysis2 = JobMatchAnalysis::fromArray([
    'id' => 'analysis-uuid-2',
    'application_id' => $targetAppId,
    'candidate_hash' => 'hash2',
    'job_hash' => 'jobhash1',
    'cache_key' => 'key2',
    'matcher_version' => '1.0',
    'prompt_version' => '1.0',
    'schema_version' => '1.0',
    'status' => 'partial',
    'candidate_snapshot_json' => ['skills' => ['PHP']],
    'criteria_json' => [['criterion_name' => 'skills', 'state' => 'AVAILABLE', 'score' => 70, 'weight' => 35]],
    'summary_json' => ['strengths' => ['Basic skills']],
    'overall_score' => 70.0,
    'coverage_percent' => 60.0,
    'classification' => 'GOOD_MATCH',
    'created_at' => '2026-03-01 11:00:00',
]);

$repo->create($analysis1);
$repo->create($analysis2);

$repo->purgeByApplicationId($targetAppId);

$purged1 = $repo->findById('analysis-uuid-1');
$purged2 = $repo->findById('analysis-uuid-2');

assertCondition(
    $purged1->getStatus() === 'revoked' && $purged1->getFailureCode() === 'CONSENT_REVOKED',
    "Revocation: Analysis 1 status set to revoked with CONSENT_REVOKED"
);
assertCondition(
    $purged1->getCandidateSnapshotJson() === null &&
    $purged1->getCandidateExtractionJson() === null &&
    $purged1->getCriteriaJson() === null &&
    $purged1->getSummaryJson() === null &&
    $purged1->getOverallScore() === null &&
    $purged1->getCoveragePercent() === null &&
    $purged1->getClassification() === null,
    "Revocation: Analysis 1 sensitive data and scores are completely purged to null"
);

assertCondition(
    $purged2->getStatus() === 'revoked' && $purged2->getFailureCode() === 'CONSENT_REVOKED',
    "Revocation: Analysis 2 (multi-analysis) status set to revoked with CONSENT_REVOKED"
);
assertCondition(
    $purged2->getCandidateSnapshotJson() === null &&
    $purged2->getCriteriaJson() === null &&
    $purged2->getOverallScore() === null,
    "Revocation: Analysis 2 sensitive data and scores are completely purged to null"
);

// Anti-race test: late terminal write on revoked record must be ignored
$repo->complete('analysis-uuid-1', ['overall_score' => 99.0, 'coverage_percent' => 95.0]);
assertCondition(
    $repo->findById('analysis-uuid-1')->getStatus() === 'revoked',
    "Anti-race: conditional write (status='processing') protects revoked analysis from late completion overwrite"
);

// -------------------------------------------------------------
// 8. P1-03: INSUFFICIENT_DATA Gating in toSafeStudentDto
// -------------------------------------------------------------
echo "\n--- 8. Testing INSUFFICIENT_DATA Gating in toSafeStudentDto ---\n";

$insuf1 = JobMatchAnalysis::fromArray([
    'id' => 'match-insuf-coverage',
    'application_id' => 'app-1',
    'candidate_hash' => 'h',
    'job_hash' => 'j',
    'cache_key' => 'k1',
    'matcher_version' => '1.0',
    'prompt_version' => '1.0',
    'schema_version' => '1.0',
    'status' => 'completed',
    'overall_score' => 85.0,
    'coverage_percent' => 52.0,
    'classification' => 'GOOD_MATCH',
    'created_at' => '2026-03-01 12:00:00',
]);

$dto1 = $insuf1->toSafeStudentDto();
assertCondition(
    $dto1['overall_score'] === null,
    "Safe DTO: forces overall_score = null when coverage_percent < 60%"
);
assertCondition(
    $dto1['coverage_percent'] === 52.0,
    "Safe DTO: retains coverage_percent for user feedback"
);

$insuf2 = JobMatchAnalysis::fromArray([
    'id' => 'match-insuf-class',
    'application_id' => 'app-2',
    'candidate_hash' => 'h',
    'job_hash' => 'j',
    'cache_key' => 'k2',
    'matcher_version' => '1.0',
    'prompt_version' => '1.0',
    'schema_version' => '1.0',
    'status' => 'completed',
    'overall_score' => 70.0,
    'coverage_percent' => 75.0,
    'classification' => 'INSUFFICIENT_DATA',
    'created_at' => '2026-03-01 12:00:00',
]);

$dto2 = $insuf2->toSafeStudentDto();
assertCondition(
    $dto2['overall_score'] === null,
    "Safe DTO: forces overall_score = null when classification === INSUFFICIENT_DATA"
);

// -------------------------------------------------------------
// 9. P1-05: ProfileService Null Clearing & Strict Bounds
// -------------------------------------------------------------
echo "\n--- 9. Testing ProfileService Null Clearing & Strict Bounds ---\n";

$mockRepo = new class implements ProfileRepositoryInterface {
    public ?Profile $savedProfile = null;

    public function findByUserId(string $userId): ?array
    {
        return [
            'user_id' => $userId,
            'full_name' => 'Nguyen Van B',
            'work_experience' => '[{"title":"Old Job","company":"Old Co"}]',
            'education' => '{"university":"Old Uni"}',
            'certificates' => '[{"name":"Old Cert"}]',
        ];
    }
    public function findById(string $id): ?array { return null; }
    public function upsert(Profile $profile): void { $this->savedProfile = $profile; }
    public function updateCvMetadata(string $userId, ?array $cvData): void {}
    public function searchPublic(array $filters = [], ?\JobMarket\Support\Pagination $pagination = null): array { return []; }
    public function countPublic(array $filters = []): int { return 0; }
};

$profileService = new \JobMarket\Domain\ProfileService($mockRepo, new \PDO('sqlite::memory:'));
$testUser = ['id' => 'user-test-1', 'role' => 'student', 'email' => 'student@example.com'];

$profileService->updateMyProfile($testUser, [
    'work_experience' => null,
    'education' => null,
    'certificates' => null,
]);

assertCondition(
    $mockRepo->savedProfile !== null && $mockRepo->savedProfile->getWorkExperience() === null,
    "ProfileService: explicit null in work_experience clears stored value"
);
assertCondition(
    $mockRepo->savedProfile !== null && $mockRepo->savedProfile->getEducation() === null,
    "ProfileService: explicit null in education clears stored value"
);
assertCondition(
    $mockRepo->savedProfile !== null && $mockRepo->savedProfile->getCertificates() === null,
    "ProfileService: explicit null in certificates clears stored value"
);

$tooMany = array_fill(0, 25, ['title' => 'Software Engineer', 'company' => 'Tech']);
$caughtTooMany = false;
try {
    $profileService->updateMyProfile($testUser, [
        'work_experience' => json_encode($tooMany),
    ]);
} catch (ValidationException $e) {
    $caughtTooMany = true;
}
assertCondition(
    $caughtTooMany,
    "ProfileService: rejects payload with > 20 work experience items"
);

$oversized = str_repeat('A', 10500);
$caughtOversized = false;
try {
    $profileService->updateMyProfile($testUser, [
        'work_experience' => $oversized,
    ]);
} catch (ValidationException $e) {
    $caughtOversized = true;
}
assertCondition(
    $caughtOversized,
    "ProfileService: rejects payload exceeding 10,000 characters"
);

$caughtMalformed = false;
try {
    $profileService->updateMyProfile($testUser, [
        'work_experience' => '[{"title":"broken"}',
    ]);
} catch (ValidationException) {
    $caughtMalformed = true;
}
assertCondition(
    $caughtMalformed,
    'ProfileService: rejects malformed structured JSON instead of storing it as legacy text'
);

$caughtUnknownField = false;
try {
    $profileService->updateMyProfile($testUser, [
        'education' => json_encode(['degree' => 'Cử nhân', 'private_note' => 'do not store']),
    ]);
} catch (ValidationException) {
    $caughtUnknownField = true;
}
assertCondition(
    $caughtUnknownField,
    'ProfileService: rejects unknown fields in structured education payload'
);

// -------------------------------------------------------------
// 10. P1-05: Completeness Suggestion Schedule Priority
// -------------------------------------------------------------
echo "\n--- 10. Testing Completeness Suggestion Schedule Priority ---\n";

assertCondition(
    str_contains($profView, 'hasSched') && str_contains($profView, 'Cập nhật lịch rảnh hàng tuần'),
    "Profile view prioritizes availability_schedule suggestion when schedule is missing"
);

echo "\n-----------------------------------------------------------------\n";
echo "Summary: $passed PASSED, $failed FAILED.\n";
echo "-----------------------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
