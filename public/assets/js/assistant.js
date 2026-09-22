/**
 * JobMarketSV Chatbot Widget (CHAT-P1-01)
 * Accessible, in-memory AI assistant for public and student job pages.
 */

(function () {
    "use strict";

    // 1. Role Guard & Feature Flag: Check enablement & role before initializing
    function isFeatureEnabled() {
        if (typeof window !== "undefined" && window.__CHATBOT_CONFIG__) {
            if (window.__CHATBOT_CONFIG__.enabled === false) {
                return false;
            }
        }
        return true;
    }

    // Guest, Student, and Developer are allowed.
    // Company is allowed ONLY when both global chatbot and company assistant features are enabled.
    // Admin is strictly forbidden.
    function isAllowedRole() {
        if (typeof TokenStorage === "undefined") {
            return true; // Guest allowed
        }
        const user = TokenStorage.getUser();
        if (!user || !user.role) {
            return true; // Guest allowed
        }
        const role = String(user.role).toLowerCase();
        if (role === "student" || role === "developer") {
            return true;
        }
        if (role === "company") {
            const isCompanyEnabled = (typeof window !== "undefined" && window.__CHATBOT_CONFIG__ && window.__CHATBOT_CONFIG__.enabled !== false && window.__CHATBOT_CONFIG__.companyEnabled === true);
            return isCompanyEnabled;
        }
        return false;
    }

    function getUserRole() {
        if (typeof TokenStorage === "undefined") return "guest";
        const user = TokenStorage.getUser();
        if (!user || !user.role) return "guest";
        return String(user.role).toLowerCase();
    }

    if (!isFeatureEnabled()) {
        return;
    }

    if (!isAllowedRole()) {
        return;
    }

    // 2. State & In-memory Conversation History
    // Kept ONLY in memory. Resets automatically on page refresh or tab close.
    const MAX_HISTORY_TURNS = 10;
    const conversationHistory = [];
    let isSubmitting = false;
    let isOpen = false;

    // DOM Elements references
    let triggerBtn = null;
    let dialogEl = null;
    let messagesContainer = null;
    let formEl = null;
    let inputEl = null;
    let sendBtn = null;

    // 3. Widget Initialization
    function initWidget() {
        if (document.getElementById("chatbot-widget")) {
            return; // Prevent duplicate initialization
        }

        const widgetWrapper = document.createElement("div");
        widgetWrapper.id = "chatbot-widget";

        // Trigger Button
        triggerBtn = document.createElement("button");
        triggerBtn.type = "button";
        triggerBtn.className = "chatbot-trigger";
        triggerBtn.id = "chatbot-trigger";
        triggerBtn.setAttribute("aria-label", "Mở trợ lý tìm việc AI");
        triggerBtn.setAttribute("aria-expanded", "false");
        triggerBtn.setAttribute("aria-controls", "chatbot-dialog");

        const iconSpan = document.createElement("span");
        iconSpan.className = "chatbot-trigger-icon";
        iconSpan.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v1a7 7 0 0 0-7 7v4a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H4a5 5 0 0 1 5-5V5a1 1 0 0 1 2 0v1a5 5 0 0 1 5 5h-1a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-4a7 7 0 0 0-7-7V5a3 3 0 0 0-3-3z"/><circle cx="9" cy="13" r="1"/><circle cx="15" cy="13" r="1"/></svg>';

        const labelSpan = document.createElement("span");
        labelSpan.className = "chatbot-trigger-label";
        labelSpan.textContent = "Trợ lý AI";

        triggerBtn.appendChild(iconSpan);
        triggerBtn.appendChild(labelSpan);

        // Dialog Container
        dialogEl = document.createElement("div");
        dialogEl.className = "chatbot-dialog";
        dialogEl.id = "chatbot-dialog";
        dialogEl.setAttribute("role", "dialog");
        dialogEl.setAttribute("aria-label", "Trợ lý tìm việc JobMarketSV");
        dialogEl.setAttribute("aria-hidden", "true");

        // Header
        const headerEl = document.createElement("div");
        headerEl.className = "chatbot-header";

        const headerInfo = document.createElement("div");
        headerInfo.className = "chatbot-header-info";

        const avatar = document.createElement("div");
        avatar.className = "chatbot-avatar";
        avatar.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>';

        const headerText = document.createElement("div");
        headerText.className = "chatbot-header-text";

        const headerTitle = document.createElement("h3");
        headerTitle.textContent = "Trợ lý JobMarketSV";

        const statusIndicator = document.createElement("div");
        statusIndicator.className = "chatbot-status";
        const dot = document.createElement("span");
        dot.className = "chatbot-status-dot";
        const statusText = document.createElement("span");
        statusText.textContent = "Sẵn sàng hỗ trợ";
        statusIndicator.appendChild(dot);
        statusIndicator.appendChild(statusText);

        headerText.appendChild(headerTitle);
        headerText.appendChild(statusIndicator);

        headerInfo.appendChild(avatar);
        headerInfo.appendChild(headerText);

        const closeBtn = document.createElement("button");
        closeBtn.type = "button";
        closeBtn.className = "chatbot-close-btn";
        closeBtn.setAttribute("aria-label", "Đóng trợ lý");
        closeBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
        closeBtn.addEventListener("click", closeDialog);

        headerEl.appendChild(headerInfo);
        headerEl.appendChild(closeBtn);

        // Messages Stream
        messagesContainer = document.createElement("div");
        messagesContainer.className = "chatbot-messages";
        messagesContainer.setAttribute("aria-live", "polite");

        // Footer & Input Form
        const footerEl = document.createElement("div");
        footerEl.className = "chatbot-footer";

        formEl = document.createElement("form");
        formEl.className = "chatbot-form";
        formEl.addEventListener("submit", handleSubmit);

        inputEl = document.createElement("input");
        inputEl.type = "text";
        inputEl.className = "chatbot-input";
        inputEl.placeholder = "Hỏi về tìm việc, lịch ca, chuẩn bị CV...";
        inputEl.maxLength = 1000;
        inputEl.setAttribute("aria-label", "Nhập nội dung hỏi trợ lý AI");

        sendBtn = document.createElement("button");
        sendBtn.type = "submit";
        sendBtn.className = "chatbot-send-btn";
        sendBtn.setAttribute("aria-label", "Gửi tin nhắn");
        sendBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';

        formEl.appendChild(inputEl);
        formEl.appendChild(sendBtn);

        const helpText = document.createElement("small");
        helpText.className = "chatbot-footer-help";
        helpText.textContent = "Gợi ý mang tính tham khảo. Không chia sẻ mật khẩu hay dữ liệu riêng tư.";

        footerEl.appendChild(formEl);
        footerEl.appendChild(helpText);

        // Assemble Dialog
        dialogEl.appendChild(headerEl);
        dialogEl.appendChild(messagesContainer);
        dialogEl.appendChild(footerEl);

        // Assemble Widget Wrapper
        widgetWrapper.appendChild(dialogEl);
        widgetWrapper.appendChild(triggerBtn);

        document.body.appendChild(widgetWrapper);

        // Initial welcome message and quick prompts
        renderWelcomeState();

        // Event Listeners
        triggerBtn.addEventListener("click", toggleDialog);

        // Close on Escape key and restore focus
        document.addEventListener("keydown", handleKeyDown);
    }

    // 4. Open / Close / Toggle Management
    function toggleDialog() {
        if (isOpen) {
            closeDialog();
        } else {
            openDialog();
        }
    }

    function openDialog() {
        isOpen = true;
        dialogEl.classList.add("chatbot-dialog--open");
        dialogEl.setAttribute("aria-hidden", "false");
        triggerBtn.setAttribute("aria-expanded", "true");
        triggerBtn.setAttribute("aria-label", "Thu gọn trợ lý tìm việc AI");
        triggerBtn.classList.add("chatbot-trigger--hidden");
        triggerBtn.setAttribute("aria-hidden", "true");
        triggerBtn.setAttribute("tabindex", "-1");

        // Focus input field smoothly
        setTimeout(() => {
            if (inputEl) {
                inputEl.focus();
            }
        }, 100);

        scrollToBottom();
    }

    function closeDialog() {
        isOpen = false;
        dialogEl.classList.remove("chatbot-dialog--open");
        dialogEl.setAttribute("aria-hidden", "true");
        triggerBtn.classList.remove("chatbot-trigger--hidden");
        triggerBtn.removeAttribute("aria-hidden");
        triggerBtn.removeAttribute("tabindex");
        triggerBtn.setAttribute("aria-expanded", "false");
        triggerBtn.setAttribute("aria-label", "Mở trợ lý tìm việc AI");

        // Restore focus to trigger button for accessible keyboard navigation
        if (triggerBtn) {
            triggerBtn.focus();
        }
    }

    function handleKeyDown(e) {
        if (isOpen && e.key === "Escape") {
            e.preventDefault();
            closeDialog();
            return;
        }

        // Keep focus within dialog on Tab / Shift+Tab when open
        if (isOpen && e.key === "Tab") {
            const focusableElements = dialogEl.querySelectorAll(
                'button:not([disabled]), input:not([disabled]), a[href]'
            );
            if (focusableElements.length === 0) return;

            const firstEl = focusableElements[0];
            const lastEl = focusableElements[focusableElements.length - 1];

            if (e.shiftKey && document.activeElement === firstEl) {
                e.preventDefault();
                lastEl.focus();
            } else if (!e.shiftKey && document.activeElement === lastEl) {
                e.preventDefault();
                firstEl.focus();
            }
        }
    }

    // 5. Welcome Message & Static Quick Prompts
    function renderWelcomeState() {
        const welcomeCard = document.createElement("div");
        welcomeCard.className = "chatbot-welcome-card";

        const title = document.createElement("div");
        title.className = "chatbot-welcome-title";

        const desc = document.createElement("p");
        desc.className = "chatbot-welcome-desc";

        const promptsContainer = document.createElement("div");
        promptsContainer.className = "chatbot-quick-prompts";

        const currentRole = getUserRole();
        let prompts = [];

        if (currentRole === "company") {
            title.innerHTML = '<i class="ri-user-smile-line" style="color:var(--primary);margin-right:4px;"></i> Xin chào Quý Doanh nghiệp!';
            desc.textContent = "Tôi là trợ lý hỗ trợ nhà tuyển dụng JobMarketSV. Tôi có thể hướng dẫn bạn cách đăng tin, quản lý hồ sơ ứng viên và quy trình xác minh tài khoản.";
            prompts = [
                "Hướng dẫn đăng tin tuyển dụng",
                "Quy trình xác minh doanh nghiệp",
                "Quản lý trạng thái ứng viên"
            ];
        } else {
            title.innerHTML = '<i class="ri-user-smile-line" style="color:var(--primary);margin-right:4px;"></i> Xin chào bạn!';
            desc.textContent = "Tôi là trợ lý tìm việc JobMarketSV. Tôi có thể hỗ trợ bạn tìm kiếm việc làm theo ca, hướng dẫn quy trình nộp đơn và cách chuẩn bị CV.";
            prompts = [
                "Tìm việc theo ca",
                "Cách ứng tuyển",
                "Chuẩn bị CV"
            ];
        }

        prompts.forEach((promptText) => {
            const chip = document.createElement("button");
            chip.type = "button";
            chip.className = "chatbot-prompt-chip";
            chip.textContent = promptText;
            chip.addEventListener("click", () => {
                if (!isSubmitting) {
                    sendUserMessage(promptText);
                }
            });
            promptsContainer.appendChild(chip);
        });

        welcomeCard.appendChild(title);
        welcomeCard.appendChild(desc);
        welcomeCard.appendChild(promptsContainer);

        messagesContainer.appendChild(welcomeCard);
    }

    // 6. Message Submission & Streaming Display
    async function handleSubmit(e) {
        e.preventDefault();
        if (isSubmitting || !inputEl) return;

        const text = inputEl.value.trim();
        if (!text) return;

        inputEl.value = "";
        await sendUserMessage(text);
    }

    async function sendUserMessage(text) {
        if (isSubmitting) return;

        // 1. Render user message bubble (Strict textContent)
        appendUserBubble(text);

        // 2. Update in-memory history slice
        const historyToSend = conversationHistory.slice(-MAX_HISTORY_TURNS);
        conversationHistory.push({ role: "user", text: text });

        // 3. Set loading state
        setSubmitting(true);
        const typingEl = appendTypingIndicator();
        scrollToBottom();

        try {
            // 4. Send request to POST /assistant/chat
            // ONLY message and bounded in-memory history are sent in the body.
            // No JWT, profile, CV, or private data is ever included in the request body.
            const response = await apiRequest("/assistant/chat", {
                method: "POST",
                body: {
                    message: text,
                    history: historyToSend
                }
            });

            // 5. Remove typing indicator
            if (typingEl && typingEl.parentNode) {
                typingEl.parentNode.removeChild(typingEl);
            }

            if (response && response.success && response.data) {
                const answerText = response.data.answer || "Không nhận được phản hồi phù hợp.";
                const safeLinks = Array.isArray(response.data.job_links) ? response.data.job_links : [];

                // Append assistant response bubble (Strict textContent)
                appendBotBubble(answerText, safeLinks);

                // Save to in-memory history
                conversationHistory.push({ role: "model", text: answerText });
            } else {
                handleErrorResponse(response);
            }
        } catch (err) {
            if (typingEl && typingEl.parentNode) {
                typingEl.parentNode.removeChild(typingEl);
            }
            appendErrorNotice("Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại kết nối mạng của bạn.");
        } finally {
            setSubmitting(false);
            scrollToBottom();
            if (inputEl) {
                inputEl.focus();
            }
        }
    }

    // 7. Safe Bubble Appenders (Strict textContent, No innerHTML for content)
    function appendUserBubble(text) {
        const group = document.createElement("div");
        group.className = "chatbot-msg-group chatbot-msg-group--user";

        const bubble = document.createElement("div");
        bubble.className = "chatbot-bubble";
        bubble.textContent = text; // Safe textContent

        group.appendChild(bubble);
        messagesContainer.appendChild(group);
    }

    function appendBotBubble(answerText, safeLinks) {
        const group = document.createElement("div");
        group.className = "chatbot-msg-group chatbot-msg-group--bot";

        const bubble = document.createElement("div");
        bubble.className = "chatbot-bubble";
        bubble.textContent = answerText; // Safe textContent
        group.appendChild(bubble);

        // Render validated server-owned job links only
        if (safeLinks.length > 0) {
            const cardsContainer = document.createElement("div");
            cardsContainer.className = "chatbot-job-cards";

            safeLinks.forEach((job) => {
                // Strictly validate internal job URL pattern
                const rawUrl = String(job.url || "");
                if (!/^\/viec-lam\/[a-zA-Z0-9\-_]+$/.test(rawUrl)) {
                    return; // Ignore invalid or external URLs
                }

                const card = document.createElement("a");
                card.className = "chatbot-job-card";
                card.href = rawUrl;

                const titleEl = document.createElement("div");
                titleEl.className = "chatbot-job-title";
                titleEl.textContent = job.title || "Chi tiết việc làm";

                const compEl = document.createElement("div");
                compEl.className = "chatbot-job-company";
                compEl.textContent = job.company_name || "Doanh nghiệp tuyển dụng";

                card.appendChild(titleEl);
                card.appendChild(compEl);

                if (job.salary) {
                    const salaryEl = document.createElement("div");
                    salaryEl.className = "chatbot-job-salary";
                    const icon = document.createElement("i");
                    icon.className = "ri-money-dollar-circle-line";
                    icon.style.marginRight = "3px";
                    salaryEl.appendChild(icon);
                    salaryEl.appendChild(document.createTextNode(" " + job.salary));
                    card.appendChild(salaryEl);
                }

                cardsContainer.appendChild(card);
            });

            if (cardsContainer.children.length > 0) {
                group.appendChild(cardsContainer);
            }
        }

        // Feedback actions (Thumbs up / down) - CHAT-P2-01
        const feedbackEl = document.createElement("div");
        feedbackEl.className = "chatbot-feedback";

        const fbLabel = document.createElement("span");
        fbLabel.className = "chatbot-feedback-label";
        fbLabel.textContent = "Hữu ích?";

        const upBtn = document.createElement("button");
        upBtn.type = "button";
        upBtn.className = "chatbot-feedback-btn chatbot-feedback-btn--up";
        upBtn.setAttribute("aria-label", "Đánh giá câu trả lời hữu ích");
        upBtn.innerHTML = '<i class="ri-thumb-up-line"></i>';

        const downBtn = document.createElement("button");
        downBtn.type = "button";
        downBtn.className = "chatbot-feedback-btn chatbot-feedback-btn--down";
        downBtn.setAttribute("aria-label", "Đánh giá câu trả lời chưa hữu ích");
        downBtn.innerHTML = '<i class="ri-thumb-down-line"></i>';

        const handleRating = async (rating) => {
            upBtn.disabled = true;
            downBtn.disabled = true;
            try {
                await apiRequest("/assistant/feedback", {
                    method: "POST",
                    body: { rating: rating }
                });
            } catch {
                // Silently handle
            } finally {
                feedbackEl.innerHTML = '<span class="chatbot-feedback--done">Cảm ơn phản hồi của bạn!</span>';
            }
        };

        upBtn.addEventListener("click", () => handleRating("up"));
        downBtn.addEventListener("click", () => handleRating("down"));

        feedbackEl.appendChild(fbLabel);
        feedbackEl.appendChild(upBtn);
        feedbackEl.appendChild(downBtn);
        group.appendChild(feedbackEl);

        messagesContainer.appendChild(group);
    }

    function appendTypingIndicator() {
        const typingEl = document.createElement("div");
        typingEl.className = "chatbot-typing";
        typingEl.setAttribute("aria-label", "Trợ lý đang phản hồi");

        for (let i = 0; i < 3; i++) {
            const dot = document.createElement("span");
            dot.className = "chatbot-typing-dot";
            typingEl.appendChild(dot);
        }

        messagesContainer.appendChild(typingEl);
        return typingEl;
    }

    function appendErrorNotice(errorMessage) {
        const group = document.createElement("div");
        group.className = "chatbot-msg-group chatbot-msg-group--bot";

        const notice = document.createElement("div");
        notice.className = "chatbot-error-notice";
        notice.textContent = errorMessage; // Safe textContent

        group.appendChild(notice);
        messagesContainer.appendChild(group);
    }

    // 8. Error Mapping & Discretion
    function handleErrorResponse(res) {
        const status = res ? res.status : 0;
        const errCode = (res && res.errors && res.errors.error_code) ? res.errors.error_code : "";

        let userMsg = "Đã có lỗi xảy ra trong quá trình xử lý yêu cầu. Vui lòng thử lại sau.";

        if (status === 429 || errCode === "RATE_LIMIT_EXCEEDED" || errCode === "PROVIDER_RATE_LIMIT") {
            userMsg = "Bạn đã gửi quá nhiều yêu cầu chat. Vui lòng chờ ít phút trước khi thử lại.";
        } else if (errCode === "COMPANY_CHAT_DISABLED") {
            userMsg = "Tính năng trợ lý AI dành cho doanh nghiệp hiện đang tạm tắt. Vui lòng quay lại sau.";
        } else if (status === 403 || errCode === "FORBIDDEN_ROLE") {
            userMsg = "Tính năng trợ lý tìm việc hiện chỉ hỗ trợ sinh viên, doanh nghiệp và khách vãng lai.";
        } else if (errCode === "CONTENT_BLOCKED") {
            userMsg = "Nội dung câu hỏi đã bị chặn do không phù hợp với tiêu chuẩn an toàn. Vui lòng đặt câu hỏi liên quan đến tìm việc làm part-time.";
        } else if (status === 422) {
            userMsg = (res && res.message) ? res.message : "Nội dung tin nhắn không hợp lệ hoặc quá dài. Vui lòng thử lại với nội dung ngắn hơn.";
        } else if (status === 504 || errCode === "TIMEOUT") {
            userMsg = "Yêu cầu phản hồi quá thời gian cho phép. Vui lòng thử lại sau ít phút.";
        } else if (status === 503 || errCode === "SERVICE_UNAVAILABLE" || errCode === "RATE_LIMIT_STORAGE_ERROR" || errCode === "ASSISTANT_ERROR") {
            userMsg = "Trợ lý AI hiện đang tạm tắt hoặc đang được bảo trì. Vui lòng quay lại sau ít phút.";
        }

        appendErrorNotice(userMsg);
    }

    // 9. Utilities
    function setSubmitting(submitting) {
        isSubmitting = submitting;
        if (sendBtn) {
            sendBtn.disabled = submitting;
        }
        if (inputEl) {
            inputEl.disabled = submitting;
        }
    }

    function scrollToBottom() {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    // Mount on DOM Ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initWidget);
    } else {
        initWidget();
    }
})();
