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
