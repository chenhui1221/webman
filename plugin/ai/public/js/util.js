"use strict";
const {$, confetti, navigator, document} = window;
export function formatDate(timestamp) {
    if (!timestamp) {
        return "";
    }
    const date = new Date(timestamp);
    const now = new Date();
    // 如果是当天的，返回 小时:分钟
    if (date.toDateString() === now.toDateString()) {
        const hours = date.getHours().toString().padStart(2, "0");
        const minutes = date.getMinutes().toString().padStart(2, "0");
        return `${hours}:${minutes}`;
    }
    // 如果是昨天的，返回昨天
    const yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return "昨天";
    }
    // 如果是前天的，返回前天
    const beforeYesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 2);
    if (date.toDateString() === beforeYesterday.toDateString()) {
        return "前天";
    }
    // 如果是今年的，返回 月-日 如 01-25
    if (date.getFullYear() === now.getFullYear()) {
        const month = (date.getMonth() + 1).toString().padStart(2, "0");
        const day = date.getDate().toString().padStart(2, "0");
        return `${month}-${day}`;
    }
    // 其它返回 年-月-日 如 2021-03-16
    const year = date.getFullYear().toString();
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const day = date.getDate().toString().padStart(2, "0");
    return `${year}-${month}-${day}`;
}

export function setBit(binaryString, index, bit) {
    let binaryArray = Array.from(binaryString);
    binaryArray[index] = bit;
    return binaryArray.join("");
}

export function fireConfetti() {
    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }
    for (let i = 0; i < 8; i++) {
        confetti({
            startVelocity: 30,
            particleCount: 4,
            spread: 360,
            ticks: 60,
            origin: {x: randomInRange(0.15, 0.9), y: randomInRange(0, 0.6)}
        });
    }
}

export function speak(content) {
    const synthesis = window.speechSynthesis;
    if (synthesis.speaking) {
        synthesis.cancel();
        if (content && content === synthesis.content) {
            return;
        }
    }
    synthesis.content = content;
    let message = new SpeechSynthesisUtterance();
    message.rate = 1.5;
    message.text = content;
    synthesis.speak(message);
}

export function copyToClipboard(content) {
    if (navigator.clipboard) {
        return navigator.clipboard.writeText(content);
    }
    let input = document.createElement("textarea");
    input.setAttribute("readonly", "readonly");
    input.value = content;
    document.body.appendChild(input);
    input.select();
    if (document.execCommand("copy")) {
        document.execCommand("copy");
    }
    document.body.removeChild(input);
}

export function xhrOnProgress(fun) {
    return function () {
        let xhr = $.ajaxSettings.xhr();
        if (typeof fun !== "function") {
            return xhr;
        }
        if (fun) {
            xhr.addEventListener("progress", fun);
        }
        return xhr;
    };
}

export function hasChinese(content) {
    return /[\u4E00-\u9FA5]/.test(content);
}


// 打开或创建IndexedDB数据库
function openIndexedDB() {
    return new Promise((resolve, reject) => {
        if (openIndexedDB.instance) {
            resolve(openIndexedDB.instance);
        }
        const request = indexedDB.open('ai-history', 1);
        request.onupgradeneeded = function(event) {
            // 创建对象存储空间
            const db = event.target.result;
            const chatHistoryStore = db.createObjectStore('history', { keyPath: 'chatId' });
            chatHistoryStore.createIndex("roleIdIndex", "roleId");
        };
        request.onsuccess = function(event) {
            openIndexedDB.instance = event.target.result;
            resolve(openIndexedDB.instance);
        };
        request.onerror = function(event) {
            reject('无法打开/创建IndexedDB数据库');
        };
    });
}

// 保存历史记录，每个角色保留100个历史对话
export async function historySave(roleId, chatId, title, time, messages) {
    try {
        const items = await historyList(roleId);
        const limit = 100;
        if (items.length >= limit) {
            await historyDelete(roleId, items[items.length - 1].chatId);
        }
        messages = JSON.parse(JSON.stringify(messages));
        const db = await openIndexedDB();
        const transaction = db.transaction(['history'], 'readwrite');
        const objectStore = transaction.objectStore('history');
        objectStore.put({ roleId, chatId, title, time, messages});
    } catch (error) {
        console.error('保存历史记录出错：', error);
    }
}

// 获取历史记录列表
export async function historyList(roleId) {
    const db = await openIndexedDB();
    const transaction = db.transaction(['history'], 'readonly');
    const objectStore = transaction.objectStore('history');
    const request = objectStore.getAll();
    return new Promise((resolve, reject) => {
        request.onsuccess = function(event) {
            resolve(event.target.result.filter(item => item.roleId === roleId).reverse());
        };
        request.onerror = function(event) {
            reject('获取历史记录列表出错');
        };
    });
}

// 获取历史记录
export async function historyGet(roleId, chatId) {
    try {
        const db = await openIndexedDB();
        const transaction = db.transaction(['history'], 'readonly');
        const objectStore = transaction.objectStore('history');
        const request = objectStore.get(chatId);
        return new Promise((resolve, reject) => {
            request.onsuccess = function(event) {
                const item = event.target.result;
                if (item && item.roleId === roleId) {
                    resolve(item);
                } else {
                    reject('历史记录不存在或不属于指定角色');
                }
            };
            request.onerror = function(event) {
                reject('获取历史记录出错');
            };
        });
    } catch (error) {
        console.error('获取历史记录出错：', error);
        return null;
    }
}

// 删除历史记录
export async function historyDelete(roleId, chatId) {
    try {
        const db = await openIndexedDB();
        const transaction = db.transaction(['history'], 'readwrite');
        const objectStore = transaction.objectStore('history');
        const request = objectStore.delete(chatId);
        request.onsuccess = function() {
            console.log('已删除历史记录');
        };
    } catch (error) {
        console.error('删除历史记录出错：', error);
    }
}

