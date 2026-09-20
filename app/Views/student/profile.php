<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Loading State -->
    <div id="profile-loading" style="text-align:center;padding:3rem 0;">
        <div class="job-card skeleton" style="height:150px;margin-bottom:1rem;"></div>
        <div class="job-card skeleton" style="height:400px;"></div>
    </div>

    <!-- Main Profile Content -->
    <div id="profile-content" style="display:none;">
        <!-- Completion Progress Card -->
        <div class="surface-card" style="padding:1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem;">
            <div style="flex:1;min-width:260px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                    <span style="font-weight:700;color:var(--dark);font-size:1rem;">Mức Độ Hoàn Thiện Hồ Sơ</span>
                    <span id="profile-percent-badge" style="font-weight:800;color:var(--primary);font-size:1.1rem;">0%</span>
                </div>
                <div style="background:#e2e8f0;height:10px;border-radius:5px;overflow:hidden;">
                    <div id="profile-progress-bar" style="background:var(--primary);height:100%;width:0%;transition:width 0.4s ease;"></div>
                </div>
                <p style="font-size:0.8rem;color:var(--text-muted);margin:0.5rem 0 0;">
                    Hồ sơ trên 80% sẽ tăng 3 lần cơ hội được nhà tuyển dụng xem và phản hồi nhanh.
                </p>
                <div id="profile-completeness-suggestion" style="margin-top:0.6rem;font-size:0.82rem;color:var(--primary);display:flex;align-items:center;gap:0.4rem;">
                    <i class="ri-lightbulb-line"></i> <strong>Gợi ý:</strong> Thêm kinh nghiệm làm việc để tăng độ bao phủ dữ liệu đánh giá.
                </div>
            </div>
            <div>
                <a href="#schedule-section" class="btn btn-outline btn-sm">Cập nhật lịch rảnh</a>
            </div>
        </div>

        <!-- Profile Edit Form -->
        <form id="profile-form" class="form-card">
            <div id="profile-alert" style="display:none;margin-bottom:1.5rem;" class="toast toast-error"></div>

            <div class="form-section">
                <h3 class="form-section-title">
                    1. Thông Tin Cá Nhân Cơ Bản
                </h3>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Họ và Tên <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="prof-fullname" class="form-control" placeholder="Nguyễn Văn A" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Tài Khoản</label>
                        <input type="email" id="prof-email" class="form-control" disabled style="background:#f1f5f9;cursor:not-allowed;">
                        <small class="form-help">Email dùng để nhận thông báo và không thể thay đổi tại đây.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số Điện Thoại <span style="color:var(--danger)">*</span></label>
                        <input type="tel" id="prof-phone" class="form-control" placeholder="0988776655" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ngày Sinh</label>
                        <input type="date" id="prof-dob" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Giới Tính</label>
                        <select id="prof-gender" class="form-control">
                            <option value="">-- Chọn giới tính --</option>
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Khu Vực Sinh Sống / Trọ</label>
                        <select id="prof-location" class="form-control">
                            <option value="">-- Chọn Quận / Huyện --</option>
                            <!-- Dynamic locations -->
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">
                    2. Học Vấn & Giới Thiệu
                </h3>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.25rem;margin-bottom:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Trường Đại Học / Cao Đẳng</label>
                        <input type="text" id="prof-university" class="form-control" maxlength="255" placeholder="ĐH Quốc Gia, ĐH Bách Khoa, ĐH Ngoại Thương...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Chuyên Ngành</label>
                        <input type="text" id="prof-major" class="form-control" maxlength="255" placeholder="Công nghệ thông tin, Marketing, Ngôn ngữ Anh...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sinh Viên Năm</label>
                        <select id="prof-academic-year" class="form-control">
                            <option value="">-- Chọn năm học --</option>
                            <option value="1">Năm nhất (Năm 1)</option>
                            <option value="2">Năm 2</option>
                            <option value="3">Năm 3</option>
                            <option value="4">Năm 4</option>
                            <option value="5">Năm 5+</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bằng Cấp / Hệ Đào Tạo</label>
                        <input type="text" id="prof-education-degree" class="form-control" maxlength="100" placeholder="Cử nhân, Kỹ sư, Cao đẳng, Trung cấp...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Năm Tốt Nghiệp (Dự kiến)</label>
                        <input type="text" id="prof-grad-year" class="form-control" maxlength="20" placeholder="2026">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label">Chi Tiết Quá Trình Học Vấn (Tùy chọn)</label>
                    <textarea id="prof-education-desc" class="form-control" rows="2" maxlength="2000" placeholder="Điểm GPA, đề tài nghiên cứu, thành tích học tập nổi bật..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Giới Thiệu Bản Thân (Bio)</label>
                    <textarea id="prof-bio" class="form-control" rows="3" placeholder="Chia sẻ đôi nét về bạn, mục tiêu công việc part-time hoặc tính cách nổi bật..."></textarea>
                </div>
            </div>

            <!-- 3. Work Experience Section (CV-AI-P1-05) -->
            <div class="form-section">
                <h3 class="form-section-title">
                    3. Kinh Nghiệm Làm Việc & Dự Án
                </h3>
                <p style="font-size:0.85rem;color:var(--text-muted);margin:-0.5rem 0 1rem;">
                    Cung cấp các công việc bán thời gian, thực tập hoặc dự án đã từng làm để AI đánh giá độ phù hợp với yêu cầu tuyển dụng.
                </p>

                <div id="prof-experience-list" style="display:flex;flex-direction:column;gap:1rem;margin-bottom:1rem;">
                    <!-- Dynamic Experience Entries -->
                </div>

                <button type="button" onclick="addExperienceRow()" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:0.35rem;">
                    <span>➕</span> <span>Thêm kinh nghiệm / dự án</span>
                </button>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">
                    4. Kỹ Năng, Chứng Chỉ & Hồ Sơ CV
                </h3>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label">Kỹ Năng Nổi Bật (Chọn các kỹ năng bạn có)</label>
                    <div id="prof-skills-container" style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0.75rem;background:#f8fafc;border-radius:var(--radius);border:1px solid var(--border);">
                        <!-- Dynamic skills -->
                    </div>
                </div>

                <!-- Certificates (CV-AI-P1-05) -->
                <div class="form-group" style="margin-top:1.5rem;margin-bottom:1.25rem;">
                    <label class="form-label" style="font-weight:700;display:flex;align-items:center;justify-content:space-between;">
                        <span>Chứng Chỉ & Ngoại Ngữ (Certificates)</span>
                        <span style="font-weight:normal;font-size:0.8rem;color:var(--text-muted);">Ngoại ngữ, tin học, kỹ năng nghề nghiệp...</span>
                    </label>
                    <div id="prof-certificates-list" style="display:flex;flex-direction:column;gap:0.6rem;margin-bottom:0.75rem;">
                        <!-- Dynamic Certificate Rows -->
                    </div>
                    <button type="button" onclick="addCertificateRow()" class="btn btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;">
                        <span>➕</span> <span>Thêm chứng chỉ</span>
                    </button>
                    <small class="form-help" style="display:block;margin-top:0.35rem;">Ví dụ: IELTS 6.5, TOEIC 750, Tin học MOS, JLPT N3...</small>
                </div>

                <!-- Active CV Upload Section (CV-P0-01) -->
                <div class="form-group" style="margin-top:1.5rem;">
                    <label class="form-label" style="font-weight:700;display:flex;align-items:center;justify-content:space-between;">
                        <span>Hồ Sơ CV Cá Nhân (Tệp PDF)</span>
                        <span style="font-weight:normal;font-size:0.8rem;color:var(--text-muted);">Tối đa 1 CV hoạt động, dung lượng &le; 5 MB</span>
                    </label>

                    <!-- CV Alert Box -->
                    <div id="cv-feedback-alert" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:var(--radius);font-size:0.9rem;"></div>

                    <!-- Hidden File Input -->
                    <input type="file" id="cv-file-input" accept=".pdf,application/pdf" style="display:none;">

                    <!-- State 1: Loading State -->
                    <div id="cv-state-loading" style="display:none;padding:1.5rem;background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);text-align:center;">
                        <span style="color:var(--text-muted);font-size:0.9rem;">Đang tải trạng thái CV...</span>
                    </div>

                    <!-- State 2: Uploading State -->
                    <div id="cv-state-uploading" style="display:none;padding:2rem 1.5rem;background:#f8fafc;border:2px dashed var(--primary);border-radius:var(--radius);text-align:center;">
                        <div style="font-size:1.5rem;margin-bottom:0.5rem;display:inline-block;">⏳</div>
                        <div style="font-weight:600;color:var(--primary);margin-bottom:0.25rem;">Đang tải lên và xử lý tệp PDF an toàn...</div>
                        <small style="color:var(--text-muted);">Vui lòng chờ trong giây lát, hệ thống đang kiểm tra định dạng và lưu trữ bảo mật.</small>
                    </div>

                    <!-- State 3: Empty State (No CV uploaded) -->
                    <div id="cv-state-empty" style="display:none;padding:2rem 1.5rem;background:#f8fafc;border:2px dashed #cbd5e1;border-radius:var(--radius);text-align:center;cursor:pointer;transition:border-color 0.2s;"
                         onclick="document.getElementById('cv-file-input').click();">
                        <div style="width:52px;height:52px;margin:0 auto 0.75rem;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center;color:var(--primary);">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <div style="font-weight:700;color:var(--dark);font-size:1.05rem;margin-bottom:0.35rem;">Chưa có CV trong hồ sơ</div>
                        <p style="font-size:0.875rem;color:var(--text-muted);margin:0 auto 0.75rem;max-width:480px;line-height:1.5;">
                            Tải lên tệp CV (định dạng PDF) để hoàn thiện hồ sơ và sẵn sàng nộp đơn ứng tuyển việc làm.
                        </p>
                        <div style="display:inline-flex;align-items:center;gap:0.5rem;font-size:0.8rem;color:var(--text-muted);margin-bottom:1rem;">
                            <span>📄 Định dạng: <strong>PDF (.pdf)</strong></span>
                            <span>&bull;</span>
                            <span>Dung lượng tối đa: <strong>5 MB</strong></span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="event.stopPropagation(); document.getElementById('cv-file-input').click();">
                                📤 Chọn tệp PDF từ máy tính
                            </button>
                        </div>
                    </div>

                    <!-- State 4: Active CV State (File uploaded) -->
                    <div id="cv-state-active" style="display:none;padding:1.25rem;background:var(--surface);border:1px solid var(--secondary);border-left:5px solid var(--secondary);border-radius:var(--radius);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                            <div style="display:flex;align-items:center;gap:1rem;min-width:240px;">
                                <div style="width:48px;height:56px;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#ef4444;flex-shrink:0;">
                                    <span style="font-size:1.2rem;line-height:1;">📄</span>
                                    <span style="font-weight:800;font-size:0.7rem;margin-top:2px;">PDF</span>
                                </div>
                                <div>
                                    <div id="cv-active-name" style="font-weight:700;color:var(--dark);word-break:break-all;font-size:1rem;">ten_file_cv.pdf</div>
                                    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:0.25rem;font-size:0.82rem;color:var(--text-muted);">
                                        <span id="cv-active-size">0 KB</span>
                                        <span>&bull;</span>
                                        <span id="cv-active-date">Đã tải lên: 01/01/2026</span>
                                    </div>
                                    <div style="margin-top:0.4rem;">
                                        <span style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.6rem;background:#d1fae5;color:#065f46;border-radius:4px;font-size:0.78rem;font-weight:600;">
                                            ✓ CV đang hoạt động (Sẵn sàng ứng tuyển)
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                                <button type="button" id="btn-analyze-cv" class="btn btn-primary btn-sm" onclick="handleAnalyzeCv();" title="Gửi CV tới Google Gemini để trích xuất thông tin nghề nghiệp">
                                    ✨ Đọc CV Bằng Gemini
                                </button>
                                <button type="button" id="btn-replace-cv" class="btn btn-outline btn-sm" onclick="document.getElementById('cv-file-input').click();">
                                    Thay thế CV
                                </button>
                                <button type="button" id="btn-delete-cv" class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger);" onclick="handleDeleteCv();">
                                    Xóa CV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section" id="schedule-section">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <h3 class="form-section-title" style="margin-bottom:0.35rem;">
                            4. Lịch Rảnh Trong Tuần (Availability Schedule)
                        </h3>
                        <p class="form-section-desc" style="margin:0;">
                            Đánh dấu các ca bạn có thể đi làm part-time để hệ thống ưu tiên gợi ý việc làm khớp lịch học:
                        </p>
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="selectAllSchedule();" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;background:#f0fdf4;border-color:#86efac;color:#166534;font-weight:600;">
                            <i class="ri-flashlight-line"></i> Rảnh cả tuần
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="selectEveningsOnly();" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;background:var(--primary-light);border-color:var(--primary-border);color:var(--primary-text);font-weight:600;">
                            <i class="ri-moon-line"></i> Rảnh tất cả ca tối
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="clearAllSchedule();" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:#b91c1c;border-color:#fca5a5;background:#fef2f2;font-weight:600;">
                            <span>🧹</span> Xóa chọn tất cả
                        </button>
                    </div>
                </div>

                <div class="table-responsive" style="margin-bottom:1rem;">
                    <table class="data-table" style="text-align:center;">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Ca làm việc</th>
                                <th style="text-align:center;">Thứ 2</th>
                                <th style="text-align:center;">Thứ 3</th>
                                <th style="text-align:center;">Thứ 4</th>
                                <th style="text-align:center;">Thứ 5</th>
                                <th style="text-align:center;">Thứ 6</th>
                                <th style="text-align:center;">Thứ 7</th>
                                <th style="text-align:center;">Chủ Nhật</th>
                            </tr>
                        </thead>
                        <tbody id="schedule-matrix-body">
                            <!-- Rendered by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. Khu Vực Làm Việc Mong Muốn (Preferred Work Locations) -->
            <div class="form-section" id="preferred-locations-section">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:0.75rem;margin-bottom:0.75rem;">
                    <div>
                        <h3 class="form-section-title" style="margin-bottom:0.35rem;">
                            5. Khu Vực Làm Việc Mong Muốn
                        </h3>
                        <p class="form-section-desc" style="margin:0;">
                            Chọn tối đa 10 địa điểm/khu vực quanh nơi ở hoặc trường học để nhận gợi ý việc làm phù hợp và tính khoảng cách chính xác.
                        </p>
                    </div>
                    <span id="pref-locs-counter" class="job-locations-counter">0 / 10 khu vực</span>
                </div>

                <!-- Autocomplete Input row -->
                <div style="display:flex;gap:0.75rem;align-items:flex-start;flex-wrap:wrap;margin-bottom:1rem;">
                    <div style="flex:1;min-width:260px;">
                        <div id="pref-loc-ac-container"></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                        <label for="pref-radius-select" style="font-size:0.85rem;font-weight:600;white-space:nowrap;color:var(--dark);">Bán kính:</label>
                        <select id="pref-radius-select" class="form-control" style="width:auto;font-size:0.875rem;padding:0.6rem 0.85rem;">
                            <option value="2">2 km</option>
                            <option value="5">5 km</option>
                            <option value="10" selected>10 km</option>
                            <option value="20">20 km</option>
                            <option value="50">50 km</option>
                        </select>
                        <button type="button" id="btn-add-pref-loc" class="btn btn-outline" style="padding:0.6rem 1rem;font-weight:600;white-space:nowrap;" onclick="handleAddPreferredLocation()">
                            ➕ Thêm khu vực
                        </button>
                    </div>
                </div>

                <!-- Chips container -->
                <div id="pref-locs-chips" class="preferred-locs-list">
                    <!-- Rendered by JS -->
                </div>

                <div id="pref-locs-empty" style="padding:1rem;background:#f8fafc;border:1px dashed var(--border);border-radius:var(--radius-sm);text-align:center;font-size:0.85rem;color:var(--text-muted);">
                    Chưa có khu vực mong muốn nào. Hãy tìm kiếm và thêm địa điểm ở trên.
                </div>
            </div>

            <div class="form-actions">
                <a href="/student/dashboard" class="btn btn-outline">Hủy Bỏ</a>
                <button type="submit" id="btn-save-profile" class="btn btn-primary btn-lg">
                    Lưu Hồ Sơ Sinh Viên
                </button>
            </div>
        </form>
    </div>
</div>

<div id="cv-ai-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.68);z-index:10020;align-items:center;justify-content:center;padding:1rem;" role="dialog" aria-modal="true" aria-labelledby="cv-ai-modal-title">
    <div style="background:var(--surface,#fff);border-radius:16px;width:100%;max-width:720px;max-height:90vh;overflow:auto;box-shadow:0 24px 70px rgba(15,23,42,.28);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);">
            <div>
                <h3 id="cv-ai-modal-title" style="margin:0;color:var(--dark);font-size:1.18rem;">Gemini Đã Đọc CV</h3>
                <p style="margin:.35rem 0 0;color:var(--text-muted);font-size:.82rem;">Kiểm tra các phần muốn điền vào hồ sơ. Hệ thống chưa lưu cho đến khi bạn bấm “Lưu Hồ Sơ Sinh Viên”.</p>
            </div>
            <button type="button" onclick="closeCvAiModal()" class="modal-close-btn" aria-label="Đóng">&times;</button>
        </div>
        <div id="cv-ai-result" style="padding:1.25rem 1.5rem;"></div>
        <div style="padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.75rem;">
            <button type="button" onclick="closeCvAiModal()" class="btn btn-outline btn-sm">Hủy</button>
            <button type="button" onclick="applyCvAiResult()" class="btn btn-primary btn-sm">Điền Vào Hồ Sơ</button>
        </div>
    </div>
</div>

<script>
let cvAiExtraction = null;
let preferredLocations = [];
let prefLocAutocomplete = null;
const DAYS = [
    { key: "monday", label: "T2" },
    { key: "tuesday", label: "T3" },
    { key: "wednesday", label: "T4" },
    { key: "thursday", label: "T5" },
    { key: "friday", label: "T6" },
    { key: "saturday", label: "T7" },
    { key: "sunday", label: "CN" }
];

const SHIFTS = [
    { key: "morning", label: '<i class="ri-sun-cloudy-line"></i> Ca Sáng (08:00 - 12:00)' },
    { key: "afternoon", label: '<i class="ri-sun-line"></i> Ca Chiều (13:00 - 17:00)' },
    { key: "evening", label: '<i class="ri-moon-clear-line"></i> Ca Tối (18:00 - 22:00)' }
];

document.addEventListener("DOMContentLoaded", async () => {
    // 1. Auth Guard
    if (!TokenStorage.isLoggedIn()) {
        showToast("Vui lòng đăng nhập để xem hồ sơ.", "error");
        window.location.href = `/login?login_required=1&redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const user = TokenStorage.getUser();
    if (!user || (user.role !== "student" && user.role !== "developer")) {
        showToast("Chỉ tài khoản Sinh viên mới có quyền truy cập hồ sơ sinh viên.", "error");
        setTimeout(() => { window.location.href = "/viec-lam"; }, 1000);
        return;
    }

    renderScheduleTable();
    initCvHandlers();
    await Promise.all([loadLocations(), loadSkills()]);
    // Initialize Preferred Location Autocomplete
    prefLocAutocomplete = new AddressAutocomplete("#pref-loc-ac-container", {
        id: "pref-loc-input",
        placeholder: "Nhập địa điểm (ví dụ: Bến Nghé, Cầu Giấy, tên trường...)",
        required: false
    });

    await Promise.all([loadProfile(), loadPreferredLocations()]);

    // Form Submit
    document.getElementById("profile-form").addEventListener("submit", handleSaveProfile);
});

function renderScheduleTable() {
    const tbody = document.getElementById("schedule-matrix-body");
    const dayRow = `
        <tr style="background:var(--surface-hover);border-bottom:2px solid var(--border);">
            <td style="padding:0.65rem 0.75rem;text-align:left;font-weight:700;color:var(--primary);font-size:0.84rem;">
                <i class="ri-flashlight-line"></i> Rảnh cả ngày
            </td>
            ${DAYS.map(day => `
                <td style="padding:0.65rem 0.75rem;">
                    <label style="display:inline-flex;flex-direction:column;align-items:center;cursor:pointer;gap:0.2rem;font-size:0.75rem;font-weight:600;color:var(--text-muted);" title="Chọn rảnh cả ngày ${escapeHtml(day.label)}">
                        <input type="checkbox" class="schedule-day-check" data-day="${escapeHtml(day.key)}" onchange="toggleDaySchedule('${escapeHtml(day.key)}', this.checked)" style="width:17px;height:17px;cursor:pointer;accent-color:var(--primary);">
                        <span style="font-size:0.72rem;">Cả ngày</span>
                    </label>
                </td>
            `).join("")}
        </tr>
    `;

    const shiftRows = SHIFTS.map(shift => `
        <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:0.75rem;text-align:left;font-weight:600;color:var(--dark);">${shift.label}</td>
            ${DAYS.map(day => `
                <td style="padding:0.25rem;">
                    <label style="display:inline-flex;align-items:center;justify-content:center;min-width:44px;min-height:44px;cursor:pointer;margin:auto;" title="Chọn ${escapeHtml(day.label)}">
                        <input type="checkbox" class="schedule-check" data-day="${escapeHtml(day.key)}" data-shift="${escapeHtml(shift.key)}" onchange="syncAllDayCheckboxes()" style="width:20px;height:20px;cursor:pointer;accent-color:var(--primary);">
                    </label>
                </td>
            `).join("")}
        </tr>
    `).join("");

    tbody.innerHTML = dayRow + shiftRows;
}

function selectAllSchedule() {
    document.querySelectorAll(".schedule-check").forEach(cb => { cb.checked = true; });
    syncAllDayCheckboxes();
    showToast("Đã chọn rảnh tất cả các ca trong tuần!", "info");
}

function clearAllSchedule() {
    document.querySelectorAll(".schedule-check").forEach(cb => { cb.checked = false; });
    syncAllDayCheckboxes();
    showToast("Đã xóa tất cả các ca đã chọn!", "info");
}

function selectEveningsOnly() {
    document.querySelectorAll(".schedule-check").forEach(cb => {
        cb.checked = (cb.getAttribute("data-shift") === "evening");
    });
    syncAllDayCheckboxes();
    showToast("Đã chọn tất cả ca tối (18:00 - 22:00)!", "info");
}

function toggleDaySchedule(dayKey, isChecked) {
    document.querySelectorAll(`.schedule-check[data-day="${dayKey}"]`).forEach(cb => {
        cb.checked = isChecked;
    });
}

function syncAllDayCheckboxes() {
    DAYS.forEach(day => {
        const dayChecks = Array.from(document.querySelectorAll(`.schedule-check[data-day="${day.key}"]`));
        const allChecked = dayChecks.length > 0 && dayChecks.every(cb => cb.checked);
        const dayHeaderCb = document.querySelector(`.schedule-day-check[data-day="${day.key}"]`);
        if (dayHeaderCb) dayHeaderCb.checked = allChecked;
    });
}

async function loadLocations() {
    const res = await apiRequest("/locations");
    const sel = document.getElementById("prof-location");
    if (res && res.success && Array.isArray(res.data)) {
        res.data.forEach(loc => {
            const opt = document.createElement("option");
            opt.value = loc.id;
            opt.textContent = `${loc.name} (${loc.city || 'Hà Nội'})`;
            sel.appendChild(opt);
        });
    }
}

async function loadSkills() {
    const res = await apiRequest("/skills");
    const container = document.getElementById("prof-skills-container");
    if (res && res.success && Array.isArray(res.data)) {
        container.innerHTML = res.data.map(sk => `
            <label style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.35rem 0.65rem;background:#fff;border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;font-size:0.85rem;">
                <input type="checkbox" class="skill-checkbox" value="${escapeHtml(sk.id)}" style="cursor:pointer;">
                <span>${escapeHtml(sk.name)}</span>
            </label>
        `).join("");
    }
}

async function loadProfile() {
    const loadingEl = document.getElementById("profile-loading");
    const contentEl = document.getElementById("profile-content");

    const res = await apiRequest("/student/profile", { requireAuth: true });
    loadingEl.style.display = "none";

    if (res && res.success && res.data) {
        contentEl.style.display = "block";
        const prof = res.data;

        // Progress
        const percent = prof.profile_completion_percent || 0;
        document.getElementById("profile-percent-badge").innerText = `${percent}%`;
        document.getElementById("profile-progress-bar").style.width = `${percent}%`;

        // Completeness Suggestion (strictly non-sensitive)
        updateProfileCompletenessSuggestion(prof);

        // Fields
        document.getElementById("prof-fullname").value = prof.full_name || "";
        document.getElementById("prof-email").value = prof.email || "";
        document.getElementById("prof-phone").value = prof.phone || "";
        document.getElementById("prof-dob").value = prof.date_of_birth || "";
        document.getElementById("prof-gender").value = prof.gender || "";
        document.getElementById("prof-university").value = prof.university || "";
        document.getElementById("prof-major").value = prof.major || "";
        document.getElementById("prof-academic-year").value = prof.academic_year || "";
        document.getElementById("prof-bio").value = prof.bio || "";
        document.getElementById("prof-location").value = prof.location_id || "";

        // Load Education Details
        loadEducationFields(prof);

        // Load Work Experience Rows (CV-AI-P1-05)
        loadWorkExperienceFields(prof.work_experience);

        // Load Certificates Rows (CV-AI-P1-05)
        loadCertificateFields(prof.certificates);

        // CV
        renderCvState(prof.active_cv);

        // Skills
        const selectedSkills = Array.isArray(prof.skill_ids) ? prof.skill_ids : [];
        document.querySelectorAll(".skill-checkbox").forEach(cb => {
            cb.checked = selectedSkills.includes(cb.value);
        });

        // Schedule Matrix
        const sched = prof.available_schedule || prof.availability_schedule || {};
        if (typeof sched === "object" && sched !== null) {
            document.querySelectorAll(".schedule-check").forEach(cb => {
                const day = cb.getAttribute("data-day");
                const shift = cb.getAttribute("data-shift");
                if (sched[day] && Array.isArray(sched[day])) {
                    cb.checked = sched[day].includes(shift);
                }
            });
            syncAllDayCheckboxes();
        }
    } else {
        showToast((res && res.message) ? res.message : "Không thể tải hồ sơ sinh viên.", "error");
    }
}


/* ==========================================================================
   STUDENT PREFERRED LOCATIONS (MAX 10)
   ========================================================================== */
async function loadPreferredLocations() {
    try {
        const res = await apiRequest("/student/preferred-locations", { requireAuth: true });
        if (res && res.success && Array.isArray(res.data)) {
            preferredLocations = res.data;
        } else {
            preferredLocations = [];
        }
        renderPreferredLocations();
    } catch (err) {
        console.error("Load preferred locations error:", err);
    }
}

function renderPreferredLocations() {
    const chipsContainer = document.getElementById("pref-locs-chips");
    const emptyEl = document.getElementById("pref-locs-empty");
    const counterEl = document.getElementById("pref-locs-counter");
    const addBtn = document.getElementById("btn-add-pref-loc");

    counterEl.innerText = `${preferredLocations.length} / 10 khu vực`;
    if (addBtn) {
        addBtn.disabled = preferredLocations.length >= 10;
        addBtn.title = preferredLocations.length >= 10 ? "Đã đạt tối đa 10 khu vực." : "";
    }

    if (preferredLocations.length === 0) {
        chipsContainer.innerHTML = "";
        emptyEl.style.display = "block";
        return;
    }

    emptyEl.style.display = "none";
    chipsContainer.innerHTML = preferredLocations.map((loc, idx) => {
        const radius = loc.preferred_radius_km || 10;
        const nameParts = [loc.commune, loc.province].filter(Boolean);
        let displayName = nameParts.length > 0 ? nameParts.join(", ") : (loc.address_text || "Khu vực");
        if (displayName.length > 35) displayName = displayName.substring(0, 32) + "...";

        return `
            <span class="preferred-loc-chip" title="${escapeHtml(loc.address_text || '')}">
                <span><i class="ri-map-pin-2-line"></i> ${escapeHtml(displayName)} · <strong>${radius} km</strong></span>
                <button type="button" class="remove-chip-btn" onclick="handleRemovePreferredLocation(${idx})" title="Xóa khu vực này" aria-label="Xóa">&times;</button>
            </span>
        `;
    }).join("");
}

function handleAddPreferredLocation() {
    if (preferredLocations.length >= 10) {
        showToast("Tối đa 10 khu vực mong muốn.", "warning");
        return;
    }

    const selected = prefLocAutocomplete.getSelected();
    const rawVal = prefLocAutocomplete.getValue();
    const radius = parseInt(document.getElementById("pref-radius-select").value, 10) || 10;

    if (!selected) {
        showToast("Vui lòng chọn một địa điểm từ danh sách gợi ý.", "warning");
        return;
    }

    // Tránh trùng lặp
    const exists = preferredLocations.some(l => {
        if (selected && l.place_id && l.place_id === selected.place_id) return true;
        if (selected && l.provider_place_id && l.provider_place_id === selected.place_id) return true;
        return (l.address_text || "").toLowerCase() === (rawVal || "").toLowerCase();
    });

    if (exists) {
        showToast("Khu vực này đã có trong danh sách của bạn.", "info");
        return;
    }

    preferredLocations.push({
        place_id: selected.place_id,
        session_token: selected.session_token,
        preferred_radius_km: radius,
        address_text: selected.description,
        commune: selected.commune || null,
        province: selected.province || null,
        district_text_legacy: selected.district_text_legacy || null
    });

    prefLocAutocomplete.clear();
    renderPreferredLocations();
    showToast("Đã thêm khu vực. Nhấn \"Lưu Hồ Sơ Sinh Viên\" để hoàn tất.", "success");
}

function handleRemovePreferredLocation(index) {
    preferredLocations.splice(index, 1);
    renderPreferredLocations();
}

async function savePreferredLocationsToServer() {
    const payload = {
        locations: preferredLocations.map(l => {
            const item = {
                preferred_radius_km: l.preferred_radius_km || 10
            };
            if (l.place_id) {
                item.place_id = l.place_id;
                if (l.session_token) item.session_token = l.session_token;
            } else if (l.provider_place_id) {
                item.place_id = l.provider_place_id;
            }
            if (l.address_text) item.address_text = l.address_text;
            if (l.latitude !== undefined && l.latitude !== null) item.latitude = l.latitude;
            if (l.longitude !== undefined && l.longitude !== null) item.longitude = l.longitude;
            if (l.commune) item.commune = l.commune;
            if (l.province) item.province = l.province;
            return item;
        })
    };

    const res = await apiRequest("/student/preferred-locations", {
        method: "PUT",
        body: payload,
        requireAuth: true
    });

    if (!res || !res.success || !Array.isArray(res.data)) {
        throw new Error(res && res.message ? res.message : "Không thể lưu khu vực làm việc mong muốn.");
    }

    preferredLocations = res.data;
    renderPreferredLocations();
}

async function handleSaveProfile(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-save-profile");
    const alertBox = document.getElementById("profile-alert");
    alertBox.style.display = "none";

    btn.disabled = true;
    btn.innerText = "Đang lưu hồ sơ...";

    // Collect skills
    const selectedSkillIds = [];
    document.querySelectorAll(".skill-checkbox:checked").forEach(cb => {
        selectedSkillIds.push(cb.value);
    });

    // Collect schedule matrix
    const scheduleObj = {};
    DAYS.forEach(day => {
        scheduleObj[day.key] = [];
    });
    document.querySelectorAll(".schedule-check:checked").forEach(cb => {
        const day = cb.getAttribute("data-day");
        const shift = cb.getAttribute("data-shift");
        if (scheduleObj[day]) {
            scheduleObj[day].push(shift);
        }
    });

    // Collect Work Experience, Education, and Certificates (CV-AI-P1-05)
    const workExp = collectWorkExperience();
    const education = collectEducation();
    const certificates = collectCertificates();

    // Construct Payload (EXCLUDE SENSITIVE: role, user_id, profile_completion_percent)
    const payload = {
        full_name: document.getElementById("prof-fullname").value.trim(),
        phone: document.getElementById("prof-phone").value.trim(),
        date_of_birth: document.getElementById("prof-dob").value || null,
        gender: document.getElementById("prof-gender").value || null,
        university: document.getElementById("prof-university").value.trim(),
        major: document.getElementById("prof-major").value.trim(),
        academic_year: document.getElementById("prof-academic-year").value ? parseInt(document.getElementById("prof-academic-year").value) : null,
        bio: document.getElementById("prof-bio").value.trim(),
        location_id: document.getElementById("prof-location").value || null,
        skill_ids: selectedSkillIds,
        availability_schedule: scheduleObj,
        work_experience: workExp,
        education: education,
        certificates: certificates
    };

    const res = await apiRequest("/student/profile", {
        method: "PUT",
        body: payload,
        requireAuth: true
    });

    btn.disabled = false;
    btn.innerText = "Lưu Hồ Sơ Sinh Viên";

    if (res && res.success) {
        try {
            await savePreferredLocationsToServer();
            showToast("Lưu hồ sơ sinh viên và khu vực mong muốn thành công!", "success");
        } catch (err) {
            console.error("Save preferred locations error:", err);
            showToast(`Hồ sơ đã lưu, nhưng khu vực mong muốn chưa lưu được: ${err.message}`, "warning");
        }
        if (res.data && res.data.profile_completion_percent !== undefined) {
            const pct = res.data.profile_completion_percent;
            document.getElementById("profile-percent-badge").innerText = `${pct}%`;
            document.getElementById("profile-progress-bar").style.width = `${pct}%`;
        }
        if (res.data) {
            updateProfileCompletenessSuggestion(res.data);
        }
        window.scrollTo({ top: 0, behavior: "smooth" });
    } else {
        let errMsg = (res && res.message) ? res.message : "Cập nhật hồ sơ thất bại.";
        if (res && res.errors) {
            const list = Object.values(res.errors).flat().map(escapeHtml).join("<br>&bull; ");
            errMsg += `<br>&bull; ${list}`;
        }
        alertBox.innerHTML = errMsg;
        alertBox.style.display = "block";
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
}

/* =========================================================
 * PROFILE COMPLETENESS & STRUCTURED DATA HELPERS (CV-AI-P1-05)
 * ========================================================= */

function addExperienceRow(item = {}) {
    const list = document.getElementById("prof-experience-list");
    if (!list) return;
    if (list.querySelectorAll(".experience-item").length >= 20) {
        if (typeof showToast === "function") showToast("Tối đa 20 mục kinh nghiệm.", "warning");
        return;
    }

    const row = document.createElement("div");
    row.className = "experience-item";
    row.style.cssText = "background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius-sm);padding:1rem;position:relative;";
    row.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
            <span style="font-weight:700;font-size:0.9rem;color:var(--dark);">Vị trí / Dự án</span>
            <button type="button" onclick="removeExperienceRow(this)" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:0.8rem;padding:0.2rem 0.5rem;">✕ Xóa mục này</button>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:0.75rem;margin-bottom:0.75rem;">
            <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.82rem;">Chức danh / Vị trí</label>
                <input type="text" class="form-control exp-title" maxlength="255" placeholder="VD: Phục vụ cafe, CTV Content..." value="${escapeHtml(item.title || "")}">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.82rem;">Đơn vị / Dự án</label>
                <input type="text" class="form-control exp-company" maxlength="255" placeholder="VD: Chuỗi Highlands, CLB Tin học..." value="${escapeHtml(item.company || "")}">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-size:0.82rem;">Thời gian (tháng / năm)</label>
                <input type="text" class="form-control exp-duration" maxlength="100" placeholder="VD: 6 tháng, 03/2023 - 09/2023" value="${escapeHtml(item.duration || "")}">
            </div>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.82rem;">Mô tả công việc & kỹ năng sử dụng</label>
            <textarea class="form-control exp-desc" maxlength="2000" rows="2" placeholder="Mô tả tóm tắt nhiệm vụ chính, kết quả đạt được...">${escapeHtml(item.description || "")}</textarea>
        </div>
    `;
    list.appendChild(row);
}

function removeExperienceRow(btn) {
    const item = btn.closest(".experience-item");
    if (item) item.remove();
}

function loadWorkExperienceFields(workExp) {
    const list = document.getElementById("prof-experience-list");
    if (!list) return;
    list.innerHTML = "";

    if (!workExp) {
        addExperienceRow();
        return;
    }

    let items = [];
    if (typeof workExp === "string") {
        try {
            const parsed = JSON.parse(workExp);
            if (Array.isArray(parsed)) items = parsed;
        } catch (e) {
            items = [{ description: workExp }];
        }
    } else if (Array.isArray(workExp)) {
        items = workExp;
    }

    if (items.length === 0) {
        addExperienceRow();
    } else {
        items.forEach(it => addExperienceRow(it));
    }
}

function collectWorkExperience() {
    const rows = document.querySelectorAll("#prof-experience-list .experience-item");
    const items = [];
    rows.forEach(r => {
        const title = r.querySelector(".exp-title")?.value.trim() || "";
        const company = r.querySelector(".exp-company")?.value.trim() || "";
        const duration = r.querySelector(".exp-duration")?.value.trim() || "";
        const description = r.querySelector(".exp-desc")?.value.trim() || "";
        if (title || company || duration || description) {
            items.push({ title, company, duration, description });
        }
    });
    return items.length > 0 ? JSON.stringify(items) : null;
}

function addCertificateRow(item = {}) {
    const list = document.getElementById("prof-certificates-list");
    if (!list) return;
    if (list.querySelectorAll(".cert-item").length >= 20) {
        if (typeof showToast === "function") showToast("Tối đa 20 chứng chỉ.", "warning");
        return;
    }

    const row = document.createElement("div");
    row.className = "cert-item";
    row.style.cssText = "display:flex;gap:0.75rem;align-items:center;background:#fff;padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:var(--radius-sm);";
    row.innerHTML = `
        <input type="text" class="form-control cert-name" maxlength="255" placeholder="Tên chứng chỉ (VD: IELTS 6.5, MOS Excel...)" style="flex:2;font-size:0.85rem;" value="${escapeHtml(item.name || "")}">
        <input type="text" class="form-control cert-year" maxlength="100" placeholder="Năm cấp / Tổ chức" style="flex:1;font-size:0.85rem;" value="${escapeHtml(item.year || "")}">
        <button type="button" onclick="removeCertificateRow(this)" style="background:none;border:none;color:var(--danger);cursor:pointer;padding:0.2rem 0.5rem;font-size:1.1rem;line-height:1;" title="Xóa chứng chỉ">&times;</button>
    `;
    list.appendChild(row);
}

function removeCertificateRow(btn) {
    const item = btn.closest(".cert-item");
    if (item) item.remove();
}

function loadCertificateFields(certs) {
    const list = document.getElementById("prof-certificates-list");
    if (!list) return;
    list.innerHTML = "";

    if (!certs) {
        addCertificateRow();
        return;
    }

    let items = [];
    if (typeof certs === "string") {
        try {
            const parsed = JSON.parse(certs);
            if (Array.isArray(parsed)) items = parsed;
        } catch (e) {
            items = certs.split(",").map(s => ({ name: s.trim() })).filter(x => x.name);
        }
    } else if (Array.isArray(certs)) {
        items = certs;
    }

    if (items.length === 0) {
        addCertificateRow();
    } else {
        items.forEach(it => addCertificateRow(it));
    }
}

function collectCertificates() {
    const rows = document.querySelectorAll("#prof-certificates-list .cert-item");
    const items = [];
    rows.forEach(r => {
        const name = r.querySelector(".cert-name")?.value.trim() || "";
        const year = r.querySelector(".cert-year")?.value.trim() || "";
        if (name || year) {
            items.push({ name, year });
        }
    });
    return items.length > 0 ? JSON.stringify(items) : null;
}

function loadEducationFields(prof) {
    const degEl = document.getElementById("prof-education-degree");
    const yrEl = document.getElementById("prof-grad-year");
    const descEl = document.getElementById("prof-education-desc");

    const rawEdu = prof.education;
    if (rawEdu) {
        try {
            const parsed = typeof rawEdu === "string" ? JSON.parse(rawEdu) : rawEdu;
            if (typeof parsed === "object" && parsed !== null) {
                if (degEl && parsed.degree) degEl.value = parsed.degree;
                if (yrEl && parsed.grad_year) yrEl.value = parsed.grad_year;
                if (descEl && parsed.description) descEl.value = parsed.description;
                return;
            }
        } catch (e) {
            if (descEl) descEl.value = rawEdu;
            return;
        }
    }
}

function collectEducation() {
    const university = document.getElementById("prof-university")?.value.trim() || "";
    const major = document.getElementById("prof-major")?.value.trim() || "";
    const degree = document.getElementById("prof-education-degree")?.value.trim() || "";
    const gradYear = document.getElementById("prof-grad-year")?.value.trim() || "";
    const desc = document.getElementById("prof-education-desc")?.value.trim() || "";

    if (!university && !major && !degree && !gradYear && !desc) {
        return null;
    }

    if (!degree && !gradYear && !desc) {
        return (university || major) ? `${university} - ${major}` : null;
    }

    return JSON.stringify({
        university,
        major,
        degree,
        grad_year: gradYear,
        description: desc
    });
}

function updateProfileCompletenessSuggestion(prof) {
    const el = document.getElementById("profile-completeness-suggestion");
    if (!el) return;

    // Strictly non-sensitive: skills, schedule, experience, education, location, certs
    const hasSched = prof.availability_schedule && (
        (Array.isArray(prof.availability_schedule) && prof.availability_schedule.length > 0) ||
        (typeof prof.availability_schedule === "object" && Object.keys(prof.availability_schedule).length > 0) ||
        (typeof prof.availability_schedule === "string" && prof.availability_schedule.trim().length > 2 && prof.availability_schedule !== "[]" && prof.availability_schedule !== "{}")
    );
    const hasExp = prof.work_experience && (
        (typeof prof.work_experience === "string" && prof.work_experience.trim().length > 2 && prof.work_experience !== "[]") ||
        (Array.isArray(prof.work_experience) && prof.work_experience.length > 0)
    );
    const hasSkills = (Array.isArray(prof.skill_ids) && prof.skill_ids.length > 0) || (prof.skills && prof.skills.trim().length > 0);
    const hasEdu = (prof.university && prof.university.trim().length > 0) || (prof.education && prof.education.trim().length > 0);
    const hasLoc = prof.location_id || prof.preferred_location;
    const hasCert = prof.certificates && (
        (typeof prof.certificates === "string" && prof.certificates.trim().length > 2 && prof.certificates !== "[]") ||
        (Array.isArray(prof.certificates) && prof.certificates.length > 0)
    );

    if (!hasSched) {
        el.innerHTML = `<i class="ri-time-line"></i> <strong>Gợi ý:</strong> Cập nhật lịch rảnh hàng tuần để hệ thống đánh giá chính xác độ khớp thời gian làm việc.`;
    } else if (!hasExp) {
        el.innerHTML = `<i class="ri-briefcase-line"></i> <strong>Gợi ý:</strong> Thêm kinh nghiệm hoặc dự án thực tế để tăng độ đầy đủ dữ liệu khi đánh giá phù hợp.`;
    } else if (!hasSkills) {
        el.innerHTML = `<i class="ri-tools-line"></i> <strong>Gợi ý:</strong> Chọn ít nhất 3 kỹ năng nổi bật để đối sánh chi tiết với yêu cầu công việc.`;
    } else if (!hasEdu) {
        el.innerHTML = `<i class="ri-graduation-cap-line"></i> <strong>Gợi ý:</strong> Hoàn thiện thông tin trường và chuyên ngành học vấn.`;
    } else if (!hasLoc) {
        el.innerHTML = `<i class="ri-map-pin-2-line"></i> <strong>Gợi ý:</strong> Chọn khu vực bạn đang sinh sống để đánh giá khoảng cách làm việc.`;
    } else if (!hasCert) {
        el.innerHTML = `<i class="ri-file-list-3-line"></i> <strong>Gợi ý:</strong> Bổ sung chứng chỉ ngoại ngữ hoặc kỹ năng tin học để làm nổi bật hồ sơ.`;
    } else {
        el.innerHTML = `<i class="ri-sparkling-line"></i> <strong>Tuyệt vời!</strong> Hồ sơ của bạn đã có đầy đủ thông tin để hỗ trợ đánh giá mức độ phù hợp toàn diện.`;
    }
}

// ----------------------------------------------------
// CV Management Functions (CV-P0-01)
// ----------------------------------------------------

function initCvHandlers() {
    const fileInput = document.getElementById("cv-file-input");
    if (fileInput) {
        fileInput.addEventListener("change", (e) => {
            if (e.target.files && e.target.files[0]) {
                handleCvUpload(e.target.files[0]);
            }
        });
    }

    const dropZone = document.getElementById("cv-state-empty");
    if (dropZone) {
        dropZone.addEventListener("dragover", (e) => {
            e.preventDefault();
            dropZone.style.borderColor = "var(--primary)";
            dropZone.style.background = "#eef2ff";
        });
        dropZone.addEventListener("dragleave", (e) => {
            e.preventDefault();
            dropZone.style.borderColor = "#cbd5e1";
            dropZone.style.background = "#f8fafc";
        });
        dropZone.addEventListener("drop", (e) => {
            e.preventDefault();
            dropZone.style.borderColor = "#cbd5e1";
            dropZone.style.background = "#f8fafc";
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                handleCvUpload(e.dataTransfer.files[0]);
            }
        });
    }
}

function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}

function formatDate(dateStr) {
    if (!dateStr) return "";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const pad = (n) => String(n).padStart(2, "0");
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function renderCvState(activeCv) {
    const emptyEl = document.getElementById("cv-state-empty");
    const activeEl = document.getElementById("cv-state-active");
    const loadingEl = document.getElementById("cv-state-loading");
    const uploadingEl = document.getElementById("cv-state-uploading");

    if (loadingEl) loadingEl.style.display = "none";
    if (uploadingEl) uploadingEl.style.display = "none";

    if (activeCv && activeCv.file_name) {
        if (emptyEl) emptyEl.style.display = "none";
        if (activeEl) {
            activeEl.style.display = "block";
            document.getElementById("cv-active-name").textContent = activeCv.file_name;
            const sizeStr = typeof formatBytes === "function" ? formatBytes(activeCv.file_size) : (activeCv.file_size + " B");
            document.getElementById("cv-active-size").textContent = sizeStr;
            const dateStr = typeof formatDateTime === "function" ? formatDateTime(activeCv.uploaded_at) : formatDate(activeCv.uploaded_at);
            document.getElementById("cv-active-date").textContent = "Đã tải lên: " + (dateStr || "Gần đây");
        }
    } else {
        if (emptyEl) emptyEl.style.display = "block";
        if (activeEl) activeEl.style.display = "none";
    }
}

function showCvFeedback(message, type = "success") {
    const el = document.getElementById("cv-feedback-alert");
    if (!el) return;
    el.style.display = "block";
    if (type === "success") {
        el.style.background = "#dcfce7";
        el.style.border = "1px solid #86efac";
        el.style.color = "#166534";
    } else {
        el.style.background = "#fee2e2";
        el.style.border = "1px solid #fca5a5";
        el.style.color = "#991b1b";
    }
    el.innerHTML = escapeHtml(message);
    setTimeout(() => {
        el.style.display = "none";
    }, 6000);
}

async function handleCvUpload(file) {
    if (!file) return;

    // Client-side quick validation
    if (!file.name.toLowerCase().endsWith(".pdf") && file.type !== "application/pdf") {
        showCvFeedback("Định dạng tệp không hợp lệ. Hệ thống chỉ chấp nhận tệp PDF (.pdf).", "error");
        return;
    }
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        showCvFeedback("Dung lượng tệp CV không được vượt quá 5 MB.", "error");
        return;
    }

    const emptyEl = document.getElementById("cv-state-empty");
    const activeEl = document.getElementById("cv-state-active");
    const uploadingEl = document.getElementById("cv-state-uploading");

    if (emptyEl) emptyEl.style.display = "none";
    if (activeEl) activeEl.style.display = "none";
    if (uploadingEl) uploadingEl.style.display = "block";

    const formData = new FormData();
    formData.append("cv_file", file);

    try {
        const res = await apiRequest("/student/cv", {
            method: "POST",
            body: formData,
            requireAuth: true
        });

        if (uploadingEl) uploadingEl.style.display = "none";

        if (res && res.success && res.data) {
            showCvFeedback("Tải lên và lưu trữ CV thành công!", "success");
            showToast("Tải lên CV thành công!", "success");
            renderCvState(res.data);

            // Update completion percentage
            const profRes = await apiRequest("/student/profile", { requireAuth: true });
            if (profRes && profRes.success && profRes.data) {
                const pct = profRes.data.profile_completion_percent || 0;
                document.getElementById("profile-percent-badge").innerText = `${pct}%`;
                document.getElementById("profile-progress-bar").style.width = `${pct}%`;
            }
        } else {
            let errMsg = (res && res.message) ? res.message : "Tải lên CV thất bại.";
            if (res && res.errors && res.errors.cv_file) {
                errMsg = res.errors.cv_file.join(" ");
            }
            showCvFeedback(errMsg, "error");
            showToast(errMsg, "error");

            // Revert to current server state
            const currentRes = await apiRequest("/student/cv", { requireAuth: true });
            renderCvState(currentRes && currentRes.data ? currentRes.data : null);
        }
    } catch (err) {
        if (uploadingEl) uploadingEl.style.display = "none";
        showCvFeedback("Lỗi kết nối khi tải lên CV. Vui lòng thử lại.", "error");
        showToast("Lỗi kết nối khi tải lên CV.", "error");
        const currentRes = await apiRequest("/student/cv", { requireAuth: true });
        renderCvState(currentRes && currentRes.data ? currentRes.data : null);
    }

    // Reset input
    const fileInput = document.getElementById("cv-file-input");
    if (fileInput) fileInput.value = "";
}

async function handleDeleteCv() {
    if (!confirm("Bạn có chắc chắn muốn xóa tệp CV hiện tại khỏi hồ sơ không?")) {
        return;
    }

    const btnDelete = document.getElementById("btn-delete-cv");
    const btnReplace = document.getElementById("btn-replace-cv");
    const origText = btnDelete ? btnDelete.innerText : "Xóa CV";

    if (btnDelete) {
        btnDelete.disabled = true;
        btnDelete.innerText = "Đang xóa...";
    }
    if (btnReplace) btnReplace.disabled = true;

    try {
        const res = await apiRequest("/student/cv", {
            method: "DELETE",
            requireAuth: true
        });

        if (res && res.success) {
            showCvFeedback("Đã xóa CV khỏi hồ sơ.", "success");
            showToast("Đã xóa CV thành công.", "success");
            renderCvState(null);

            // Update completion percentage
            const profRes = await apiRequest("/student/profile", { requireAuth: true });
            if (profRes && profRes.success && profRes.data) {
                const pct = profRes.data.profile_completion_percent || 0;
                document.getElementById("profile-percent-badge").innerText = `${pct}%`;
                document.getElementById("profile-progress-bar").style.width = `${pct}%`;
            }
        } else {
            const errMsg = (res && res.message) ? res.message : "Xóa CV thất bại.";
            showCvFeedback(errMsg, "error");
            showToast(errMsg, "error");
        }
    } catch (err) {
        showCvFeedback("Lỗi kết nối máy chủ khi xóa CV.", "error");
        showToast("Lỗi kết nối máy chủ khi xóa CV.", "error");
    } finally {
        if (btnDelete) {
            btnDelete.disabled = false;
            btnDelete.innerText = origText;
        }
        if (btnReplace) btnReplace.disabled = false;
    }
}

async function handleAnalyzeCv() {
    const btn = document.getElementById("btn-analyze-cv");
    if (!btn) return;
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = "⏳ Gemini đang đọc CV...";
    showCvFeedback("CV sẽ được gửi an toàn tới Google Gemini để trích xuất thông tin nghề nghiệp. Hệ thống không tự lưu thay đổi.", "success");

    try {
        const res = await apiRequest("/student/cv/analyze", { method: "POST", requireAuth: true });
        if (!res || !res.success || !res.data) {
            throw new Error((res && res.message) || "Không thể phân tích CV lúc này.");
        }
        cvAiExtraction = res.data;
        renderCvAiResult(res.data);
        document.getElementById("cv-ai-modal").style.display = "flex";
    } catch (error) {
        showCvFeedback(error.message || "Gemini chưa thể phân tích CV. Vui lòng thử lại.", "error");
        showToast(error.message || "Phân tích CV thất bại.", "error");
    } finally {
        btn.disabled = false;
        btn.innerHTML = original;
    }
}

function renderCvAiResult(data) {
    const educationBits = [data.university, data.major, data.education?.degree, data.education?.grad_year].filter(Boolean);
    const skills = Array.isArray(data.skills) ? data.skills : [];
    const experiences = Array.isArray(data.work_experience) ? data.work_experience : [];
    const certificates = Array.isArray(data.certificates) ? data.certificates : [];
    const section = (id, title, content, checked = true) => `
        <label style="display:block;border:1px solid var(--border);border-radius:12px;padding:1rem;margin-bottom:.75rem;cursor:pointer;background:#f8fafc;">
            <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;">
                <input type="checkbox" id="${id}" ${checked ? "checked" : ""} style="width:18px;height:18px;accent-color:var(--primary);">
                <strong style="color:var(--dark);">${escapeHtml(title)}</strong>
            </div>
            <div style="padding-left:1.65rem;color:var(--text-muted);font-size:.84rem;line-height:1.55;">${content}</div>
        </label>`;

    document.getElementById("cv-ai-result").innerHTML = `
        <div style="margin-bottom:1rem;padding:.8rem 1rem;border-radius:10px;background:var(--primary-light);color:var(--primary-text);border:1px solid var(--primary-border);font-size:.82rem;">
            Đã phân tích <strong>${escapeHtml(data.source_file || "CV PDF")}</strong>. Hãy bỏ chọn phần bạn không muốn thay đổi.
        </div>
        ${section("cv-ai-use-education", "Học vấn", educationBits.length ? educationBits.map(escapeHtml).join(" · ") : "Không tìm thấy thông tin học vấn", educationBits.length > 0)}
        ${section("cv-ai-use-skills", `Kỹ năng (${skills.length})`, skills.length ? skills.map(s => `<span class="badge" style="margin:.15rem;background:var(--primary-light);color:var(--primary-text);border:1px solid var(--primary-border);">${escapeHtml(s)}</span>`).join("") : "Không tìm thấy kỹ năng", skills.length > 0)}
        ${section("cv-ai-use-experience", `Kinh nghiệm (${experiences.length})`, experiences.length ? experiences.map(x => `<div>• <strong>${escapeHtml(x.title || "Kinh nghiệm")}</strong>${x.company ? ` tại ${escapeHtml(x.company)}` : ""}${x.duration ? ` · ${escapeHtml(x.duration)}` : ""}</div>`).join("") : "Không tìm thấy kinh nghiệm", experiences.length > 0)}
        ${section("cv-ai-use-certificates", `Chứng chỉ (${certificates.length})`, certificates.length ? certificates.map(x => `<div>• ${escapeHtml(x.name || "")}${x.year ? ` · ${escapeHtml(x.year)}` : ""}</div>`).join("") : "Không tìm thấy chứng chỉ", certificates.length > 0)}
        ${Array.isArray(data.unmatched_skills) && data.unmatched_skills.length ? `<div style="font-size:.78rem;color:#92400e;background:#fffbeb;padding:.7rem .85rem;border-radius:8px;">Một số kỹ năng chưa có trong danh mục để tự đánh dấu: ${data.unmatched_skills.map(escapeHtml).join(", ")}.</div>` : ""}
    `;
}

function applyCvAiResult() {
    const data = cvAiExtraction;
    if (!data) return;

    if (document.getElementById("cv-ai-use-education")?.checked) {
        if (data.university) document.getElementById("prof-university").value = data.university;
        if (data.major) document.getElementById("prof-major").value = data.major;
        if (data.academic_year) document.getElementById("prof-academic-year").value = String(data.academic_year);
        if (data.education?.degree) document.getElementById("prof-education-degree").value = data.education.degree;
        if (data.education?.grad_year) document.getElementById("prof-grad-year").value = data.education.grad_year;
        if (data.education?.description) document.getElementById("prof-education-desc").value = data.education.description;
    }
    if (document.getElementById("cv-ai-use-skills")?.checked && Array.isArray(data.matched_skill_ids)) {
        data.matched_skill_ids.forEach(id => {
            const checkbox = document.querySelector(`.skill-checkbox[value="${CSS.escape(id)}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    if (document.getElementById("cv-ai-use-experience")?.checked && Array.isArray(data.work_experience) && data.work_experience.length) {
        loadWorkExperienceFields(data.work_experience);
    }
    if (document.getElementById("cv-ai-use-certificates")?.checked && Array.isArray(data.certificates) && data.certificates.length) {
        loadCertificateFields(data.certificates);
    }

    closeCvAiModal();
    showCvFeedback("Gemini đã điền dữ liệu vào biểu mẫu. Vui lòng kiểm tra rồi bấm “Lưu Hồ Sơ Sinh Viên” để xác nhận.", "success");
    showToast("Đã điền dữ liệu từ CV. Hãy kiểm tra và lưu hồ sơ.", "success");
    document.getElementById("profile-form")?.scrollIntoView({ behavior: "smooth", block: "start" });
}

function closeCvAiModal() {
    const modal = document.getElementById("cv-ai-modal");
    if (modal) modal.style.display = "none";
}
</script>
