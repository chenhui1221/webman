"use strict";
const {createApp, reactive} = Vue;
import {fireConfetti, formatDate, xhrOnProgress, copyToClipboard,} from "./util.js?v=3.2.0";
import {imageChange, imageVary, imagine, checkStatus, saveStatus} from "./midjourney.js?v=3.2.2";
const win = window;
const {$, Push, TextEncoder, hljs, markdownit, userAvatar, addEventListener, location, setInterval, setTimeout, XMLHttpRequest,
    localStorage, alert, console, FormData, document, navigator, PointerEvent, scrollTo} = win;

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
                showAiInfo: false
            },
            showBuyLink: false,
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
                contextNum: 6,
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
                contextNum: 6
            },
            contextMenu: {
                top: 0,
                left: 0,
                roleId: 0,
            },
            sendMethod: "Enter", // Ctrl-Enter
            hoverMessageId: 0,
            isSlidedIn: false,
            isSlidedOut: false,
            isCompiled: true,
            showLoading: false,
            uploadPercent: 0,
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
        this.showBuyLink = location.host === "www.workerman.net";

        setInterval(() => {
            checkStatus(this, this.chat);
        }, 3000);
        checkStatus(this, this.chat);
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
                return !item.deleted && item.name.indexOf(this.keyword) !== -1;
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
                            saveStatus(message, data);
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
            checkStatus(this, this.chat);
            if (this.isMobile) {
                this.showAddressBook = false;
                this.isSlidedOut = false;
                this.isSlidedIn = true;
            }
        },
        regenerate(chat, message) {
            chat.messages = chat.messages.filter(item => item.id !== message.id);
            this.sendMessage(message.prompt, true);
        },
        sendMessage(content, withoutMessage) {
            const chat = this.chat;
            let model = chat.model || this.defaultParams.model;
            content = content || chat.content;
            if (content === "" || chat.loading) {
                return;
            }
            chat.content = "";
            this.scrollToBottom(true);
            const context = this.getContext();
            const userMessageId = this.genId();
            if (!withoutMessage) {
                chat.messages.push({
                    "id": userMessageId,
                    "type": "text",
                    "role": "user",
                    "created": new Date().getTime(),
                    "completed": true,
                    "content": content
                });
            }
            let assistantMessageId = this.genId();
            if (chat.model === "midjourney") {
                return imagine(this, chat, content);
            }
            const lastMessage = reactive({
                "id": assistantMessageId,
                "type": model,
                "subtype": "",
                "role": "assistant",
                "created": new Date().getTime(),
                "completed": false,
                "prompt": content,
                "content": this.chat.model === "dall.e" ? "生成中..." : ""
            });
            chat.messages.push(lastMessage);
            // 每个对话只保留最近40条数据
            chat.messages = chat.messages.slice(-40);
            chat.lastTime = new Date().getTime();
            this.saveData();
            chat.lastChunkIndex = 0;
            chat.loading = true;
            this.scrollToBottom(true);
            let host = "/app/ai/message/send";
            const headers = {"Content-Type": "application/json"};
            if (this.api.enable) {
                if (this.api.host) {
                    host = this.api.host;
                }
                if (this.api.key) {
                    headers.Authorization = "Bearer " + this.api.key;
                }
            }
            let maxTokens = chat.maxTokens || this.defaultParams.maxTokens;
            if (["gpt-3.5-turbo","gpt-3.5-turbo-0613", "gpt-4"].includes(model) &&
                this.getBytesLength(content) + this.getBytesLength(JSON.stringify(context)) > this.getModelMaxLength(model)) {
                model = model === "gpt-4" ? "gpt-4-32k" : "gpt-3.5-turbo-16k";
            }
            let messages = [];
            let length = 0;
            if (this.chat.rolePrompt) {
                messages.push({"role": "system", "content": this.chat.rolePrompt});
                length += this.getBytesLength(this.chat.rolePrompt);
            }
            length += this.getBytesLength(content);
            const maxTokenLength = this.getModelMaxLength(model);
            maxTokens = Math.ceil(Math.min(maxTokens, (maxTokenLength - length) / 1.5 - 10));
            const availableLength = maxTokenLength - maxTokens * 1.5;
            let tmp = [];
            for (let item of context.reverse()) {
                length += this.getBytesLength(item.content);
                if (length >= availableLength) {
                    break;
                }
                tmp.unshift(item);
            }
            messages = messages.concat(tmp);
            messages.push({"role": "user", "content": content});
            const useUserDefinedHost = this.api.enable && this.api.host === "";
            let data = {
                "max_tokens": maxTokens,
                "temperature": chat.temperature || this.defaultParams.temperature,
                "stream": true,
                "messages": messages,
                "model": model,
                "user_message_id": useUserDefinedHost ? undefined : userMessageId,
                "assistant_message_id": useUserDefinedHost ? undefined : assistantMessageId,
                "role_id": useUserDefinedHost ? undefined : chat.roleId,
            };
            data = JSON.stringify(data);
            $.ajax({
                url: host,
                data: data,
                type: "POST",
                dataType: "json",
                headers: headers,
                complete: () => {
                    let message = this.lastMessage(chat);
                    if (!chat.loading || message.id !== assistantMessageId) {
                        return;
                    }
                    chat.lastChunkIndex = 0;
                    chat.loading = false;
                    lastMessage.completed = true;
                    this.saveData();
                },
                success:  (res) => {
                    let message = this.lastMessage(chat);
                    if (!chat.loading || message.id !== assistantMessageId) {
                        return;
                    }
                    if (res.error && res.error.message) {
                        lastMessage.type = "text";
                        lastMessage.content = res.error.message;
                        const keywords = ["exceeded", "deactivated", "not active"];
                        for (const keyword of keywords) {
                            if (!userAvatar && res.error.message.includes(keyword)) {
                                lastMessage.content = "官方apikey余额不足，如有需要请 [购买此程序](https://www.workerman.net/app/view/ai) 自行部署使用。\n同时欢迎 [捐赠](/donate) 以便官方购买更多的apikey持续提供免费服务";
                            }
                        }
                    } else if (res.data && res.data[0] && res.data[0].url) {
                        lastMessage.content = `![](${res.data[0].url})`;
                    } else {
                        console.log(res);
                    }
                },
                xhr: xhrOnProgress((event) => {
                    let message = this.lastMessage(chat);
                    // 已经取消
                    if (!chat.loading || message.id !== assistantMessageId) {
                        return;
                    }
                    const xhr = event.target;
                    const {responseText} = xhr;
                    let thunks = responseText.substring(chat.lastChunkIndex);
                    chat.lastChunkIndex = responseText.length;
                    const arr = thunks.split("\n");
                    arr.forEach((chunk) => {
                        if (chunk === "") {
                            return;
                        }
                        chunk = chunk.substring(6).trim();
                        if (chunk === "" || chunk === "[DONE]") {
                            return;
                        }
                        try {
                            const data = JSON.parse(chunk);
                            if (message.error) {
                                message.content += message.error.message || "";
                            } else {
                                message.content += data.choices[0].delta.content || "";
                            }
                            this.scrollToBottom();
                        } catch (e) {}
                    });
                })
            });
        },
        translate(options) {
            const {content, success, complete} = options;
            let data = JSON.stringify({
                "max_tokens": 500,
                "temperature": 0,
                "messages": [
                    {"role": "user", "content": content}
                ],
                "model": "gpt-3.5-turbo",
            });
            $.ajax({
                url: "/app/ai/message/translate",
                data: data,
                type: "POST",
                dataType: "json",
                headers: {"Content-Type": "application/json"},
                complete: (xhr, status) => {
                    complete(xhr, status);
                },
                success: (res) => {
                    success(res);
                },
            });
        },
        deleteMessage(id) {
            this.chat.messages = this.chat.messages.filter(message => message.id !== id);
            this.saveData();
        },
        imageVary(message, action, index) {
            imageVary(this, message, action, index);
        },
        imageChange(message, options, index) {
            imageChange(this, message, options, index);
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
            this.$refs.uploadInput.click();
        },
        uploadImage(event) {
            const file = event.target.files[0];
            this.doUploadImage(file);
            this.$refs.uploadForm.reset();
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
                chat.loading = false;
                chat.lastChunkIndex = 0;
                chat.lastTime = chat.lastTime || 0;
                chat.pinned = chat.pinned || 0;
                if (chat.model === "midjourney" && !chat.midjourneyHeightRatio) {
                    chat.midjourneyWidthRatio = chat.midjourneyHeightRatio = 1;
                    chat.midjourneyChaos = 0;
                    chat.midjourneyStylize = 100;
                }
                for (const message of chat.messages) {
                    message.completed = true;
                    message.buttonBits = message.buttonBits || "0000000000000000";
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
            roleInfo = roleInfo instanceof PointerEvent ? this.roleInfo : roleInfo;
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
        destroy() {
            this.cancel();
            this.chat.messages = [];
            this.formatRoles();
            fireConfetti();
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
            let contextNum = parseInt(this.chat.contextNum || this.defaultParams.contextNum);
            if (contextNum !== 0) {
                this.chat.messages.slice(-contextNum).forEach(function (message) {
                    context.push({role: message.role, content: message.content || ""});
                });
            }
            return context;
        },
        listenCopyCodes() {
            $(document).on("click", ".hljs .block-copy",  () => {
                this.copyToClipboard($(this).parent().next().text());
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
            const messageBox = this.$refs.messageBox;
            const behavior = smooth !== false ? "smooth" : "auto";
            if (force || messageBox.scrollHeight - messageBox.clientHeight <= messageBox.scrollTop + 100) {
                this.$nextTick(() => {
                    messageBox.scrollTo({top: messageBox.scrollHeight, behavior: behavior});
                });
            }
        },
        scrollToTop(smooth) {
            const behavior = smooth !== false ? "smooth" : "auto";
            this.$refs.messageBox.scrollTo({top: 0, behavior: behavior});
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
        getBytesLength(text) {
            this.encoder = this.encoder || new TextEncoder();
            return this.encoder.encode(text).length;
        },
        getModelMaxLength(model) {
            const base = 6000;
            const match = model.match(/-(\d+)k$/);
            if (!match) {
                return base;
            }
            return parseInt(match[1]) * base / 4;
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
        }
    }
}).mount("#app");