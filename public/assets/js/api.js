/**
 * Job Marketplace API Client (Vanilla JS)
 * Handles Token Management, Bearer Headers, Error Formatting, XSS Sanitization & Toasts.
 */

const API_BASE = window.location.origin;

// 1. Token Storage (JWT & User profile)
const TokenStorage = {
    TOKEN_KEY: "jobmarket_token",
    USER_KEY: "jobmarket_user",

    getToken() {
        return localStorage.getItem(this.TOKEN_KEY) || null;
    },

    setToken(token, user = null) {
        if (token) {
            localStorage.setItem(this.TOKEN_KEY, token);
        }
        if (user) {
            localStorage.setItem(this.USER_KEY, JSON.stringify(user));
        }
    },

    getUser() {
        const raw = localStorage.getItem(this.USER_KEY);
        if (!raw) return null;
        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    },

    clear() {
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.USER_KEY);
    },

    isLoggedIn() {
        return !!this.getToken();
    }
};

// 2. Safe XSS Escaping
function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// 3. Centralized API Fetcher
async function apiRequest(endpoint, options = {}) {
    const url = endpoint.startsWith("http") ? endpoint : `${API_BASE}${endpoint}`;
    const token = TokenStorage.getToken();

    const headers = {
        "Accept": "application/json",
        ...(options.headers || {})
    };

    if (token) {
        headers["Authorization"] = `Bearer ${token}`;
    }

    if (options.body && typeof options.body === "object" && !(options.body instanceof FormData)) {
        headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(options.body);
    }

    const config = {
        ...options,
        headers
    };

    try {
        const response = await fetch(url, config);
        const data = await response.json().catch(() => null);

        // Handle 401 Unauthorized globally
        if (response.status === 401) {
            TokenStorage.clear();
            if (options.requireAuth) {
                window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
                return { success: false, message: "Phiên đăng nhập đã hết hạn." };
            }
        }

        if (!response.ok) {
            return {
                success: false,
                status: response.status,
                message: (data && data.message) ? data.message : `Lỗi hệ thống (${response.status})`,
                errors: (data && data.errors) ? data.errors : null
            };
        }

        return data;
    } catch (err) {
        console.error("API Request Error:", err);
        return {
            success: false,
            message: "Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại mạng hoặc máy chủ."
        };
    }
}

// 4. Toast Notifications
function showToast(message, type = "success") {
    let container = document.getElementById("toast-container");
    if (!container) {
        container = document.createElement("div");
        container.id = "toast-container";
        container.className = "toast-container";
        document.body.appendChild(container);
    }

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateY(20px)";
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// 5. Common Formatters
function formatCurrency(amount) {
    if (!amount) return "Thoả thuận";
    return new Intl.NumberFormat("vi-VN").format(amount) + " đ";
}

function formatDate(dateString) {
    if (!dateString) return "";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString("vi-VN");
}

function formatDateTime(dateString) {
    if (!dateString) return "";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    const pad = (n) => String(n).padStart(2, "0");
    return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}

function getShiftLabel(shift) {
    const map = {
        "morning": "Ca Sáng (08:00 - 12:00)",
        "afternoon": "Ca Chiều (13:00 - 17:00)",
        "evening": "Ca Tối (18:00 - 22:00)",
        "flexible": "Linh Hoạt / Tự Chọn",
        "weekend": "Cuối Tuần"
    };
    return map[shift] || shift || "Linh hoạt";
}

function getWorkTypeLabel(type) {
    const map = {
        "part_time": "Bán thời gian",
        "internship": "Thực tập sinh",
        "full_time": "Toàn thời gian"
    };
    return map[type] || type || "Part-time";
}

// 6. Semantic Status Badge Helpers (UI-P0-03)
function getAppStatusBadge(status) {
    const map = {
        "pending":     { label: "Chờ xem xét", modifier: "status-badge--warning" },
        "viewed":      { label: "Đã xem", modifier: "status-badge--info" },
        "shortlisted": { label: "Phù hợp", modifier: "status-badge--info" },
        "accepted":    { label: "Trúng tuyển", modifier: "status-badge--success" },
        "rejected":    { label: "Chưa phù hợp", modifier: "status-badge--danger" },
        "withdrawn":   { label: "Đã rút đơn", modifier: "status-badge--neutral" }
    };
    const s = map[status] || { label: status || "Không rõ", modifier: "status-badge--neutral" };
    return `<span class="status-badge ${s.modifier}"><span class="status-dot"></span>${escapeHtml(s.label)}</span>`;
}

function getJobStatusBadge(status) {
    const map = {
        "published":        { label: "Đang hiển thị", modifier: "status-badge--success" },
        "draft":            { label: "Bản nháp", modifier: "status-badge--neutral" },
        "pending_approval": { label: "Chờ duyệt", modifier: "status-badge--warning" },
        "hidden":           { label: "Tạm ẩn", modifier: "status-badge--neutral" },
        "closed":           { label: "Đã đóng", modifier: "status-badge--neutral" },
        "expired":          { label: "Hết hạn", modifier: "status-badge--neutral" },
        "rejected":         { label: "Bị từ chối", modifier: "status-badge--danger" }
    };
    const s = map[status] || { label: status || "Không rõ", modifier: "status-badge--neutral" };
    return `<span class="status-badge ${s.modifier}"><span class="status-dot"></span>${escapeHtml(s.label)}</span>`;
}

function getVerificationBadge(status) {
    const map = {
        "verified": { label: "Đã xác thực", modifier: "status-badge--success" },
        "pending":  { label: "Chờ xác minh", modifier: "status-badge--warning" },
        "rejected": { label: "Bị từ chối", modifier: "status-badge--danger" }
    };
    const s = map[status] || { label: status || "Chưa xác minh", modifier: "status-badge--neutral" };
    return `<span class="status-badge ${s.modifier}"><span class="status-dot"></span>${escapeHtml(s.label)}</span>`;
}

// 7. Protected CV Delivery Helper (CV-P0-02 & CV-P1-01)
async function viewApplicationCv(appId, btnElement = null) {
    const token = TokenStorage.getToken();
    if (!token) {
        showToast("Vui lòng đăng nhập để xem CV.", "error");
        return;
    }

    let origHtml = "";
    let isBtn = false;
    if (btnElement && btnElement instanceof HTMLElement) {
        isBtn = true;
        origHtml = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = `⏳ Đang mở...`;
    }

    try {
        const response = await fetch(`/applications/${encodeURIComponent(appId)}/cv`, {
            headers: {
                "Authorization": `Bearer ${token}`
            }
        });

        if (!response.ok) {
            let msg = `Không thể tải CV (${response.status})`;
            try {
                const errData = await response.json();
                if (errData && errData.message) {
                    msg = errData.message;
                }
            } catch (_) {}

            if (response.status === 401) {
                msg = "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.";
            } else if (response.status === 403) {
                msg = "Bạn không có quyền truy cập hồ sơ CV này.";
            } else if (response.status === 404) {
                msg = "Không tìm thấy tệp CV đã nộp cho đơn ứng tuyển này.";
            }

            showToast(msg, "error");
            return;
        }

        const blob = await response.blob();
        const blobUrl = URL.createObjectURL(blob);
        
        // Open safely in new tab or trigger click fallback
        const win = window.open(blobUrl, "_blank");
        if (!win || win.closed || typeof win.closed === "undefined") {
            const link = document.createElement("a");
            link.href = blobUrl;
            link.target = "_blank";
            link.rel = "noopener noreferrer";
            document.body.appendChild(link);
            link.click();
            setTimeout(() => {
                document.body.removeChild(link);
            }, 100);
        }

        setTimeout(() => {
            URL.revokeObjectURL(blobUrl);
        }, 60000);
    } catch (err) {
        console.error("Error fetching CV snapshot:", err);
        showToast("Lỗi kết nối máy chủ khi tải CV. Vui lòng thử lại sau.", "error");
    } finally {
        if (isBtn && btnElement) {
            btnElement.disabled = false;
            btnElement.innerHTML = origHtml;
        }
    }
}

// Explicit window exports to prevent any scope issues
if (typeof window !== "undefined") {
    window.escapeHtml = escapeHtml;
    window.apiRequest = apiRequest;
    window.showToast = showToast;
    window.formatCurrency = formatCurrency;
    window.formatDate = formatDate;
    window.formatDateTime = formatDateTime;
    window.formatBytes = formatBytes;
    window.getShiftLabel = getShiftLabel;
    window.getWorkTypeLabel = getWorkTypeLabel;
    window.getAppStatusBadge = getAppStatusBadge;
    window.getJobStatusBadge = getJobStatusBadge;
    window.getVerificationBadge = getVerificationBadge;
    window.viewApplicationCv = viewApplicationCv;
}
