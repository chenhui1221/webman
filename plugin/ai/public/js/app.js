"use strict";
const {createApp, reactive} = Vue;
import {fireConfetti, formatDate, xhrOnProgress, copyToClipboard, historySave, historyList, historyGet, historyDelete, speak} from "./util.js?v=3.4.1";
import {mjImageChange, mjImageVary, mjImagine, mjCheckStatus, mjSaveStatus} from "./midjourney.js?v=3.4.4";
import handlers from "./model.handlers.js?v=3.4.0";

const win = window;
const {$, Push, hljs, markdownit, addEventListener, location, setInterval, setTimeout, XMLHttpRequest,
    localStorage, alert, FormData, document, navigator, PointerEvent, scrollTo} = win;

win.ai = createApp({
    beforeMount() {
        this.listenCopyCodes();
        this.initMarkdown();
        this.listenImageClick();
    },
    data() {
        return {
            module: "chat",
            isMobile: false,
            smallWindow: false,
            roleList: [],
            roleId: 1,
            loginUser: {
                username: "",
                nickname: "",
                avatar: "/app/ai/avatar/user.png",
                vip: false,
                vipExpiredAt: ""
            },
            keyword: "",
            showAddressBook: true,
            box: {
                showContextMenu: false,
                showParams: false,
                showApiKey: false,
                showSendMethod: false,
                showRoleInfo: false,
                showMore: false,
                showAiInfo: false,
                showHistory: false,
            },
            api: {
              key: "",
              host: "",
              enable: false
            },
            setting: {
                defaultModels: {},
                dbEnabled: false,
                enabledAlipay: false,
                enabledWechat: false,
                enablePayment: false,
            },
            defaultParams: {
                model: "gpt-3.5-turbo-0613",
                maxTokens: 2000,
                temperature: 0.5,
                contextNum: 5,
            },
            roleInfo: {
                roleId: 0,
                name: "",
                desc: "",
                avatar: "",
                pinned: 0,
                lastTime: 0,
                greeting: "",
                rolePrompt: "",
                model: "gpt-3.5-turbo-0613",
                maxTokens: 2000,
                temperature: 0.5,
                contextNum: 5
            },
            contextMenu: {
                top: 0,
                left: 0,
                roleId: 0,
            },
            history: [],
            historyKeyword: "",
            sendMethod: "Enter", // Ctrl-Enter
            hoverMessageId: 0,
            isSlidedIn: false,
            isSlidedOut: false,
            isCompiled: true,
            showLoading: false,
            uploadPercent: 0,
            supportSpeak: false,
            iframe: {
                user: "/app/ai/user",
                vip: "/app/ai/user/vip",
                market: "/app/ai/market",
            }
        };
    },
    mounted() {
        addEventListener("resize", this.checkMobile);
        addEventListener("resize", this.setFontSize);
        this.checkMobile();
        this.setFontSize();
        this.loadSetting();
        this.loadData();
        this.loadUserInfo(()=>{
            this.listen();
        });
        this.listenLink();
        setInterval(() => {
            mjCheckStatus(this, this.chat);
        }, 3000);
        mjCheckStatus(this, this.chat);
        this.supportSpeak = 'speechSynthesis' in win;
    },
    watch: {
        "chat.model": function () {
            this.formatRoles();
        }
    },
    computed: {
        chat() {
            return this.roleList.find(item => item.roleId === this.roleId) || {};
        },
        filter() {
            return [...this.roleList.filter((item) => {
                return !item.deleted && item.name && item.name.indexOf(this.keyword) !== -1;
            })].sort((a, b) => {
                if (a.pinned !== b.pinned) {
                    return b.pinned - a.pinned;
                }
                if(b.lastTime - a.lastTime) {
                    return b.lastTime - a.lastTime;
                }
                return b.installed - a.installed;
            });
        },
        historyItems() {
            return this.history.filter(item => {
                return item.messages.filter(message => !this.historyKeyword || message.content.indexOf(this.historyKeyword) !== -1).length;
            });
        },
        showShadowLayer() {
            return Object.values(this.box).some(value => value);
        },
        isSmallWindow() {
            return this.smallWindow && !this.isMobile;
        }
    },
    methods: {
        loadData() {
            const data = JSON.parse(localStorage.getItem("ai.data") || "{}");
            ["roleId", "api", "roleList", "sendMethod", "smallWindow"].forEach((name) => {
                if (typeof data[name] !== "undefined") {
                    this[name] = data[name];
                }
            });
            this.formatRoles();
            this.loadRoles();
            this.scrollToBottom(true, false);
        },
        loadSetting(cb) {
            $.ajax({
                url: "/app/ai/setting",
                success: (res) => {
                    if (res.code) {
                        return alert(res.msg);
                    }
                    this.setting = res.data;
                    if (cb) {
                        cb(res.data)
                    }
                }
            });
        },
        loadUserInfo(cb) {
            $.ajax({
                url: "/app/ai/user/info",
                success: (res) => {
                    if (res.code) {
                        return alert(res.msg);
                    }
                    this.loginUser = res.data;
                    if (cb) {
                        cb();
                    }
                }
            });
        },
        loadRoles(reset, cb) {
            $.ajax({
                url: "/app/ai/roles",
                success: (res) => {
                    if (res.code) {
                        return alert(res.msg);
                    }
                    if (reset) {
                        this.roleList = [];
                    }
                    const roles = res.data;
                    this.roleList = this.roleList.concat(roles.filter(role => !this.roleList.some(chat => chat.roleId === role.roleId)));
                    this.formatRoles();
                    this.scrollToBottom(true, false);
                    if(cb) {
                        cb();
                    }
                }
            });
        },
        listen() {
            if (!this.loginUser.apikey) {
                return;
            }
            const https = location.protocol === "https:";
            this.connection = new Push({
                "url": https ? "wss://" + location.hostname : "ws://" + location.hostname + ":3131",
                "app_key": this.loginUser.apikey,
            });
            const channel = this.connection.subscribe(this.loginUser.sid);
            channel.on("mj-state-change", (data) => {
                for (let chat of this.roleList) {
                    for (let message of chat.messages) {
                        if (message.taskId === data.id) {
                            mjSaveStatus(message, data);
                            return this.saveData();
                        }
                    }
                }
            });
            channel.on("sensitive-content", (data) => {
                const chat = this.roleList.find(item => parseInt(item.roleId) === parseInt(data.roleId));
                if (chat) {
                    chat.loading = false;
                    chat.messages = [];
                    chat.messages.push({
                        "id": this.genId(),
                        "type": chat.model,
                        "subtype": "",
                        "role": "assistant",
                        "created": new Date().getTime(),
                        "completed": false,
                        "prompt": "",
                        "content": "检测到敏感内容，对话已经清理",
                    });
                    this.saveData();
                }
            });
        },
        switchModule(name) {
            this.module = name;
            this.hideAll();
        },
        switchRoleId(roleId) {
            this.roleId = roleId;
            this.saveData();
            this.scrollToBottom(true, false);
            mjCheckStatus(this, this.chat);
            if (this.isMobile) {
                this.showAddressBook = false;
                this.isSlidedOut = false;
                this.isSlidedIn = true;
            }
        },
        regenerate(chat, message) {
            chat.messages = chat.messages.filter(item => item.id !== message.id);
            if (chat.messages[chat.messages.length - 1]['content'] === message.prompt) {
                chat.messages.splice(-1, 1);
            }
            this.sendMessage(message.prompt);
        },
        sendMessage(content) {
            const chat = this.chat;
            content = content || chat.content;
            if (content === "" || chat.loading) {
                return;
            }
            if (!chat.id) {
                chat.id = new Date().getTime();
                chat.title = content.substring(0, 15);
                chat.time = new Date().getTime();
            }
            chat.content = "";
            this.scrollToBottom(true);
            const context = this.getContext();
            const userMessageId = this.genId();
            chat.messages.push({
                "id": userMessageId,
                "type": "text",
                "role": "user",
                "created": new Date().getTime(),
                "completed": true,
                "content": content
            });
            let assistantMessageId = this.genId();
            if (chat.model === "midjourney") {
                return mjImagine(this, chat, content);
            }
            const lastMessage = reactive({
                "id": assistantMessageId,
                "type": chat.model,
                "subtype": "",
                "role": "assistant",
                "created": new Date().getTime(),
                "completed": false,
                "prompt": content,
                "content": chat.model === "dall.e" ? "生成中..." : ""
            });
            chat.messages.push(lastMessage);
            // 每个对话只保留最近100条数据
            chat.messages = chat.messages.slice(-100);
            chat.lastTime = new Date().getTime();
            this.saveData();
            chat.loading = true;
            this.scrollToBottom(true);
            let url = "/app/ai/message/send";
            const headers = {"Content-Type": "application/json"};
            if (/gpt/.test(chat.model) && this.api.enable) {
                if (this.api.host) {
                    url = this.api.host;
                }
                if (this.api.key) {
                    headers.Authorization = "Bearer " + this.api.key;
                }
            }

            if (this.chat.rolePrompt) {
                context.unshift({"role": "system", "content": this.chat.rolePrompt});
            }
            context.push({"role": "user", "content": content});

            const modelType = this.getModelType(chat.model);
            const handler = handlers[modelType];
            if (!handler) {
                lastMessage.content = "未找到模型类型" + modelType;
                lastMessage.completed = true;
                chat.loading = false;
                return;
            }
            const {model, messages} = handler.prepare(chat.model, context);
            const useUserDefinedHost = this.api.enable && this.api.host;
            let data = {
                "temperature": chat.temperature || this.defaultParams.temperature,
                "stream": true,
                "messages": messages,
                "model": model,
                "chat_id": useUserDefinedHost ? undefined : chat.id,
                "user_message_id": useUserDefinedHost ? undefined : userMessageId,
                "assistant_message_id": useUserDefinedHost ? undefined : assistantMessageId,
                "role_id": useUserDefinedHost ? undefined : chat.roleId,
            };
            data = JSON.stringify(data);
            $.ajax({
                url: url,
                data: data,
                type: "POST",
                dataType: "json",
                headers: headers,
                complete: () => {
                    let message = this.lastMessage(chat);
                    if (!chat.loading || message.id !== assistantMessageId) {
                        return;
                    }
                    lastMessage.lastChunkIndex = 0;
                    chat.loading = false;
                    lastMessage.completed = true;
                    this.saveData();
                },
                success: (res) => {
                    let message = this.lastMessage(chat);
                    if (!chat.loading || message.id !== assistantMessageId) {
                        return;
                    }
                    handler.success(res, message);
                },
                xhr: xhrOnProgress((event) => {
                    let message = this.lastMessage(chat);
                    if (!chat.loading || message.id !== assistantMessageId) {
                        return;
                    }
                    const xhr = event.target;
                    const {responseText} = xhr;
                    handler.progress(message, responseText);
                    this.scrollToBottom();
                })
            });
        },
        getModelType(model) {
            for (let modelType in handlers) {
                if ((handlers[modelType]['models']||[]).includes(model)) {
                    return modelType;
                }
            }
            return model.split('-')[0];
        },
        translate(options) {
            const {content, complete} = options;
            let data = JSON.stringify({
                "max_tokens": 500,
                "temperature": 0.1,
                "messages": [
                    {"role": "user", "content": content}
                ]
            });
            $.ajax({
                url: "/app/ai/message/translate",
                data: data,
                type: "POST",
                dataType: "json",
                headers: {"Content-Type": "application/json"},
                complete: (xhr, status) => {
                    if (status !== "success") {
                        return complete("", status);
                    }
                    const res = JSON.parse(xhr.responseText);
                    if (res.error && res.error.message) { // error
                        return complete("", res.error.message);
                    }
                    if (res.choices && res.choices[0] && res.choices[0].message && res.choices[0].message.content) { // gpt
                        return complete(res.choices[0].message.content);
                    }
                    if (res.output && res.output.text) { // qwen-plus
                        return complete(res.output.text);
                    }
                    if (res.result) { //ernie-bot-turbo
                        return complete(res.result);
                    }
                }
            });
        },
        deleteMessage(id) {
            this.chat.messages = this.chat.messages.filter(message => message.id !== id);
            this.saveData();
        },
        mjImageVary(message, action, index) {
            mjImageVary(this, message, action, index);
        },
        mjImageChange(message, options, index) {
            mjImageChange(this, message, options, index);
        },
        handleDrop(event) {
            const files = event.dataTransfer.files;
            for (let i = 0; i < Math.min(files.length, 5); i++) {
                const file = files[i];
                if (file.type.startsWith("image")) {
                    this.doUploadImage(file);
                    event.preventDefault();
                }
            }
        },
        handlePaste(event) {
            const items = event.clipboardData.items;
            for (let i = 0; i < Math.min(items.length, 5); i++) {
                const item = items[i];
                if (item.type.indexOf("image") !== -1) {
                    const file = item.getAsFile();
                    this.doUploadImage(file);
                }
            }
        },
        doUploadImage(file, cb) {
            const formData = new FormData();
            if (file.size > 10*1024*1024) {
                return alert("单个文件不能大于10M");
            }
            formData.append("image", file);
            $.ajax({
                url: "/app/ai/upload/image",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                xhr: () => {
                    const xhr = new XMLHttpRequest();
                    xhr.upload.addEventListener("progress", (event) => {
                        if (event.lengthComputable) {
                            this.uploadPercent = Math.round((event.loaded / event.total) * 100);
                        }
                    }, false);
                    return xhr;
                },
                success: (res) => {
                    if (cb) {
                        cb(res);
                    }
                    if(!res.code) {
                        this.chat.content += (/\s$/.test(this.chat.content) ? "" : "\n") + location.protocol + "//" + location.host + res.data.url + "\n";
                    }
                }
            });
        },
        openUploadImage() {
            this.$refs["uploadInput"].click();
        },
        uploadImage(event) {
            const file = event.target.files[0];
            this.doUploadImage(file);
            this.$refs["uploadForm"].reset();
        },
        openContextMenu(roleId, event) {
            this.contextMenu.roleId = roleId;
            const winHeight = win.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
            const contextMenuHeight = 110;
            this.contextMenu.top = event.clientY > winHeight - contextMenuHeight ? event.clientY - contextMenuHeight : event.clientY;
            this.contextMenu.left = event.clientX + 5;
            this.box.showContextMenu = true;
            event.preventDefault();
        },
        closeContextMenu() {
            this.contextMenu.roleId = 0;
            this.box.showContextMenu = false;
        },
        formatRoles() {
            for (const chat of this.roleList) {
                if (!chat.messages) {
                    chat.messages = [];
                }
                if (!chat.messages.length && chat.greeting) {
                    chat.messages = [{
                        "role": "assistant",
                        "created": new Date().getTime(),
                        "content":  chat.greeting
                    }];
                }
                for (let message of chat.messages) {
                    if (message.choices) {
                        message.content = message.choices[0].delta.content;
                        message.choices = null;
                    }
                }
                chat.content = chat.content || "";
                chat.loading = chat.loading || false;
                chat.lastTime = chat.lastTime || 0;
                chat.pinned = chat.pinned || 0;
                if (chat.model === "midjourney" && !chat.midjourneyHeightRatio) {
                    chat.midjourneyWidthRatio = chat.midjourneyWidthRatio || 1;
                    chat.midjourneyHeightRatio = chat.midjourneyHeightRatio || 1;
                    chat.midjourneyChaos = chat.midjourneyChaos || 0;
                    chat.midjourneyStylize = chat.midjourneyStylize || 100;
                }
                for (const message of chat.messages) {
                    message.completed = message.completed || true;
                    if (chat.model === "midjourney") {
                        message.buttonBits = message.buttonBits || "0000000000000000";
                    }
                }
            }
        },
        sendMethodSelect(item) {
            this.box.showSendMethod = false;
            this.sendMethod = item;
            this.saveData();
        },
        showPanel(name) {
            this.hideAll();
            this.box["show" + name] = true;
        },
        hideAll() {
            if (this.box.showParams) {
                this.saveData();
            }
            for (let key in this.box) {
                this.box[key] = false;
            }
            this.closeContextMenu();
        },
        showRoleInfoBox() {
            this.clearRoleInfo();
            this.box.showRoleInfo = true;
        },
        clearRoleInfo() {
            this.roleInfo.roleId = 0;
            this.roleInfo.avatar = "/app/ai/avatar/ai.png";
            this.roleInfo.name = this.roleInfo.desc = this.roleInfo.greeting = this.roleInfo.rolePrompt = "";
            this.roleInfo.model = 'gpt-3.5-turbo-0613';
        },
        editRole(roleId) {
            this.clearRoleInfo();
            this.roleInfo.roleId = roleId;
            for (const item of this.roleList) {
                if (item.roleId === roleId) {
                    this.roleInfo = Object.assign({}, item);
                    break;
                }
            }
            this.closeContextMenu();
            this.box.showRoleInfo = true;
        },
        deleteRole(roleId) {
            for (const item of this.roleList) {
                if (item.roleId === roleId) {
                    item.deleted = true;
                    break;
                }
            }
            this.saveData();
            this.closeContextMenu();
            const role = this.roleList.find(role => !role.deleted);
            if (role) {
                this.roleId = role.roleId;
            }
        },
        pinRole(roleId) {
            for (const item of this.roleList) {
                if (item.roleId === roleId) {
                    item.pinned = item.pinned ? 0 : 1;
                    break;
                }
            }
            this.saveData();
            this.closeContextMenu();
        },
        saveRole(roleInfo) {
            this.hideAll();
            const time = new Date().getTime();
            roleInfo = roleInfo || this.roleInfo;
            roleInfo.roleId = roleInfo.roleId || time;
            roleInfo.pinned = 0;
            roleInfo.deleted = false;
            roleInfo.lastTime = new Date().getTime();
            const index = this.roleList.findIndex(item => item.roleId === roleInfo.roleId);
            if (index !== -1) {
                this.roleList[index] = Object.assign({}, this.roleList[index], roleInfo);
                this.saveData();
                return;
            }
            roleInfo.messages = [];
            this.roleList.push(Object.assign({}, roleInfo));
            this.formatRoles();
            this.saveData();
        },
        saveData(key, value) {
            if (key) {
                this[key] = value;
            }
            localStorage.setItem("ai.data", JSON.stringify({
                roleId: this.roleId,
                api: this.api,
                roleList: this.roleList,
                sendMethod: this.sendMethod,
                smallWindow: this.smallWindow,
            }));
        },
        uploadAvatar() {
            const formdata = new FormData();
            formdata.append("avatar", $("#avatar")[0].files[0]);
            $.ajax({
                url: "/app/ai/upload/avatar",
                type: "post",
                contentType: false,
                processData: false,
                data: formdata,
                success: (res) => {
                    this.roleInfo.avatar = res.data.url;
                }
            });
        },
        cancel() {
            this.chat.loading = false;
            let message = this.lastMessage(this.chat);
            if(message) {
                message.completed = true;
            }
            this.saveData();
        },
        async newChat() {
            this.cancel();
            const chat = this.chat;
            if (chat.id) {
                await historySave(chat.roleId, chat.id, chat.title, chat.time, chat.messages);
            }
            this.chat.messages = [];
            this.formatRoles();
            fireConfetti();
            this.chat.id = 0;
            this.saveData();
        },
        async showHistory(roleId) {
            this.box.showHistory = !this.box.showHistory;
            if (!this.box.showHistory) {
                return;
            }
            this.history = await historyList(roleId);
        },
        async deleteHistory(roleId, chatId) {
            await historyDelete(roleId, chatId);
            this.history = await historyList(roleId);
        },
        async historyGet(roleId, chatId) {
            const chat = this.chat;
            if (chat.id) {
                await historySave(chat.roleId, chat.id, chat.title, chat.time, chat.messages);
            }
            const {title, messages} = await historyGet(roleId, chatId);
            chat.title = title;
            chat.messages = messages;
            chat.id = chatId;
            this.hideAll();
            this.saveData();
        },
        lastMessage(chat) {
            return chat.messages[chat.messages.length - 1];
        },
        resetSystem() {
            localStorage.clear();
            this.loadRoles(true, () => {
                fireConfetti();
            });
            this.hideAll();
        },
        getContext() {
            let context = [];
            let contextNum = parseInt(this.chat.contextNum || this.defaultParams.contextNum) * 2;
            if (contextNum !== 0) {
                this.chat.messages.slice(-contextNum).forEach(function (message) {
                    context.push({role: message.role, content: message.content || ""});
                });
            }
            return context;
        },
        listenCopyCodes() {
            $(document).on("click", ".hljs .block-copy",  (event) => {
                this.copyToClipboard($(event.target).parent().next().text());
            });
        },
        copyToClipboard(content) {
            copyToClipboard(content);
        },
        listenImageClick() {
            $(document).on("click", ".message-list .message-body img", function () {
                let imgUrl = $(this).attr("src");
                const imgExt = imgUrl.split(".").pop();
                if (imgExt.length <= 4) {
                    imgUrl = imgUrl.replace("-sm.", "-lg.");
                }
                $(".overlay img").attr("src", imgUrl);
                $(".img-preview").show();
            });
            $(document).on("click", ".overlay, .close", function () {
                $(".overlay").hide();
            });
        },
        initMarkdown() {
            this.md = markdownit().set({
                linkify: false,
                breaks: true,
                html: false,
                highlight: function (str, lang) {
                    const header = `<div class="d-flex justify-content-end align-items-center" style="margin-top:-10px;margin-right:-8px;"><span class="text-secondary">${lang}</span><span class="block-copy ml-2 iconfont iconfont-bg"></span></div>`;
                    if (lang && hljs.getLanguage(lang)) {
                        return "<pre class=\"hljs\">" + header + "<code>" + hljs.highlight(str, {language: lang}).value + "</code></pre>";
                    }
                    return "<pre class=\"hljs\">" + header + "<code>" + hljs.highlightAuto(str).value + "</code></pre>";
                }
            });
        },
        markdown(content) {
            return this.md.render(content||'');
        },
        handleEnter(event) {
            if ((event.key === this.sendMethod && !event.ctrlKey) || (event.key !== this.sendMethod && event.ctrlKey)) {
                event.preventDefault();
                this.sendMessage();
            }
        },
        scrollToBottom(force, smooth) {
            const messageBox = this.$refs["messageBox"];
            const behavior = smooth !== false ? "smooth" : "auto";
            if (force || messageBox.scrollHeight - messageBox.clientHeight <= messageBox.scrollTop + 100) {
                this.$nextTick(() => {
                    messageBox.scrollTo({top: messageBox.scrollHeight, behavior: behavior});
                });
            }
        },
        scrollToTop(smooth) {
            const behavior = smooth !== false ? "smooth" : "auto";
            this.$refs["messageBox"].scrollTo({top: 0, behavior: behavior});
        },
        genId() {
            return new Date().getTime() + String(Math.floor(Math.random() * 1000));
        },
        formatDate(timestamp) {
            return formatDate(timestamp);
        },
        checkMobile() {
            this.isMobile = win.innerWidth <= 768; // 假设小于768px的宽度为移动端
        },
        handleInputFocus() {
            // 修复苹果浏览器键盘遮挡输入框问题
            const isSafari = /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);
            if (!this.isMobile || !isSafari) {
                return;
            }
            setTimeout(() => {
                if (!this.innerHeight) {
                    this.innerHeight = win.innerHeight < 500 ? win.innerHeight : 395;
                } else {
                    this.innerHeight = win.innerHeight < 500 ? win.innerHeight : this.innerHeight;
                }
                scrollTo(0, document.body.scrollHeight - this.innerHeight);
            }, 100);
        },
        slideOut() {
            setTimeout(() => {
                this.showAddressBook = true;
            }, 80);
            this.isSlidedOut = true;
            this.isSlidedIn = false;
        },
        setFontSize() {
            if (!this.isMobile) {
                document.documentElement.style.fontSize = "15px";
                return;
            }
            const screenWidth = win.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            const baseWidth = 414; // 基准宽度
            const baseFontSize = 17; // 基准字体大小
            const fontSize = Math.min(screenWidth * baseFontSize / baseWidth, baseFontSize);
            document.documentElement.style.fontSize = fontSize + "px";
        },
        listenLink() {
            const ai = this;
            $(document).on("click", ".message-list a", function (e) {
                const link = $(this);
                if (!ai.isMobile) {
                    if (link.attr("href").startsWith("/app/ai/user/vip")) {
                        ai.switchModule("vip");
                        e.preventDefault();
                        return false;
                    }
                    if (link.attr("href").startsWith("/app/ai/user")) {
                        ai.switchModule("me");
                        e.preventDefault();
                        return false;
                    }
                }
                link.attr("target", "_blank");
            });
        },
        logout() {
            $.ajax({
                url: '/app/user/logout',
                success: () => {
                    this.loadUserInfo();
                    this.switchModule('chat');
                    this.$nextTick(() => {
                        this.switchModule('me');
                    });
                }
            });
            this.hideAll();
        },
        speak(content) {
            speak(content);
        }
    }
}).mount("#app");