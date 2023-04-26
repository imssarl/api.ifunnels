
# dump of api.ifunnels.local project database at 2020_07_24_07_38_02.;

SET NAMES utf8;


# structure of u_groups table;

DROP TABLE IF EXISTS u_groups;
CREATE TABLE `u_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;


# content of u_groups table;

INSERT INTO u_groups VALUES ('1','Super Admin','Super Admin','full access (root user)');
INSERT INTO u_groups VALUES ('2','System Users','System Users','');
INSERT INTO u_groups VALUES ('3','Content Admin','Content Admin','');
INSERT INTO u_groups VALUES ('4','Visitor','Visitor','default users group');
INSERT INTO u_groups VALUES ('5','Unlimited','Unlimit','access to all frontend modules');


# structure of u_link table;

DROP TABLE IF EXISTS u_link;
CREATE TABLE `u_link` (
  `group_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


# content of u_link table;

INSERT INTO u_link VALUES ('1','1');
INSERT INTO u_link VALUES ('1','2');
INSERT INTO u_link VALUES ('1','3');


# structure of u_rights table;

DROP TABLE IF EXISTS u_rights;
CREATE TABLE `u_rights` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  PRIMARY KEY (`id`),
  KEY `sys_name_idx` (`sys_name`)
) ENGINE=MyISAM AUTO_INCREMENT=404 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;


# content of u_rights table;

INSERT INTO u_rights VALUES ('1','configuration_@_modules','Configuration -> Modules','module action right');
INSERT INTO u_rights VALUES ('2','configuration_@_sites_list','Configuration -> Sites list','module action right');
INSERT INTO u_rights VALUES ('3','configuration_@_set_site','Configuration -> Set site','module action right');
INSERT INTO u_rights VALUES ('4','configuration_@_sites_map','Configuration -> Sites map','module action right');
INSERT INTO u_rights VALUES ('5','configuration_@_set_page','Configuration -> Set page','module action right');
INSERT INTO u_rights VALUES ('6','configuration_@_ajax_fillfields','Configuration -> Fill fields','module action right');
INSERT INTO u_rights VALUES ('7','configuration_@_just_install_me','Configuration -> Just install me','module action right');
INSERT INTO u_rights VALUES ('395','access_@_groups2right','Access rights -> Assign groups to right','module action right');
INSERT INTO u_rights VALUES ('393','access_@_template2group','Access rights -> Assign template to groups','module action right');
INSERT INTO u_rights VALUES ('392','access_@_hosting2group','Access rights -> Assign hosting to groups','module action right');
INSERT INTO u_rights VALUES ('13','access_@_groups','Access rights -> Manage groups','module action right');
INSERT INTO u_rights VALUES ('14','access_@_rights','Access rights -> Manage rights','module action right');
INSERT INTO u_rights VALUES ('15','access_@_rights2group','Access rights -> Assign rights to group','module action right');
INSERT INTO u_rights VALUES ('397','site1_apiv1_@_emailfunnels','Ifunnels API V1 -> Email funnels','module action right');
INSERT INTO u_rights VALUES ('398','site1_apiv1_@_contacts','Ifunnels API V1 -> Contacts','module action right');
INSERT INTO u_rights VALUES ('399','site1_apiv1_@_resource','Ifunnels API V1 -> Resource','module action right');
INSERT INTO u_rights VALUES ('359','billing_aggregator_@_send_sms','Billing Aggregator -> Send SMS','module action right');
INSERT INTO u_rights VALUES ('360','billing_aggregator_@_statistic','Billing Aggregator -> Statistic','module action right');
INSERT INTO u_rights VALUES ('361','billing_aggregator_@_clientdata','Billing Aggregator -> Client Data','module action right');
INSERT INTO u_rights VALUES ('362','billing_aggregator_@_cancellations','Billing Aggregator -> Cancellations','module action right');
INSERT INTO u_rights VALUES ('363','site1_traffic_@_campaign','Traffic module -> Create Campaign','module action right');
INSERT INTO u_rights VALUES ('364','site1_traffic_@_manager','Traffic module -> Manage Campaigns','module action right');
INSERT INTO u_rights VALUES ('365','site1_traffic_@_promote','Traffic module -> Promote Campaigns','module action right');
INSERT INTO u_rights VALUES ('366','site1_traffic_@_manage_promote','Traffic module -> Manage Promotions','module action right');
INSERT INTO u_rights VALUES ('367','site1_traffic_@_browse','Traffic module -> Browse Campaigns','module action right');
INSERT INTO u_rights VALUES ('368','site1_traffic_@_credits','Traffic module -> Credits','module action right');
INSERT INTO u_rights VALUES ('369','site1_traffic_@_show_campaign','Traffic module -> Show Campaign','module action right');
INSERT INTO u_rights VALUES ('370','site1_traffic_@_client_trafic_exchange','Traffic module -> Client Traffic Exchange Action','module action right');
INSERT INTO u_rights VALUES ('319','site1_article_rewriter_@_main','CNM Article Rewriter -> Form','module action right');
INSERT INTO u_rights VALUES ('320','site1_article_rewriter_@_defaultVariations','CNM Article Rewriter -> Default Variations','module action right');
INSERT INTO u_rights VALUES ('321','site1_article_rewriter_@_userVariations','CNM Article Rewriter -> User Variations','module action right');
INSERT INTO u_rights VALUES ('328','configuration_@_backups','Configuration -> DB backups','module action right');
INSERT INTO u_rights VALUES ('400','site1_apiv1_@_token','Ifunnels API V1 -> Token','module action right');
INSERT INTO u_rights VALUES ('341','configuration_@_view_table','Configuration -> View DB table','module action right');
INSERT INTO u_rights VALUES ('401','site1_apiv1_@_authorize','Ifunnels API V1 -> Authorize','module action right');
INSERT INTO u_rights VALUES ('352','billing_aggregator_@_manage','Billing Aggregator -> Manage','module action right');
INSERT INTO u_rights VALUES ('353','call_service_@_voice','Call Service -> Calls','module action right');
INSERT INTO u_rights VALUES ('354','call_service_@_sms','Call Service -> SMS','module action right');
INSERT INTO u_rights VALUES ('355','call_service_@_cron','Call Service -> Cron','module action right');
INSERT INTO u_rights VALUES ('371','site1_conversionpixel_@_conversionpixel','CNM convresionpixel -> Conversionpixel','module action right');
INSERT INTO u_rights VALUES ('372','site1_page_@_page','CNM page -> CNM Page','module action right');
INSERT INTO u_rights VALUES ('373','email_funnels_@_getcode','Email Funnels QJMPZ -> Get Code','module action right');
INSERT INTO u_rights VALUES ('374','email_funnels_@_unsubscribe','Email Funnels QJMPZ -> Unsubscribe page','module action right');
INSERT INTO u_rights VALUES ('375','email_funnels_@_webhook','Email Funnels QJMPZ -> Webhook action','module action right');
INSERT INTO u_rights VALUES ('396','site1_apiv1_@_leadchannels','Ifunnels API V1 -> Lead Channels','module action right');
INSERT INTO u_rights VALUES ('394','access_@_content2group','Access rights -> Assign content to groups','module action right');
INSERT INTO u_rights VALUES ('380','members_@_logout','Members -> Logout','module action right');
INSERT INTO u_rights VALUES ('381','members_@_blacklist','Members -> Blacklist','module action right');
INSERT INTO u_rights VALUES ('382','members_@_broadcast','Members -> Email Broadcast','module action right');
INSERT INTO u_rights VALUES ('383','members_@_set','Members -> Add/Edit account','module action right');
INSERT INTO u_rights VALUES ('384','members_@_import','Members -> Import/Export','module action right');
INSERT INTO u_rights VALUES ('385','members_@_manage','Members -> Manage','module action right');
INSERT INTO u_rights VALUES ('403','site1_apiv1_@_memberships','Ifunnels API V1 -> Memberships','module action right');
INSERT INTO u_rights VALUES ('402','site1_apiv1_@_sales','Ifunnels API V1 -> Sales','module action right');


# structure of u_rights2group table;

DROP TABLE IF EXISTS u_rights2group;
CREATE TABLE `u_rights2group` (
  `group_id` int(11) unsigned NOT NULL DEFAULT '0',
  `rights_id` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;


# content of u_rights2group table;

INSERT INTO u_rights2group VALUES ('4','400');
INSERT INTO u_rights2group VALUES ('4','399');
INSERT INTO u_rights2group VALUES ('4','396');
INSERT INTO u_rights2group VALUES ('4','397');
INSERT INTO u_rights2group VALUES ('4','398');
INSERT INTO u_rights2group VALUES ('4','401');
INSERT INTO u_rights2group VALUES ('1','396');
INSERT INTO u_rights2group VALUES ('1','397');
INSERT INTO u_rights2group VALUES ('1','398');
INSERT INTO u_rights2group VALUES ('4','375');
INSERT INTO u_rights2group VALUES ('4','374');
INSERT INTO u_rights2group VALUES ('4','373');
INSERT INTO u_rights2group VALUES ('1','375');
INSERT INTO u_rights2group VALUES ('1','374');
INSERT INTO u_rights2group VALUES ('1','373');
INSERT INTO u_rights2group VALUES ('4','372');
INSERT INTO u_rights2group VALUES ('1','1');
INSERT INTO u_rights2group VALUES ('1','372');
INSERT INTO u_rights2group VALUES ('4','371');
INSERT INTO u_rights2group VALUES ('1','371');
INSERT INTO u_rights2group VALUES ('1','355');
INSERT INTO u_rights2group VALUES ('1','354');
INSERT INTO u_rights2group VALUES ('1','353');
INSERT INTO u_rights2group VALUES ('1','352');
INSERT INTO u_rights2group VALUES ('5','350');
INSERT INTO u_rights2group VALUES ('1','350');
INSERT INTO u_rights2group VALUES ('1','341');
INSERT INTO u_rights2group VALUES ('2','370');
INSERT INTO u_rights2group VALUES ('3','370');
INSERT INTO u_rights2group VALUES ('4','370');
INSERT INTO u_rights2group VALUES ('5','336');
INSERT INTO u_rights2group VALUES ('1','336');
INSERT INTO u_rights2group VALUES ('5','335');
INSERT INTO u_rights2group VALUES ('1','335');
INSERT INTO u_rights2group VALUES ('1','328');
INSERT INTO u_rights2group VALUES ('13','326');
INSERT INTO u_rights2group VALUES ('12','326');
INSERT INTO u_rights2group VALUES ('10','326');
INSERT INTO u_rights2group VALUES ('9','326');
INSERT INTO u_rights2group VALUES ('8','326');
INSERT INTO u_rights2group VALUES ('7','326');
INSERT INTO u_rights2group VALUES ('6','326');
INSERT INTO u_rights2group VALUES ('5','326');
INSERT INTO u_rights2group VALUES ('1','326');
INSERT INTO u_rights2group VALUES ('5','321');
INSERT INTO u_rights2group VALUES ('5','320');
INSERT INTO u_rights2group VALUES ('1','321');
INSERT INTO u_rights2group VALUES ('1','320');
INSERT INTO u_rights2group VALUES ('5','319');
INSERT INTO u_rights2group VALUES ('1','319');
INSERT INTO u_rights2group VALUES ('5','314');
INSERT INTO u_rights2group VALUES ('1','314');
INSERT INTO u_rights2group VALUES ('5','301');
INSERT INTO u_rights2group VALUES ('1','301');
INSERT INTO u_rights2group VALUES ('13','29');
INSERT INTO u_rights2group VALUES ('13','30');
INSERT INTO u_rights2group VALUES ('13','31');
INSERT INTO u_rights2group VALUES ('12','29');
INSERT INTO u_rights2group VALUES ('12','30');
INSERT INTO u_rights2group VALUES ('12','31');
INSERT INTO u_rights2group VALUES ('8','29');
INSERT INTO u_rights2group VALUES ('1','399');
INSERT INTO u_rights2group VALUES ('10','29');
INSERT INTO u_rights2group VALUES ('10','267');
INSERT INTO u_rights2group VALUES ('10','30');
INSERT INTO u_rights2group VALUES ('10','31');
INSERT INTO u_rights2group VALUES ('9','31');
INSERT INTO u_rights2group VALUES ('9','30');
INSERT INTO u_rights2group VALUES ('9','267');
INSERT INTO u_rights2group VALUES ('9','29');
INSERT INTO u_rights2group VALUES ('7','29');
INSERT INTO u_rights2group VALUES ('7','267');
INSERT INTO u_rights2group VALUES ('7','30');
INSERT INTO u_rights2group VALUES ('7','31');
INSERT INTO u_rights2group VALUES ('8','30');
INSERT INTO u_rights2group VALUES ('8','31');
INSERT INTO u_rights2group VALUES ('5','29');
INSERT INTO u_rights2group VALUES ('5','275');
INSERT INTO u_rights2group VALUES ('5','267');
INSERT INTO u_rights2group VALUES ('1','275');
INSERT INTO u_rights2group VALUES ('5','30');
INSERT INTO u_rights2group VALUES ('5','31');
INSERT INTO u_rights2group VALUES ('6','267');
INSERT INTO u_rights2group VALUES ('1','267');
INSERT INTO u_rights2group VALUES ('6','30');
INSERT INTO u_rights2group VALUES ('6','31');
INSERT INTO u_rights2group VALUES ('6','29');
INSERT INTO u_rights2group VALUES ('1','359');
INSERT INTO u_rights2group VALUES ('1','360');
INSERT INTO u_rights2group VALUES ('1','361');
INSERT INTO u_rights2group VALUES ('1','362');
INSERT INTO u_rights2group VALUES ('1','363');
INSERT INTO u_rights2group VALUES ('1','364');
INSERT INTO u_rights2group VALUES ('1','365');
INSERT INTO u_rights2group VALUES ('1','366');
INSERT INTO u_rights2group VALUES ('1','367');
INSERT INTO u_rights2group VALUES ('1','368');
INSERT INTO u_rights2group VALUES ('1','369');
INSERT INTO u_rights2group VALUES ('1','370');
INSERT INTO u_rights2group VALUES ('1','98');
INSERT INTO u_rights2group VALUES ('1','97');
INSERT INTO u_rights2group VALUES ('1','96');
INSERT INTO u_rights2group VALUES ('1','95');
INSERT INTO u_rights2group VALUES ('1','94');
INSERT INTO u_rights2group VALUES ('1','93');
INSERT INTO u_rights2group VALUES ('1','92');
INSERT INTO u_rights2group VALUES ('1','91');
INSERT INTO u_rights2group VALUES ('1','90');
INSERT INTO u_rights2group VALUES ('1','89');
INSERT INTO u_rights2group VALUES ('1','88');
INSERT INTO u_rights2group VALUES ('1','87');
INSERT INTO u_rights2group VALUES ('1','86');
INSERT INTO u_rights2group VALUES ('1','85');
INSERT INTO u_rights2group VALUES ('1','84');
INSERT INTO u_rights2group VALUES ('1','83');
INSERT INTO u_rights2group VALUES ('1','82');
INSERT INTO u_rights2group VALUES ('1','81');
INSERT INTO u_rights2group VALUES ('1','80');
INSERT INTO u_rights2group VALUES ('1','79');
INSERT INTO u_rights2group VALUES ('1','78');
INSERT INTO u_rights2group VALUES ('1','77');
INSERT INTO u_rights2group VALUES ('1','76');
INSERT INTO u_rights2group VALUES ('1','75');
INSERT INTO u_rights2group VALUES ('1','74');
INSERT INTO u_rights2group VALUES ('1','73');
INSERT INTO u_rights2group VALUES ('1','72');
INSERT INTO u_rights2group VALUES ('1','71');
INSERT INTO u_rights2group VALUES ('1','6');
INSERT INTO u_rights2group VALUES ('1','7');
INSERT INTO u_rights2group VALUES ('1','1');
INSERT INTO u_rights2group VALUES ('1','5');
INSERT INTO u_rights2group VALUES ('1','3');
INSERT INTO u_rights2group VALUES ('1','2');
INSERT INTO u_rights2group VALUES ('1','4');
INSERT INTO u_rights2group VALUES ('1','400');
INSERT INTO u_rights2group VALUES ('1','392');
INSERT INTO u_rights2group VALUES ('1','393');
INSERT INTO u_rights2group VALUES ('1','394');
INSERT INTO u_rights2group VALUES ('1','395');
INSERT INTO u_rights2group VALUES ('1','401');
INSERT INTO u_rights2group VALUES ('1','13');
INSERT INTO u_rights2group VALUES ('1','14');
INSERT INTO u_rights2group VALUES ('1','15');
INSERT INTO u_rights2group VALUES ('1','29');
INSERT INTO u_rights2group VALUES ('1','31');
INSERT INTO u_rights2group VALUES ('1','30');
INSERT INTO u_rights2group VALUES ('4','403');
INSERT INTO u_rights2group VALUES ('1','402');
INSERT INTO u_rights2group VALUES ('1','403');
INSERT INTO u_rights2group VALUES ('4','402');


# structure of u_users table;

DROP TABLE IF EXISTS u_users;
CREATE TABLE `u_users` (
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


# content of u_users table;

INSERT INTO u_users VALUES ('1','0','0','0','1','cadmin@api.info','d7734fec458a2e784589d18b1b767c7dj259dU9e','cadmin','UTC','','','','0','0','1585289712');

