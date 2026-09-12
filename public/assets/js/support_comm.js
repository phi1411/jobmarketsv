/**
 * Support Chat Widget (Student & Company <-> Admin)
 * Real-time incremental polling via HTTP with persistence
 */

(function () {
    let supportConvId = null;
    let lastMsgId = null;
    let isDialogOpen = false;
    let activePollTimer = null;
    let unreadCheckTimer = null;
    let currentUser = null;

    document.addEventListener("DOMContentLoaded", () => {
        initSupportChat();
    });

    function initSupportChat() {
        if (typeof TokenStorage === "undefined") return;

        currentUser = TokenStorage.getUser();
        if (!currentUser) return;

        // If user is Admin, they use /admin/support instead of floating widget
        if (currentUser.role === "admin") return;

        // Only render for student or company
        if (currentUser.role !== "student" && currentUser.role !== "company") return;

        injectSupportChatHtml();
        checkUnreadCount();

        // Check unread count periodically when dialog is closed
        unreadCheckTimer = setInterval(() => {
            if (!isDialogOpen) {
                checkUnreadCount();
            }
        }, 30000);
    }

    function injectSupportChatHtml() {
        // Prevent duplicate injection
        if (document.getElementById("support-chat-trigger-btn")) return;

        const triggerBtn = document.createElement("button");
        triggerBtn.id = "support-chat-trigger-btn";
        triggerBtn.className = "support-chat-trigger";
        triggerBtn.setAttribute("aria-label", "Mở chat hỗ trợ trực tuyến");
        triggerBtn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span>Hỗ trợ Admin</span>
            <span id="support-chat-badge" class="support-chat-badge">0</span>
        `;
        triggerBtn.addEventListener("click", toggleSupportDialog);
        document.body.appendChild(triggerBtn);

        const dialog = document.createElement("div");
        dialog.id = "support-chat-dialog";
        dialog.className = "support-chat-dialog";
        dialog.innerHTML = `
            <div class="support-chat-header">
                <div class="support-chat-header-info">
                    <div class="support-chat-header-avatar">🛡️</div>
                    <div>
                        <div class="support-chat-header-title">Hỗ Trợ Trực Tuyến</div>
                        <div class="support-chat-header-subtitle">
                            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#34d399;"></span>
                            Quản Trị Viên JobMarketSV
                        </div>
                    </div>
                </div>
                <button type="button" class="support-chat-header-close" id="support-chat-close-btn" aria-label="Đóng chat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="support-chat-stream" id="support-chat-stream">
                <div class="support-chat-intro">
                    💬 Chào bạn! Bạn có câu hỏi về việc làm, ứng tuyển hoặc tài khoản? Hãy gửi tin nhắn, Quản trị viên sẽ hỗ trợ bạn trực tiếp tại đây!
                </div>
                <div id="support-chat-history" style="display:flex;flex-direction:column;gap:0.5rem;">
                    <div style="text-align:center;color:#94a3b8;font-size:0.82rem;padding:1rem;">Đang tải lịch sử tin nhắn...</div>
                </div>
            </div>

            <div class="support-chat-footer">
                <form class="support-chat-input-form" id="support-chat-form">
                    <input type="text" class="support-chat-input" id="support-chat-input" 
                           placeholder="Nhập tin nhắn..." autocomplete="off">
                    <button type="submit" class="support-chat-send-btn" id="support-chat-send-btn" aria-label="Gửi tin nhắn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>
                </form>
            </div>
        `;

        document.body.appendChild(dialog);

        document.getElementById("support-chat-close-btn").addEventListener("click", toggleSupportDialog);
        document.getElementById("support-chat-form").addEventListener("submit", handleSendMessage);
    }

    async function toggleSupportDialog() {
        const dialog = document.getElementById("support-chat-dialog");
        if (!dialog) return;

        isDialogOpen = !isDialogOpen;

        if (isDialogOpen) {
            dialog.classList.add("open");
            hideBadge();
            await loadInitialMessages();
            startPolling();
            document.getElementById("support-chat-input").focus();
        } else {
            dialog.classList.remove("open");
            stopPolling();
        }
    }

    async function checkUnreadCount() {
        if (!currentUser) return;
        try {
            const res = await apiRequest("/support/unread-count");
            if (res && res.success && res.data) {
                const count = res.data.unread_count || 0;
                updateBadge(count);
            }
        } catch (e) {
            // Ignore background network error
        }
    }

    function updateBadge(count) {
        const badge = document.getElementById("support-chat-badge");
        if (!badge) return;
        if (count > 0 && !isDialogOpen) {
            badge.innerText = count > 99 ? "99+" : count;
            badge.style.display = "flex";
        } else {
            badge.style.display = "none";
        }
    }

    function hideBadge() {
        const badge = document.getElementById("support-chat-badge");
        if (badge) badge.style.display = "none";
    }

    async function loadInitialMessages() {
        const historyEl = document.getElementById("support-chat-history");
        historyEl.innerHTML = `<div style="text-align:center;color:#94a3b8;font-size:0.82rem;padding:1rem;">Đang tải lịch sử tin nhắn...</div>`;

        try {
            const res = await apiRequest("/support/messages?limit=60");
            if (res && res.success && res.data) {
                supportConvId = res.data.conversation_id;
                const messages = res.data.messages || [];

                historyEl.innerHTML = "";
                if (messages.length === 0) {
                    historyEl.innerHTML = `<div style="text-align:center;color:#94a3b8;font-size:0.82rem;padding:1rem;">Chưa có tin nhắn nào. Hãy gửi lời chào đầu tiên!</div>`;
                } else {
                    messages.forEach(msg => {
                        lastMsgId = msg.id;
                        renderBubble(msg, historyEl);
                    });
                }
                scrollToBottom();
            } else {
                historyEl.innerHTML = `<div style="text-align:center;color:#ef4444;font-size:0.82rem;padding:1rem;">Không thể tải lịch sử trò chuyện.</div>`;
            }
        } catch (e) {
            historyEl.innerHTML = `<div style="text-align:center;color:#ef4444;font-size:0.82rem;padding:1rem;">Lỗi kết nối.</div>`;
        }
    }

    function startPolling() {
        stopPolling();
        activePollTimer = setInterval(pollNewMessages, 3500);
    }

    function stopPolling() {
        if (activePollTimer) {
            clearInterval(activePollTimer);
            activePollTimer = null;
        }
    }

    async function pollNewMessages() {
        if (!isDialogOpen || !supportConvId) return;

        let url = `/support/messages?conversation_id=${encodeURIComponent(supportConvId)}&limit=50`;
        if (lastMsgId) {
            url += `&after_id=${encodeURIComponent(lastMsgId)}`;
        }

        try {
            const res = await apiRequest(url);
            if (res && res.success && res.data) {
                const messages = res.data.messages || [];
                if (messages.length > 0) {
                    const historyEl = document.getElementById("support-chat-history");
                    messages.forEach(msg => {
                        lastMsgId = msg.id;
                        renderBubble(msg, historyEl);
                    });
                    scrollToBottom();
                }
            }
        } catch (e) {
            // Ignore polling errors
        }
    }

    function renderBubble(msg, container) {
        const isMine = (msg.sender_id === currentUser.id);
        const timeStr = formatTimeOnly(msg.created_at);

        const wrap = document.createElement("div");
        wrap.className = `support-msg-wrap ${isMine ? 'mine' : 'theirs'}`;

        const senderLabel = isMine ? "Bạn" : "Quản Trị Viên";
        wrap.innerHTML = `
            <div class="support-msg-meta">${senderLabel} &bull; ${timeStr}</div>
            <div class="support-msg-bubble">${escapeHtml(msg.content)}</div>
        `;

        container.appendChild(wrap);
    }

    async function handleSendMessage(e) {
        e.preventDefault();
        const input = document.getElementById("support-chat-input");
        const sendBtn = document.getElementById("support-chat-send-btn");
        const content = input.value.trim();

        if (!content) return;

        sendBtn.disabled = true;
        input.value = "";

        try {
            const res = await apiRequest("/support/messages", {
                method: "POST",
                body: {
                    conversation_id: supportConvId,
                    content: content
                }
            });

            sendBtn.disabled = false;
            input.focus();

            if (res && res.success && res.data) {
                lastMsgId = res.data.id;
                const historyEl = document.getElementById("support-chat-history");
                renderBubble(res.data, historyEl);
                scrollToBottom();
            } else {
                if (typeof showToast === "function") {
                    showToast((res && res.message) ? res.message : "Gửi tin nhắn thất bại", "error");
                }
            }
        } catch (err) {
            sendBtn.disabled = false;
            input.focus();
            if (typeof showToast === "function") {
                showToast("Lỗi gửi tin nhắn", "error");
            }
        }
    }

    function scrollToBottom() {
        const stream = document.getElementById("support-chat-stream");
        if (stream) {
            stream.scrollTop = stream.scrollHeight;
        }
    }

    function formatTimeOnly(dateStr) {
        if (!dateStr) return "";
        const date = new Date(dateStr.replace(/-/g, "/"));
        const hours = String(date.getHours()).padStart(2, "0");
        const minutes = String(date.getMinutes()).padStart(2, "0");
        return `${hours}:${minutes}`;
    }
})();
