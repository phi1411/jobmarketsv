<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Filter bar -->
    <div style="background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <label for="filter-app-status" style="font-size:0.9rem;font-weight:600;color:var(--dark);">Lọc theo trạng thái:</label>
            <select id="filter-app-status" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.88rem;" onchange="loadApplications()">
                <option value="">Tất cả trạng thái</option>
                <option value="pending">Chưa phản hồi</option>
                <option value="interview">Mời phỏng vấn</option>
                <option value="accepted">Trúng tuyển</option>
                <option value="rejected">Từ chối</option>
                <option value="withdrawn">Đã rút đơn</option>
            </select>
        </div>
        <div id="apps-count-text" style="font-size:0.88rem;color:var(--text-muted);font-weight:600;">
            Đang tải dữ liệu...
        </div>
    </div>

    <!-- Loading State -->
    <div id="apps-loading" style="text-align:center;padding:2rem 0;">
        <div class="job-card skeleton" style="height:140px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:140px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:140px;"></div>
    </div>

    <!-- Error State -->
    <div id="apps-error" class="state-error" style="display:none;">
        <div class="state-error-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <h3 class="state-error-title">Không Thể Tải Đơn Ứng Tuyển</h3>
        <p id="apps-err-msg" class="state-error-desc">Đã xảy ra lỗi khi kết nối.</p>
        <button onclick="loadApplications()" class="btn btn-primary btn-sm">Thử Lại</button>
    </div>

    <!-- Applications Container -->
    <div id="apps-container" style="display:flex;flex-direction:column;gap:1rem;">
        <!-- Dynamic Cards -->
    </div>

    <!-- Empty State -->
    <div id="apps-empty" class="surface-card empty-state" style="display:none;">
        <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
        </div>
        <h3 class="empty-state-title">Chưa Có Đơn Ứng Tuyển Nào</h3>
        <p class="empty-state-text">
            Bạn chưa gửi đơn ứng tuyển vào công việc part-time nào. Hãy khám phá ngay các công việc phù hợp với lịch học của bạn!
        </p>
        <div class="empty-state-action">
            <a href="/viec-lam" class="btn btn-primary">Khám Phá Việc Làm Ngay</a>
        </div>
    </div>

    <!-- Pagination -->
    <div id="apps-pagination" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;"></div>
</div>

<!-- Match Analysis Modal (CV-AI-P1-04) -->
<div id="match-analysis-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;" role="dialog" aria-modal="true" aria-labelledby="modal-match-title">
    <div class="modal-card" style="background:var(--surface);border-radius:var(--radius);max-width:680px;width:100%;padding:1.5rem;box-shadow:var(--shadow-lg);position:relative;max-height:90vh;overflow-y:auto;box-sizing:border-box;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">
            <div>
                <h3 id="modal-match-title" style="font-size:1.2rem;font-weight:700;color:var(--dark);margin:0;display:flex;align-items:center;gap:0.5rem;">
                    <i class="ri-robot-2-line" style="color:var(--primary);"></i> <span>Đánh Giá Mức Độ Phù Hợp (AI)</span>
                </h3>
                <div id="modal-match-subtitle" style="font-size:0.85rem;color:var(--text-muted);margin-top:0.25rem;"></div>
            </div>
            <button type="button" onclick="closeMatchModal()" style="background:none;border:none;font-size:1.5rem;line-height:1;cursor:pointer;color:var(--text-muted);padding:0.2rem 0.5rem;" aria-label="Đóng hộp thoại">&times;</button>
        </div>

        <div id="modal-match-body">
            <!-- Dynamic Content -->
        </div>
    </div>
</div>

<script>
let currentAppPage = 1;

function initStudentApplicationsPage() {
    // Auth Guard
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để xem đơn ứng tuyển.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản Sinh viên mới có quyền xem trang này.", "error");
        setTimeout(() => { window.location.href = "/viec-lam"; }, 1000);
        return;
    }

    loadApplications(1);
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initStudentApplicationsPage);
} else {
    initStudentApplicationsPage();
}

function getStatusBadge(status) {
    return (typeof getAppStatusBadge === "function") ? getAppStatusBadge(status) : `<span class="badge">${escapeHtml(status)}</span>`;
}

function renderApplicationTimeline(history) {
    const labels = {pending:"Đã nộp",interview:"Mời phỏng vấn",accepted:"Trúng tuyển",rejected:"Chưa phù hợp",withdrawn:"Đã rút đơn"};
    const events = (Array.isArray(history) ? history : []).filter(event => Object.prototype.hasOwnProperty.call(labels, event.status));
    if (!events.length) return '';
    return `<div style="margin-top:1rem;padding:1rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);">
        <div style="font-size:.82rem;font-weight:800;color:var(--dark);margin-bottom:.75rem;">TIẾN TRÌNH ỨNG TUYỂN</div>
        <div style="display:flex;gap:.5rem;overflow-x:auto;padding-bottom:.25rem;">${events.map((event,index)=>`<div style="display:flex;align-items:center;min-width:max-content;"><div style="display:flex;align-items:center;gap:.4rem;padding:.45rem .65rem;background:#fff;border:1px solid ${index===events.length-1?'var(--primary-border)':'var(--border)'};border-radius:999px;font-size:.79rem;"><span style="width:8px;height:8px;border-radius:50%;background:${event.status==='rejected'?'#ef4444':event.status==='accepted'?'#10b981':'var(--primary)'}"></span><strong>${escapeHtml(labels[event.status]||event.status)}</strong><span style="color:var(--text-muted)">${formatDate(event.created_at)}</span></div>${index<events.length-1?'<span style="color:#94a3b8;margin:0 .25rem">→</span>':''}</div>`).join('')}</div>
    </div>`;
}

async function loadApplications(page = 1) {
    currentAppPage = page;
    const loadingEl = document.getElementById("apps-loading");
    const errorEl = document.getElementById("apps-error");
    const container = document.getElementById("apps-container");
    const emptyEl = document.getElementById("apps-empty");
    const countText = document.getElementById("apps-count-text");
    const paginationEl = document.getElementById("apps-pagination");

    if (loadingEl) loadingEl.style.display = "block";
    if (errorEl) errorEl.style.display = "none";
    if (container) container.innerHTML = "";
    if (emptyEl) emptyEl.style.display = "none";
    if (paginationEl) paginationEl.innerHTML = "";

    try {
        const statusFilter = document.getElementById("filter-app-status").value;
        const params = new URLSearchParams({ page: currentAppPage, per_page: 10 });
        if (statusFilter) params.append("status", statusFilter);

        const res = await apiRequest(`/student/applications?${params.toString()}`, { requireAuth: true });

        if (res && res.success && Array.isArray(res.data)) {
            const apps = res.data;
            const meta = res.meta || {};
            const total = meta.total || apps.length;

            if (countText) countText.innerText = `Tìm thấy ${total} đơn ứng tuyển`;

            if (apps.length === 0) {
                if (emptyEl) emptyEl.style.display = "block";
                return;
            }

            // Populate in-memory map for safe modal access without inline attribute reflection
            window.loadedApplicationsMap = new Map();
            apps.forEach(app => {
                window.loadedApplicationsMap.set(String(app.id), app);
            });

            container.innerHTML = apps.map(app => {
                const canWithdraw = app.status === "pending";
                return `
                    <div class="data-card" style="margin:0;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.25rem;">
                        <div style="flex:1;min-width:280px;">
                            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;flex-wrap:wrap;">
                                <h3 style="font-size:1.15rem;font-weight:700;margin:0;color:var(--dark);">
                                    <a href="/viec-lam/${encodeURIComponent(app.job_id)}">${escapeHtml(app.job_title || "Vị trí việc làm")}</a>
                                </h3>
                                ${getStatusBadge(app.status)}
                            </div>

                            <div style="color:var(--text-muted);font-size:0.88rem;margin-bottom:0.75rem;">
                                <strong>${escapeHtml(app.company_name || "Nhà tuyển dụng")}</strong> &bull;
                                Ngày nộp: ${formatDate(app.created_at)} &bull;
                                Ca mong muốn: <strong>${escapeHtml(getShiftLabel(app.preferred_shift))}</strong>
                            </div>

                            ${app.cover_letter ? `
                                <div style="background:#f8fafc;border-left:3px solid var(--border);padding:0.6rem 0.85rem;font-size:0.85rem;color:var(--text);margin-bottom:0.75rem;border-radius:0 var(--radius) var(--radius) 0;line-height:1.5;">
                                    <em>"${escapeHtml(app.cover_letter)}"</em>
                                </div>
                            ` : ''}

                            ${app.has_cv_snapshot ? `
                                <div style="font-size:0.82rem;color:var(--text);margin-top:0.35rem;display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
                                    <span><i class="ri-file-pdf-line"></i> CV đã nộp:</span>
                                    <strong style="color:var(--dark);word-break:break-all;">${escapeHtml(app.cv_file_name || 'Bản sao PDF')}</strong>
                                    ${app.cv_file_size ? `<span style="color:var(--text-muted);font-size:0.78rem;">(${typeof formatBytes === 'function' ? formatBytes(app.cv_file_size) : app.cv_file_size + ' B'})</span>` : ''}
                                </div>
                            ` : (app.cv_url_snapshot ? `
                                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.35rem;">
                                    CV đính kèm lúc nộp: <a href="${escapeHtml(app.cv_url_snapshot)}" target="_blank" rel="noopener noreferrer" style="text-decoration:underline;">Xem liên kết CV</a>
                                </div>
                            ` : `
                                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.35rem;">
                                    Không có tệp CV đính kèm
                                </div>
                            `)}
                            ${app.student_message ? `
                                <div style="margin-top:1rem;padding:1rem;background:${app.status==='rejected'?'#fef2f2':app.status==='accepted'?'#f0fdf4':'var(--primary-light)'};border-left:4px solid ${app.status==='rejected'?'#ef4444':app.status==='accepted'?'#10b981':'var(--primary)'};border-radius:var(--radius-sm);line-height:1.55;">
                                    <strong>Thông báo từ nhà tuyển dụng:</strong><br>${escapeHtml(app.student_message)}
                                </div>
                            ` : ''}
                            ${renderApplicationTimeline(app.status_history)}
                            <!-- Match analysis status row (CV-AI-P1-04) -->
                            <div style="margin-top:0.6rem;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                                <span style="font-size:0.8rem;color:var(--text-muted);font-weight:600;">Độ phù hợp (AI):</span>
                                ${app.ai_match_consent ? `
                                    <span class="badge btn-open-match-modal" style="background:var(--primary-light);color:var(--primary-text);border:1px solid var(--primary-border);font-size:0.75rem;cursor:pointer;display:inline-flex;align-items:center;gap:0.25rem;" data-app-id="${escapeHtml(app.id)}">
                                        <i class="ri-brain-line"></i> Đã cấp quyền (Xem chi tiết)
                                    </span>
                                ` : `
                                    <span class="badge btn-open-match-modal" style="background:#f1f5f9;color:#64748b;font-size:0.75rem;cursor:pointer;display:inline-flex;align-items:center;gap:0.25rem;" data-app-id="${escapeHtml(app.id)}">
                                        <i class="ri-lock-line"></i> Chưa phân tích (Chưa cấp quyền)
                                    </span>
                                `}
                            </div>
                        </div>

                        <div class="row-actions student-app-actions">
                            <button type="button" class="btn btn-outline btn-sm row-action-btn btn-open-match-modal" data-app-id="${escapeHtml(app.id)}" style="border-color:var(--primary-border);color:var(--primary-text);background:var(--primary-light);display:inline-flex;align-items:center;gap:0.35rem;">
                                <i class="ri-brain-line"></i> <span>Độ phù hợp (AI)</span>
                            </button>
                            ${app.has_cv_snapshot ? `
                                <button type="button" class="btn btn-outline btn-sm row-action-btn btn-view-cv" data-app-id="${escapeHtml(app.id)}" style="display:inline-flex;align-items:center;gap:0.35rem;">
                                    <i class="ri-file-pdf-line"></i> Xem CV đã nộp
                                </button>
                            ` : ''}
                            <a href="/viec-lam/${encodeURIComponent(app.job_id)}" class="btn btn-outline btn-sm row-action-btn">
                                Xem việc làm &rarr;
                            </a>
                            ${canWithdraw ? `
                                <button type="button" class="btn btn-sm row-action-btn btn-withdraw-app" data-app-id="${escapeHtml(app.id)}" style="background:#fff;border:1px solid var(--border);color:var(--danger);">
                                    Rút đơn ứng tuyển
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join("");

            bindAppsContainerDelegation();

            // Render Pagination
            const totalPages = meta.total_pages || 1;
            if (totalPages > 1) {
                let pagHtml = "";
                for (let i = 1; i <= totalPages; i++) {
                    pagHtml += `<button onclick="loadApplications(${i})" class="btn btn-sm ${i === currentAppPage ? 'btn-primary' : 'btn-outline'}" style="min-width:36px;">${i}</button>`;
                }
                paginationEl.innerHTML = pagHtml;
            }

        } else {
            if (errorEl) errorEl.style.display = "block";
            const msgEl = document.getElementById("apps-err-msg");
            if (msgEl) msgEl.innerText = (res && res.message) ? res.message : "Đã có lỗi xảy ra.";
        }
    } catch (err) {
        console.error("Error loading student applications:", err);
        if (errorEl) errorEl.style.display = "block";
        const msgEl = document.getElementById("apps-err-msg");
        if (msgEl) msgEl.innerText = "Lỗi kết nối máy chủ hoặc tải dữ liệu.";
    } finally {
        if (loadingEl) loadingEl.style.display = "none";
    }
}

function bindAppsContainerDelegation() {
    const container = document.getElementById("apps-container");
    if (container && !container._hasDelegation) {
        container._hasDelegation = true;
        container.addEventListener("click", (e) => {
            const matchBtn = e.target.closest(".btn-open-match-modal");
            if (matchBtn) {
                e.preventDefault();
                const appId = matchBtn.getAttribute("data-app-id");
                if (appId) openMatchAnalysisModal(appId);
                return;
            }
            const viewCvBtn = e.target.closest(".btn-view-cv");
            if (viewCvBtn) {
                e.preventDefault();
                const appId = viewCvBtn.getAttribute("data-app-id");
                if (appId) viewApplicationCv(appId, viewCvBtn);
                return;
            }
            const withdrawBtn = e.target.closest(".btn-withdraw-app");
            if (withdrawBtn) {
                e.preventDefault();
                const appId = withdrawBtn.getAttribute("data-app-id");
                if (appId) handleWithdraw(appId);
                return;
            }
        });
    }
}

async function handleWithdraw(applicationId) {
    if (!confirm("Bạn có chắc chắn muốn rút đơn ứng tuyển này không?\nHành động này không thể hoàn tác.")) {
        return;
    }

    const res = await apiRequest(`/applications/${encodeURIComponent(applicationId)}/withdraw`, {
        method: "POST",
        requireAuth: true
    });

    if (res && res.success) {
        showToast("Rút đơn ứng tuyển thành công!", "success");
        loadApplications(currentAppPage);
    } else {
        showToast((res && res.message) ? res.message : "Không thể rút đơn ứng tuyển.", "error");
    }
}

/* =========================================================
 * MATCH ANALYSIS MODAL & ACTIONS (CV-AI-P1-04)
 * ========================================================= */
let currentModalAppId = null;
let lastFocusedAppElement = null;
let matchAnalysisPollTimer = null;
let matchAnalysisPollCount = 0;
const MAX_MATCH_POLLS = 6;
let rateLimitCountdownInterval = null;

function clearRateLimitCountdown() {
    if (rateLimitCountdownInterval) {
        clearInterval(rateLimitCountdownInterval);
        rateLimitCountdownInterval = null;
    }
}

function clearMatchPolling() {
    if (matchAnalysisPollTimer) {
        clearTimeout(matchAnalysisPollTimer);
        matchAnalysisPollTimer = null;
    }
    matchAnalysisPollCount = 0;
}

function bindModalBodyDelegation() {
    const body = document.getElementById("modal-match-body");
    if (body && !body._hasDelegation) {
        body._hasDelegation = true;
        body.addEventListener("click", (e) => {
            const reanalyzeBtn = e.target.closest(".btn-reanalyze-match");
            if (reanalyzeBtn && currentModalAppId) {
                e.preventDefault();
                requestReanalysis(currentModalAppId);
                return;
            }
            const retryBtn = e.target.closest(".btn-retry-match");
            if (retryBtn && currentModalAppId) {
                e.preventDefault();
                loadMatchAnalysisData(currentModalAppId);
                return;
            }
            const revokeBtn = e.target.closest(".btn-revoke-consent");
            if (revokeBtn && currentModalAppId) {
                e.preventDefault();
                revokeMatchConsent(currentModalAppId);
                return;
            }
            const closeBtn = e.target.closest(".btn-close-match-modal");
            if (closeBtn) {
                e.preventDefault();
                closeMatchModal();
                return;
            }
        });
    }
}

function openMatchAnalysisModal(applicationId) {
    if (!applicationId) return;
    lastFocusedAppElement = document.activeElement;
    currentModalAppId = applicationId;
    clearRateLimitCountdown();
    clearMatchPolling();

    const modal = document.getElementById("match-analysis-modal");
    const subTitle = document.getElementById("modal-match-subtitle");
    const body = document.getElementById("modal-match-body");
    if (!modal || !body) return;

    bindModalBodyDelegation();

    const app = window.loadedApplicationsMap ? window.loadedApplicationsMap.get(String(applicationId)) : null;

    if (subTitle) {
        const jobTitle = (app && app.job_title) ? String(app.job_title) : "Vị trí việc làm";
        const compName = (app && app.company_name) ? ` • ${String(app.company_name)}` : "";
        subTitle.textContent = `${jobTitle}${compName}`;
    }

    modal.style.display = "flex";

    const hasConsent = app ? Boolean(app.ai_match_consent) : true;
    if (!hasConsent) {
        renderMatchModalConsentDeclined();
        return;
    }

    loadMatchAnalysisData(applicationId);
}

function closeMatchModal() {
    clearRateLimitCountdown();
    clearMatchPolling();
    currentModalAppId = null;
    const modal = document.getElementById("match-analysis-modal");
    if (modal) modal.style.display = "none";
    if (lastFocusedAppElement && typeof lastFocusedAppElement.focus === "function") {
        lastFocusedAppElement.focus();
    }
}

window.addEventListener("click", (e) => {
    const modal = document.getElementById("match-analysis-modal");
    if (modal && e.target === modal) {
        closeMatchModal();
    }
});

window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        closeMatchModal();
    }
});

function renderMatchModalConsentDeclined() {
    const body = document.getElementById("modal-match-body");
    if (!body) return;
    body.innerHTML = `
        <div style="text-align:center;padding:2rem 1rem;">
            <div style="font-size:2.5rem;margin-bottom:0.75rem;color:var(--text-muted);"><i class="ri-lock-line"></i></div>
            <h4 style="color:var(--dark);margin-bottom:0.5rem;font-size:1.1rem;">Chưa Cấp Quyền Phân Tích AI</h4>
            <p style="font-size:0.88rem;color:var(--text-muted);max-width:440px;margin:0 auto 1.5rem;line-height:1.5;">
                Đơn ứng tuyển này không được bật quyền phân tích bằng AI lúc nộp đơn hoặc quyền phân tích đã bị bạn thu hồi trước đó.
            </p>
            <div style="font-size:0.82rem;color:#64748b;background:#f8fafc;padding:0.75rem 1rem;border-radius:var(--radius-sm);border:1px solid #e2e8f0;line-height:1.5;text-align:left;margin-bottom:1.5rem;">
                <i class="ri-alert-line" style="color:#f59e0b;margin-right:4px;"></i> <strong>Lưu ý:</strong> Điểm phù hợp chỉ phản ánh mức độ khớp giữa dữ liệu hồ sơ hiện có và yêu cầu công việc. Đây không phải xác suất được tuyển và không thay thế quyết định của nhà tuyển dụng.
            </div>
            <button type="button" class="btn btn-outline btn-sm btn-close-match-modal">Đóng</button>
        </div>
    `;
}

function renderMatchModalRevoked() {
    const body = document.getElementById("modal-match-body");
    if (!body) return;
    body.innerHTML = `
        <div style="text-align:center;padding:2rem 1rem;">
            <div style="font-size:2.5rem;margin-bottom:0.75rem;color:var(--primary);"><i class="ri-shield-check-line"></i></div>
            <h4 style="color:var(--dark);margin-bottom:0.5rem;font-size:1.1rem;">Quyền Phân Tích AI Đã Thu Hồi</h4>
            <p style="font-size:0.88rem;color:var(--text-muted);max-width:440px;margin:0 auto 1.5rem;line-height:1.5;">
                Toàn bộ dữ liệu snapshot và kết quả phân tích AI của đơn này đã được xóa an toàn khỏi hệ thống.
            </p>
            <div style="font-size:0.82rem;color:#64748b;background:#f8fafc;padding:0.75rem 1rem;border-radius:var(--radius-sm);border:1px solid #e2e8f0;line-height:1.5;text-align:left;margin-bottom:1.5rem;">
                <i class="ri-alert-line" style="color:#f59e0b;margin-right:4px;"></i> <strong>Lưu ý:</strong> Điểm phù hợp chỉ phản ánh mức độ khớp giữa dữ liệu hồ sơ hiện có và yêu cầu công việc. Đây không phải xác suất được tuyển và không thay thế quyết định của nhà tuyển dụng.
            </div>
            <button type="button" class="btn btn-outline btn-sm btn-close-match-modal">Đóng</button>
        </div>
    `;
}

function renderMatchModalNotStarted() {
    const body = document.getElementById("modal-match-body");
    if (!body) return;
    body.innerHTML = `
        <div style="text-align:center;padding:2rem 1rem;">
            <div style="font-size:2.5rem;margin-bottom:0.75rem;color:var(--primary);"><i class="ri-robot-2-line"></i></div>
            <h4 style="color:var(--dark);margin-bottom:0.5rem;font-size:1.1rem;">Chưa Có Kết Quả Đánh Giá</h4>
            <p style="font-size:0.88rem;color:var(--text-muted);max-width:440px;margin:0 auto 1.5rem;line-height:1.5;">
                Đơn ứng tuyển của bạn đã sẵn sàng phân tích. Hãy nhấn nút bên dưới để bắt đầu đánh giá độ phù hợp với công việc.
            </p>
            <button type="button" class="btn btn-primary btn-sm btn-reanalyze-match">
                Bắt Đầu Phân Tích Bằng AI
            </button>
            <div style="font-size:0.82rem;color:#64748b;background:#f8fafc;padding:0.75rem 1rem;border-radius:var(--radius-sm);border:1px solid #e2e8f0;line-height:1.5;text-align:left;margin-top:1.5rem;">
                <i class="ri-alert-line" style="color:#f59e0b;margin-right:4px;"></i> <strong>Lưu ý:</strong> Điểm phù hợp chỉ phản ánh mức độ khớp giữa dữ liệu hồ sơ hiện có và yêu cầu công việc. Đây không phải xác suất được tuyển và không thay thế quyết định của nhà tuyển dụng.
            </div>
        </div>
    `;
}

function getFailureExplanation(code) {
    const map = {
        "AI_DISABLED": "Tính năng phân tích AI hiện đang tạm bảo trì trên hệ thống.",
        "AI_TIMEOUT": "Quá trình phân tích AI bị quá thời gian cho phép. Vui lòng thử lại.",
        "AI_RATE_LIMITED": "Dịch vụ AI đang nhận nhiều yêu cầu. Vui lòng thử lại sau giây lát.",
        "AI_UNAVAILABLE": "Dịch vụ AI tạm thời không phản hồi. Vui lòng thử lại sau.",
        "AI_INVALID_RESPONSE": "Dữ liệu phản hồi từ AI không đúng cấu trúc yêu cầu.",
        "INSUFFICIENT_DATA": "Hồ sơ của bạn chưa đủ dữ liệu để hoàn thành đánh giá độ phù hợp.",
        "CONSENT_REQUIRED": "Đơn ứng tuyển chưa được cấp quyền phân tích AI.",
        "CONSENT_REVOKED": "Quyền phân tích AI cho đơn này đã bị thu hồi.",
        "COOLDOWN_ACTIVE": "Yêu cầu phân tích vừa được thực hiện gần đây. Vui lòng đợi ít phút.",
        "ANALYSIS_STALLED": "Quá trình phân tích đang bị gián đoạn hoặc mất nhiều thời gian hơn dự kiến."
    };
    return map[code] || `Quá trình phân tích gặp sự cố (Mã: ${code}).`;
}

function renderMatchModalRateLimited(retryAfterSeconds, applicationId) {
    clearRateLimitCountdown();
    clearMatchPolling();

    const body = document.getElementById("modal-match-body");
    if (!body) return;

    let remaining = Math.max(1, parseInt(retryAfterSeconds, 10) || 60);

    body.innerHTML = `
        <div style="text-align:center;padding:2.5rem 1rem;">
            <div style="font-size:2.2rem;margin-bottom:0.5rem;color:#b45309;"><i class="ri-time-line"></i></div>
            <h4 style="color:var(--dark);margin-bottom:0.5rem;font-size:1.1rem;">Thao Tác Quá Nhanh</h4>
            <p style="font-size:0.88rem;color:var(--text-muted);max-width:440px;margin:0 auto 1.25rem;line-height:1.5;">
                Bạn đã gửi yêu cầu phân tích quá nhiều lần trong thời gian ngắn.
                <br>Vui lòng đợi <strong id="rate-limit-countdown-num" style="color:#b45309;font-size:1rem;">${remaining}</strong> giây trước khi gửi lại yêu cầu.
            </p>
            <div style="display:flex;justify-content:center;gap:0.75rem;">
                <button type="button" id="btn-rate-limit-retry" class="btn btn-outline btn-sm btn-reanalyze-match" disabled style="opacity:0.6;cursor:not-allowed;">
                    Thử Lại (<span id="rate-limit-btn-timer">${remaining}s</span>)
                </button>
                <button type="button" class="btn btn-outline btn-sm btn-close-match-modal">Đóng</button>
            </div>
        </div>
    `;

    rateLimitCountdownInterval = setInterval(() => {
        remaining--;
        const numEl = document.getElementById("rate-limit-countdown-num");
        const btnTimer = document.getElementById("rate-limit-btn-timer");
        const retryBtn = document.getElementById("btn-rate-limit-retry");

        if (numEl) numEl.textContent = String(remaining);
        if (btnTimer) btnTimer.textContent = `${remaining}s`;

        if (remaining <= 0) {
            clearRateLimitCountdown();
            if (retryBtn) {
                retryBtn.disabled = false;
                retryBtn.style.opacity = "1";
                retryBtn.style.cursor = "pointer";
                retryBtn.classList.remove("btn-outline");
                retryBtn.classList.add("btn-primary");
                retryBtn.textContent = "Thử Lại Ngay";
            }
            if (numEl && numEl.parentElement) {
                numEl.parentElement.innerHTML = "Bạn đã có thể thử lại phân tích.";
            }
        }
    }, 1000);
}

function renderMatchModalProcessing(isTimeout = false) {
    const body = document.getElementById("modal-match-body");
    if (!body) return;
    if (isTimeout) {
        body.innerHTML = `
            <div aria-live="polite" style="text-align:center;padding:2.5rem 1rem;">
                <div style="font-size:2.2rem;margin-bottom:0.75rem;color:var(--primary);"><i class="ri-loader-4-line ri-spin"></i></div>
                <h4 style="color:var(--dark);margin-bottom:0.5rem;font-size:1.1rem;">Đang Xử Lý Trong Nền</h4>
                <p style="font-size:0.88rem;color:var(--text-muted);max-width:440px;margin:0 auto 1.5rem;line-height:1.5;">
                    Quá trình phân tích đang mất nhiều thời gian hơn dự kiến hoặc đang xếp hàng xử lý. Bạn có thể nhấn kiểm tra lại hoặc quay lại sau ít phút.
                </p>
                <div style="display:flex;justify-content:center;gap:0.75rem;">
                    <button type="button" class="btn btn-outline btn-sm btn-retry-match">Kiểm Tra Lại</button>
                    <button type="button" class="btn btn-primary btn-sm btn-reanalyze-match">Phân Tích Lại</button>
                </div>
            </div>
        `;
        return;
    }
    body.innerHTML = `
        <div aria-live="polite" style="text-align:center;padding:3rem 1rem;">
            <div class="spinner" style="width:32px;height:32px;border:3px solid var(--primary-border);border-top-color:var(--primary);border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 1rem;"></div>
            <div style="font-size:1rem;color:var(--dark);font-weight:600;">Đang phân tích độ phù hợp với công việc...</div>
            <small style="color:var(--text-muted);display:block;margin-top:0.35rem;">Hệ thống đang đối chiếu dữ liệu hồ sơ và yêu cầu tuyển dụng.</small>
        </div>
    `;
}

async function loadMatchAnalysisData(applicationId, isPolling = false) {
    const body = document.getElementById("modal-match-body");
    if (!body) return;

    if (!isPolling) {
        clearMatchPolling();
        clearRateLimitCountdown();
        renderMatchModalProcessing(false);
    }

    try {
        const res = await apiRequest(`/applications/${encodeURIComponent(applicationId)}/match-analysis`, {
            requireAuth: true
        });

        if (currentModalAppId !== applicationId) return;

        if (res && res.success && res.data) {
            const status = res.data.status;
            if (status === "processing") {
                if (matchAnalysisPollCount < MAX_MATCH_POLLS) {
                    matchAnalysisPollCount++;
                    const delays = [1500, 2000, 2500, 3000, 3500, 4000];
                    const delay = delays[matchAnalysisPollCount - 1] || 3000;
                    renderMatchModalProcessing(false);
                    matchAnalysisPollTimer = setTimeout(() => {
                        loadMatchAnalysisData(applicationId, true);
                    }, delay);
                } else {
                    renderMatchModalProcessing(true);
                }
                return;
            }

            clearMatchPolling();

            if (status === "revoked") {
                renderMatchModalRevoked();
            } else if (status === "declined_consent") {
                renderMatchModalConsentDeclined();
            } else if (status === "not_started") {
                renderMatchModalNotStarted();
            } else {
                renderMatchModalContent(res.data, applicationId);
            }
        } else if (res && (res.status === 404 || (res.data && res.data.status === "not_started"))) {
            clearMatchPolling();
            renderMatchModalNotStarted();
        } else if (res && res.status === 429) {
            clearMatchPolling();
            let retryAfter = 60;
            if (res.data && res.data.retry_after) retryAfter = parseInt(res.data.retry_after, 10) || 60;
            else if (res.retry_after) retryAfter = parseInt(res.retry_after, 10) || 60;
            renderMatchModalRateLimited(retryAfter, applicationId);
        } else {
            clearMatchPolling();
            body.innerHTML = `
                <div style="text-align:center;padding:2rem 1rem;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;color:var(--danger);"><i class="ri-error-warning-line"></i></div>
                    <h4 style="color:var(--dark);margin-bottom:0.5rem;">Không thể tải kết quả phân tích</h4>
                    <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:1rem;">${escapeHtml(res?.message || 'Đã có lỗi xảy ra.')}</p>
                    <button type="button" class="btn btn-outline btn-sm btn-retry-match">Thử lại</button>
                </div>
            `;
        }
    } catch (err) {
        if (currentModalAppId !== applicationId) return;
        clearMatchPolling();
        body.innerHTML = `
            <div style="text-align:center;padding:2rem 1rem;">
                <div style="font-size:2rem;margin-bottom:0.5rem;color:var(--danger);"><i class="ri-error-warning-line"></i></div>
                <h4 style="color:var(--dark);margin-bottom:0.5rem;">Lỗi kết nối máy chủ</h4>
                <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:1rem;">Không thể lấy dữ liệu phân tích lúc này.</p>
                <button type="button" class="btn btn-outline btn-sm btn-retry-match">Thử lại</button>
            </div>
        `;
    }
}

function renderMatchModalContent(data, applicationId) {
    const body = document.getElementById("modal-match-body");
    if (!body) return;

    const coverage = (data.coverage_percent !== null && data.coverage_percent !== undefined) ? Number(data.coverage_percent) : 0;
    const rawScore = (data.overall_score !== null && data.overall_score !== undefined) ? Number(data.overall_score) : null;
    const classification = data.classification || "Chưa đủ dữ liệu";
    const status = data.status || "completed";
    const isStale = Boolean(data.is_stale || status === "stale");
    const isInsufficient = rawScore === null || coverage < 60 || classification === "INSUFFICIENT_DATA" || ["processing", "failed", "revoked", "declined_consent"].includes(status);
    const failureCode = data.failure_code;
    const completedAt = data.completed_at ? formatDate(data.completed_at) : "Chưa hoàn tất";

    let statusLabel = "Hoàn thành";
    let statusBg = "#dcfce7";
    let statusFg = "#15803d";
    if (status === "partial") {
        statusLabel = "Phân tích một phần";
        statusBg = "#fef3c7";
        statusFg = "#b45309";
    } else if (status === "failed") {
        statusLabel = "Thất bại";
        statusBg = "#fee2e2";
        statusFg = "#b91c1c";
    } else if (isStale) {
        statusLabel = "Dữ liệu cũ";
        statusBg = "#ffedd5";
        statusFg = "#c2410c";
    }

    const summary = data.summary || {};
    const strengths = Array.isArray(summary.strengths) ? summary.strengths : [];
    const considerations = Array.isArray(summary.considerations) ? summary.considerations : [];

    // 5 published criteria used consistently across student and employer views.
    const MVP_CRITERIA = [
        { key: "age", label: "Độ Tuổi", icon: '<i class="ri-user-heart-line"></i>', weight: 10 },
        { key: "experience", label: "Kinh Nghiệm", icon: '<i class="ri-briefcase-line"></i>', weight: 15 },
        { key: "skills", label: "Kỹ Năng", icon: '<i class="ri-tools-line"></i>', weight: 30 },
        { key: "education", label: "Học Vấn", icon: '<i class="ri-graduation-cap-line"></i>', weight: 15 },
        { key: "availability", label: "Lịch Làm Việc", icon: '<i class="ri-calendar-schedule-line"></i>', weight: 30 }
    ];

    const criteriaMap = {};
    if (Array.isArray(data.criteria)) {
        data.criteria.forEach(c => {
            if (c && c.criterion_name) {
                criteriaMap[c.criterion_name] = c;
            }
        });
    } else if (data.criteria && typeof data.criteria === "object") {
        Object.keys(data.criteria).forEach(k => {
            const item = data.criteria[k];
            const name = (item && item.criterion_name) ? item.criterion_name : k;
            criteriaMap[name] = item;
        });
    }

    body.innerHTML = `
        <!-- Stale Data Warning Banner -->
        ${isStale ? `
            <div style="background:#fff7ed;border:1px solid #ffedd5;border-left:4px solid #ea580c;padding:0.75rem 1rem;border-radius:var(--radius-sm);margin-bottom:1rem;font-size:0.85rem;color:#9a3412;display:flex;align-items:center;justify-content:space-between;flex-wrap:gap;gap:0.5rem;">
                <div><i class="ri-alert-line" style="color:#ea580c;margin-right:4px;"></i> <strong>Hồ sơ hoặc JD đã thay đổi:</strong> Dữ liệu hồ sơ của bạn hoặc nội dung tin tuyển dụng đã được chỉnh sửa kể từ lần phân tích trước. Vui lòng phân tích lại để có kết quả mới nhất.</div>
                <button type="button" class="btn btn-sm btn-reanalyze-match" style="background:#ea580c;color:#fff;border:none;font-weight:600;"><i class="ri-refresh-line"></i> Phân tích lại ngay</button>
            </div>
        ` : ''}

        <!-- Top Score & Status Card -->
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;margin-bottom:1.25rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
                <div>
                    <span style="font-size:0.82rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;letter-spacing:0.5px;">Điểm Phù Hợp Tổng Thể</span>
                    ${isInsufficient ? `
                        <div style="font-size:1.1rem;font-weight:700;color:#b45309;margin-top:0.4rem;display:flex;align-items:center;gap:0.35rem;">
                            <i class="ri-alert-line"></i> <span>Chưa đủ dữ liệu để tính điểm tổng thể</span>
                        </div>
                    ` : `
                        <div style="display:flex;align-items:baseline;gap:0.5rem;margin-top:0.25rem;">
                            <span style="font-size:2.2rem;font-weight:800;color:var(--primary);line-height:1;">
                                ${escapeHtml(String(Math.round(rawScore)))}
                            </span>
                            <span style="font-size:1rem;color:var(--text-muted);font-weight:600;">/ 100</span>
                        </div>
                    `}
                </div>
                <div style="text-align:right;">
                    <div style="margin-bottom:0.35rem;">
                        <span class="badge" style="background:${isInsufficient ? '#fef3c7' : '#e0e7ff'};color:${isInsufficient ? '#92400e' : '#3730a3'};font-size:0.85rem;font-weight:700;padding:0.35rem 0.75rem;">
                            ${escapeHtml(isInsufficient ? 'Chưa đủ dữ liệu' : classification)}
                        </span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.5rem;justify-content:flex-end;">
                        <span class="badge" style="background:${statusBg};color:${statusFg};font-size:0.75rem;">
                            ${escapeHtml(statusLabel)}
                        </span>
                        <span style="font-size:0.78rem;color:var(--text-muted);">
                            Độ phủ: <strong>${escapeHtml(String(coverage))}%</strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Score Progress Bar or Insufficient Notice -->
            ${isInsufficient ? `
                <div style="background:#fffbeb;border:1px solid #fef3c7;border-radius:var(--radius-sm);padding:0.75rem 1rem;font-size:0.84rem;color:#92400e;line-height:1.5;margin-bottom:0.75rem;">
                    <i class="ri-information-line" style="margin-right:4px;"></i> <strong>Cần bổ sung hồ sơ:</strong> Độ bao phủ dữ liệu hiện tại chỉ đạt <strong>${escapeHtml(String(coverage))}%</strong> (&lt; 60%). Để hệ thống đưa ra điểm số tổng thể chính xác, vui lòng bổ sung thêm kỹ năng, kinh nghiệm, học vấn và lịch làm việc trong hồ sơ cá nhân của bạn.
                </div>
            ` : `
                <div style="background:#e2e8f0;height:8px;border-radius:4px;overflow:hidden;margin-bottom:0.75rem;">
                    <div style="background:var(--primary);height:100%;width:${Math.min(100, Math.max(0, Math.round(rawScore)))}%;transition:width 0.4s ease;"></div>
                </div>
            `}

            ${summary.overview ? `
                <div style="font-size:0.88rem;color:var(--text);line-height:1.5;margin-top:0.5rem;">
                    ${escapeHtml(summary.overview)}
                </div>
            ` : ''}

            ${status === 'partial' ? `
                <div style="margin-top:0.5rem;font-size:0.8rem;color:#b45309;background:#fef3c7;padding:0.4rem 0.65rem;border-radius:var(--radius-sm);">
                    <i class="ri-alert-line" style="margin-right:4px;"></i> Đánh giá hoàn tất một phần dựa trên thuật toán và dữ liệu hồ sơ có sẵn.
                </div>
            ` : ''}

            ${(failureCode || status === 'failed') ? `
                <div style="margin-top:0.75rem;font-size:0.84rem;color:#b91c1c;background:#fee2e2;padding:0.6rem 0.85rem;border-radius:var(--radius-sm);border:1px solid #fecdd3;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
                    <div><i class="ri-alert-line" style="margin-right:4px;"></i> <strong>Thông báo:</strong> ${escapeHtml(getFailureExplanation(failureCode || 'GENERAL_ERROR'))}</div>
                    <button type="button" class="btn btn-sm btn-reanalyze-match" style="background:#dc2626;color:#fff;border:none;font-size:0.8rem;padding:0.25rem 0.6rem;cursor:pointer;">Thử lại</button>
                </div>
            ` : ''}
        </div>

        <!-- 5 Criteria Breakdown -->
        <h4 style="font-size:0.95rem;font-weight:700;color:var(--dark);margin-bottom:0.75rem;">Chi Tiết 5 Tiêu Chí Đánh Giá</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:0.75rem;margin-bottom:1.25rem;">
            ${MVP_CRITERIA.map(c => {
                const crit = criteriaMap[c.key] || criteriaMap[c.key.replace(/s$/, "")] || {};
                const hasScore = crit.score !== null && crit.score !== undefined;
                const state = crit.state || (hasScore ? 'AVAILABLE' : 'UNKNOWN');
                const isUnknown = state === 'UNKNOWN' || state === 'NOT_AVAILABLE';
                const isNotApplicable = state === 'NOT_APPLICABLE';
                const critScoreText = (hasScore && !isUnknown && !isNotApplicable) ? `${Math.round(crit.score)}/100` : '--';
                const weightVal = crit.weight !== undefined ? crit.weight : (crit.weight_percent !== undefined ? crit.weight_percent : c.weight);
                const noteText = crit.evidence || crit.note || crit.details?.notes || crit.details?.reason || '';
                const gapSignals = Array.isArray(crit.details?.gap_signals) ? crit.details.gap_signals : [];
                const missingSkills = Array.isArray(crit.details?.missing_skills) ? crit.details.missing_skills
                    : (Array.isArray(crit.details?.unmatched) ? crit.details.unmatched
                    : (Array.isArray(crit.missing_skills) ? crit.missing_skills : []));
                const missingList = [...gapSignals, ...missingSkills].filter((v, i, a) => a.indexOf(v) === i);
                const evidenceCount = crit.details?.evidence_count ?? (Array.isArray(crit.details?.matched) ? crit.details.matched.length : null);

                return `
                    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.85rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.35rem;">
                            <span style="font-size:0.85rem;font-weight:700;color:var(--dark);display:flex;align-items:center;gap:0.35rem;">
                                <span>${c.icon}</span> <span>${escapeHtml(c.label)}</span>
                                <small style="color:var(--text-muted);font-weight:500;">(${escapeHtml(String(weightVal))}%)</small>
                            </span>
                            <div style="display:flex;align-items:center;gap:0.35rem;">
                                ${isNotApplicable ? `
                                    <span class="badge" style="background:#f1f5f9;color:#64748b;font-size:0.7rem;">Không áp dụng</span>
                                ` : (isUnknown ? `
                                    <span class="badge" style="background:#f1f5f9;color:#64748b;font-size:0.7rem;">Chưa đủ dữ liệu</span>
                                ` : '')}
                                <strong style="font-size:0.9rem;color:${(hasScore && !isUnknown && !isNotApplicable) ? 'var(--primary)' : 'var(--text-muted)'};">${critScoreText}</strong>
                            </div>
                        </div>
                        ${noteText ? `<div style="font-size:0.8rem;color:var(--text-muted);line-height:1.4;">${escapeHtml(noteText)}</div>` : ''}
                        ${evidenceCount !== null && evidenceCount > 0 ? `
                            <div style="font-size:0.75rem;color:#15803d;margin-top:0.25rem;">
                                <i class="ri-check-line" style="margin-right:2px;"></i> Khớp ${escapeHtml(String(evidenceCount))} mục dữ liệu
                            </div>
                        ` : ''}
                        ${missingList.length > 0 ? `
                            <div style="margin-top:0.35rem;display:flex;flex-wrap:wrap;gap:0.25rem;align-items:center;">
                                <span style="font-size:0.72rem;color:var(--danger);font-weight:600;">Cần bổ sung:</span>
                                ${missingList.map(m => `<span class="badge" style="background:#fee2e2;color:#b91c1c;font-size:0.72rem;padding:0.1rem 0.4rem;">${escapeHtml(m)}</span>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('')}
        </div>

        <!-- Strengths & Considerations -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1rem;margin-bottom:1.25rem;">
            ${strengths.length > 0 ? `
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:0.85rem 1rem;">
                    <div style="font-size:0.88rem;font-weight:700;color:#166534;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.35rem;">
                        <i class="ri-checkbox-circle-fill" style="color:#16a34a;"></i> <span>Điểm Mạnh Nổi Bật</span>
                    </div>
                    <ul style="margin:0;padding-left:1.25rem;font-size:0.82rem;color:#14532d;line-height:1.5;">
                        ${strengths.map(s => `<li>${escapeHtml(s)}</li>`).join('')}
                    </ul>
                </div>
            ` : ''}

            ${considerations.length > 0 ? `
                <div style="background:#fffbeb;border:1px solid #fef3c7;border-radius:var(--radius-sm);padding:0.85rem 1rem;">
                    <div style="font-size:0.88rem;font-weight:700;color:#92400e;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.35rem;">
                        <i class="ri-lightbulb-line" style="color:#d97706;"></i> <span>Điểm Cần Bổ Sung / Lưu Ý</span>
                    </div>
                    <ul style="margin:0;padding-left:1.25rem;font-size:0.82rem;color:#78350f;line-height:1.5;">
                        ${considerations.map(c => `<li>${escapeHtml(c)}</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
        </div>

        <!-- Missing Data Notice & Suggestion -->
        ${coverage < 100 ? `
            <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:var(--radius-sm);padding:0.75rem 1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;">
                <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.4;">
                    <i class="ri-file-edit-line" style="margin-right:4px;"></i> Hồ sơ của bạn còn thiếu một số mục (kinh nghiệm, bằng cấp/chứng chỉ, lịch làm việc). Cập nhật hồ sơ để tăng độ chính xác đánh giá phù hợp.
                </div>
                <a href="/student/profile" target="_blank" class="btn btn-outline btn-sm" style="font-size:0.78rem;padding:0.25rem 0.55rem;background:#fff;">
                    Cập nhật hồ sơ &rarr;
                </a>
            </div>
        ` : ''}

        <!-- Meta & Actions -->
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border);font-size:0.8rem;color:var(--text-muted);">
            <div>
                <span>Thời gian phân tích: <strong>${escapeHtml(completedAt)}</strong></span>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <button type="button" class="btn btn-outline btn-sm btn-reanalyze-match" style="font-size:0.8rem;">
                    <i class="ri-refresh-line"></i> Phân tích lại
                </button>
                <button type="button" class="btn btn-sm btn-revoke-consent" style="font-size:0.78rem;color:var(--danger);background:#fff;border:1px solid #fecdd3;">
                    Thu hồi quyền phân tích AI
                </button>
            </div>
        </div>

        <!-- BẮT BUỘC - Mandatory Disclaimer -->
        <div style="font-size:0.8rem;color:#64748b;background:#f8fafc;padding:0.75rem 1rem;border-radius:var(--radius-sm);border:1px solid #e2e8f0;margin-top:1rem;line-height:1.5;">
            <i class="ri-alert-line" style="color:#f59e0b;margin-right:4px;"></i> <strong>Lưu ý:</strong> Điểm phù hợp chỉ phản ánh mức độ khớp giữa dữ liệu hồ sơ hiện có và yêu cầu công việc. Đây không phải xác suất được tuyển và không thay thế quyết định của nhà tuyển dụng.
        </div>
    `;
}

async function requestReanalysis(applicationId) {
    if (!applicationId) return;
    clearMatchPolling();
    clearRateLimitCountdown();
    renderMatchModalProcessing(false);

    try {
        const res = await apiRequest(`/applications/${encodeURIComponent(applicationId)}/match-analysis`, {
            method: "POST",
            requireAuth: true
        });

        if (currentModalAppId !== applicationId) return;

        if (res && res.success && res.data) {
            showToast("Phân tích thành công!", "success");
            renderMatchModalContent(res.data, applicationId);
        } else if (res && res.status === 429) {
            let retryAfter = 60;
            if (res.data && res.data.retry_after) retryAfter = parseInt(res.data.retry_after, 10) || 60;
            else if (res.retry_after) retryAfter = parseInt(res.retry_after, 10) || 60;
            showToast(`Bạn thao tác quá nhanh, vui lòng đợi ${retryAfter} giây trước khi thử lại.`, "error");
            renderMatchModalRateLimited(retryAfter, applicationId);
        } else {
            showToast((res && res.message) ? res.message : "Không thể phân tích lại.", "error");
            loadMatchAnalysisData(applicationId);
        }
    } catch (err) {
        if (currentModalAppId !== applicationId) return;
        showToast("Lỗi kết nối khi phân tích lại.", "error");
        loadMatchAnalysisData(applicationId);
    }
}

async function revokeMatchConsent(applicationId) {
    if (!applicationId) return;
    if (!confirm("Bạn có chắc chắn muốn thu hồi quyền phân tích AI cho đơn ứng tuyển này?\nToàn bộ dữ liệu kết quả phân tích sẽ bị xóa và hành động này không thể hoàn tác.")) {
        return;
    }

    try {
        const res = await apiRequest(`/applications/${encodeURIComponent(applicationId)}/match-consent`, {
            method: "PATCH",
            body: { consent: false },
            requireAuth: true
        });

        if (currentModalAppId !== applicationId) return;

        if (res && res.success) {
            showToast("Đã thu hồi quyền phân tích AI thành công.", "success");
            if (window.loadedApplicationsMap && window.loadedApplicationsMap.has(String(applicationId))) {
                const appObj = window.loadedApplicationsMap.get(String(applicationId));
                appObj.ai_match_consent = 0;
            }
            renderMatchModalRevoked();
            loadApplications(currentAppPage);
        } else {
            showToast((res && res.message) ? res.message : "Thu hồi quyền phân tích thất bại.", "error");
        }
    } catch (err) {
        if (currentModalAppId !== applicationId) return;
        showToast("Lỗi kết nối khi thu hồi quyền phân tích.", "error");
    }
}
</script>
