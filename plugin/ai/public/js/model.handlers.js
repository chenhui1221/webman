"use strict";

function getTokenLength(text) {
    getTokenLength.encoder = getTokenLength.encoder || new TextEncoder();
    return Math.floor(getTokenLength.encoder.encode(text).length/1.5);
}

function formatMessages(messages) {
    if (messages[0]['role'] === 'system') {
        messages[0]['role'] = 'user';
        // 如果第二条也是user则插入一条assistant的ok消息
        if (messages[1] && messages[1]['role'] === 'user') {
            messages.splice(1, 0, {
                "role": "assistant",
                "content": "ok"
            });
        }
    }

    // 国内接口要求第一条的role必须是user，如果不是则删除掉
    while (messages.length > 0 && messages[0].role !== "user") {
        messages.shift();
    }

    // 空消息设置为空格避免报错
    for (let message of messages) {
        message['content'] = message['content']||' ';
    }

    // 强制格式化消息为user assistant交错的信息
    const mergedMessages = [];
    let currentRole = null;
    for (let i = 0; i < messages.length; i++) {
        const { role, content } = messages[i];
        if (role !== currentRole) {
            mergedMessages.push({ role, content });
            currentRole = role;
        } else {
            mergedMessages[mergedMessages.length - 1].content += " " + content;
        }
    }
    return mergedMessages;
}


// gpt
const gpt = {
    prepare(model, messages) {
        const originalModel = model;
        // 用户输入超过2000tokens使用 16k 32k上下文
        const contextLimit = 2000;
        const tokenLength = getTokenLength(JSON.stringify(messages));
        if (["gpt-3.5-turbo","gpt-3.5-turbo-0613", "gpt-4"].includes(model)) {
            if (tokenLength  > contextLimit) {
                model = model === "gpt-4" ? "gpt-4-32k" : "gpt-3.5-turbo-16k";
            }
        }
        // 上下文过长则缩短上下文
        if (["gpt-4-32k", "gpt-3.5-turbo-16k"].includes(model)) {
            const maxContextTokens = model === "gpt-3.5-turbo-16k" ? 14000 : 34000;
            if (tokenLength > maxContextTokens) {
                const startPosition = messages[0]['role'] === 'system' ? 1 : 0;
                while (messages.length > 1 + startPosition && getTokenLength(JSON.stringify(messages)) > maxContextTokens) {
                    messages.splice(startPosition, 1);
                }
            }
        }
        if (getTokenLength(JSON.stringify(messages)) < contextLimit) {
            model = originalModel;
        }
        return {model, messages}
    },
    progress(message, responseText) {
        const lastIndex = responseText.lastIndexOf("\n");
        if (lastIndex === -1) {
            return;
        }
        let thunks = responseText.substring(message.lastChunkIndex||0);
        message.lastChunkIndex = lastIndex;
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
                if (data.error) {
                    message.content += data.error.message || "";
                } else {
                    message.content += data.choices[0].delta.content || "";
                }
                this.scrollToBottom();
            } catch (e) {}
        });
    },
    success(data, message) {
        if (data.error && data.error.message) {
            message.type = "text";
            message.content = data.error.message;
        }
    }
}

// dalle
const dalle = {
    models: ['dall.e'],
    prepare(model, messages) {
        return {model, messages}
    },
    progress(message, responseText) {},
    success(data, message) {
        if (data.error && data.error.message) {
            message.type = "text";
            message.content = data.error.message;
        } else if (data.data && data.data[0] && data.data[0].url) {
            message.content = `![](${data.data[0].url})`;
        } else {
            message.content = JSON.stringify(data);
        }
    }
}

// 阿里通义千问
const qwen = {
    prepare(model, messages) {
        messages = formatMessages(messages);
        return {model, messages}
    },
    progress(message, responseText) {
        const arr = responseText.split("\n");
        for (let chunk of arr.reverse()) {
            if (/data:/.test(chunk)) {
                chunk = chunk.substring(5).trim();
                try {
                    const data = JSON.parse(chunk);
                    if (data.error) {
                        message.content = data.error.message || data.error;
                    } else if (data.message) {
                        message.content = data.message;
                    } else {
                        message.content = data.output.text;
                    }
                    return;
                } catch (e) {}
            }
        }
    },
    success(data, message) {
        if (data.error) {
            message.content = data.error.message || data.error;
        } else if (data.code && data.message) {
            message.content = data.message;
        } else if (data.error && data.error.message) {
            message.content = data.error.message;
        }
    }
}

// 百度ernie
const ernie = {
    prepare(model, messages) {
        messages = formatMessages(messages);
        return {model, messages}
    },
    progress(message, responseText) {
        const lastIndex = responseText.lastIndexOf("\n");
        if (lastIndex === -1) {
            return;
        }
        let thunks = responseText.substring(message.lastChunkIndex||0);
        message.lastChunkIndex = lastIndex;
        const arr = thunks.split("\n");
        arr.forEach((chunk) => {
            if (chunk === "") {
                return;
            }
            chunk = chunk.substring(6).trim();
            try {
                const data = JSON.parse(chunk);
                if (data["error_msg"]) {
                    message.content += message["error_msg"] || "";
                } else {
                    message.content += data.result;
                }
            } catch (e) {}
        });
    },
    success(data, message) {
        if (data.error) {
            message.content = data.error.message || data.error;
        } else if (data["error_msg"]) {
            message.type = "text";
            message.content = data["error_msg"];
        }
    }
}


// spark
const spark = {
    prepare(model, messages) {
        messages = formatMessages(messages);
        return {model, messages}
    },
    progress(message, responseText) {
        const lastIndex = responseText.lastIndexOf("\n");
        if (lastIndex === -1) {
            return;
        }
        let thunks = responseText.substring(message.lastChunkIndex||0);
        message.lastChunkIndex = lastIndex;
        const arr = thunks.split("\n");
        arr.forEach((chunk) => {
            if (chunk === "") {
                return;
            }
            chunk = chunk.substring(6).trim();
            try {
                const data = JSON.parse(chunk);
                if (data.error) {
                    message.content += data.error.message || "";
                } else if(data.header.code) {
                    message.content += data.header.message;
                } else {
                    message.content += data.payload.choices.text[0].content || "";
                }
            } catch (e) {}
        });
    },
    success(data, message) {
        if (data.error && data.error.message) {
            message.type = "text";
            message.content = data.error.message;
        }
    }
}


// chatglm
const chatglm = {
    prepare(model, messages) {
        messages = formatMessages(messages);
        return {model, messages}
    },
    progress(message, responseText) {
        const lastIndex = responseText.lastIndexOf("\n\n");
        if (lastIndex === -1) {
            return;
        }
        let thunks = responseText.substring(message.lastChunkIndex||0);
        message.lastChunkIndex = lastIndex;
        thunks = thunks.split("\n\n");
        let extractedData = '';
        for (let i = 0; i < thunks.length; i++) {
            const lines = thunks[i].split("\n");
            let dataFieldCount = 0;
            for (let j = 0; j < lines.length; j++) {
                let line = lines[j];
                if (line.startsWith("event:finish")) {
                    break;
                } else if (line.startsWith("data:")) {
                    // 多行data需要加一个换行
                    if (dataFieldCount++ > 0) {
                        extractedData += "\n";
                    }
                    extractedData += line.substring(5);
                }
            }
        }
        message.content += extractedData;
    },
    success(data, message) {
        if (data.error && data.error.message) {
            message.type = "text";
            message.content = data.error.message;
        } else if (data.code) {
            message.type = "text";
            message.content = data.msg;
        }
    }
}

export default {
    gpt,
    dalle,
    qwen,
    ernie,
    spark,
    chatglm
}