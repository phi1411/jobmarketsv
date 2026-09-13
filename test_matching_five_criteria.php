<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);
require __DIR__ . '/vendor/autoload.php';

use JobMarket\Domain\Matching\Contracts\CandidateProfileContract;
use JobMarket\Domain\Matching\Contracts\JobRequirementsContract;
use JobMarket\Domain\Matching\Enums\CriterionName;
use JobMarket\Domain\Matching\Enums\DataState;
use JobMarket\Domain\Matching\Services\DeterministicMatcher;
use JobMarket\Domain\Matching\Services\Normalizers\SkillNormalizer;

function assertFiveCriteria(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("[FAIL] {$message}");
    }
    echo "[PASS] {$message}" . PHP_EOL;
}

function criteriaByName($result): array
{
    $mapped = [];
    foreach ($result->criteria as $criterion) {
        $mapped[$criterion->criterionName->value] = $criterion;
    }
    return $mapped;
}

$matcher = new DeterministicMatcher();

// Không có yêu cầu nào: cả 5 tiêu chí phải tự động đạt.
$noRequirements = $matcher->match(new CandidateProfileContract(), new JobRequirementsContract());
$criteria = criteriaByName($noRequirements);
$keys = array_keys($criteria);
sort($keys);
assertFiveCriteria(
    $keys === ['age', 'availability', 'education', 'experience', 'skills'],
    'Matcher chỉ trả đúng 5 tiêu chí đã công bố'
);
foreach ($criteria as $key => $criterion) {
    assertFiveCriteria(
        $criterion->state === DataState::AVAILABLE && $criterion->score === 100.0,
        "Tiêu chí {$key} tự động đạt khi nhà tuyển dụng không yêu cầu"
    );
}
assertFiveCriteria($noRequirements->overallScore === 100.0, 'Không có yêu cầu cho kết quả tổng 100%');
assertFiveCriteria($noRequirements->coveragePercent === 100.0, 'Năm tiêu chí đạt có độ phủ 100%');

// Tuổi chỉ dùng số tuổi đã tính, không cần đưa ngày sinh vào contract.
$insideAge = $matcher->match(
    new CandidateProfileContract(ageYears: 20),
    new JobRequirementsContract(minimumAge: 18, maximumAge: 22)
);
assertFiveCriteria(criteriaByName($insideAge)[CriterionName::AGE->value]->score === 100.0, 'Tuổi trong khoảng được tính đạt');

$outsideAge = $matcher->match(
    new CandidateProfileContract(ageYears: 25),
    new JobRequirementsContract(minimumAge: 18, maximumAge: 22)
);
assertFiveCriteria(criteriaByName($outsideAge)[CriterionName::AGE->value]->score === 0.0, 'Tuổi ngoài khoảng được tính chưa đạt');

$missingAge = $matcher->match(
    new CandidateProfileContract(ageYears: null),
    new JobRequirementsContract(minimumAge: 18)
);
$missingAgeCriterion = criteriaByName($missingAge)[CriterionName::AGE->value];
assertFiveCriteria($missingAgeCriterion->state === DataState::UNKNOWN && $missingAgeCriterion->score === null, 'Thiếu ngày sinh không bị suy đoán hoặc tự động đánh trượt');

// Kỹ năng tự nhập đi qua cùng bộ chuẩn hóa như kỹ năng có sẵn.
$normalizer = new SkillNormalizer();
$customSkills = $matcher->match(
    new CandidateProfileContract(skills: $normalizer->normalizeCandidateSkills(null, 'Chụp hình sản phẩm')),
    new JobRequirementsContract(skills: $normalizer->normalizeJobSkills(['Chụp hình sản phẩm']))
);
assertFiveCriteria(criteriaByName($customSkills)[CriterionName::SKILLS->value]->score === 100.0, 'Kỹ năng tự nhập được chuẩn hóa và đối chiếu chính xác');

echo 'Hoàn tất kiểm tra matcher 5 tiêu chí.' . PHP_EOL;
