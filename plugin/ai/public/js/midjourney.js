"use strict";
/**
 * midjourney作图相关
 */
import {hasChinese, setBit} from "./util.js";
const {reactive} = Vue;
const $ = window.$;

// 获取midjourney参数
export function getParams(chat, prompt) {
    let params = "";
    if (!prompt.includes("--ar") && chat.midjourneyWidthRatio !== chat.midjourneyHeightRatio) {
        params = " --ar " + (chat.midjourneyWidthRatio + ":" + chat.midjourneyHeightRatio);
    }
    if (!prompt.includes("--c") && !prompt.includes("--chaos") && parseInt(chat.midjourneyChaos) !== 0) {
        params += " --c " + chat.midjourneyChaos;
    }
    if (!prompt.includes("--s") && !prompt.includes("--stylize") && parseInt(chat.midjourneyStylize) !== 100) {
        params += " --s " + chat.midjourneyStylize;
    }
    return params;
}

// 发送作图消息
function sendMessage(ai, chat, content, lastMessage) {
    content += getParams(chat, content);
    chat.messages = chat.messages.slice(-100); // 保留100条
    chat.lastTime = new Date().getTime();
    ai.saveData();
    chat.loading = true;
    ai.scrollToBottom(true);
    const messages = [{"role": "user", "content": content}];
    let data = {messages, model: chat.model};
    const userMessage = chat.messages[chat.messages.length - 2];
    data["user_message_id"] = userMessage && userMessage.role === "user" ? userMessage.id : null;
    data["assistant_message_id"] = lastMessage.id;
    data["role_id"] = chat.roleId;
    data["raw_prompt"] = lastMessage.rawPrompt;
    data = JSON.stringify(data);
    $.ajax({
        url: "/app/ai/midjourney/imagine",
        data: data,
        type: "POST",
        dataType: "json",
        headers: {"Content-Type": "application/json"},
        complete: () => {
            chat.loading = false;
            lastMessage.completed = true;
            ai.saveData();
        },
        success: (res) => {
            lastMessage.id = lastMessage.taskId = res.result;
            if (res.error && res.error.message) {
                lastMessage.type = "text";
                lastMessage.content = res.error.message;
            } else if (![1, 22].includes(res.code)) {
                lastMessage.type = "text";
                lastMessage.content = res.error || res.description;
            }
        }
    });
}

// 作图
export function imagine(ai, chat, content) {
    // 产生一条作图消息
    const lastMessage = reactive({
        "id": ai.genId(),
        "type": "midjourney",
        "subtype": "multi",
        "prompt": content,
        "rawPrompt": content,
        "role": "assistant",
        "created": new Date().getTime(),
        "completed": false,
        "content": "",
        "buttonBits" : "0000000000000000",
    });
    chat.messages.push(lastMessage);
    // 没有中文则直接发送mj请求
    if (!hasChinese(content)) {
        return sendMessage(ai, chat, content, lastMessage);
    }
    // 有中文则先翻译再发请求
    ai.translate({
        content: content,
        success: (res) => {
            if (res.error && res.error.message) {
                lastMessage.type = "text";
                lastMessage.content = "中文翻译成英文时出现错误: " + res.error.message;
                ai.saveData();
                return;
            }
            lastMessage.prompt = res.choices[0].message.content.replace("-- ", "--");
            sendMessage(ai, chat, lastMessage.prompt, lastMessage);
        },
        complete: () => {
            chat.loading = false;
            lastMessage.completed = true;
        }
    });
}

// 选择或变换
export function imageChange(ai, message, action, index) {
    const chat = ai.chat;
    const lastMessage = reactive({
        "id": ai.genId(),
        "type": "midjourney",
        "subtype": action === "UPSCALE" ? "single" : "multi",
        "prompt": message.prompt,
        "role": "assistant",
        "created": new Date().getTime(),
        "completed": false,
        "content": "",
        "buttonBits": "0000000000000000",
    });
    const position = ["UPSCALE", "VARIATION"].indexOf(action);
    message.buttonBits = position !== -1 ? setBit(message.buttonBits, position * 4 + index - 1, 1) : setBit(message.buttonBits, 8, 1);
    chat.messages.push(lastMessage);
    chat.lastTime = new Date().getTime();
    ai.saveData();
    chat.loading = true;
    ai.scrollToBottom(true);
    let host = "/app/ai/midjourney/change";
    $.ajax({
        url: host,
        data: {action, taskId: message.taskId, index},
        type: "POST",
        dataType: "json",
        complete: () => {
            chat.loading = false;
            lastMessage.completed = true;
            ai.saveData();
        },
        success: (res) => {
            lastMessage.taskId = res.result;
            if (res.error && res.error.message) {
                lastMessage.type = "text";
                lastMessage.content = res.error.message;
            } else if (![1, 22].includes(res.code)) {
                lastMessage.type = "text";
                lastMessage.content = res.description;
            }
        }
    });
}

// 垫图变换
export function imageVary(ai, message, options, index) {
    const chat = ai.chat;
    const lastMessage = reactive({
        "id": ai.genId(),
        "type": "midjourney",
        "subtype": "multi",
        "prompt": message.prompt,
        "role": "assistant",
        "created": new Date().getTime(),
        "completed": false,
        "content": "",
        "buttonBits":  "0000000000000000",
    });
    message.buttonBits = setBit(message.buttonBits, index - 1, 1);
    chat.messages.push(lastMessage);
    chat.lastTime = new Date().getTime();
    ai.saveData();
    chat.loading = true;
    ai.scrollToBottom(true);
    let optionsString = "";
    for (const key in options) {
        optionsString += `${key} ${options[key]} `;
    }
    $.ajax({
        url: "/app/ai/midjourney/imagine",
        data: {messages: [{content: message.prompt + " " + optionsString}], url: message.content, model: "midjourney"},
        type: "POST",
        dataType: "json",
        complete: () => {
            chat.loading = false;
            lastMessage.completed = true;
            ai.saveData();
        },
        success: function (res) {
            lastMessage.taskId = res.result;
            if (res.error && res.error.message) {
                lastMessage.type = "text";
                lastMessage.content = res.error.message;
            } else if (![1, 22].includes(res.code)) {
                lastMessage.type = "text";
                lastMessage.content = res.description;
            }
        }
    });
}


// 将作图状态保存到消息中
export function saveStatus(message, data) {
    message.progress = data.progress;
    message.content = data.imageUrl;
    if (data.failReason) {
        message.progress = "100%";
        message.type = "text";
        message.content = data.failReason;
    }
}

// 检查作图任务状态
function check(ai, message) {
    $.ajax({
        url: "/app/ai/midjourney/status",
        data: {type: "midjourney", taskId: message.taskId},
        dataType: "json",
        success: function (res) {
            if (!res.code) {
                saveStatus(message, res);
                ai.saveData();
            }
        }
    });
}

// 检查对话中所有作图任务状态
export function checkStatus(ai, chat) {
    for (const message of chat.messages || []) {
        if (message.type === "midjourney" && message.progress !== "100%" && message.taskId) {
            check(ai, message);
        }
    }
}

