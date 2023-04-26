-- --------------------------------------------------------
-- Хост:                         127.0.0.1
-- Версия сервера:               5.6.43-log - MySQL Community Server (GPL)
-- Операционная система:         Win64
-- HeidiSQL Версия:              10.1.0.5464
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Дамп структуры базы данных prod_api
CREATE DATABASE IF NOT EXISTS `prod_api` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `prod_api`;

-- Дамп структуры для таблица prod_api.oauth_access_tokens
CREATE TABLE IF NOT EXISTS `oauth_access_tokens` (
  `access_token` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` varchar(80) DEFAULT NULL,
  `expires` timestamp NOT NULL,
  `scope` varchar(4000) DEFAULT NULL,
  PRIMARY KEY (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Дамп данных таблицы prod_api.oauth_access_tokens: ~0 rows (приблизительно)
/*!40000 ALTER TABLE `oauth_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_access_tokens` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.oauth_authorization_codes
CREATE TABLE IF NOT EXISTS `oauth_authorization_codes` (
  `authorization_code` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` varchar(80) DEFAULT NULL,
  `redirect_uri` varchar(2000) DEFAULT NULL,
  `expires` timestamp NOT NULL,
  `scope` varchar(4000) DEFAULT NULL,
  `id_token` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`authorization_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Дамп данных таблицы prod_api.oauth_authorization_codes: ~0 rows (приблизительно)
/*!40000 ALTER TABLE `oauth_authorization_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_authorization_codes` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.oauth_clients
CREATE TABLE IF NOT EXISTS `oauth_clients` (
  `client_id` varchar(80) NOT NULL,
  `client_secret` varchar(80) DEFAULT NULL,
  `redirect_uri` varchar(2000) DEFAULT NULL,
  `grant_types` varchar(80) DEFAULT NULL,
  `scope` varchar(4000) DEFAULT NULL,
  `user_id` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Дамп данных таблицы prod_api.oauth_clients: ~0 rows (приблизительно)
/*!40000 ALTER TABLE `oauth_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_clients` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.oauth_jwt
CREATE TABLE IF NOT EXISTS `oauth_jwt` (
  `client_id` varchar(80) NOT NULL,
  `subject` varchar(80) DEFAULT NULL,
  `public_key` varchar(2000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Дамп данных таблицы prod_api.oauth_jwt: ~0 rows (приблизительно)
/*!40000 ALTER TABLE `oauth_jwt` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_jwt` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.oauth_refresh_tokens
CREATE TABLE IF NOT EXISTS `oauth_refresh_tokens` (
  `refresh_token` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` varchar(80) DEFAULT NULL,
  `expires` timestamp NOT NULL,
  `scope` varchar(4000) DEFAULT NULL,
  PRIMARY KEY (`refresh_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Дамп данных таблицы prod_api.oauth_refresh_tokens: ~0 rows (приблизительно)
/*!40000 ALTER TABLE `oauth_refresh_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_refresh_tokens` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.oauth_scopes
CREATE TABLE IF NOT EXISTS `oauth_scopes` (
  `scope` varchar(80) NOT NULL,
  `is_default` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Дамп данных таблицы prod_api.oauth_scopes: ~0 rows (приблизительно)
/*!40000 ALTER TABLE `oauth_scopes` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_scopes` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.oauth_users
CREATE TABLE IF NOT EXISTS `oauth_users` (
  `username` varchar(80) NOT NULL DEFAULT '',
  `password` varchar(80) DEFAULT NULL,
  `first_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT NULL,
  `scope` varchar(4000) DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Дамп данных таблицы prod_api.oauth_users: ~0 rows (приблизительно)
/*!40000 ALTER TABLE `oauth_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_users` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.sys_action
CREATE TABLE IF NOT EXISTS `sys_action` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(11) unsigned NOT NULL DEFAULT '0',
  `flg_tree` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `flg_tpl` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `action` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `added` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `module_idx` (`module_id`)
) ENGINE=MyISAM AUTO_INCREMENT=397 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- Дамп данных таблицы prod_api.sys_action: 52 rows
/*!40000 ALTER TABLE `sys_action` DISABLE KEYS */;
INSERT INTO `sys_action` (`id`, `module_id`, `flg_tree`, `flg_tpl`, `action`, `title`, `description`, `added`) VALUES
	(1, 3, 0, 0, 'modules', 'Modules', NULL, 1242312819),
	(2, 3, 0, 0, 'sites_list', 'Sites list', NULL, 1242312819),
	(3, 3, 0, 0, 'set_site', 'Set site', NULL, 1242312819),
	(4, 3, 0, 0, 'sites_map', 'Sites map', NULL, 1242312819),
	(5, 3, 0, 0, 'set_page', 'Set page', NULL, 1242312819),
	(6, 3, 0, 3, 'ajax_fillfields', 'Fill fields', NULL, 1242312819),
	(7, 3, 0, 2, 'just_install_me', 'Just install me', NULL, 1242312819),
	(387, 5, 0, 0, 'groups2right', 'Assign groups to right', NULL, 1585301920),
	(13, 5, 0, 0, 'groups', 'Manage groups', NULL, 1242312847),
	(14, 5, 0, 0, 'rights', 'Manage rights', NULL, 1242312847),
	(15, 5, 0, 0, 'rights2group', 'Assign rights to group', NULL, 1242312847),
	(391, 77, 2, 3, 'authorize', 'Authorize', NULL, 1585301948),
	(392, 77, 2, 3, 'token', 'Token', NULL, 1585301948),
	(388, 5, 0, 0, 'content2group', 'Assign content to groups', NULL, 1585301920),
	(365, 70, 1, 1, 'client_trafic_exchange', 'Client Traffic Exchange Action', NULL, 1432280399),
	(364, 70, 1, 1, 'show_campaign', 'Show Campaign', NULL, 1432280399),
	(363, 70, 1, 0, 'credits', 'Credits', NULL, 1432280399),
	(354, 68, 0, 0, 'send_sms', 'Send SMS', NULL, 1427713017),
	(355, 68, 0, 0, 'statistic', 'Statistic', NULL, 1429286797),
	(356, 68, 0, 0, 'clientdata', 'Client Data', NULL, 1430321764),
	(357, 68, 0, 0, 'cancellations', 'Cancellations', NULL, 1431619297),
	(358, 70, 1, 0, 'campaign', 'Create Campaign', NULL, 1432280399),
	(359, 70, 1, 0, 'manager', 'Manage Campaigns', NULL, 1432280399),
	(360, 70, 1, 0, 'promote', 'Promote Campaigns', NULL, 1432280399),
	(361, 70, 1, 0, 'manage_promote', 'Manage Promotions', NULL, 1432280399),
	(362, 70, 1, 0, 'browse', 'Browse Campaigns', NULL, 1432280399),
	(396, 77, 2, 3, 'leadchannels', 'Lead Channels', NULL, 1585301948),
	(395, 77, 2, 3, 'emailfunnels', 'Email funnels', NULL, 1585301948),
	(394, 77, 2, 3, 'contacts', 'Contacts', NULL, 1585301948),
	(393, 77, 2, 3, 'resource', 'Resource', NULL, 1585301948),
	(390, 5, 0, 0, 'hosting2group', 'Assign hosting to groups', NULL, 1585301920),
	(314, 63, 1, 0, 'main', 'Form', NULL, 1288360758),
	(315, 63, 1, 3, 'defaultVariations', 'Default Variations', NULL, 1288865208),
	(316, 63, 1, 3, 'userVariations', 'User Variations', NULL, 1288865208),
	(323, 3, 0, 0, 'backups', 'DB backups', NULL, 1291118006),
	(336, 3, 0, 1, 'view_table', 'View DB table', NULL, 1296641598),
	(347, 68, 0, 0, 'manage', 'Manage', NULL, 1426063669),
	(348, 69, 0, 0, 'voice', 'Calls', NULL, 1426171661),
	(349, 69, 0, 0, 'sms', 'SMS', NULL, 1426171661),
	(350, 69, 0, 0, 'cron', 'Cron', NULL, 1426171661),
	(366, 71, 1, 1, 'conversionpixel', 'Conversionpixel', NULL, 1470928987),
	(367, 73, 1, 1, 'page', 'CNM Page', NULL, 1471532243),
	(368, 74, 1, 1, 'getcode', 'Get Code', NULL, 1525427479),
	(369, 74, 1, 1, 'unsubscribe', 'Unsubscribe page', NULL, 1525427479),
	(370, 74, 1, 1, 'webhook', 'Webhook action', NULL, 1525427479),
	(375, 75, 0, 0, 'manage', 'Manage', NULL, 1585289596),
	(376, 75, 0, 0, 'import', 'Import/Export', NULL, 1585289596),
	(377, 75, 0, 0, 'set', 'Add/Edit account', NULL, 1585289596),
	(378, 75, 0, 0, 'broadcast', 'Email Broadcast', NULL, 1585289596),
	(379, 75, 0, 0, 'blacklist', 'Blacklist', NULL, 1585289596),
	(380, 75, 0, 2, 'logout', 'Logout', NULL, 1585289596),
	(389, 5, 0, 0, 'template2group', 'Assign template to groups', NULL, 1585301920);
/*!40000 ALTER TABLE `sys_action` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.sys_module
CREATE TABLE IF NOT EXISTS `sys_module` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `added` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=78 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- Дамп данных таблицы prod_api.sys_module: 13 rows
/*!40000 ALTER TABLE `sys_module` DISABLE KEYS */;
INSERT INTO `sys_module` (`id`, `name`, `title`, `description`, `added`) VALUES
	(9, 'site1', 'CNM Frontend', NULL, 1585290477),
	(2, 'backend', 'Control panel', NULL, 1585290478),
	(3, 'configuration', 'Configuration', NULL, 1585290475),
	(77, 'site1_apiv1', 'Ifunnels API V1', NULL, 1585301948),
	(5, 'access', 'Access rights', NULL, 1585301920),
	(36, 'Project_AdvancedOptions', '', NULL, 1248789455),
	(70, 'site1_traffic', 'Traffic module', NULL, 1432280399),
	(63, 'site1_article_rewriter', 'CNM Article Rewriter', NULL, 1288865422),
	(71, 'site1_conversionpixel', 'CNM convresionpixel', NULL, 1470928987),
	(72, 'site1_squeeze', 'CNM Squeeze', NULL, 1470991652),
	(73, 'site1_page', 'CNM page', NULL, 1471532243),
	(74, 'email_funnels', 'Email Funnels QJMPZ', NULL, 1525427479),
	(75, 'members', 'Members', NULL, 1585293291);
/*!40000 ALTER TABLE `sys_module` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.sys_page
CREATE TABLE IF NOT EXISTS `sys_page` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `level` int(11) unsigned NOT NULL DEFAULT '0',
  `sort` smallint(3) unsigned NOT NULL DEFAULT '0',
  `root_id` int(11) unsigned DEFAULT NULL,
  `action_id` int(11) unsigned DEFAULT NULL,
  `item_id` int(11) unsigned DEFAULT NULL,
  `sys_name` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `meta_keywords` text,
  `meta_robots` tinyint(1) unsigned DEFAULT NULL,
  `flg_onmap` tinyint(1) unsigned DEFAULT NULL,
  `added` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=332 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- Дамп данных таблицы prod_api.sys_page: 40 rows
/*!40000 ALTER TABLE `sys_page` DISABLE KEYS */;
INSERT INTO `sys_page` (`id`, `pid`, `level`, `sort`, `root_id`, `action_id`, `item_id`, `sys_name`, `title`, `meta_description`, `meta_keywords`, `meta_robots`, `flg_onmap`, `added`) VALUES
	(2, 1, 1, 0, 2, NULL, NULL, 'site-backend', 'Control panel', NULL, NULL, 0, 0, 1242312804),
	(4, 2, 2, 6, 2, NULL, NULL, 'configuration', 'Configuration', NULL, NULL, 0, 0, 1585036163),
	(5, 4, 3, 0, 2, 1, NULL, 'modules', 'Modules', NULL, NULL, 0, 0, 1585036163),
	(6, 4, 3, 2, 2, 2, NULL, 'sites_list', 'Sites list', NULL, NULL, 0, 0, 1585036163),
	(7, 4, 3, 3, 2, 3, NULL, 'set_site', 'Set site', NULL, NULL, 0, 0, 1585036163),
	(8, 4, 3, 4, 2, 4, NULL, 'sites_map', 'Sites map', NULL, NULL, 0, 0, 1585036163),
	(9, 4, 3, 5, 2, 5, NULL, 'set_page', 'Set page', NULL, NULL, 0, 0, 1585036163),
	(10, 4, 3, 6, 2, 6, NULL, 'ajax_fillfields', 'Fill fields', NULL, NULL, 0, 0, 1585036163),
	(11, 4, 3, 8, 2, 7, NULL, 'just_install_me', 'Just install me', NULL, NULL, 0, 0, 1585036163),
	(318, 18, 3, 0, 2, 388, NULL, 'content2group', 'Assign content to groups', NULL, NULL, 0, 0, 1585301920),
	(317, 18, 3, 0, 2, 387, NULL, 'groups2right', 'Assign groups to right', NULL, NULL, 0, 0, 1585301920),
	(18, 2, 2, 4, 2, NULL, NULL, 'access', 'Access rights', NULL, NULL, 0, 0, 1585036161),
	(19, 18, 3, 0, 2, 13, NULL, 'groups', 'Manage groups', NULL, NULL, 0, 0, 1585036161),
	(20, 18, 3, 1, 2, 14, NULL, 'rights', 'Manage rights', NULL, NULL, 0, 0, 1585036161),
	(21, 18, 3, 2, 2, 15, NULL, 'rights2group', 'Assign rights to group', NULL, NULL, 0, 0, 1585036161),
	(331, 295, 2, 5, 295, 0, NULL, 'v1', 'Version 1', NULL, NULL, 0, 1, 1585303860),
	(329, 331, 2, 4, 295, 396, NULL, 'leadchannels', 'Lead Channels', NULL, NULL, 0, 1, 1585302109),
	(328, 331, 2, 3, 295, 395, NULL, 'emailfunnels', 'Email Funnels', NULL, NULL, 0, 1, 1585302076),
	(327, 321, 3, 0, 2, 396, NULL, 'leadchannels', 'Lead Channels', NULL, NULL, 0, 0, 1585301948),
	(326, 321, 3, 0, 2, 395, NULL, 'emailfunnels', 'Email funnels', NULL, NULL, 0, 0, 1585301948),
	(325, 321, 3, 0, 2, 394, NULL, 'contacts', 'Contacts', NULL, NULL, 0, 0, 1585301948),
	(324, 321, 3, 0, 2, 393, NULL, 'resource', 'Resource', NULL, NULL, 0, 0, 1585301948),
	(330, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
	(320, 18, 3, 0, 2, 390, NULL, 'hosting2group', 'Assign hosting to groups', NULL, NULL, 0, 0, 1585301920),
	(321, 2, 2, 0, 2, NULL, NULL, 'site1_apiv1', 'Ifunnels API V1', NULL, NULL, 0, 0, 1585301948),
	(322, 321, 3, 0, 2, 391, NULL, 'authorize', 'Authorize', NULL, NULL, 0, 0, 1585301948),
	(323, 321, 3, 0, 2, 392, NULL, 'token', 'Token', NULL, NULL, 0, 0, 1585301948),
	(246, 4, 3, 1, 2, 323, NULL, 'backups', 'DB backups', NULL, NULL, 0, 0, 1585036163),
	(309, 303, 3, 0, 2, 380, NULL, 'logout', 'Logout', NULL, NULL, 0, 0, 1585289596),
	(308, 303, 3, 0, 2, 379, NULL, 'blacklist', 'Blacklist', NULL, NULL, 0, 0, 1585289596),
	(307, 303, 3, 0, 2, 378, NULL, 'broadcast', 'Email Broadcast', NULL, NULL, 0, 0, 1585289596),
	(306, 303, 3, 0, 2, 377, NULL, 'set', 'Add/Edit account', NULL, NULL, 0, 0, 1585289596),
	(305, 303, 3, 0, 2, 376, NULL, 'import', 'Import/Export', NULL, NULL, 0, 0, 1585289596),
	(304, 303, 3, 0, 2, 375, NULL, 'manage', 'Manage', NULL, NULL, 0, 0, 1585289596),
	(260, 4, 3, 7, 2, 336, NULL, 'view_table', 'View DB table', NULL, NULL, 0, 0, 1585036163),
	(303, 2, 2, 0, 2, NULL, NULL, 'members', 'Members', NULL, NULL, 0, 0, 1585289596),
	(298, 331, 2, 2, 295, 394, NULL, 'contacts', 'Contacts', NULL, NULL, 0, 1, 1585056356),
	(297, 331, 2, 1, 295, 392, NULL, 'token', 'Token', NULL, NULL, 0, 1, 1585056368),
	(296, 331, 2, 0, 295, 391, NULL, 'authorize', 'Authorize', NULL, NULL, 0, 1, 1585043199),
	(295, 294, 1, 0, 295, 0, NULL, 'ifunnels-api', 'Ifunnels API', NULL, NULL, 0, 1, 1585042870);
/*!40000 ALTER TABLE `sys_page` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.sys_site
CREATE TABLE IF NOT EXISTS `sys_site` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `root_id` int(11) unsigned NOT NULL DEFAULT '0',
  `flg_type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `flg_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `sys_name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `added` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- Дамп данных таблицы prod_api.sys_site: 3 rows
/*!40000 ALTER TABLE `sys_site` DISABLE KEYS */;
INSERT INTO `sys_site` (`id`, `root_id`, `flg_type`, `flg_active`, `domain`, `sys_name`, `title`, `added`) VALUES
	(1, 2, 1, 1, '/site-backend', 'site-backend', 'Control panel', 1242312804),
	(2, 3, 0, 1, 'api.ifunnels.com', 'api', 'Frontend', 1242312804),
	(4, 295, 0, 1, 'members.cnmbeta.info', 'cnm', 'Frontend', 1585042870);
/*!40000 ALTER TABLE `sys_site` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.u_groups
CREATE TABLE IF NOT EXISTS `u_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- Дамп данных таблицы prod_api.u_groups: 5 rows
/*!40000 ALTER TABLE `u_groups` DISABLE KEYS */;
INSERT INTO `u_groups` (`id`, `sys_name`, `title`, `description`) VALUES
	(1, 'Super Admin', 'Super Admin', 'full access (root user)'),
	(2, 'System Users', 'System Users', ''),
	(3, 'Content Admin', 'Content Admin', ''),
	(4, 'Visitor', 'Visitor', 'default users group'),
	(5, 'Unlimited', 'Unlimit', 'access to all frontend modules');
/*!40000 ALTER TABLE `u_groups` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.u_link
CREATE TABLE IF NOT EXISTS `u_link` (
  `group_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Дамп данных таблицы prod_api.u_link: 3 rows
/*!40000 ALTER TABLE `u_link` DISABLE KEYS */;
INSERT INTO `u_link` (`group_id`, `user_id`) VALUES
	(1, 1),
	(1, 2),
	(1, 3);
/*!40000 ALTER TABLE `u_link` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.u_rights
CREATE TABLE IF NOT EXISTS `u_rights` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  PRIMARY KEY (`id`),
  KEY `sys_name_idx` (`sys_name`)
) ENGINE=MyISAM AUTO_INCREMENT=402 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- Дамп данных таблицы prod_api.u_rights: 52 rows
/*!40000 ALTER TABLE `u_rights` DISABLE KEYS */;
INSERT INTO `u_rights` (`id`, `sys_name`, `title`, `description`) VALUES
	(1, 'configuration_@_modules', 'Configuration -> Modules', 'module action right'),
	(2, 'configuration_@_sites_list', 'Configuration -> Sites list', 'module action right'),
	(3, 'configuration_@_set_site', 'Configuration -> Set site', 'module action right'),
	(4, 'configuration_@_sites_map', 'Configuration -> Sites map', 'module action right'),
	(5, 'configuration_@_set_page', 'Configuration -> Set page', 'module action right'),
	(6, 'configuration_@_ajax_fillfields', 'Configuration -> Fill fields', 'module action right'),
	(7, 'configuration_@_just_install_me', 'Configuration -> Just install me', 'module action right'),
	(395, 'access_@_groups2right', 'Access rights -> Assign groups to right', 'module action right'),
	(393, 'access_@_template2group', 'Access rights -> Assign template to groups', 'module action right'),
	(392, 'access_@_hosting2group', 'Access rights -> Assign hosting to groups', 'module action right'),
	(13, 'access_@_groups', 'Access rights -> Manage groups', 'module action right'),
	(14, 'access_@_rights', 'Access rights -> Manage rights', 'module action right'),
	(15, 'access_@_rights2group', 'Access rights -> Assign rights to group', 'module action right'),
	(397, 'site1_apiv1_@_emailfunnels', 'Ifunnels API V1 -> Email funnels', 'module action right'),
	(398, 'site1_apiv1_@_contacts', 'Ifunnels API V1 -> Contacts', 'module action right'),
	(399, 'site1_apiv1_@_resource', 'Ifunnels API V1 -> Resource', 'module action right'),
	(359, 'billing_aggregator_@_send_sms', 'Billing Aggregator -> Send SMS', 'module action right'),
	(360, 'billing_aggregator_@_statistic', 'Billing Aggregator -> Statistic', 'module action right'),
	(361, 'billing_aggregator_@_clientdata', 'Billing Aggregator -> Client Data', 'module action right'),
	(362, 'billing_aggregator_@_cancellations', 'Billing Aggregator -> Cancellations', 'module action right'),
	(363, 'site1_traffic_@_campaign', 'Traffic module -> Create Campaign', 'module action right'),
	(364, 'site1_traffic_@_manager', 'Traffic module -> Manage Campaigns', 'module action right'),
	(365, 'site1_traffic_@_promote', 'Traffic module -> Promote Campaigns', 'module action right'),
	(366, 'site1_traffic_@_manage_promote', 'Traffic module -> Manage Promotions', 'module action right'),
	(367, 'site1_traffic_@_browse', 'Traffic module -> Browse Campaigns', 'module action right'),
	(368, 'site1_traffic_@_credits', 'Traffic module -> Credits', 'module action right'),
	(369, 'site1_traffic_@_show_campaign', 'Traffic module -> Show Campaign', 'module action right'),
	(370, 'site1_traffic_@_client_trafic_exchange', 'Traffic module -> Client Traffic Exchange Action', 'module action right'),
	(319, 'site1_article_rewriter_@_main', 'CNM Article Rewriter -> Form', 'module action right'),
	(320, 'site1_article_rewriter_@_defaultVariations', 'CNM Article Rewriter -> Default Variations', 'module action right'),
	(321, 'site1_article_rewriter_@_userVariations', 'CNM Article Rewriter -> User Variations', 'module action right'),
	(328, 'configuration_@_backups', 'Configuration -> DB backups', 'module action right'),
	(400, 'site1_apiv1_@_token', 'Ifunnels API V1 -> Token', 'module action right'),
	(341, 'configuration_@_view_table', 'Configuration -> View DB table', 'module action right'),
	(401, 'site1_apiv1_@_authorize', 'Ifunnels API V1 -> Authorize', 'module action right'),
	(352, 'billing_aggregator_@_manage', 'Billing Aggregator -> Manage', 'module action right'),
	(353, 'call_service_@_voice', 'Call Service -> Calls', 'module action right'),
	(354, 'call_service_@_sms', 'Call Service -> SMS', 'module action right'),
	(355, 'call_service_@_cron', 'Call Service -> Cron', 'module action right'),
	(371, 'site1_conversionpixel_@_conversionpixel', 'CNM convresionpixel -> Conversionpixel', 'module action right'),
	(372, 'site1_page_@_page', 'CNM page -> CNM Page', 'module action right'),
	(373, 'email_funnels_@_getcode', 'Email Funnels QJMPZ -> Get Code', 'module action right'),
	(374, 'email_funnels_@_unsubscribe', 'Email Funnels QJMPZ -> Unsubscribe page', 'module action right'),
	(375, 'email_funnels_@_webhook', 'Email Funnels QJMPZ -> Webhook action', 'module action right'),
	(396, 'site1_apiv1_@_leadchannels', 'Ifunnels API V1 -> Lead Channels', 'module action right'),
	(394, 'access_@_content2group', 'Access rights -> Assign content to groups', 'module action right'),
	(380, 'members_@_logout', 'Members -> Logout', 'module action right'),
	(381, 'members_@_blacklist', 'Members -> Blacklist', 'module action right'),
	(382, 'members_@_broadcast', 'Members -> Email Broadcast', 'module action right'),
	(383, 'members_@_set', 'Members -> Add/Edit account', 'module action right'),
	(384, 'members_@_import', 'Members -> Import/Export', 'module action right'),
	(385, 'members_@_manage', 'Members -> Manage', 'module action right');
/*!40000 ALTER TABLE `u_rights` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.u_rights2group
CREATE TABLE IF NOT EXISTS `u_rights2group` (
  `group_id` int(11) unsigned NOT NULL DEFAULT '0',
  `rights_id` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- Дамп данных таблицы prod_api.u_rights2group: 146 rows
/*!40000 ALTER TABLE `u_rights2group` DISABLE KEYS */;
INSERT INTO `u_rights2group` (`group_id`, `rights_id`) VALUES
	(4, 400),
	(4, 399),
	(4, 396),
	(4, 397),
	(4, 398),
	(4, 401),
	(1, 396),
	(1, 397),
	(1, 398),
	(4, 375),
	(4, 374),
	(4, 373),
	(1, 375),
	(1, 374),
	(1, 373),
	(4, 372),
	(1, 1),
	(1, 372),
	(4, 371),
	(1, 371),
	(1, 355),
	(1, 354),
	(1, 353),
	(1, 352),
	(5, 350),
	(1, 350),
	(1, 341),
	(2, 370),
	(3, 370),
	(4, 370),
	(5, 336),
	(1, 336),
	(5, 335),
	(1, 335),
	(1, 328),
	(13, 326),
	(12, 326),
	(10, 326),
	(9, 326),
	(8, 326),
	(7, 326),
	(6, 326),
	(5, 326),
	(1, 326),
	(5, 321),
	(5, 320),
	(1, 321),
	(1, 320),
	(5, 319),
	(1, 319),
	(5, 314),
	(1, 314),
	(5, 301),
	(1, 301),
	(13, 29),
	(13, 30),
	(13, 31),
	(12, 29),
	(12, 30),
	(12, 31),
	(8, 29),
	(1, 399),
	(10, 29),
	(10, 267),
	(10, 30),
	(10, 31),
	(9, 31),
	(9, 30),
	(9, 267),
	(9, 29),
	(7, 29),
	(7, 267),
	(7, 30),
	(7, 31),
	(8, 30),
	(8, 31),
	(5, 29),
	(5, 275),
	(5, 267),
	(1, 275),
	(5, 30),
	(5, 31),
	(6, 267),
	(1, 267),
	(6, 30),
	(6, 31),
	(6, 29),
	(1, 359),
	(1, 360),
	(1, 361),
	(1, 362),
	(1, 363),
	(1, 364),
	(1, 365),
	(1, 366),
	(1, 367),
	(1, 368),
	(1, 369),
	(1, 370),
	(1, 98),
	(1, 97),
	(1, 96),
	(1, 95),
	(1, 94),
	(1, 93),
	(1, 92),
	(1, 91),
	(1, 90),
	(1, 89),
	(1, 88),
	(1, 87),
	(1, 86),
	(1, 85),
	(1, 84),
	(1, 83),
	(1, 82),
	(1, 81),
	(1, 80),
	(1, 79),
	(1, 78),
	(1, 77),
	(1, 76),
	(1, 75),
	(1, 74),
	(1, 73),
	(1, 72),
	(1, 71),
	(1, 6),
	(1, 7),
	(1, 1),
	(1, 5),
	(1, 3),
	(1, 2),
	(1, 4),
	(1, 400),
	(1, 392),
	(1, 393),
	(1, 394),
	(1, 395),
	(1, 401),
	(1, 13),
	(1, 14),
	(1, 15),
	(1, 29),
	(1, 31),
	(1, 30);
/*!40000 ALTER TABLE `u_rights2group` ENABLE KEYS */;

-- Дамп структуры для таблица prod_api.u_users
CREATE TABLE IF NOT EXISTS `u_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `cost_id` int(11) unsigned NOT NULL DEFAULT '0',
  `item_id` int(11) unsigned NOT NULL DEFAULT '0',
  `flg_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL DEFAULT '',
  `passwd` varchar(255) NOT NULL DEFAULT '',
  `nickname` varchar(255) NOT NULL DEFAULT '',
  `timezone` varchar(255) NOT NULL DEFAULT '',
  `reg_code` varchar(255) NOT NULL DEFAULT '',
  `forgot_code` varchar(255) NOT NULL DEFAULT '',
  `payment_code` varchar(255) NOT NULL DEFAULT '',
  `forgot_added` int(11) unsigned NOT NULL DEFAULT '0',
  `next_payment` int(11) unsigned NOT NULL DEFAULT '0',
  `added` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- Дамп данных таблицы prod_api.u_users: 1 rows
/*!40000 ALTER TABLE `u_users` DISABLE KEYS */;
INSERT INTO `u_users` (`id`, `parent_id`, `cost_id`, `item_id`, `flg_status`, `email`, `passwd`, `nickname`, `timezone`, `reg_code`, `forgot_code`, `payment_code`, `forgot_added`, `next_payment`, `added`) VALUES
	(1, 0, 0, 0, 1, 'cadmin@api.info', 'd7734fec458a2e784589d18b1b767c7dj259dU9e', 'cadmin', 'UTC', '', '', '', 0, 0, 1585289712);
/*!40000 ALTER TABLE `u_users` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
