CREATE TABLE `ai_apikeys` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `apikey` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'apikey',
  `state` tinyint DEFAULT '0' COMMENT '禁用',
  `last_error` text COLLATE utf8mb4_general_ci COMMENT '错误信息',
  `error_count` int DEFAULT '0' COMMENT '错误次数',
  `last_message_at` datetime DEFAULT NULL COMMENT '消息时间',
  `message_count` int DEFAULT NULL COMMENT '消息数',
  PRIMARY KEY (`id`),
  KEY `error_count` (`error_count`),
  KEY `last_message_at` (`last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='APIKEY';

CREATE TABLE `ai_orders` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `order_id` varchar(50) NOT NULL COMMENT '订单id',
  `user_id` int NOT NULL COMMENT '用户id',
  `total_amount` decimal(10,2) NOT NULL COMMENT '须支付金额',
  `paid_amount` decimal(10,2) DEFAULT NULL COMMENT '已支付总额',
  `paid_at` datetime DEFAULT NULL COMMENT '支付时间',
  `state` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid' COMMENT '状态',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  `data` text COMMENT '业务数据',
  `payment_method` enum('wechat','alipay') NOT NULL DEFAULT 'alipay' COMMENT '支付方式',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI订单';

CREATE TABLE `ai_roles` (
  `roleId` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '名称',
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '/app/ai/avatar/ai.png' COMMENT '头像',
  `desc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '简介',
  `rolePrompt` text COLLATE utf8mb4_general_ci COMMENT '角色提示',
  `greeting` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '问候语',
  `model` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'gpt-3.5-turbo-16k' COMMENT '模型',
  `contextNum` int DEFAULT '12' COMMENT '上下文数',
  `maxTokens` int DEFAULT '2000' COMMENT '最大tokens',
  `temperature` double(8,2) DEFAULT '0.50' COMMENT '温度',
  PRIMARY KEY (`roleId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI角色';

CREATE TABLE `ai_users` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int NOT NULL COMMENT '用户id',
  `expired_at` datetime DEFAULT NULL COMMENT '过期时间',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI用户表';

alter table ai_users add column `message_count` int DEFAULT '0' COMMENT '消息数';
alter table ai_apikeys add column `suspended` tinyint DEFAULT '0' COMMENT '停用';

alter table ai_roles add column `preinstalled` tinyint DEFAULT '1' COMMENT '预安装';
alter table ai_roles add column `installed` int DEFAULT NULL COMMENT '安装量';
alter table ai_roles add column `category` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '分类';

CREATE TABLE `ai_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '主键',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `user_id` int DEFAULT NULL COMMENT '用户id',
  `session_id` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'session id',
  `role_id` bigint DEFAULT NULL COMMENT '角色id',
  `model` varchar(32) NOT NULL default 'gpt-3.5-turbo',
  `chat_id` int DEFAULT NULL COMMENT '对话id',
  `message_id` bigint DEFAULT NULL COMMENT '消息id',
  `role` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '角色',
  `content` mediumtext COLLATE utf8mb4_general_ci COMMENT '内容',
  `ip` varchar(32) DEFAULT NULL COMMENT 'ip',
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`),
  KEY `user_id-session_id-role_id-chat_id` (`user_id`,`session_id`,`chat_id`),
  KEY `user_id-role_id-chat_id` (`user_id`,`chat_id`),
  KEY `session_id-role_id-chat_id` (`session_id`,`chat_id`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI消息';

CREATE TABLE `ai_ban` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `type` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '类型',
  `value` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '值',
  `log` mediumtext COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '日志',
  `expired_at` datetime DEFAULT NULL COMMENT '有效期',
  PRIMARY KEY (`id`),
  KEY `item` (`type`,`value`,`expired_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='封禁表';

alter table ai_apikeys add column `gpt4` tinyint DEFAULT '10' COMMENT '支持gpt4' after message_count;
alter table ai_users add column `available_gpt3` int DEFAULT '10' COMMENT '剩余gpt3.5量';
alter table ai_users add column `available_gpt4` int DEFAULT '10' COMMENT '剩余gpt4.0量';
alter table ai_users add column `available_dalle` int DEFAULT '10' COMMENT '剩余dalle作图量';
alter table ai_users add column `available_midjourney` int DEFAULT '10' COMMENT '剩余midjourney作图量';
alter table ai_users add column `available_ernie` int DEFAULT '10' COMMENT '剩余文心一言量';
alter table ai_users add column `available_qwen` int DEFAULT '10' COMMENT '剩余通义千问量';
alter table ai_users add column `available_spark` int DEFAULT '10' COMMENT '剩余讯飞星火量';

alter table ai_messages modify `chat_id` bigint DEFAULT NULL COMMENT '对话id';
alter table ai_messages modify `ip` varchar(64) DEFAULT NULL COMMENT 'ip';
alter table ai_users add column `available_chatglm` int DEFAULT '10' COMMENT '剩余清华智普量';
