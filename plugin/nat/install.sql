CREATE TABLE `nat_users` (
 `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
 `created_at` datetime DEFAULT NULL COMMENT '创建时间',
 `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
 `user_id` int(11) NOT NULL COMMENT '用户id',
 `token` varchar(255) NOT NULL COMMENT 'token',
 PRIMARY KEY (`id`),
 UNIQUE KEY `user_id` (`user_id`),
 KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='nat用户表';

CREATE TABLE `nat_apps` (
 `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
 `created_at` datetime DEFAULT NULL COMMENT '创建时间',
 `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
 `name` varchar(255) NOT NULL COMMENT '名称',
 `domain` varchar(255) NOT NULL COMMENT '域名',
 `local_ip` varchar(255) NOT NULL COMMENT '本地ip',
 `local_port` int(11) NOT NULL COMMENT '本地端口',
 `user_id` int(11) NOT NULL COMMENT '用户id',
 `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
 PRIMARY KEY (`id`),
 KEY `user_id` (`user_id`),
 KEY `updated_at` (`updated_at`),
 KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='nat应用';