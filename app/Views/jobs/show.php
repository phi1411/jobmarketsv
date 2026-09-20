<div id="job-detail-loading" class="container" style="padding:4rem 1.25rem;">
    <div class="job-card skeleton" style="height:350px;margin-bottom:2rem;"></div>
    <div class="job-card skeleton" style="height:250px;"></div>
</div>

<div id="job-detail-wrapper" style="display:none;">
    <!-- Detail Header -->
    <section class="detail-header">
        <div class="container">
            <div style="margin-bottom:1.5rem;">
                <a href="/viec-lam" class="btn btn-outline btn-sm">&larr; Quay lại danh sách việc làm</a>
            </div>

            <div class="detail-top">
                <div style="display:flex;gap:1.5rem;align-items:flex-start;">
                    <div id="job-company-logo" class="company-logo" style="width:72px;height:72px;font-size:1.75rem;">J</div>
                    <div>
                        <h1 id="job-title" style="font-size:1.85rem;font-weight:800;color:var(--dark);margin-bottom:0.5rem;line-height:1.25;">Đang tải...</h1>
                        <div id="job-company-name" style="font-size:1.1rem;color:var(--text-muted);margin-bottom:1rem;">Công ty</div>
                        <div id="job-badges-header" class="job-badges"></div>
                    </div>
                </div>

                <div class="detail-actions" style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                    <button id="btn-report-job" class="btn btn-outline" onclick="openReportJobModal()" style="display:none;color:#b91c1c;border-color:#fecaca;">⚑ Báo cáo tin</button>
                    <button id="btn-favorite" class="btn btn-outline" onclick="handleToggleFavorite()">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        Lưu tin
                    </button>
                    <button id="btn-apply-top" class="btn btn-primary btn-lg" onclick="handleApplyClick()">
                        Ứng Tuyển Ngay &rarr;
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Detail Body -->
    <div class="container">
        <div id="apply-match-banner" style="display:none;margin-bottom:1.5rem;"></div>
        <div class="detail-content">
            <!-- Left Column: Details -->
            <div>
                <div class="detail-card">
                    <h2 class="detail-card-title">Mô Tả Công Việc</h2>
                    <div id="job-description" style="white-space:pre-line;color:var(--text);line-height:1.7;"></div>
                </div>

                <div class="detail-card">
                    <h2 class="detail-card-title">Yêu Cầu Ứng Viên</h2>
                    <div id="job-requirements" style="white-space:pre-line;color:var(--text);line-height:1.7;"></div>
                </div>

                <div class="detail-card">
                    <h2 class="detail-card-title">Quyền Lợi Được Hưởng</h2>
                    <div id="job-benefits" style="white-space:pre-line;color:var(--text);line-height:1.7;"></div>
                </div>

                <div id="detail-locations-card" class="detail-card" style="display:none;">
                    <div class="job-map-heading">
                        <h2 class="detail-card-title" style="margin:0;">Địa Điểm Làm Việc</h2>
                        <span id="detail-locs-count" class="badge-loc badge-loc-primary"></span>
                    </div>
                    <div id="job-detail-map" class="job-detail-map" role="region" aria-label="Bản đồ các địa điểm làm việc"></div>
                    <div id="job-map-unavailable" class="job-map-unavailable" style="display:none;"></div>
                    <div id="detail-locations-list" class="job-detail-location-list"></div>
                </div>
            </div>

            <!-- Right Column: Meta & Employer Info -->
            <div>
                <div class="detail-card">
                    <h3 class="detail-card-title">Thông Tin Việc Làm</h3>
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:1rem;font-size:0.95rem;">
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Mức lương:</span>
                            <strong id="meta-salary" style="color:var(--secondary);">Thoả thuận</strong>
                        </li>
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Ca làm việc:</span>
                            <span id="meta-shift" style="font-weight:600;">Linh hoạt</span>
                        </li>
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Hình thức:</span>
                            <span id="meta-work-type" style="font-weight:600;">Part-time</span>
                        </li>
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Địa điểm:</span>
                            <span id="meta-location" style="font-weight:600;text-align:right;">Hà Nội</span>
                        </li>
                        <li style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);">Hạn nộp hồ sơ:</span>
                            <span id="meta-deadline" style="font-weight:600;color:var(--danger);">Còn tuyển</span>
                        </li>
                    </ul>

                    <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border);">
                        <button class="btn btn-primary" style="width:100%;" onclick="handleApplyClick()">
                            Ứng Tuyển Vị Trí Này
                        </button>
                    </div>
                </div>

                <div class="detail-card">
                    <h3 class="detail-card-title">Về Nhà Tuyển Dụng</h3>
                    <div id="company-info-box">
                        <div id="comp-sidebar-name" style="font-weight:700;font-size:1.05rem;margin-bottom:0.5rem;color:var(--dark);">Công ty</div>
                        <p id="comp-sidebar-desc" style="font-size:0.88rem;color:var(--text-muted);line-height:1.5;">Doanh nghiệp đối tác tuyển dụng sinh viên part-time trên hệ thống.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="report-job-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:10000;align-items:center;justify-content:center;padding:1rem;" role="dialog" aria-modal="true">
    <div class="detail-card" style="width:min(520px,100%);margin:0;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;"><h3 style="margin:0;">Báo cáo tin tuyển dụng</h3><button class="modal-close-btn" onclick="closeReportJobModal()">&times;</button></div>
        <p style="color:var(--text-muted);font-size:.9rem;">Báo cáo được gửi riêng tới quản trị viên. Vui lòng chọn lý do chính xác.</p>
        <form onsubmit="submitJobReport(event)">
            <div class="form-group"><label class="form-label">Lý do</label><select id="job-report-reason" class="form-control" required><option value="">Chọn lý do</option><option value="scam">Nghi ngờ lừa đảo</option><option value="salary_mismatch">Thông tin lương không chính xác</option><option value="fee_required">Yêu cầu ứng viên đóng phí</option><option value="inappropriate">Nội dung không phù hợp</option><option value="other">Lý do khác</option></select></div>
            <div class="form-group"><label class="form-label">Mô tả thêm</label><textarea id="job-report-description" class="form-control" maxlength="500" rows="4" placeholder="Thông tin giúp quản trị viên xác minh nhanh hơn..."></textarea></div>
            <div style="display:flex;justify-content:flex-end;gap:.75rem;"><button type="button" class="btn btn-outline" onclick="closeReportJobModal()">Hủy</button><button id="submit-report-btn" class="btn btn-primary">Gửi báo cáo</button></div>
        </form>
    </div>
</div>

<!-- Apply Job Modal -->
<div id="apply-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;" role="dialog" aria-modal="true" aria-labelledby="apply-modal-title">
    <div class="modal-card" style="background:var(--surface);border-radius:var(--radius);max-width:550px;width:100%;padding:1.5rem;box-shadow:var(--shadow);position:relative;max-height:90vh;overflow-y:auto;box-sizing:border-box;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">
            <h3 id="apply-modal-title" style="font-size:1.2rem;font-weight:700;color:var(--dark);margin:0;">Ứng Tuyển Việc Làm</h3>
            <button type="button" onclick="closeApplyModal()" class="modal-close-btn" aria-label="Đóng hộp thoại">&times;</button>
        </div>

        <div id="apply-job-info" style="background:var(--bg);border-radius:var(--radius-sm);padding:0.75rem 1rem;margin-bottom:1.25rem;border:1px solid var(--border);">
            <div id="apply-modal-job-title" style="font-weight:700;color:var(--dark);font-size:0.95rem;"></div>
            <div id="apply-modal-company" style="font-size:0.85rem;color:var(--text-muted);"></div>
        </div>

        <div id="apply-cv-readiness" style="margin-bottom:1.25rem;">
            <!-- Loading State -->
            <div id="apply-cv-loading" style="display:flex;align-items:center;gap:0.6rem;font-size:0.88rem;color:var(--text-muted);padding:0.85rem 1rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);">
                <span>⏳ Đang kiểm tra tệp CV trong hồ sơ của bạn...</span>
            </div>

            <!-- Ready State (Active CV exists) -->
            <div id="apply-cv-ready" style="display:none;padding:0.85rem 1rem;background:#f0fdf4;border:1px solid #86efac;border-left:4px solid #10b981;border-radius:var(--radius-sm);">
                <div style="font-size:0.9rem;font-weight:700;color:#166534;display:flex;align-items:center;gap:0.5rem;">
                    <span>📄</span>
                    <span>CV đính kèm: <strong id="apply-cv-name" style="word-break:break-all;"></strong></span>
                </div>
                <div style="font-size:0.82rem;color:#15803d;margin-top:0.35rem;line-height:1.4;">
                    ✓ Bản sao CV hiện tại của bạn sẽ được lưu giữ bất biến cùng đơn ứng tuyển này và chuyển tới nhà tuyển dụng.
                </div>
            </div>

            <!-- Missing State (No active CV) -->
            <div id="apply-cv-missing" style="display:none;padding:0.85rem 1rem;background:#fef2f2;border:1px solid #fca5a5;border-left:4px solid #ef4444;border-radius:var(--radius-sm);">
                <div style="font-size:0.9rem;font-weight:700;color:#991b1b;display:flex;align-items:center;gap:0.5rem;">
                    <span><i class="ri-alert-line"></i></span>
                    <span>Bạn chưa có CV trong hồ sơ</span>
                </div>
                <div style="font-size:0.82rem;color:#b91c1c;margin:0.35rem 0 0.6rem;line-height:1.4;">
                    Nhà tuyển dụng yêu cầu hồ sơ có đính kèm CV (định dạng PDF). Vui lòng tải lên CV trước khi gửi đơn ứng tuyển.
                </div>
                <div>
                    <a href="/student/profile" class="btn btn-primary btn-sm" style="font-size:0.82rem;padding:0.35rem 0.75rem;display:inline-flex;align-items:center;gap:0.35rem;">
                        Tải lên CV trong hồ sơ ngay &rarr;
                    </a>
                </div>
            </div>

            <!-- Error State (API or network error) -->
            <div id="apply-cv-error" style="display:none;padding:0.85rem 1rem;background:#fff1f2;border:1px solid #fecdd3;border-left:4px solid #e11d48;border-radius:var(--radius-sm);">
                <div style="font-size:0.9rem;font-weight:700;color:#9f1239;display:flex;align-items:center;gap:0.5rem;">
                    <span><i class="ri-alert-line"></i></span>
                    <span>Không thể kiểm tra tệp CV</span>
                </div>
                <div id="apply-cv-error-msg" style="font-size:0.82rem;color:#be123c;margin:0.35rem 0 0.5rem;line-height:1.4;">
                    Đã xảy ra lỗi khi kiểm tra thông tin CV.
                </div>
                <button type="button" class="btn btn-outline btn-sm" style="font-size:0.8rem;padding:0.25rem 0.6rem;" onclick="checkCvReadiness()">
                    Thử lại
                </button>
            </div>
        </div>

                <!-- Commute Distance Check (Pre-application) -->
        <div id="apply-commute-section" style="margin-bottom:1.25rem;">
            <!-- Loading indicator -->
            <div id="commute-loading" style="display:none;padding:0.6rem 0.85rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:0.83rem;color:var(--text-muted);align-items:center;gap:0.5rem;">
                <div class="autocomplete-spinner" style="position:static;width:14px;height:14px;"></div>
                <span>Đang kiểm tra khoảng cách đi làm...</span>
            </div>

            <!-- Soft Commute Warning (is_far === true) -->
            <div id="commute-warning-box" class="commute-warning-banner" style="display:none;">
                <div class="commute-warning-header">
                    <span><i class="ri-alert-line"></i></span>
                    <span>Lưu ý về khoảng cách di chuyển</span>
                </div>
                <div id="commute-warning-message" class="commute-warning-text"></div>
                <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                    <button type="button" id="btn-route-distance" class="commute-action-link" onclick="checkRealRouteDistance()">
                        <i class="ri-motorbike-line"></i> Xem quãng đường thực tế (Xe máy)
                    </button>
                    <span style="font-size:0.78rem;color:#92400e;">(Bạn vẫn có thể ứng tuyển)</span>
                </div>
            </div>

            <!-- Near Distance Info (is_far === false) -->
            <div id="commute-near-box" style="display:none;padding:0.65rem 0.9rem;background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #10b981;border-radius:var(--radius-sm);margin-bottom:0.75rem;">
                <div style="font-size:0.85rem;color:#166534;font-weight:600;display:flex;align-items:center;gap:0.4rem;">
                    <span><i class="ri-map-pin-2-line"></i></span>
                    <span id="commute-near-text"></span>
                </div>
                <button type="button" id="btn-route-distance-near" class="commute-action-link" style="margin-top:0.35rem;display:inline-block;color:#15803d;" onclick="checkRealRouteDistance()">
                    <i class="ri-motorbike-line"></i> Xem quãng đường thực tế (Xe máy)
                </button>
            </div>

            <!-- Manual trigger button if coordinates weren't requested yet -->
            <div id="commute-check-cta" style="display:none;margin-bottom:0.75rem;padding:0.7rem 0.85rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);">
                <div id="commute-check-cta-message" style="font-size:0.82rem;color:var(--text-muted);line-height:1.45;margin-bottom:0.55rem;">
                    Cho phép truy cập vị trí để kiểm tra khoảng cách đi làm.
                </div>
                <button type="button" id="btn-commute-check" class="btn btn-outline btn-sm" onclick="triggerCommuteCheck()" style="font-size:0.82rem;padding:0.35rem 0.75rem;display:inline-flex;align-items:center;gap:0.35rem;">
                    <span><i class="ri-map-pin-2-line"></i></span> <span>Kiểm tra khoảng cách đi làm từ vị trí của bạn</span>
                </button>
            </div>
        </div>

        <div id="apply-error-box" style="display:none;margin-bottom:1rem;" class="toast toast-error"></div>

        <form id="apply-form" onsubmit="submitApplication(event)">
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">Ca làm việc mong muốn <span style="color:var(--danger)">*</span></label>
                <select id="apply-shift" class="form-control" required>
                    <option value="morning">Ca Sáng (08:00 - 12:00)</option>
                    <option value="afternoon">Ca Chiều (13:00 - 17:00)</option>
                    <option value="evening">Ca Tối (18:00 - 22:00)</option>
                    <option value="weekend">Cuối Tuần (Thứ 7 & CN)</option>
                    <option value="flexible">Linh hoạt theo lịch học</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label">Thư giới thiệu / Lời nhắn tới nhà tuyển dụng</label>
                <textarea id="apply-cover-letter" class="form-control" rows="4" placeholder="Giới thiệu nhanh về bạn, kinh nghiệm làm thêm (nếu có) và mong muốn khi làm việc..."></textarea>
                <small class="form-help">Bản sao CV PDF từ hồ sơ của bạn sẽ được lưu giữ bất biến cùng đơn ứng tuyển này.</small>
            </div>

            <!-- AI Match Analysis Consent (CV-AI-P0-05) -->
            <div class="form-group" style="margin-bottom:1.25rem;background:#f8fafc;padding:0.75rem 1rem;border-radius:var(--radius-sm);border:1px solid var(--border);">
                <label style="display:flex;align-items:flex-start;gap:0.6rem;cursor:pointer;margin:0;font-weight:normal;">
                    <input type="checkbox" id="apply-ai-consent" style="margin-top:0.25rem;">
                    <span style="font-size:0.85rem;color:var(--dark);line-height:1.4;">
                        Tôi đồng ý cho JobMarketSV sử dụng AI để phân tích dữ liệu hồ sơ nhằm đánh giá mức độ phù hợp với vị trí này. Kết quả chỉ mang tính tham khảo và không quyết định tuyển dụng.
                    </span>
                </label>
                <small style="display:block;margin-top:0.35rem;font-size:0.78rem;color:var(--text-muted);">
                    (Tùy chọn) Đơn ứng tuyển của bạn vẫn sẽ được gửi tới nhà tuyển dụng bình thường nếu không chọn phân tích AI.
                </small>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding-top:1rem;border-top:1px solid var(--border);">
                <button type="button" onclick="closeApplyModal()" class="btn btn-outline btn-sm">Hủy</button>
                <button type="submit" id="btn-submit-apply" class="btn btn-primary btn-sm" disabled style="opacity:0.6;cursor:not-allowed;">
                    Gửi Đơn Ứng Tuyển
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const currentJobId = <?= json_encode($jobId ?? "") ?>;
const goongMaptilesKey = <?= json_encode($goongMaptilesKey ?? "", JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
let currentJobData = null;
let ephemeralCoords = null; // Stored in runtime memory only during session
let jobDetailMap = null;
let lastCommuteIsFar = false;

document.addEventListener("DOMContentLoaded", async () => {
    if (!currentJobId) {
        showToast("Không tìm thấy mã việc làm.", "error");
        return;
    }

    const signedInUser = TokenStorage.getUser();
    if (signedInUser && ["student", "developer"].includes(signedInUser.role)) {
        document.getElementById("btn-report-job").style.display = "inline-flex";
    }

    // Call REST API GET /jobs/{id}
    const res = await apiRequest(`/jobs/${encodeURIComponent(currentJobId)}`);

    document.getElementById("job-detail-loading").style.display = "none";

    if (res && res.success && res.data) {
        const job = res.data;
        currentJobData = job;
        document.getElementById("job-detail-wrapper").style.display = "block";

        // Update Title & Meta
        document.title = `${job.title} | JobMarket SV`;
        document.getElementById("job-title").innerText = job.title;
        const logoEl = document.getElementById("job-company-logo");
        if (job.company_logo) {
            logoEl.innerHTML = `<img src="${escapeHtml(job.company_logo)}" alt="${escapeHtml(job.company_name)}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;" onerror="this.outerHTML='${escapeHtml(job.company_name ? job.company_name.substring(0, 1) : 'J')}'">`;
        } else {
            logoEl.innerText = (job.company_name ? job.company_name.substring(0, 1) : "J");
        }

        // Badges
        const ageRequirement = job.minimum_age && job.maximum_age
            ? `${job.minimum_age}–${job.maximum_age} tuổi`
            : (job.minimum_age ? `Từ ${job.minimum_age} tuổi` : (job.maximum_age ? `Đến ${job.maximum_age} tuổi` : null));
        document.getElementById("job-badges-header").innerHTML = `
            <span class="badge badge-salary" style="font-size:0.85rem;padding:0.35rem 0.75rem;"><i class="ri-money-dollar-circle-line"></i> ${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}</span>
            <span class="badge badge-shift" style="font-size:0.85rem;padding:0.35rem 0.75rem;"><i class="ri-time-line"></i> ${job.shift_type ? getShiftLabel(job.shift_type) : "Không yêu cầu ca cố định"}</span>
            ${ageRequirement ? `<span class="badge" style="font-size:0.85rem;padding:0.35rem 0.75rem;background:#fdf2f8;color:#9d174d;"><i class="ri-cake-2-line"></i> ${escapeHtml(ageRequirement)}</span>` : ""}
            <span class="badge badge-location" style="font-size:0.85rem;padding:0.35rem 0.75rem;"><i class="ri-map-pin-2-line"></i> ${escapeHtml(job.location_name || job.city || "Hà Nội")}</span>
        `;

        // Content
        document.getElementById("job-description").innerText = job.description || "Chưa có mô tả chi tiết.";
        document.getElementById("job-requirements").innerText = job.requirements || "Không yêu cầu kinh nghiệm đặc biệt.";
        document.getElementById("job-benefits").innerText = job.benefits || "Hưởng mức lương theo giờ và các chế độ phụ cấp.";

        // Sidebar Meta
        document.getElementById("meta-salary").innerText = `${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}`;
        document.getElementById("meta-shift").innerText = job.shift_type ? getShiftLabel(job.shift_type) : "Không yêu cầu cố định";
        document.getElementById("meta-work-type").innerText = getWorkTypeLabel(job.work_type);
        document.getElementById("meta-location").innerText = `${job.district ? job.district + ', ' : ''}${job.city || 'Hà Nội'}`;
        document.getElementById("meta-deadline").innerText = formatDate(job.application_deadline) || "Còn tuyển";

        // Company Sidebar
        document.getElementById("comp-sidebar-name").innerText = job.company_name || "Nhà tuyển dụng";
        if (job.company_description) {
            document.getElementById("comp-sidebar-desc").innerText = job.company_description;
        }
        renderJobDetailLocations(job.work_locations || []);
    } else {
        document.getElementById("job-detail-loading").innerHTML = `
            <div class="empty-state" style="background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);margin-top:2rem;">
                <div class="empty-icon">❌</div>
                <h2>Việc làm không tồn tại hoặc đã đóng</h2>
                <p>Tin tuyển dụng này có thể đã hết hạn nộp hoặc đã được nhà tuyển dụng tạm đóng.</p>
                <a href="/viec-lam" class="btn btn-primary" style="margin-top:1.5rem;">Xem các việc làm khác</a>
            </div>
        `;
        document.getElementById("job-detail-loading").style.display = "block";
    }
});

function openReportJobModal() {
    if (!TokenStorage.isLoggedIn()) { window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`; return; }
    document.getElementById("report-job-modal").style.display = "flex";
}
function closeReportJobModal() { document.getElementById("report-job-modal").style.display = "none"; }
async function submitJobReport(event) {
    event.preventDefault(); const button = document.getElementById("submit-report-btn"); button.disabled = true;
    const res = await apiRequest(`/jobs/${encodeURIComponent(currentJobId)}/reports`, {method:"POST", requireAuth:true, body:{reason:document.getElementById("job-report-reason").value,description:document.getElementById("job-report-description").value.trim()}});
    button.disabled = false;
    if(res?.success){showToast("Báo cáo đã được gửi tới quản trị viên.","success");closeReportJobModal();} else showToast(res?.message || "Không thể gửi báo cáo.","error");
}

function handleApplyClick() {
    handleApplyJob();
}

function handleApplyJob() {
    const user = TokenStorage.getUser();

    if (!user) {
        showToast("Vui lòng đăng nhập với tài khoản Sinh viên để nộp đơn ứng tuyển.", "error");
        setTimeout(() => {
            window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        }, 600);
        return;
    }

    if (user.role !== "student" && user.role !== "developer") {
        showToast("Chỉ tài khoản Sinh viên mới có quyền nộp đơn ứng tuyển.", "error");
        return;
    }

    openApplyModal();
}

let lastApplyFocusElement = null;


/* ==========================================================================
   WORK LOCATIONS & COMMUTE CHECK
   ========================================================================== */
function renderJobDetailLocations(locations) {
    const card = document.getElementById("detail-locations-card");
    const list = document.getElementById("detail-locations-list");
    const countBadge = document.getElementById("detail-locs-count");

    if (!Array.isArray(locations) || locations.length === 0) {
        card.style.display = "none";
        return;
    }

    card.style.display = "block";
    countBadge.innerText = `${locations.length} cơ sở làm việc`;

    list.innerHTML = locations.map((loc, idx) => {
        const isPrimary = !!loc.is_primary;
        const isVerified = loc.geocode_status === "verified" || loc.provider === "goong";
        const branchName = loc.branch_name ? escapeHtml(loc.branch_name) : `Cơ sở ${idx + 1}`;
        const addressText = escapeHtml(loc.address_text || "");
        const parts = [loc.commune, loc.province].filter(Boolean);
        const subAddress = parts.length > 0 ? parts.join(", ") : "";
        const legacyDistrict = loc.district_text_legacy ? escapeHtml(loc.district_text_legacy) : "";

        // Tạo Google Maps search link an toàn mà không leak Goong key
        let mapUrl = "#";
        if (loc.latitude && loc.longitude) {
            mapUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.latitude)},${encodeURIComponent(loc.longitude)}`;
        } else if (loc.address_text) {
            mapUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(loc.address_text)}`;
        }

        return `
            <div class="location-card ${isPrimary ? 'is-primary' : ''}">
                <div class="location-card-info">
                    <div class="location-card-title">
                        <span>${branchName}</span>
                        ${isPrimary ? '<span class="badge-loc badge-loc-primary">★ Địa điểm chính</span>' : ''}
                        ${isVerified ? '<span class="badge-loc badge-loc-verified">✓ Đã xác thực</span>' : '<span class="badge-loc badge-loc-manual">✎ Nhập thủ công</span>'}
                    </div>
                    <div class="location-card-address">${addressText}</div>
                    <div class="location-card-meta">
                        ${subAddress ? `<span style="font-size:0.8rem;color:var(--text-muted);"><i class="ri-map-pin-2-line"></i> ${escapeHtml(subAddress)}</span>` : ''}
                        ${legacyDistrict ? `<span class="badge-loc" style="background:#e2e8f0;color:#475569;">${legacyDistrict}</span>` : ''}
                    </div>
                </div>
                <div class="location-card-actions">
                    <a href="${mapUrl}" target="_blank" rel="noopener noreferrer" class="btn-loc-action" style="text-decoration:none;" title="Mở trên bản đồ">
                        <i class="ri-map-2-line"></i> Xem trên bản đồ
                    </a>
                </div>
            </div>
        `;
    }).join("");

    renderGoongJobMap(locations);
}

function renderGoongJobMap(locations) {
    const mapEl = document.getElementById("job-detail-map");
    const unavailableEl = document.getElementById("job-map-unavailable");
    const validLocations = locations.filter(loc => {
        if (loc.latitude === null || loc.latitude === undefined || loc.latitude === ""
            || loc.longitude === null || loc.longitude === undefined || loc.longitude === "") {
            return false;
        }
        const lat = Number(loc.latitude);
        const lng = Number(loc.longitude);
        return Number.isFinite(lat) && Number.isFinite(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
    });

    if (jobDetailMap && typeof jobDetailMap.remove === "function") {
        jobDetailMap.remove();
        jobDetailMap = null;
    }

    if (validLocations.length === 0) {
        mapEl.style.display = "none";
        unavailableEl.style.display = "block";
        unavailableEl.innerText = "Bản đồ sẽ hiển thị sau khi nhà tuyển dụng xác thực tọa độ địa điểm.";
        return;
    }

    if (!goongMaptilesKey || !window.goongjs) {
        mapEl.style.display = "none";
        unavailableEl.style.display = "block";
        unavailableEl.innerText = "Không thể tải bản đồ lúc này. Bạn vẫn có thể mở từng địa điểm bằng liên kết bên dưới.";
        return;
    }

    mapEl.style.display = "block";
    unavailableEl.style.display = "none";
    window.goongjs.accessToken = goongMaptilesKey;

    const primary = validLocations.find(loc => loc.is_primary) || validLocations[0];
    jobDetailMap = new window.goongjs.Map({
        container: mapEl,
        style: "https://tiles.goong.io/assets/goong_map_web.json",
        center: [Number(primary.longitude), Number(primary.latitude)],
        zoom: validLocations.length === 1 ? 15 : 11
    });
    jobDetailMap.addControl(new window.goongjs.NavigationControl(), "top-right");

    const bounds = new window.goongjs.LngLatBounds();
    validLocations.forEach((loc, idx) => {
        const lngLat = [Number(loc.longitude), Number(loc.latitude)];
        const branchName = loc.branch_name || `Cơ sở ${idx + 1}`;
        const popupHtml = `<div class="job-map-popup"><strong>${escapeHtml(branchName)}</strong><br>${escapeHtml(loc.address_text || "")}</div>`;
        new window.goongjs.Marker({ color: loc.is_primary ? "#059669" : "#10b981" })
            .setLngLat(lngLat)
            .setPopup(new window.goongjs.Popup({ offset: 22 }).setHTML(popupHtml))
            .addTo(jobDetailMap);
        bounds.extend(lngLat);
    });

    jobDetailMap.once("load", () => {
        if (validLocations.length > 1) {
            jobDetailMap.fitBounds(bounds, { padding: 52, maxZoom: 15, duration: 0 });
        }
        jobDetailMap.resize();
    });
}

function resetCommuteCheckUi() {
    const loading = document.getElementById("commute-loading");
    const warningBox = document.getElementById("commute-warning-box");
    const nearBox = document.getElementById("commute-near-box");
    const cta = document.getElementById("commute-check-cta");
    const submitBtn = document.getElementById("btn-submit-apply");

    if (loading) loading.style.display = "none";
    if (warningBox) warningBox.style.display = "none";
    if (nearBox) nearBox.style.display = "none";
    if (cta) cta.style.display = "none";
    lastCommuteIsFar = false;
    if (submitBtn) submitBtn.innerText = "Gửi Đơn Ứng Tuyển";
}

function showCommuteCheckPrompt(message, actionable = true) {
    const cta = document.getElementById("commute-check-cta");
    const messageEl = document.getElementById("commute-check-cta-message");
    const button = document.getElementById("btn-commute-check");
    const loading = document.getElementById("commute-loading");

    if (loading) loading.style.display = "none";
    if (messageEl) messageEl.innerText = message;
    if (button) button.style.display = actionable ? "inline-flex" : "none";
    if (cta) cta.style.display = "block";
}

function hasGeocodedWorkLocation() {
    const locations = currentJobData && Array.isArray(currentJobData.work_locations)
        ? currentJobData.work_locations
        : [];

    return locations.some((loc) => {
        if (loc.latitude === null || loc.latitude === undefined || loc.latitude === ""
            || loc.longitude === null || loc.longitude === undefined || loc.longitude === "") {
            return false;
        }
        return Number.isFinite(Number(loc.latitude)) && Number.isFinite(Number(loc.longitude));
    });
}

async function prepareCommuteCheck() {
    resetCommuteCheckUi();

    if (!hasGeocodedWorkLocation()) {
        showCommuteCheckPrompt("Tin tuyển dụng này chưa có tọa độ địa điểm để kiểm tra khoảng cách.", false);
        return;
    }

    if (ephemeralCoords) {
        await runCommuteCheck(false);
        return;
    }

    if (!window.isSecureContext) {
        showCommuteCheckPrompt("Trình duyệt chỉ cho phép lấy GPS trên HTTPS. Hãy mở trang bằng địa chỉ bắt đầu bằng https:// rồi thử lại.", false);
        return;
    }

    if (!navigator.geolocation) {
        showCommuteCheckPrompt("Trình duyệt này không hỗ trợ xác định vị trí.", false);
        return;
    }

    if (navigator.permissions && typeof navigator.permissions.query === "function") {
        try {
            const permission = await navigator.permissions.query({ name: "geolocation" });
            if (permission.state === "granted") {
                triggerCommuteCheck();
                return;
            }
            if (permission.state === "denied") {
                showCommuteCheckPrompt("Chrome đang chặn vị trí cho trang này. Bấm biểu tượng bên trái thanh địa chỉ, mở Cài đặt trang web, cho phép Vị trí rồi tải lại trang.", false);
                return;
            }
        } catch (err) {
            console.debug("Không đọc được trạng thái quyền vị trí:", err);
        }
    }

    showCommuteCheckPrompt("Nhấn nút bên dưới và chọn Cho phép khi trình duyệt hỏi quyền vị trí. Tọa độ chỉ dùng cho lần kiểm tra này, không được lưu vào hồ sơ.");
}

function triggerCommuteCheck() {
    if (!window.isSecureContext) {
        showCommuteCheckPrompt("Trình duyệt chỉ cho phép lấy GPS trên HTTPS. Hãy mở trang bằng địa chỉ bắt đầu bằng https:// rồi thử lại.", false);
        return;
    }
    if (!navigator.geolocation) {
        showCommuteCheckPrompt("Trình duyệt này không hỗ trợ xác định vị trí.", false);
        return;
    }
    const cta = document.getElementById("commute-check-cta");
    const loading = document.getElementById("commute-loading");
    if (cta) cta.style.display = "none";
    if (loading) loading.style.display = "flex";

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            ephemeralCoords = {
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude
            };
            runCommuteCheck(false);
        },
        (err) => {
            if (loading) loading.style.display = "none";
            console.warn("Geolocation error:", err);
            if (err && err.code === err.PERMISSION_DENIED) {
                showCommuteCheckPrompt("Chrome đang chặn vị trí cho trang này. Bấm biểu tượng bên trái thanh địa chỉ, mở Cài đặt trang web, cho phép Vị trí rồi tải lại trang.", false);
                showToast("Bạn chưa cấp quyền vị trí cho trang web.", "info");
            } else if (err && err.code === err.TIMEOUT) {
                showCommuteCheckPrompt("Không lấy được vị trí trong thời gian chờ. Bạn có thể thử lại.");
                showToast("Yêu cầu vị trí đã hết thời gian chờ.", "info");
            } else {
                showCommuteCheckPrompt("Thiết bị chưa xác định được vị trí. Hãy kiểm tra dịch vụ Location của Windows rồi thử lại.");
                showToast("Không thể xác định vị trí để kiểm tra khoảng cách đi làm.", "info");
            }
        },
        { timeout: 8000, enableHighAccuracy: false }
    );
}

async function runCommuteCheck(useRoute = false, vehicle = 'bike') {
    if (!ephemeralCoords || !currentJobId) return;

    const loading = document.getElementById("commute-loading");
    const warningBox = document.getElementById("commute-warning-box");
    const warningMsg = document.getElementById("commute-warning-message");
    const nearBox = document.getElementById("commute-near-box");
    const nearText = document.getElementById("commute-near-text");
    const cta = document.getElementById("commute-check-cta");

    if (loading) loading.style.display = "flex";
    if (warningBox) warningBox.style.display = "none";
    if (nearBox) nearBox.style.display = "none";
    if (cta) cta.style.display = "none";

    try {
        const payload = {
            latitude: ephemeralCoords.latitude,
            longitude: ephemeralCoords.longitude,
            max_commute_km: 15,
            use_route: useRoute,
            vehicle: vehicle
        };

        const res = await apiRequest(`/jobs/${encodeURIComponent(currentJobId)}/commute-check`, {
            method: "POST",
            body: payload
        });

        if (loading) loading.style.display = "none";

        if (res && res.success && res.data) {
            const d = res.data;
            const distKm = d.route_distance_km || d.straight_line_distance_km;
            const distVi = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 1 }).format(distKm);

            if (d.is_far) {
                lastCommuteIsFar = true;
                if (warningMsg) {
                    warningMsg.innerText = d.warning || `Công việc này cách khu vực của bạn khoảng ${distVi} km, vượt mức ${d.max_commute_km} km mong muốn. Bạn vẫn có thể ứng tuyển.`;
                }
                if (warningBox) warningBox.style.display = "block";

                // Vẫn cho phép ứng tuyển và làm rõ nút submit
                const submitBtn = document.getElementById("btn-submit-apply");
                if (submitBtn) {
                    submitBtn.innerText = "Vẫn Gửi Đơn Ứng Tuyển";
                }
            } else {
                lastCommuteIsFar = false;
                if (nearText) {
                    nearText.innerText = `Khoảng cách đi làm: khoảng ${distVi} km ${useRoute ? '(theo đường bộ xe máy)' : '(đường chim bay)'} - Thuận tiện đi lại!`;
                }
                if (nearBox) nearBox.style.display = "block";
                const submitBtn = document.getElementById("btn-submit-apply");
                if (submitBtn) submitBtn.innerText = "Gửi Đơn Ứng Tuyển";
            }
        } else {
            showCommuteCheckPrompt((res && res.message) ? res.message : "Chưa thể kiểm tra khoảng cách lúc này. Bạn có thể thử lại.");
        }
    } catch (err) {
        if (loading) loading.style.display = "none";
        console.error("Commute check error:", err);
        showCommuteCheckPrompt("Không kết nối được dịch vụ kiểm tra khoảng cách. Bạn vẫn có thể ứng tuyển hoặc thử lại.");
    }
}

function checkRealRouteDistance() {
    runCommuteCheck(true, 'bike');
}

async function openApplyModal() {
    lastApplyFocusElement = document.activeElement;
    const modal = document.getElementById("apply-modal");
    if (!modal) return;
    document.getElementById("apply-modal-job-title").innerText = currentJobData ? currentJobData.title : "";
    document.getElementById("apply-modal-company").innerText = currentJobData ? (currentJobData.company_name || "Nhà tuyển dụng") : "";
    if (currentJobData && currentJobData.shift_type) {
        const sel = document.getElementById("apply-shift");
        if (sel && sel.querySelector(`option[value="${currentJobData.shift_type}"]`)) {
            sel.value = currentJobData.shift_type;
        }
    }
    const errBox = document.getElementById("apply-error-box");
    if (errBox) errBox.style.display = "none";

    modal.style.display = "flex";
    void prepareCommuteCheck();
    await checkCvReadiness();
}

async function checkCvReadiness() {
    const cvLoading = document.getElementById("apply-cv-loading");
    const cvReady = document.getElementById("apply-cv-ready");
    const cvMissing = document.getElementById("apply-cv-missing");
    const cvError = document.getElementById("apply-cv-error");
    const btnSubmit = document.getElementById("btn-submit-apply");

    if (cvLoading) cvLoading.style.display = "flex";
    if (cvReady) cvReady.style.display = "none";
    if (cvMissing) cvMissing.style.display = "none";
    if (cvError) cvError.style.display = "none";

    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.style.opacity = "0.6";
        btnSubmit.style.cursor = "not-allowed";
    }

    try {
        const cvRes = await apiRequest("/student/cv", { requireAuth: true });
        if (cvLoading) cvLoading.style.display = "none";

        if (cvRes && cvRes.success) {
            if (cvRes.data && cvRes.data.file_name) {
                const formattedSize = typeof formatBytes === "function"
                    ? formatBytes(cvRes.data.file_size)
                    : `${(cvRes.data.file_size / 1024).toFixed(1)} KB`;
                document.getElementById("apply-cv-name").innerText = `${cvRes.data.file_name} (${formattedSize})`;
                if (cvReady) cvReady.style.display = "block";
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = "1";
                    btnSubmit.style.cursor = "pointer";
                    btnSubmit.innerText = lastCommuteIsFar ? "Vẫn Gửi Đơn Ứng Tuyển" : "Gửi Đơn Ứng Tuyển";
                }
            } else {
                if (cvMissing) cvMissing.style.display = "block";
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.style.opacity = "0.6";
                    btnSubmit.style.cursor = "not-allowed";
                }
            }
        } else {
            if (cvError) {
                const msgEl = document.getElementById("apply-cv-error-msg");
                if (msgEl) msgEl.innerText = (cvRes && cvRes.message) ? cvRes.message : "Không thể kiểm tra tệp CV.";
                cvError.style.display = "block";
            } else if (cvMissing) {
                cvMissing.style.display = "block";
            }
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.style.opacity = "0.6";
                btnSubmit.style.cursor = "not-allowed";
            }
        }
    } catch (err) {
        if (cvLoading) cvLoading.style.display = "none";
        if (cvError) {
            const msgEl = document.getElementById("apply-cv-error-msg");
            if (msgEl) msgEl.innerText = "Lỗi kết nối máy chủ khi kiểm tra hồ sơ CV.";
            cvError.style.display = "block";
        } else if (cvMissing) {
            cvMissing.style.display = "block";
        }
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = "0.6";
            btnSubmit.style.cursor = "not-allowed";
        }
    }
}

function closeApplyModal() {
    const modal = document.getElementById("apply-modal");
    if (modal) modal.style.display = "none";
    if (lastApplyFocusElement && typeof lastApplyFocusElement.focus === "function") {
        lastApplyFocusElement.focus();
    }
}

// Close apply modal on backdrop click & ESC key
window.addEventListener("click", (e) => {
    const modal = document.getElementById("apply-modal");
    if (modal && e.target === modal) {
        closeApplyModal();
    }
});

window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        closeApplyModal();
    }
});

async function submitApplication(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-submit-apply");
    const errBox = document.getElementById("apply-error-box");
    if (errBox) errBox.style.display = "none";

    btn.disabled = true;
    btn.style.opacity = "0.6";
    btn.style.cursor = "not-allowed";
    btn.innerText = "Đang gửi đơn...";

    const shift = document.getElementById("apply-shift").value;
    const coverLetter = document.getElementById("apply-cover-letter").value.trim();
    const aiConsent = document.getElementById("apply-ai-consent")?.checked === true;

    try {
        const res = await apiRequest(`/jobs/${encodeURIComponent(currentJobId)}/applications`, {
            method: "POST",
            body: {
                preferred_shift: shift,
                cover_letter: coverLetter,
                ai_match_consent: aiConsent
            },
            requireAuth: true
        });

        if (res && res.success) {
            closeApplyModal();
            showToast("Ứng tuyển thành công! Nhà tuyển dụng sẽ xem xét hồ sơ của bạn.", "success");
            // Update apply buttons
            const btnTop = document.getElementById("btn-apply-top");
            const btnBottom = document.getElementById("btn-apply-bottom");
            if (btnTop) {
                btnTop.innerText = "✅ Đã Ứng Tuyển";
                btnTop.disabled = true;
                btnTop.classList.remove("btn-primary");
                btnTop.classList.add("btn-secondary");
            }
            if (btnBottom) {
                btnBottom.innerText = "✅ Đã Ứng Tuyển";
                btnBottom.disabled = true;
                btnBottom.classList.remove("btn-primary");
                btnBottom.classList.add("btn-secondary");
            }

            // Trigger Match Analysis in background if consent was given (CV-AI-P1-04)
            const appId = res.data && (res.data.id || res.data.application_id);
            if (aiConsent && appId) {
                triggerPostApplyMatchAnalysis(appId);
            } else if (!aiConsent && appId) {
                showPostApplyNoConsentBanner();
            }
        } else {
            let msg = (res && res.message) ? res.message : "Ứng tuyển không thành công.";
            if (res && res.errors) {
                msg += " " + Object.values(res.errors).flat().map(escapeHtml).join(" ");
            }
            if (errBox) {
                errBox.innerText = msg;
                errBox.style.display = "block";
            }
            btn.disabled = false;
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
            btn.innerText = "Gửi Đơn Ứng Tuyển";
        }
    } catch (err) {
        if (errBox) {
            errBox.innerText = "Lỗi kết nối máy chủ khi gửi đơn ứng tuyển.";
            errBox.style.display = "block";
        }
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.style.cursor = "pointer";
        btn.innerText = "Gửi Đơn Ứng Tuyển";
    }
}

let postApplyPollTimer = null;
let postApplyPollCount = 0;
const MAX_POST_APPLY_POLLS = 6;

function clearPostApplyPolling() {
    if (postApplyPollTimer) {
        clearTimeout(postApplyPollTimer);
        postApplyPollTimer = null;
    }
    postApplyPollCount = 0;
}

function showPostApplyNoConsentBanner() {
    clearPostApplyPolling();
    const banner = document.getElementById("apply-match-banner");
    if (!banner) return;
    banner.style.display = "block";
    banner.innerHTML = `
        <div style="background:#f8fafc;border:1px solid var(--border);border-left:4px solid #64748b;border-radius:var(--radius);padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
            <div style="font-size:0.88rem;color:var(--text);">
                <i class="ri-information-line"></i> <strong>Bạn chưa bật phân tích AI cho đơn ứng tuyển này.</strong> Bạn có thể theo dõi tiến độ xét duyệt hồ sơ trong mục <a href="/student/applications" style="font-weight:600;text-decoration:underline;">Ứng tuyển của tôi</a>.
            </div>
            <button type="button" class="btn btn-outline btn-sm" style="font-size:0.78rem;padding:0.25rem 0.5rem;" onclick="document.getElementById('apply-match-banner').style.display='none'">Đóng</button>
        </div>
    `;
}

async function triggerPostApplyMatchAnalysis(applicationId) {
    const banner = document.getElementById("apply-match-banner");
    if (!banner) return;

    clearPostApplyPolling();

    // Lightweight loading indicator without blocking UI
    banner.style.display = "block";
    banner.innerHTML = `
        <div aria-live="polite" style="background:var(--primary-light);border:1px solid var(--primary-border);border-radius:var(--radius);padding:1rem 1.25rem;display:flex;align-items:center;gap:0.75rem;">
            <div class="spinner" style="width:20px;height:20px;border:3px solid var(--primary-border);border-top-color:var(--primary);border-radius:50%;animation:spin 1s linear infinite;"></div>
            <div>
                <strong style="color:var(--primary-text);font-size:0.95rem;">Đang phân tích độ phù hợp với công việc...</strong>
                <div style="font-size:0.82rem;color:var(--primary);margin-top:0.2rem;">Hệ thống AI đang đối chiếu an toàn hồ sơ của bạn với vị trí này.</div>
            </div>
        </div>
    `;

    try {
        const analysisRes = await apiRequest(`/applications/${encodeURIComponent(applicationId)}/match-analysis`, {
            method: "POST",
            requireAuth: true
        });

        if (analysisRes && analysisRes.success && analysisRes.data) {
            const data = analysisRes.data;
            if (data.status === "processing") {
                pollPostApplyMatchAnalysis(applicationId);
                return;
            }
            renderPostApplyMatchResult(data);
        } else {
            renderPostApplyFallback();
        }
    } catch (err) {
        // AI failure must never affect application success
        renderPostApplyFallback();
    }
}

async function pollPostApplyMatchAnalysis(applicationId) {
    if (postApplyPollCount >= MAX_POST_APPLY_POLLS) {
        clearPostApplyPolling();
        const banner = document.getElementById("apply-match-banner");
        if (banner) {
            banner.innerHTML = `
                <div style="background:#f8fafc;border:1px solid var(--border);border-left:4px solid var(--primary);border-radius:var(--radius);padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                    <div style="font-size:0.88rem;color:var(--text);">
                        ⏳ Phân tích độ phù hợp đang được xử lý trong nền. Bạn có thể theo dõi kết quả trong mục <a href="/student/applications" style="font-weight:600;text-decoration:underline;">Ứng tuyển của tôi</a>.
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" style="font-size:0.78rem;padding:0.25rem 0.5rem;" onclick="document.getElementById('apply-match-banner').style.display='none'">Đóng</button>
                </div>
            `;
        }
        return;
    }

    postApplyPollCount++;
    const delays = [1500, 2000, 2500, 3000, 3500, 4000];
    const delay = delays[postApplyPollCount - 1] || 3000;

    postApplyPollTimer = setTimeout(async () => {
        try {
            const res = await apiRequest(`/applications/${encodeURIComponent(applicationId)}/match-analysis`, {
                method: "GET",
                requireAuth: true
            });

            if (res && res.success && res.data) {
                if (res.data.status === "processing") {
                    pollPostApplyMatchAnalysis(applicationId);
                    return;
                }
                clearPostApplyPolling();
                renderPostApplyMatchResult(res.data);
            } else {
                clearPostApplyPolling();
                renderPostApplyFallback();
            }
        } catch (err) {
            clearPostApplyPolling();
            renderPostApplyFallback();
        }
    }, delay);
}

function renderPostApplyMatchResult(data) {
    const banner = document.getElementById("apply-match-banner");
    if (!banner) return;

    const rawScore = (data.overall_score !== null && data.overall_score !== undefined) ? Number(data.overall_score) : null;
    const coverage = (data.coverage_percent !== null && data.coverage_percent !== undefined) ? Number(data.coverage_percent) : null;
    const isInsufficient = rawScore === null || (coverage !== null && coverage < 60) || data.classification === "INSUFFICIENT_DATA";

    const scoreDisplay = isInsufficient ? 'Chưa đủ dữ liệu' : `${Math.round(rawScore)}/100`;
    const coverageDisplay = coverage !== null ? `${Math.round(coverage)}%` : '--';
    const classification = isInsufficient ? 'Chưa đủ dữ liệu' : (data.classification || 'Phù hợp');
    const summary = data.summary?.overview || 'Đã phân tích độ khớp giữa hồ sơ và yêu cầu công việc.';

    banner.innerHTML = `
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #16a34a;border-radius:var(--radius);padding:1.25rem;box-shadow:var(--shadow-sm);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;margin-bottom:0.75rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:1.25rem;">✨</span>
                    <strong style="color:#166534;font-size:1rem;">Kết quả đánh giá độ phù hợp (AI)</strong>
                    <span class="badge" style="background:#dcfce7;color:#15803d;font-weight:700;">${escapeHtml(classification)}</span>
                </div>
                <div style="display:flex;align-items:center;gap:1rem;">
                    <span style="font-size:0.88rem;color:#166534;">Điểm phù hợp: <strong style="font-size:1.15rem;color:#15803d;">${escapeHtml(String(scoreDisplay))}</strong></span>
                    <span style="font-size:0.85rem;color:var(--text-muted);">Độ phủ dữ liệu: <strong>${escapeHtml(String(coverageDisplay))}</strong></span>
                </div>
            </div>
            <p style="font-size:0.88rem;color:#14532d;line-height:1.5;margin:0 0 0.75rem;">
                ${escapeHtml(summary)}
            </p>
            <div style="font-size:0.8rem;color:#64748b;background:#f8fafc;padding:0.6rem 0.85rem;border-radius:var(--radius-sm);border:1px solid #e2e8f0;margin-bottom:0.75rem;line-height:1.4;">
                <i class="ri-alert-line"></i> <em>Điểm phù hợp chỉ phản ánh mức độ khớp giữa dữ liệu hồ sơ hiện có và yêu cầu công việc. Đây không phải xác suất được tuyển và không thay thế quyết định của nhà tuyển dụng.</em>
            </div>
            <div style="text-align:right;">
                <a href="/student/applications" class="btn btn-outline btn-sm" style="font-size:0.85rem;">
                    Xem chi tiết trong mục Ứng tuyển của tôi &rarr;
                </a>
            </div>
        </div>
    `;
}

function renderPostApplyFallback() {
    const banner = document.getElementById("apply-match-banner");
    if (!banner) return;
    banner.innerHTML = `
        <div style="background:#f8fafc;border:1px solid var(--border);border-left:4px solid #94a3b8;border-radius:var(--radius);padding:0.85rem 1.15rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
            <div style="font-size:0.88rem;color:var(--text);">
                <i class="ri-information-line"></i> Phân tích độ phù hợp tạm thời không khả dụng, bạn có thể xem lại sau trong mục <a href="/student/applications" style="font-weight:600;text-decoration:underline;">Ứng tuyển của tôi</a>.
            </div>
            <button type="button" class="btn btn-outline btn-sm" style="font-size:0.78rem;padding:0.25rem 0.5rem;" onclick="document.getElementById('apply-match-banner').style.display='none'">Đóng</button>
        </div>
    `;
}

async function handleToggleFavorite() {
    const user = TokenStorage.getUser();

    if (!user) {
        showToast("Vui lòng đăng nhập để lưu việc làm yêu thích.", "error");
        setTimeout(() => {
            window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
        }, 800);
        return;
    }

    if (user.role !== "student") {
        showToast("Chỉ tài khoản Sinh viên mới có tính năng lưu việc làm yêu thích.", "error");
        return;
    }

    // Call REST API POST /favorites/jobs/{id}
    const res = await apiRequest(`/favorites/jobs/${encodeURIComponent(currentJobId)}`, {
        method: "POST"
    });

    if (res && res.success) {
        showToast(res.message || "Đã lưu việc làm vào danh sách yêu thích!", "success");
        const btn = document.getElementById("btn-favorite");
        btn.classList.remove("btn-outline");
        btn.classList.add("btn-secondary");
        btn.innerHTML = `<i class="ri-heart-fill"></i> Đã lưu`;
    } else {
        showToast(res && res.message ? res.message : "Không thể lưu việc làm.", "error");
    }
}
</script>
