-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-09-23 19:46:33
-- 服务器版本： 5.6.50-log
-- PHP 版本： 7.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `cs`
--

-- --------------------------------------------------------

--
-- 表的结构 `bot`
--

CREATE TABLE `bot` (
  `id` int(10) UNSIGNED NOT NULL COMMENT '自增主键ID',
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '机器人Token',
  `chat_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '群组或用户ID',
  `url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '群组或频道的链接',
  `jqr` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='机器人设置';

-- --------------------------------------------------------

--
-- 表的结构 `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `uid` varchar(1100) NOT NULL COMMENT '用户id',
  `user` varchar(255) NOT NULL COMMENT '用户名',
  `name` varchar(255) DEFAULT NULL COMMENT '用户昵称',
  `ddh` varchar(255) NOT NULL COMMENT '订单号',
  `createtime` int(11) NOT NULL COMMENT '添加时间',
  `dqtime` int(11) DEFAULT NULL COMMENT '到期时间',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0未支付1已支付'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单';

-- --------------------------------------------------------

--
-- 表的结构 `pay`
--

CREATE TABLE `pay` (
  `pid` int(11) NOT NULL COMMENT '商户ID',
  `md5` varchar(32) NOT NULL COMMENT '商户MD5密钥'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付';

--
-- 转存表中的数据 `pay`
--

INSERT INTO `pay` (`pid`, `md5`) VALUES
(1277, '2TwIisI67bSZ0jb9qhZ6w04P2eTs2cOb');

-- --------------------------------------------------------

--
-- 表的结构 `taocan`
--

CREATE TABLE `taocan` (
  `id` int(11) NOT NULL COMMENT '自增主键',
  `name` varchar(255) NOT NULL COMMENT '套餐',
  `price` decimal(10,2) NOT NULL COMMENT '价格',
  `shijian` int(11) DEFAULT NULL COMMENT '时间',
  `createtime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='套餐';

--
-- 转存表中的数据 `taocan`
--

INSERT INTO `taocan` (`id`, `name`, `price`, `shijian`, `createtime`) VALUES
(1, '月付套餐', '1.00', 10, '2025-09-23 19:40:56');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@qq.com', '$2y$10$bye/vwuScHSxN8LoCFCmoung6ArxO5ykVGH1HAAF1wq9QJ8fvyEe6', '2025-09-19 22:20:46', '2025-09-19 22:25:53');

--
-- 转储表的索引
--

--
-- 表的索引 `bot`
--
ALTER TABLE `bot`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_token` (`token`(50));

--
-- 表的索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `pay`
--
ALTER TABLE `pay`
  ADD PRIMARY KEY (`pid`);

--
-- 表的索引 `taocan`
--
ALTER TABLE `taocan`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `bot`
--
ALTER TABLE `bot`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增主键ID';

--
-- 使用表AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `taocan`
--
ALTER TABLE `taocan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增主键', AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
