/**
 * CHAT-P1-01 Chatbot Widget Headless Test Suite
 * Validates DOM generation, role guard, accessibility, XSS safety, link filtering, and error handling.
 */

const fs = require('fs');
const path = require('path');
const assert = require('assert');

const chatbotJsPath = path.join(__dirname, 'public', 'assets', 'js', 'chatbot.js');
const chatbotJsCode = fs.readFileSync(chatbotJsPath, 'utf8');

// Lightweight mock DOM environment
function createMockWindow(mockTokenUser = null, mockConfig = null) {
    const eventListeners = {};
    let focusedElement = null;

    class MockNode {
        constructor(tagName = 'div') {
            this.tagName = tagName.toUpperCase();
            this.children = [];
            this.parentNode = null;
            this.attributes = {};
            this.classList = {
                _classes: new Set(),
                add: (c) => this.classList._classes.add(c),
                remove: (c) => this.classList._classes.delete(c),
                contains: (c) => this.classList._classes.has(c)
            };
            this._textContent = '';
            this._innerHTML = '';
            this.disabled = false;
            this.value = '';
            this.type = 'text';
            this.className = '';
            this.id = '';
            this.href = '';
            this.maxLength = 0;
            this.eventListeners = {};
        }

        get textContent() {
            return this._textContent;
        }

        set textContent(val) {
            this._textContent = String(val);
            this.children = [];
            // Simple textContent escaping for innerHTML representation
            this._innerHTML = String(val)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        get innerHTML() {
            return this._innerHTML;
        }

        set innerHTML(val) {
            this._innerHTML = String(val);
            // Crude simulation: if HTML contains tags, update textContent approximation
            this._textContent = this._innerHTML.replace(/<[^>]*>/g, '');
        }

        setAttribute(name, value) {
            this.attributes[name] = String(value);
        }

        getAttribute(name) {
            return this.attributes[name] !== undefined ? this.attributes[name] : null;
        }

        removeAttribute(name) {
            delete this.attributes[name];
        }

        appendChild(child) {
            child.parentNode = this;
            this.children.push(child);
            return child;
        }

        removeChild(child) {
            const idx = this.children.indexOf(child);
            if (idx !== -1) {
                this.children.splice(idx, 1);
                child.parentNode = null;
            }
            return child;
        }

        addEventListener(type, listener) {
            if (!this.eventListeners[type]) this.eventListeners[type] = [];
            this.eventListeners[type].push(listener);
        }

        dispatchEvent(event) {
            const listeners = this.eventListeners[event.type] || [];
            listeners.forEach(l => l(event));
        }

        focus() {
            focusedElement = this;
        }

        querySelectorAll(selector) {
            const results = [];
            function walk(node) {
                for (const child of node.children) {
                    if (selector.includes('button') && child.tagName === 'BUTTON' && !child.disabled) {
                        results.push(child);
                    } else if (selector.includes('input') && child.tagName === 'INPUT' && !child.disabled) {
                        results.push(child);
                    } else if (selector.includes('a[href]') && child.tagName === 'A' && child.href) {
                        results.push(child);
                    }
                    walk(child);
                }
            }
            walk(this);
            return results;
        }
    }

    const documentMock = {
        readyState: 'complete',
        body: new MockNode('body'),
        createElement: (tag) => new MockNode(tag),
        getElementById: function(id) {
            function find(node) {
                if (node.id === id) return node;
                for (const child of node.children) {
                    const res = find(child);
                    if (res) return res;
                }
                return null;
            }
            return find(documentMock.body);
        },
        addEventListener: (type, listener) => {
            if (!eventListeners[type]) eventListeners[type] = [];
            eventListeners[type].push(listener);
        },
        dispatchEvent: (event) => {
            const listeners = eventListeners[event.type] || [];
            listeners.forEach(l => l(event));
        },
        get activeElement() {
            return focusedElement;
        }
    };

    const tokenStorageMock = {
        getUser: () => mockTokenUser
    };

    let lastApiCall = null;
    let mockApiResponse = null;

    const apiRequestMock = async (endpoint, options) => {
        lastApiCall = { endpoint, options };
        if (mockApiResponse instanceof Error) {
            throw mockApiResponse;
        }
        return mockApiResponse;
    };

    const windowContext = {
        document: documentMock,
        TokenStorage: tokenStorageMock,
        apiRequest: apiRequestMock,
        setTimeout: (fn) => fn(),
        getLastApiCall: () => lastApiCall,
        setApiResponse: (res) => { mockApiResponse = res; },
        getFocusedElement: () => focusedElement,
        __CHATBOT_CONFIG__: mockConfig
    };

    return windowContext;
}

function runWidgetInContext(context) {
    const runner = new Function(
        'window', 'document', 'TokenStorage', 'apiRequest', 'setTimeout',
        chatbotJsCode
    );
    runner(
        context,
        context.document,
        context.TokenStorage,
        context.apiRequest,
        context.setTimeout
    );
}

let passed = 0;
let total = 0;

function it(desc, fn) {
    total++;
    try {
        fn();
        console.log(`[PASS] ${desc}`);
        passed++;
    } catch (err) {
        console.error(`[FAIL] ${desc}`);
        console.error(err);
    }
}

async function itAsync(desc, fn) {
    total++;
    try {
        await fn();
        console.log(`[PASS] ${desc}`);
        passed++;
    } catch (err) {
        console.error(`[FAIL] ${desc}`);
        console.error(err);
    }
}

async function runAllTests() {
    console.log("=================================================================");
    console.log("   CHAT-P1-01 JAVASCRIPT HEADLESS WIDGET & SAFETY TEST SUITE     ");
    console.log("=================================================================\n");

    // 1. Role Guard Tests
    it("Role Guard: Global flag OFF (__CHATBOT_CONFIG__.enabled === false) MUST NOT initialize widget for guest", () => {
        const ctx = createMockWindow(null, { enabled: false });
        runWidgetInContext(ctx);
        const widget = ctx.document.getElementById("chatbot-widget");
        assert.strictEqual(widget, null, "Widget should not mount when global flag is off");
    });

    it("Role Guard: Global flag OFF (__CHATBOT_CONFIG__.enabled === false) MUST NOT initialize widget for student", () => {
        const ctx = createMockWindow({ role: "student" }, { enabled: false });
        runWidgetInContext(ctx);
        const widget = ctx.document.getElementById("chatbot-widget");
        assert.strictEqual(widget, null, "Widget should not mount for student when global flag is off");
    });

    it("Role Guard: Global flag OFF (__CHATBOT_CONFIG__.enabled === false) MUST NOT initialize widget for company even if companyEnabled is true", () => {
        const ctx = createMockWindow({ role: "company" }, { enabled: false, companyEnabled: true });
        runWidgetInContext(ctx);
        const widget = ctx.document.getElementById("chatbot-widget");
        assert.strictEqual(widget, null, "Widget should not mount for company when global flag is off");
    });

    it("Role Guard: Company role with flag OFF MUST NOT initialize the chatbot widget", () => {
        const ctx = createMockWindow({ role: "company" }, { companyEnabled: false });
        runWidgetInContext(ctx);
        const widget = ctx.document.getElementById("chatbot-widget");
        assert.strictEqual(widget, null, "Widget should not be mounted for company when flag is off");
    });

    it("Role Guard: Company role with flag ON DOES initialize the chatbot widget", () => {
        const ctx = createMockWindow({ role: "company" }, { companyEnabled: true });
        runWidgetInContext(ctx);
        const widget = ctx.document.getElementById("chatbot-widget");
        assert.notStrictEqual(widget, null, "Widget should be mounted for company when flag is on");
    });

    it("Role Guard: Admin role MUST NOT initialize the chatbot widget even if company flag is ON", () => {
        const ctx = createMockWindow({ role: "admin" }, { companyEnabled: true });
        runWidgetInContext(ctx);
        const widget = ctx.document.getElementById("chatbot-widget");
        assert.strictEqual(widget, null, "Widget should not be mounted for admin");
    });

    it("Role Guard: Guest (null user) DOES initialize the widget", () => {
        const ctx = createMockWindow(null);
        runWidgetInContext(ctx);
        const widget = ctx.document.getElementById("chatbot-widget");
        assert.notStrictEqual(widget, null, "Widget should be mounted for guest");
    });

    it("Role Guard: Student role DOES initialize the widget", () => {
        const ctx = createMockWindow({ role: "student" });
        runWidgetInContext(ctx);
        const widget = ctx.document.getElementById("chatbot-widget");
        assert.notStrictEqual(widget, null, "Widget should be mounted for student");
    });

    it("Role Guard: Developer role DOES initialize the widget", () => {
        const ctx = createMockWindow({ role: "developer" });
        runWidgetInContext(ctx);
        const widget = ctx.document.getElementById("chatbot-widget");
        assert.notStrictEqual(widget, null, "Widget should be mounted for developer");
    });

    // 2. Accessibility & DOM Structure Tests
    it("Accessibility: Trigger button and Dialog container attributes", () => {
        const ctx = createMockWindow(null);
        runWidgetInContext(ctx);

        const trigger = ctx.document.getElementById("chatbot-trigger");
        assert.notStrictEqual(trigger, null);
        assert.strictEqual(trigger.getAttribute("aria-label"), "Mở trợ lý tìm việc AI");
        assert.strictEqual(trigger.getAttribute("aria-expanded"), "false");
        assert.strictEqual(trigger.getAttribute("aria-controls"), "chatbot-dialog");

        const dialog = ctx.document.getElementById("chatbot-dialog");
        assert.notStrictEqual(dialog, null);
        assert.strictEqual(dialog.getAttribute("role"), "dialog");
        assert.strictEqual(dialog.getAttribute("aria-label"), "Trợ lý tìm việc JobMarketSV");
        assert.strictEqual(dialog.getAttribute("aria-hidden"), "true");
    });

    // 3. Open / Close / Escape & Focus Management
    it("Widget Interaction: Open via trigger button, close on Escape key", () => {
        const ctx = createMockWindow(null);
        runWidgetInContext(ctx);

        const trigger = ctx.document.getElementById("chatbot-trigger");
        const dialog = ctx.document.getElementById("chatbot-dialog");

        // Click to open
        trigger.dispatchEvent({ type: 'click' });
        assert.strictEqual(dialog.classList.contains("chatbot-dialog--open"), true);
        assert.strictEqual(dialog.getAttribute("aria-hidden"), "false");
        assert.strictEqual(trigger.getAttribute("aria-expanded"), "true");
        // Trigger button MUST be hidden and non-interactive when open to prevent mobile overlap
        assert.strictEqual(trigger.classList.contains("chatbot-trigger--hidden"), true);
        assert.strictEqual(trigger.getAttribute("aria-hidden"), "true");
        assert.strictEqual(trigger.getAttribute("tabindex"), "-1");

        // Press Escape
        ctx.document.dispatchEvent({ type: 'keydown', key: 'Escape', preventDefault: () => {} });
        assert.strictEqual(dialog.classList.contains("chatbot-dialog--open"), false);
        assert.strictEqual(dialog.getAttribute("aria-hidden"), "true");
        assert.strictEqual(trigger.getAttribute("aria-expanded"), "false");
        // Trigger button MUST reappear and restore interaction attributes
        assert.strictEqual(trigger.classList.contains("chatbot-trigger--hidden"), false);
        assert.strictEqual(trigger.getAttribute("aria-hidden"), null);
        assert.strictEqual(trigger.getAttribute("tabindex"), null);
        assert.strictEqual(ctx.getFocusedElement(), trigger, "Focus should be restored to trigger button");
    });

    // 4. Welcome State & Quick Prompts
    it("Welcome State: Renders title and 3 quick prompt chips", () => {
        const ctx = createMockWindow(null);
        runWidgetInContext(ctx);

        const welcomeCard = ctx.document.getElementById("chatbot-widget");
        assert.ok(welcomeCard);
        
        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1]; // messagesContainer
        const welcome = messages.children[0];
        assert.strictEqual(welcome.className, "chatbot-welcome-card");
        
        const prompts = welcome.children[2];
        assert.strictEqual(prompts.className, "chatbot-quick-prompts");
        assert.strictEqual(prompts.children.length, 3);
        assert.strictEqual(prompts.children[0].textContent, "Tìm việc theo ca");
        assert.strictEqual(prompts.children[1].textContent, "Cách ứng tuyển");
        assert.strictEqual(prompts.children[2].textContent, "Chuẩn bị CV");
    });

    it("Welcome State (Company): Renders company welcome title and 3 employer prompt chips", () => {
        const ctx = createMockWindow({ role: "company" }, { companyEnabled: true });
        runWidgetInContext(ctx);

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        const welcome = messages.children[0];
        assert.strictEqual(welcome.children[0].textContent, "👋 Xin chào Quý Doanh nghiệp!");
        
        const prompts = welcome.children[2];
        assert.strictEqual(prompts.children.length, 3);
        assert.strictEqual(prompts.children[0].textContent, "Hướng dẫn đăng tin tuyển dụng");
        assert.strictEqual(prompts.children[1].textContent, "Quy trình xác minh doanh nghiệp");
        assert.strictEqual(prompts.children[2].textContent, "Quản lý trạng thái ứng viên");
    });

    // 5. Safe Request Body & In-Memory History (No sensitive leaks)
    await itAsync("Security & Privacy: apiRequest receives only message and history slice (no JWT, profile, CV)", async () => {
        const ctx = createMockWindow({ role: "student", id: "stu-123", email: "stu@example.com" });
        ctx.setApiResponse({
            success: true,
            data: {
                answer: "Chào bạn, đây là các việc làm theo ca phù hợp.",
                job_links: [
                    { title: "Phục vụ quán cafe", company_name: "The Coffee House", url: "/viec-lam/job-coffee", salary: "25k/h" }
                ]
            }
        });
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        input.value = "Tìm việc làm ca tối tại Quận 1";

        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const apiCall = ctx.getLastApiCall();
        assert.notStrictEqual(apiCall, null);
        assert.strictEqual(apiCall.endpoint, "/assistant/chat");
        assert.strictEqual(apiCall.options.method, "POST");
        assert.strictEqual(apiCall.options.body.message, "Tìm việc làm ca tối tại Quận 1");
        assert.deepStrictEqual(apiCall.options.body.history, []);
        // Strict assertion: no user_id, email, token in body
        assert.strictEqual(apiCall.options.body.user_id, undefined);
        assert.strictEqual(apiCall.options.body.email, undefined);
        assert.strictEqual(apiCall.options.body.token, undefined);
    });

    // 6. XSS Immunity in User Messages & Bot Answers
    await itAsync("XSS Immunity: Untrusted HTML characters are safely treated as textContent", async () => {
        const ctx = createMockWindow(null);
        ctx.setApiResponse({
            success: true,
            data: {
                answer: "<script>alert('pwned')</script><b>Hello</b>",
                job_links: []
            }
        });
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        const xssPayload = "<img src=x onerror=alert(1)> & <script>";
        input.value = xssPayload;

        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        
        // Find user message bubble
        const userGroup = messages.children[1];
        assert.strictEqual(userGroup.className, "chatbot-msg-group chatbot-msg-group--user");
        assert.strictEqual(userGroup.children[0].textContent, xssPayload);
        // innerHTML must be escaped, never raw tags
        assert.ok(userGroup.children[0].innerHTML.includes('&lt;img'));
        assert.ok(!userGroup.children[0].innerHTML.includes('<img'));

        // Find bot message bubble
        const botGroup = messages.children[2];
        assert.strictEqual(botGroup.className, "chatbot-msg-group chatbot-msg-group--bot");
        assert.strictEqual(botGroup.children[0].textContent, "<script>alert('pwned')</script><b>Hello</b>");
        assert.ok(botGroup.children[0].innerHTML.includes('&lt;script&gt;'));
        assert.ok(!botGroup.children[0].innerHTML.includes('<script>'));
    });

    // 7. Internal Job Link Validation
    await itAsync("Link Validation: Only valid internal /viec-lam/:id routes are rendered as job cards", async () => {
        const ctx = createMockWindow(null);
        ctx.setApiResponse({
            success: true,
            data: {
                answer: "Danh sách việc làm:",
                job_links: [
                    { title: "Việc hợp lệ", url: "/viec-lam/valid-slug-123" },
                    { title: "XSS Link", url: "javascript:alert(document.cookie)" },
                    { title: "External Link", url: "https://attacker.com/malicious" },
                    { title: "Admin Path", url: "/admin/users" },
                    { title: "Relative Path", url: "../secret.txt" }
                ]
            }
        });
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        input.value = "Tìm việc";

        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        const botGroup = messages.children[2];
        const jobCardsContainer = botGroup.children[1];

        assert.notStrictEqual(jobCardsContainer, undefined);
        // Only 1 card should be created (the valid one)
        assert.strictEqual(jobCardsContainer.children.length, 1);
        assert.strictEqual(jobCardsContainer.children[0].href, "/viec-lam/valid-slug-123");
        assert.strictEqual(jobCardsContainer.children[0].children[0].textContent, "Việc hợp lệ");
    });

    // 8. In-Memory History Bounded to MAX_HISTORY_TURNS (10)
    await itAsync("History Limit: Conversation history sent to backend is bounded to 10 turns max", async () => {
        const ctx = createMockWindow(null);
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];

        // Simulate 12 turns
        for (let i = 1; i <= 12; i++) {
            ctx.setApiResponse({
                success: true,
                data: {
                    answer: `Trả lời vòng ${i}`,
                    job_links: []
                }
            });
            input.value = `Câu hỏi vòng ${i}`;
            await form.eventListeners['submit'][0]({ preventDefault: () => {} });
        }

        const lastCall = ctx.getLastApiCall();
        assert.strictEqual(lastCall.options.body.message, "Câu hỏi vòng 12");
        assert.ok(lastCall.options.body.history.length <= 10, `History length (${lastCall.options.body.history.length}) should not exceed 10`);
    });

    // 9. Error Mapping Tests
    await itAsync("Error Mapping: 429 Rate limit exceeded", async () => {
        const ctx = createMockWindow(null);
        ctx.setApiResponse({
            status: 429,
            success: false,
            errors: { error_code: "RATE_LIMIT_EXCEEDED" }
        });
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        input.value = "Hỏi nhiều lần";

        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        const errGroup = messages.children[2];
        const notice = errGroup.children[0];
        assert.strictEqual(notice.className, "chatbot-error-notice");
        assert.ok(notice.textContent.includes("Bạn đã gửi quá nhiều yêu cầu chat"));
    });

    await itAsync("Error Mapping: 403 Forbidden role", async () => {
        const ctx = createMockWindow(null);
        ctx.setApiResponse({
            status: 403,
            success: false,
            errors: { error_code: "FORBIDDEN_ROLE" }
        });
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        input.value = "Hỏi vai trò cấm";

        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        const notice = messages.children[2].children[0];
        assert.ok(notice.textContent.includes("chỉ hỗ trợ sinh viên"));
    });

    await itAsync("Error Mapping: COMPANY_CHAT_DISABLED error", async () => {
        const ctx = createMockWindow(null);
        ctx.setApiResponse({
            status: 403,
            success: false,
            errors: { error_code: "COMPANY_CHAT_DISABLED" }
        });
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        input.value = "Hỏi khi công ty bị tắt";

        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        const notice = messages.children[2].children[0];
        assert.ok(notice.textContent.includes("dành cho doanh nghiệp hiện đang tạm tắt"));
    });

    await itAsync("Error Mapping: Content blocked by safety filter", async () => {
        const ctx = createMockWindow(null);
        ctx.setApiResponse({
            status: 422,
            success: false,
            errors: { error_code: "CONTENT_BLOCKED" }
        });
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        input.value = "Nội dung không an toàn";

        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        const notice = messages.children[2].children[0];
        assert.ok(notice.textContent.includes("tiêu chuẩn an toàn"));
    });

    await itAsync("Error Mapping: 503 Service unavailable / Maintenance", async () => {
        const ctx = createMockWindow(null);
        ctx.setApiResponse({
            status: 503,
            success: false,
            errors: { error_code: "SERVICE_UNAVAILABLE" }
        });
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        input.value = "Hỏi khi tắt tính năng";

        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        const notice = messages.children[2].children[0];
        assert.ok(notice.textContent.includes("tạm tắt hoặc đang được bảo trì"));
    });

    await itAsync("Error Mapping: Network failure / Exception", async () => {
        const ctx = createMockWindow(null);
        ctx.setApiResponse(new Error("Network connection lost"));
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        input.value = "Mất mạng";

        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        const notice = messages.children[2].children[0];
        assert.ok(notice.textContent.includes("Không thể kết nối đến máy chủ"));
    });

    // 10. Feedback Interaction Tests
    await itAsync("Feedback Interaction: Thumbs up click calls POST /assistant/feedback with { rating: 'up' }", async () => {
        const ctx = createMockWindow(null);
        ctx.setApiResponse({
            success: true,
            data: {
                answer: "Câu trả lời mẫu tốt.",
                job_links: []
            }
        });
        runWidgetInContext(ctx);

        const form = ctx.document.getElementById("chatbot-dialog").children[2].children[0];
        const input = form.children[0];
        input.value = "Hỏi câu 1";
        await form.eventListeners['submit'][0]({ preventDefault: () => {} });

        const dialog = ctx.document.getElementById("chatbot-dialog");
        const messages = dialog.children[1];
        const botGroup = messages.children[2];
        const feedbackEl = botGroup.children[1]; // Feedback container
        assert.strictEqual(feedbackEl.className, "chatbot-feedback");

        const upBtn = feedbackEl.children[1];
        assert.strictEqual(upBtn.textContent, "👍");

        // Mock feedback API response
        ctx.setApiResponse({ success: true, message: "Cảm ơn bạn đã phản hồi." });

        // Click thumbs up
        await upBtn.eventListeners['click'][0]();

        const lastCall = ctx.getLastApiCall();
        assert.strictEqual(lastCall.endpoint, "/assistant/feedback");
        assert.strictEqual(lastCall.options.method, "POST");
        assert.strictEqual(lastCall.options.body.rating, "up");
        assert.ok(feedbackEl.textContent.includes("Cảm ơn"));
        assert.ok(feedbackEl.innerHTML.includes('class="chatbot-feedback--done"'), "Feedback done element must use chatbot-feedback--done class");
    });

    console.log(`\n=================================================================`);
    console.log(`   KẾT QUẢ: ${passed}/${total} BÀI TEST CHATBOT JS ĐẠT THÀNH CÔNG (${Math.round(passed/total*100)}% PASS)`);
    console.log(`=================================================================`);

    if (passed !== total) {
        process.exit(1);
    }
}

runAllTests();
