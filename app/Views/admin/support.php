<?php include __DIR__ . "/nav.php"; ?>

<div class="container" style="margin-bottom:3rem;">
    <!-- Chat Support Dashboard Layout -->
    <div style="background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow-sm);overflow:hidden;display:grid;grid-template-columns:360px 1fr;min-height:680px;height:calc(100vh - 260px);">
        
        <!-- Left Sidebar: Conversations List -->
        <div style="border-right:1px solid var(--border);display:flex;flex-direction:column;background:var(--bg);">
            <!-- Sidebar Header & Search -->
            <div style="padding:1.25rem;border-bottom:1px solid var(--border);background:var(--surface);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                    <h2 style="font-size:1.1rem;font-weight:700;color:var(--dark);margin:0;display:flex;align-items:center;gap:0.5rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Hộp Thư Hỗ Trợ
                    </h2>
                    <button onclick="refreshConversations()" class="btn btn-outline btn-sm" title="Làm mới danh sách" style="padding:0.25rem 0.6rem;font-size:0.8rem;">
                        🔄 Làm mới
                    </button>
                </div>
                <!-- Search Input -->
                <div style="position:relative;">
                    <input type="text" id="conv-search-input" class="form-control" placeholder="Tìm theo tên hoặc email..." 
                           style="width:100%;font-size:0.85rem;padding:0.5rem 0.75rem 0.5rem 2rem;border-radius:8px;"
                           oninput="filterConversations()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--text-subtle)" stroke-width="2" 
                         style="position:absolute;left:9px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
                <!-- Role Filter Tabs -->
                <div style="display:flex;gap:0.35rem;margin-top:0.65rem;overflow-x:auto;">
                    <button onclick="setFilter('all', this)" class="conv-filter-btn active" style="border:none;background:var(--primary);color:#fff;border-radius:20px;padding:0.25rem 0.65rem;font-size:0.75rem;font-weight:600;cursor:pointer;">Tất cả</button>
                    <button onclick="setFilter('student', this)" class="conv-filter-btn" style="border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:20px;padding:0.25rem 0.65rem;font-size:0.75rem;font-weight:600;cursor:pointer;">🎓 Sinh viên</button>
                    <button onclick="setFilter('company', this)" class="conv-filter-btn" style="border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:20px;padding:0.25rem 0.65rem;font-size:0.75rem;font-weight:600;cursor:pointer;">🏢 Doanh nghiệp</button>
                    <button onclick="setFilter('unread', this)" class="conv-filter-btn" style="border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:20px;padding:0.25rem 0.65rem;font-size:0.75rem;font-weight:600;cursor:pointer;">🔴 Chưa đọc</button>
                </div>
            </div>

            <!-- Conversations List Scroll Container -->
            <div id="conv-list-container" style="flex:1;overflow-y:auto;padding:0.5rem 0;">
                <!-- Skeletons while loading -->
                <div style="padding:1rem;text-align:center;color:var(--text-muted);font-size:0.9rem;" id="conv-list-loading">
                    <div class="skeleton" style="height:60px;margin-bottom:0.5rem;border-radius:8px;"></div>
                    <div class="skeleton" style="height:60px;margin-bottom:0.5rem;border-radius:8px;"></div>
                    <div class="skeleton" style="height:60px;border-radius:8px;"></div>
                </div>
                <div id="conv-list-empty" style="display:none;padding:2.5rem 1.5rem;text-align:center;color:var(--text-subtle);font-size:0.88rem;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">📭</div>
                    Chưa có tin nhắn hỗ trợ nào.
                </div>
                <div id="conv-items"></div>
            </div>
        </div>

        <!-- Right Pane: Active Chat Room -->
        <div style="display:flex;flex-direction:column;background:var(--surface);height:100%;position:relative;">
            
            <!-- Empty state when no conversation selected -->
            <div id="chat-empty-state" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--text-muted);padding:2rem;text-align:center;">
                <div style="width:72px;height:72px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;margin-bottom:1rem;">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h3 style="color:var(--dark);font-size:1.15rem;font-weight:700;margin-bottom:0.5rem;">Chọn Một Cuộc Trò Chuyện</h3>
                <p style="max-width:360px;font-size:0.9rem;line-height:1.5;">Chọn người dùng từ danh sách bên trái để phản hồi thắc mắc trực tuyến theo thời gian thực.</p>
            </div>

            <!-- Active Conversation Area -->
            <div id="chat-active-area" style="display:none;flex-direction:column;height:100%;">
                
                <!-- Chat Header -->
                <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface);z-index:2;">
                    <div style="display:flex;align-items:center;gap:0.85rem;">
                        <div id="chat-header-avatar" style="width:42px;height:42px;border-radius:50%;background:var(--primary);color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:1rem;">
                            U
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <h3 id="chat-header-name" style="font-size:1rem;font-weight:700;color:var(--dark);margin:0;">Tên Người Dùng</h3>
                                <span id="chat-header-role" class="badge" style="font-size:0.72rem;padding:0.2rem 0.5rem;border-radius:12px;background:var(--border-light);color:var(--text);">Sinh viên</span>
                            </div>
                            <div id="chat-header-email" style="font-size:0.8rem;color:var(--text-muted);margin-top:2px;">user@example.com</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span style="font-size:0.75rem;color:var(--secondary);display:inline-flex;align-items:center;gap:0.35rem;background:var(--secondary-light);padding:0.25rem 0.65rem;border-radius:20px;font-weight:600;border:1px solid var(--secondary-border);">
                            <span style="width:7px;height:7px;border-radius:50%;background:var(--secondary);"></span>
                            Đang kết nối
                        </span>
                    </div>
                </div>

                <!-- Messages Stream -->
                <div id="chat-messages-stream" style="flex:1;overflow-y:auto;padding:1.5rem;display:flex;flex-direction:column;gap:0.75rem;background:var(--bg);">
                    <!-- Messages will be populated here -->
                </div>

                <!-- Input Box Area -->
                <div style="padding:1rem 1.5rem;border-top:1px solid var(--border);background:var(--surface);z-index:2;">
                    <form id="admin-chat-form" onsubmit="handleAdminSendMessage(event)" style="display:flex;gap:0.75rem;align-items:flex-end;">
                        <div style="flex:1;position:relative;">
                            <textarea id="admin-chat-input" rows="2" class="form-control" 
                                      placeholder="Nhập câu trả lời hỗ trợ (Nhấn Enter để gửi, Shift + Enter xuống dòng)..." 
                                      style="width:100%;resize:none;font-size:0.9rem;padding:0.65rem 0.85rem;border-radius:10px;line-height:1.4;"
                                      onkeydown="handleInputKeydown(event)"></textarea>
                        </div>
                        <button type="submit" id="btn-admin-send" class="btn btn-primary" style="height:46px;padding:0 1.25rem;border-radius:10px;display:inline-flex;align-items:center;gap:0.4rem;font-weight:600;">
                            <span>Gửi</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </button>
                    </form>
                </div>

            </div>

        </div>

    </div>
</div>

<style>
.conv-item {
    padding: 0.85rem 1.15rem;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: background 0.15s ease;
    display: flex;
    align-items: center;
    gap: 0.85rem;
}
.conv-item:hover {
    background: var(--surface-hover);
}
.conv-item.active {
    background: rgba(37, 99, 235, 0.15);
    border-left: 4px solid var(--primary);
}
.msg-bubble-user {
    background: var(--surface);
    color: var(--text);
    border: 1px solid var(--border);
    border-radius: 14px 14px 14px 2px;
    padding: 0.65rem 1rem;
    max-width: 70%;
    font-size: 0.92rem;
    line-height: 1.45;
    box-shadow: var(--shadow-xs);
    word-break: break-word;
}
.msg-bubble-admin {
    background: var(--primary);
    color: #ffffff;
    border-radius: 14px 14px 2px 14px;
    padding: 0.65rem 1rem;
    max-width: 70%;
    font-size: 0.92rem;
    line-height: 1.45;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    word-break: break-word;
}
</style>

<script>
let allConversations = [];
let filteredConversations = [];
let currentFilterRole = "all";
let activeConversationId = null;
let activeOtherUser = null;
let lastMessageId = null;
let isPollingActive = false;
let messagePollingTimer = null;
let convPollingTimer = null;

document.addEventListener("DOMContentLoaded", () => {
    // Check if user is admin
    const user = TokenStorage.getUser();
    if (!user || user.role !== "admin") {
        showToast("Vui lòng đăng nhập với tài khoản Quản Trị Viên.", "error");
        setTimeout(() => { window.location.href = "/login"; }, 1200);
        return;
    }

    loadAdminConversations();

    // Start background conversation list refresh every 6 seconds
    convPollingTimer = setInterval(loadAdminConversationsQuietly, 6000);
});

// Load conversations from backend
async function loadAdminConversations() {
    const loadingEl = document.getElementById("conv-list-loading");
    if (loadingEl) loadingEl.style.display = "block";

    const res = await apiRequest("/admin/support/conversations");
    if (loadingEl) loadingEl.style.display = "none";

    if (res && res.success && Array.isArray(res.data)) {
        allConversations = res.data;
        applyFiltersAndRender();
    } else {
        document.getElementById("conv-list-empty").style.display = "block";
    }
}

async function loadAdminConversationsQuietly() {
    const res = await apiRequest("/admin/support/conversations");
    if (res && res.success && Array.isArray(res.data)) {
        allConversations = res.data;
        applyFiltersAndRender(true);
    }
}

function refreshConversations() {
    loadAdminConversations();
}

function setFilter(role, btn) {
    currentFilterRole = role;
    document.querySelectorAll(".conv-filter-btn").forEach(b => {
        b.style.background = "#fff";
        b.style.color = "#475569";
        b.style.border = "1px solid #e2e8f0";
    });
    btn.style.background = "#2563eb";
    btn.style.color = "#fff";
    btn.style.border = "none";
    applyFiltersAndRender();
}

function filterConversations() {
    applyFiltersAndRender();
}

function applyFiltersAndRender(preserveActive = false) {
    const query = (document.getElementById("conv-search-input").value || "").toLowerCase().trim();

    filteredConversations = allConversations.filter(c => {
        const other = c.other_user || {};
        const name = (other.name || "").toLowerCase();
        const email = (other.email || "").toLowerCase();
        const role = (other.role || "").toLowerCase();

        // Search match
        if (query && !name.includes(query) && !email.includes(query)) {
            return false;
        }

        // Role filter
        if (currentFilterRole === "student" && role !== "student") return false;
        if (currentFilterRole === "company" && role !== "company") return false;
        if (currentFilterRole === "unread" && (!c.unread_count || c.unread_count <= 0)) return false;

        return true;
    });

    renderConversationList();
}

function renderConversationList() {
    const container = document.getElementById("conv-items");
    const emptyEl = document.getElementById("conv-list-empty");

    if (filteredConversations.length === 0) {
        container.innerHTML = "";
        emptyEl.style.display = "block";
        return;
    }

    emptyEl.style.display = "none";
    let html = "";

    filteredConversations.forEach(c => {
        const other = c.other_user || {};
        const isActive = (c.id === activeConversationId);
        const roleBadge = other.role === "company" 
            ? `<span style="font-size:0.68rem;padding:0.15rem 0.45rem;border-radius:10px;background:#fef3c7;color:#92400e;font-weight:600;">🏢 Doanh nghiệp</span>`
            : `<span style="font-size:0.68rem;padding:0.15rem 0.45rem;border-radius:10px;background:#e0e7ff;color:#3730a3;font-weight:600;">🎓 Sinh viên</span>`;
        
        const initial = (other.name || "U").charAt(0).toUpperCase();
        const lastMsg = c.last_message ? escapeHtml(c.last_message.content) : "Bắt đầu cuộc trò chuyện...";
        const timeAgo = formatChatTime(c.last_message ? c.last_message.created_at : c.updated_at);
        const unreadBadge = c.unread_count > 0 
            ? `<span style="background:#ef4444;color:#fff;font-size:0.7rem;font-weight:700;padding:0.15rem 0.45rem;border-radius:10px;">${c.unread_count}</span>` 
            : "";

        html += `
            <div class="conv-item ${isActive ? 'active' : ''}" onclick="selectConversation('${escapeHtml(c.id)}')">
                <div style="width:40px;height:40px;border-radius:50%;background:${other.role === 'company' ? '#d97706' : '#2563eb'};color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    ${escapeHtml(initial)}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                        <span style="font-weight:700;font-size:0.9rem;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px;">
                            ${escapeHtml(other.name || "Người dùng")}
                        </span>
                        <span style="font-size:0.72rem;color:var(--text-subtle);flex-shrink:0;">${timeAgo}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.4rem;margin-bottom:3px;">
                        ${roleBadge}
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.8rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;">
                            ${lastMsg}
                        </span>
                        ${unreadBadge}
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Select and open a conversation
async function selectConversation(conversationId) {
    if (activeConversationId === conversationId) return;

    activeConversationId = conversationId;
    lastMessageId = null;

    // Highlight in list
    document.querySelectorAll(".conv-item").forEach(el => el.classList.remove("active"));
    const conv = allConversations.find(c => c.id === conversationId);
    if (!conv) return;

    activeOtherUser = conv.other_user || {};

    // Reset unread count locally
    conv.unread_count = 0;
    renderConversationList();

    // Show active area, hide empty state
    document.getElementById("chat-empty-state").style.display = "none";
    const activeArea = document.getElementById("chat-active-area");
    activeArea.style.display = "flex";

    // Update Header
    document.getElementById("chat-header-name").innerText = activeOtherUser.name || "Người dùng";
    document.getElementById("chat-header-email").innerText = activeOtherUser.email || "";
    document.getElementById("chat-header-avatar").innerText = (activeOtherUser.name || "U").charAt(0).toUpperCase();
    document.getElementById("chat-header-avatar").style.background = activeOtherUser.role === "company" ? "#d97706" : "#2563eb";

    const roleSpan = document.getElementById("chat-header-role");
    if (activeOtherUser.role === "company") {
        roleSpan.innerText = "🏢 Nhà tuyển dụng";
        roleSpan.style.background = "#fef3c7";
        roleSpan.style.color = "#92400e";
    } else {
        roleSpan.innerText = "🎓 Sinh viên";
        roleSpan.style.background = "#e0e7ff";
        roleSpan.style.color = "#3730a3";
    }

    // Clear stream
    const stream = document.getElementById("chat-messages-stream");
    stream.innerHTML = `<div style="text-align:center;color:#94a3b8;font-size:0.85rem;padding:2rem;">Đang tải lịch sử tin nhắn...</div>`;

    // Load full message history
    await fetchMessages(true);

    // Focus input
    document.getElementById("admin-chat-input").focus();

    // Start smart incremental polling
    startMessagePolling();
}

function startMessagePolling() {
    if (messagePollingTimer) clearInterval(messagePollingTimer);
    messagePollingTimer = setInterval(() => {
        if (activeConversationId) {
            fetchMessages(false);
        }
    }, 3000);
}

// Fetch messages (initial or incremental)
async function fetchMessages(isInitial = false) {
    if (!activeConversationId) return;

    let url = `/support/messages?conversation_id=${encodeURIComponent(activeConversationId)}&limit=100`;
    if (!isInitial && lastMessageId) {
        url += `&after_id=${encodeURIComponent(lastMessageId)}`;
    }

    const res = await apiRequest(url);
    if (!res || !res.success || !res.data) return;

    const messages = res.data.messages || [];
    const stream = document.getElementById("chat-messages-stream");

    if (isInitial) {
        stream.innerHTML = "";
        if (messages.length === 0) {
            stream.innerHTML = `<div style="text-align:center;color:#94a3b8;font-size:0.88rem;padding:3rem;">Chưa có tin nhắn nào trong cuộc trò chuyện này. Hãy gửi lời chào hỗ trợ!</div>`;
            return;
        }
    }

    if (messages.length > 0) {
        const currentUser = TokenStorage.getUser();
        const currentUserId = currentUser ? currentUser.id : "";

        // Remove empty placeholder if any
        if (isInitial) stream.innerHTML = "";

        messages.forEach(msg => {
            lastMessageId = msg.id;
            const isMe = (msg.sender_id === currentUserId) || (msg.sender_role === "admin");
            renderMessageBubble(msg, isMe, stream);
        });

        // Scroll to bottom smoothly
        stream.scrollTop = stream.scrollHeight;
    }
}

function renderMessageBubble(msg, isMe, container) {
    const timeStr = formatTimeOnly(msg.created_at);
    const bubbleDiv = document.createElement("div");
    bubbleDiv.style.display = "flex";
    bubbleDiv.style.flexDirection = "column";
    bubbleDiv.style.alignItems = isMe ? "flex-end" : "flex-start";
    bubbleDiv.style.marginBottom = "0.5rem";

    if (isMe) {
        bubbleDiv.innerHTML = `
            <div style="font-size:0.72rem;color:var(--text-subtle);margin-bottom:2px;display:flex;align-items:center;gap:0.35rem;">
                <span>Bạn (Quản Trị Viên)</span> &bull; <span>${timeStr}</span>
            </div>
            <div class="msg-bubble-admin">
                ${escapeHtml(msg.content)}
            </div>
        `;
    } else {
        const senderName = activeOtherUser ? activeOtherUser.name : "Người dùng";
        bubbleDiv.innerHTML = `
            <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:2px;display:flex;align-items:center;gap:0.35rem;">
                <span style="font-weight:600;">${escapeHtml(senderName)}</span> &bull; <span>${timeStr}</span>
            </div>
            <div class="msg-bubble-user">
                ${escapeHtml(msg.content)}
            </div>
        `;
    }

    container.appendChild(bubbleDiv);
}

// Handle Send Message
async function handleAdminSendMessage(e) {
    if (e) e.preventDefault();

    const input = document.getElementById("admin-chat-input");
    const content = input.value.trim();
    if (!content || !activeConversationId) return;

    const btn = document.getElementById("btn-admin-send");
    btn.disabled = true;
    input.value = "";

    const res = await apiRequest("/support/messages", {
        method: "POST",
        body: {
            conversation_id: activeConversationId,
            content: content
        }
    });

    btn.disabled = false;
    input.focus();

    if (res && res.success && res.data) {
        const stream = document.getElementById("chat-messages-stream");
        lastMessageId = res.data.id;
        renderMessageBubble(res.data, true, stream);
        stream.scrollTop = stream.scrollHeight;

        // Update local conversation list snippet
        const conv = allConversations.find(c => c.id === activeConversationId);
        if (conv) {
            conv.last_message = res.data;
            conv.updated_at = res.data.created_at;
            renderConversationList();
        }
    } else {
        showToast((res && res.message) ? res.message : "Không thể gửi tin nhắn.", "error");
    }
}

function handleInputKeydown(e) {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        handleAdminSendMessage();
    }
}

function formatChatTime(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr.replace(/-/g, "/"));
    const now = new Date();
    const diffSec = Math.floor((now - date) / 1000);

    if (diffSec < 60) return "Vừa xong";
    if (diffSec < 3600) return `${Math.floor(diffSec / 60)} phút`;
    if (diffSec < 86400) return `${Math.floor(diffSec / 3600)} giờ`;
    return `${date.getDate()}/${date.getMonth() + 1}`;
}

function formatTimeOnly(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr.replace(/-/g, "/"));
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");
    return `${hours}:${minutes}`;
}
</script>
