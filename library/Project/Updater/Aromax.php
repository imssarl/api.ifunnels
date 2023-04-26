<?php

class Project_Updater_Aromax extends Core_Updater_Abstract {

	public function update( Core_Updater $obj ) {
		$this->connect();
		$this->start();
	}

	private function connect(){
		Core_Sql::disconnect();
		Core_Sql::createConnect( new Zend_Config( array(
			'codepage'=>'utf8',
			'arhitecture'=>'single', // single or replication
			'master'=>array(
				'host'=>'localhost',
				'username'=>'root',
				'password'=>'',
				'dbname'=>'aromax',		
				'adapter'=>'pdo_mysql',
				'port'=>'',
			),
			'slave'=>array(
				'adapter'=>'pdo_mysql',
				'host'=>'',
				'port'=>'',
				'username'=>'',
				'password'=>'',
				'dbname'=>'',
			),
			'paged_select'=>array(
				'row_in_page'=>12,
				'num_of_digits'=>3,
			)
		)));
	}
	
	private function clear(){
//		Core_Sql::setExec('TRUNCATE TABLE jos_categories');
//		Core_Sql::setExec('TRUNCATE TABLE jos_content');
//		Core_Sql::setExec('TRUNCATE TABLE jos_sections');
//		Core_Sql::setExec("INSERT INTO `jos_content` (`id`, `title`, `alias`, `title_alias`, `introtext`, `fulltext`, `state`, `sectionid`, `mask`, `catid`, `created`, `created_by`, `created_by_alias`, `modified`, `modified_by`, `checked_out`, `checked_out_time`, `publish_up`, `publish_down`, `images`, `urls`, `attribs`, `version`, `parentid`, `ordering`, `metakey`, `metadesc`, `access`, `hits`, `metadata`) VALUES
//		(36, 'Where did the Installers go?', 'where-did-the-installer-go', '', '<p>The improved Installer can be found under the Extensions Menu. With versions prior to Joomla! 1.5 you needed to select a specific Extension type when you wanted to install it and use the Installer associated with it, with Joomla! 1.5 you just select the Extension you want to upload, and click on install. The Installer will do all the hard work for you.</p>', '', 1, 4, 0, 30, '2008-08-10 23:16:20', 62, '', '2010-09-30 12:03:28', 62, 0, '0000-00-00 00:00:00', '2006-10-10 04:00:00', '0000-00-00 00:00:00', '', '', 'show_title=\nlink_titles=\nshow_intro=\nshow_section=\nlink_section=\nshow_category=\nlink_category=\nshow_vote=\nshow_author=\nshow_create_date=\nshow_modify_date=\nshow_pdf_icon=\nshow_print_icon=\nshow_email_icon=\nlanguage=\nkeyref=\nreadmore=', 7, 0, 1, '', '', 0, 6, 'robots=\nauthor=');");
//		Core_Sql::setExec("INSERT INTO `jos_sections` (`id`, `title`, `name`, `alias`, `image`, `scope`, `image_position`, `description`, `published`, `checked_out`, `checked_out_time`, `ordering`, `access`, `count`, `params`) VALUES
//		(1, 'Recettes', '', 'news', 'articles.jpg', 'content', 'right', '<p>Select a news topic from the list below, then select a news article to read.</p>', 1, 0, '0000-00-00 00:00:00', 3, 0, 3, ''),
//		(4, 'Infos Pratiques', '', 'infos', '', 'content', 'left', '', 1, 0, '0000-00-00 00:00:00', 2, 0, 16, '')");
//		Core_Sql::setExec("INSERT INTO `jos_categories` (`id`, `parent_id`, `title`, `name`, `alias`, `image`, `section`, `image_position`, `description`, `published`, `checked_out`, `checked_out_time`, `editor`, `ordering`, `access`, `count`, `params`) VALUES
//		(1, 0, 'Latest', '', 'latest-news', 'taking_notes.jpg', '1', 'left', 'The latest news from the Joomla! Team', 1, 0, '0000-00-00 00:00:00', '', 1, 0, 1, ''),
//		(30, 0, 'Infos Pratiques', '', 'pratiques', '', '4', 'left', '<p>About the millions of Joomla! users and Web sites</p>', 1, 0, '0000-00-00 00:00:00', NULL, 3, 0, 0, '')");
//		Core_Sql::setExec('TRUNCATE TABLE jos_jf_content');
//		Core_Sql::setExec("DELETE FROM jos_jf_content WHERE reference_table IN('vm_category','vm_product') ");
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_product_mf_xref');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_product_discount');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_product_attribute_sku');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_product_attribute');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_zone_shipping');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_coupons');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_shipping_rate');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_shipping_carrier');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_category');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_category_xref');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_product');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_product_category_xref');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_product_price');
//		Core_Sql::setExec('TRUNCATE TABLE jos_vm_product_mf_xref');
		Core_Sql::setExec('TRUNCATE TABLE jos_users');
		Core_Sql::setExec('TRUNCATE TABLE jos_core_acl_aro');
		Core_Sql::setExec('TRUNCATE TABLE jos_core_acl_groups_aro_map');
		Core_Sql::setExec('TRUNCATE TABLE jos_vm_user_info');
		Core_Sql::setExec("INSERT INTO `jos_core_acl_aro` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`) VALUES
		(10, 'users', '62', 0, 'Administrator', 0),
		(11, 'users', '63', 0, 'Павел Ливинский', 0)");
		Core_Sql::setExec("INSERT INTO `jos_vm_user_info` (`user_info_id`, `user_id`, `address_type`, `address_type_name`, `company`, `title`, `last_name`, `first_name`, `middle_name`, `phone_1`, `phone_2`, `fax`, `address_1`, `address_2`, `city`, `state`, `country`, `zip`, `user_email`, `extra_field_1`, `extra_field_2`, `extra_field_3`, `extra_field_4`, `extra_field_5`, `cdate`, `mdate`, `perms`, `bank_account_nr`, `bank_name`, `bank_sort_code`, `bank_iban`, `bank_account_holder`, `bank_account_type`) VALUES
		('ab48dddc609e5698db3785cb7a17796f', 62, 'BT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', '', 'US', '', 'kindzadza@mail.ru', NULL, NULL, NULL, NULL, NULL, 1283787825, 1283777306, 'shopper', '', '', '', '', '', 'Checking'),
		('9e83ac996acfcc8874ea093e837c64ac', 63, 'BT', '-default-', 'Coop', 'Mr.', 'Ливинский', 'Павел', '8208984', '8885522', '555665544', '5566555', 'Street 1', '', 'Minsk', ' - ', 'BEL', '222000', 'pavel.livinskij@gmail.com', NULL, NULL, NULL, NULL, NULL, 1285320468, 1285333785, 'shopper', '', '', '', '', '', 'Savings')");
		Core_Sql::setExec("INSERT INTO `jos_users` (`id`, `name`, `username`, `email`, `password`, `usertype`, `block`, `sendEmail`, `gid`, `registerDate`, `lastvisitDate`, `activation`, `params`) VALUES
		(62, 'Administrator', 'admin', 'kindzadza@mail.ru', '57699cc6c7f2835182f318de54b1b991:9DFhwydK5YoqS1UTTzwuZD7v0LRvdJFe', 'Super Administrator', 0, 1, 25, '2010-09-06 18:43:45', '2010-09-24 13:01:58', '', 'admin_language=\nlanguage=\neditor=\nhelpsite=\ntimezone=0\n\n'),
		(63, 'Павел Ливинский', 'user', 'pavel.livinskij@gmail.com', '47235f854a2fc546734389c11ec7b869:hDu9pC9IjbMdHbHzQHXZjjSyuTUxr8wU', 'Registered', 0, 0, 18, '2010-09-24 09:27:48', '2010-09-24 13:02:51', 'fe5720646e95d94ddb82676dffb075fa', 'language=de-DE\ntimezone=0\n\n')");
		Core_Sql::setExec("INSERT INTO `jos_core_acl_groups_aro_map` (`group_id`, `section_value`, `aro_id`) VALUES
		(25, '', 10),
		(18, '', 11)");
	}
	
	private function start(){
		$this->clear();
		
		$_arrCat2product = Core_Sql::getAssoc('SELECT categoryID,productID FROM produit');
		// переносим категории
		$_arrCategory = Core_Sql::getAssoc('select * from categorie');
		foreach ( $_arrCategory as $_item ){
			$_arr = array(
				'vendor_id' => 1,
				'category_name' => $this->replace( $_item['nameFR'] ),
				'category_description' => $this->replace( $_item['nameFR']),
				'category_publish' => 'Y',
				'cdate' => time(),
				'mdate' => time()
			);
			$_newId = Core_Sql::setInsert('jos_vm_category', $_arr );
			if ( $_newId ) {
				foreach ( $_arrCat2product as &$_v ){
					if ( $_v['categoryID'] == $_item['categoryID'] ){
						$_v['categoryID'] = $_newId;
					}
				}
			}
			// линки для категорий
			Core_Sql::setInsert('jos_vm_category_xref', array(
			'category_parent_id' => 0,
			'category_child_id'	=> $_newId
			) );
			// языки для категорий
			Core_Sql::setInsert('jos_jf_content',
			array(
				'language_id' => 2,
				'reference_id' => $_newId,
				'reference_table' => 'vm_category',
				'reference_field' => 'category_name',
				'value' => $this->replace( $_item['nameDE'] ),
				'original_value' => md5( $this->replace( $_item['nameFR'] ) ),
				'modified_by' => 62,
				'modified' => date('y-m-d H:M:S',time()),
				'published' => 1
			));
			Core_Sql::setInsert('jos_jf_content',
			array(
				'language_id' => 2,
				'reference_id' => $_newId,
				'reference_table' => 'vm_category',
				'reference_field' => 'category_description',
				'value' => $this->replace( $_item['nameDE'] ),
				'original_value' => md5( $this->replace( $_item['nameFR'] ) ),
				'modified_by' => 62,
				'modified' => date('y-m-d H:M:S',time()),
				'published' => 1
			));			
		}
		
		
		//переносим продукты
		$_arrProducts = Core_Sql::getAssoc('SELECT * FROM produit');
		$i=0;
		foreach ( $_arrProducts as $_key=>$_item ){
			$_strDesc = new Core_String(strip_tags(htmlspecialchars_decode( $this->replace( str_replace("\n",'<br/>',$_item['descFR']) ))));
			$_strDesc_de = new Core_String(strip_tags(htmlspecialchars_decode( $this->replace( str_replace("\n",'<br/>',$_item['descDE'])) )));
			$_arr = array(
				'vendor_id' => 1,
				'product_name' =>$this->replace(  htmlspecialchars_decode( $_item['nameFR'] ) ),
				'product_desc' => $this->replace( htmlspecialchars_decode( str_replace("\n",'<br/>',$_item['descFR'] ) ) ),
				'product_s_desc' => $_strDesc->ellipsisEnding(150),
				'product_thumb_image' => 'resized/'.$_item['image'], 
				'product_full_image' => $_item['image'],
				'product_publish' => 'Y',
				'cdate' => strtotime($_item['dateCreation']),
				'product_sku' => 'G'. $i++,
				'mdate' => time(),
				'product_discount_id' => 0,
				'attribute' =>'',
				'product_tax_id' => 0,
				'product_unit' => 'piece',
				'product_packaging' => 0,
				'quantity_options' => 'none,0,0,1',
				'child_options' => 'N,YM,Y,N,N,Y,,,',
				'child_option_ids' => '',
				'product_order_levels' => '0,0',
				'product_weight' => '0.0000',
				'product_weight_uom' => 'pounds',
				'product_length' => '0.0000',
				'product_width' => '0.0000',
				'product_height' => '0.0000',
				'product_lwh_uom' => 'inches',
				'product_url' => '',
				'product_available_date' => time(),
				'product_special' => 'N'
			);
			$_newId=Core_Sql::setInsert('jos_vm_product', $_arr );
			if ( $_newId ){
				foreach ( $_arrCat2product as &$_v ){
					if ( $_v['productID'] == $_item['productID'] ){
						$_v['productID'] = $_newId;
					}
				}
			}
			// добавляем опции к продукту.
			$_arrNewOption = array(
				'product_id' => $_newId,
				'attribute_name' => 'Option',
				'attribute_value' => ''
			);
			Core_Sql::setInsert( 'jos_vm_product_attribute', $_arrNewOption );
			$_arrNewOption = array(
				'product_id' => $_newId,
				'attribute_name' => 'Option',
				'attribute_list' => '1'
			);
			Core_Sql::setInsert( 'jos_vm_product_attribute_sku', $_arrNewOption );
			
			$_arrManufacture = array(
				'product_id'=>$_newId,
				'manufacturer_id'=>1
			);
			Core_Sql::setInsert('jos_vm_product_mf_xref',$_arrManufacture);
			
			$_arrOptions = Core_Sql::getAssoc('SELECT * FROM options WHERE productID='.$_item['productID'] .' ORDER BY poids' );
			$_strSKU=$_arr['product_sku'];
			$intSku=1;
			foreach ( $_arrOptions as $_key=>$_option ){
				if (empty($_option['texteFR'])){
					continue;
				}
				if ( $_key == 0 ){ // Цена для родительского продукта;
					$_arrPrice = array(
					'product_id' => $_newId,
					'product_price' => $_option['price'],
					'product_currency' => 'CHF',
					'cdate' => time(),
					'shopper_group_id' => 5,
					'price_quantity_start' => $_option['poids'],
					'product_price_vdate' => 0,
					'product_price_edate' => 0
					);
					Core_Sql::setInsert('jos_vm_product_price', $_arrPrice );
				}
				
				$_arr['product_parent_id'] = $_newId;
				$_arr['product_name'] = $_option['texteFR'];
				$_arr['product_sku'] = $_strSKU .'-'.$intSku++;
				$_arr['child_options'] = '';
				$_newChildId=Core_Sql::setInsert('jos_vm_product', $_arr );
				$_arrPrice = array(
					'product_id' => $_newChildId,
					'product_price' => $_option['price'],
					'product_currency' => 'CHF',
					'cdate' => time(),
					'shopper_group_id' => 5,
					'price_quantity_start' => $_option['poids'],
					'product_price_vdate' => 0,
					'product_price_edate' => 0
				);
				Core_Sql::setInsert('jos_vm_product_price', $_arrPrice );
				// добавляем опции к продукту.
				$_arrNewOption = array(
					'product_id' => $_newChildId,
					'attribute_name' => 'Option',
					'attribute_value' => ''
				);
				Core_Sql::setInsert( 'jos_vm_product_attribute', $_arrNewOption );
				$_arrNewOption = array(
					'product_id' => $_newChildId,
					'attribute_name' => 'Option',
					'attribute_list' => '1'
				);
				Core_Sql::setInsert( 'jos_vm_product_attribute_sku', $_arrNewOption );							

				$_arrManufacture = array(
					'product_id'=>$_newChildId,
					'manufacturer_id'=>1
				);
				Core_Sql::setInsert('jos_vm_product_mf_xref',$_arrManufacture);
							
			}
			
			// языки для продуктов
			Core_Sql::setInsert('jos_jf_content',
			array(
				'language_id' => 2,
				'reference_id' => $_newId,
				'reference_table' => 'vm_product',
				'reference_field' => 'product_name',
				'value' => (!empty($_item['nameDE'])? $this->replace( htmlspecialchars_decode( $_item['nameDE'] ) ):''),
				'original_value' => md5( $_item['nameFR'] ),
				'modified_by' => 62,
				'modified' => date('y-m-d H:M:S',time()),
				'published' => 1
			));
			Core_Sql::setInsert('jos_jf_content',
			array(
				'language_id' => 2,
				'reference_id' => $_newId,
				'reference_table' => 'vm_product',
				'reference_field' => 'product_desc',
				'value' => (!empty($_item['descDE'])? $this->replace( htmlspecialchars_decode( str_replace("\n",'<br/>',$_item['descDE']) ) ) :''),
				'original_value' => md5( $_item['descFR'] ),
				'modified_by' => 62,
				'modified' => date('y-m-d H:M:S',time()),
				'published' => 1
			));
			Core_Sql::setInsert('jos_jf_content',
			array(
				'language_id' => 2,
				'reference_id' => $_newId,
				'reference_table' => 'vm_product',
				'reference_field' => 'product_s_desc',
				'value' => $_strDesc_de->ellipsisEnding(150),
				'original_value' => md5( $_strDesc->ellipsisEnding(150) ),
				'modified_by' => 62,
				'modified' => date('y-m-d H:M:S',time()),
				'published' => 1
			));
		}
			// линки на продукты
		foreach ( $_arrCat2product as $_item ){
			Core_Sql::setInsert('jos_vm_product_category_xref',array(
			'category_id' => $_item['categoryID'],
			'product_id' => $_item['productID']
			));
		}		
		/*
		
		// переносим пользователей
		$_arrUsers = Core_Sql::getAssoc('SELECT * FROM customer');
		foreach ( $_arrUsers as $_item ){
			$_arr = array(
				'name' => $_item['prenom'] . ' ' . $_item['nom'],
				'username' => $_item['email'],
				'email' => $_item['email'],
				'password' => $_item['password'],
				'usertype' => 'Registered',
				'block' => 0,
				'sendEmail' => 0,
				'gid' => 18,
				'registerDate' => $_item['creationDate'],
				'params' => 'language=de-DE timezone=0'
			);
			$_newId = Core_Sql::setInsert( 'jos_users', $_arr );
			
			$_aeroId=Core_Sql::setInsert('jos_core_acl_aro' , array(
			'section_value' => 'users',
			'value' => $_newId,
			'name' => $_arr['name']
			));
			Core_Sql::setInsert('jos_core_acl_groups_aro_map',
			array(
				'group_id' => 18,
				'aro_id' => $_aeroId
			)
			);
			
			$_arrUserInfo = array(
			'user_info_id' => md5(uniqid( $hash_secret)),
			'user_id' => $_newId,
			'address_type' => 'BT',
			'address_type_name' => '-default-',
			'company' => $_item['societe'],
			'last_name' => $_item['nom'],
			'first_name' => $_item['prenom'],
			'phone_1' => $_item['tel'],
			'address_1' => $_item['rue'],
			'city' => $_item['ville'],
			'country' => (($_item['pays'] == 'CH') ? 'CHE' : $_item['pays'] ),
			'zip' => $_item['cp'],
			'user_email' => $_item['email'],
			'cdate' => strtotime( $_item['creationDate']),
			'perms' => 'shopper',
			'bank_account_type' => 'Checking'
			);
			Core_Sql::setInsert('jos_vm_user_info', $_arrUserInfo );
		}
/*		
//		p('stop');
		// переносим статьи.
		$_arrCat2content=Core_Sql::getAssoc('SELECT categoryID as category_id, recetteID as content_id FROM recette ');
		// категории для статей.
		$_arrArtCategory = Core_Sql::getAssoc('SELECT * FROM categories_recettes');
		
		foreach ($_arrArtCategory as $_key=>$_item ){
			$_arr = array(
				'parent_id' => 0,
				'title' => $this->replace( ucfirst($_item['nameFR']) ),
				'alias' => $_item['categoryID'],
				'section' => 1,
				'image_position' => 'left',
				'published' => 1,
				'checked_out' => 0,
				'ordering' => $_key,
				'access' => 0,
				'count' => 1,
			);
			$_newId=Core_Sql::setInsert('jos_categories',$_arr);
			foreach ( $_arrCat2content as &$_v ){
				if ( $_v['category_id'] == $_item['categoryID'] ){
					$_v['category_id'] = $_newId;
				}
			}
			// языки для категорий
			Core_Sql::setInsert('jos_jf_content',
			array(
				'language_id' => 2,
				'reference_id' => $_newId,
				'reference_table' => 'categories',
				'reference_field' => 'title',
				'value' => (!empty($_item['nameDE'])? ucfirst($_item['nameDE']) :'' ),
				'original_value' => md5( ucfirst($_item['nameFR']) ),
				'modified_by' => 62,
				'modified' => date('y-m-d H:M:S',time()),
				'published' => 1
			));
		}
		// статьи
		$_arrContent=Core_Sql::getAssoc('SELECT * FROM recette ');
		foreach ( $_arrContent as $_key=>$_item ){
			foreach ( $_arrCat2content as $_v ){
				if ( $_item['recetteID'] == $_v['content_id'] ){
					$_categoryId = $_v['category_id'];
				}
			}
			$strText = new Core_String( $this->replace( strip_tags( htmlspecialchars_decode( $_item['texte'] ) ) ) );
			$_arr = array(
			'title' => $this->replace( $_item['nom'] ),
			'alias' => $_item['recetteID'],
			'introtext' =>  ( (!empty($_item['image'])) ? '<table><tr><td align="left" valign="top"><img src="/images/recettes/tn/'.$_item['image'].'" border="0"></td><td width="10"></td><td align="left" valign="top">'.$strText->ellipsisEnding(150).'<td></tr></table>' : $strText->ellipsisEnding(150)),
			'fulltext' => ( (!empty($_item['image'])) ? '<table><tr><td align="left" valign="top"><img src="/images/recettes/big/'.$_item['image'].'" border="0"></td><td width="10"></td><td align="left" valign="top">'.$this->replace( htmlspecialchars_decode( str_replace("\n",'<br/>', $_item['texte'] ) ) ).'<td></tr></table>' : $this->replace( htmlspecialchars_decode( str_replace("\n",'<br/>', $_item['texte'] ) ) )),
			'state' => 1,
			'sectionid' => 1,
			'catid' => $_categoryId,
			'created' => date('Y-m-d H:M:S',time() - 86000 ),
			'created_by' => 62,
			'modified' => date('Y-m-d H:M:S',time() - 86000 ),
			'modified_by' => 62,
			'checked_out' => 0,
			'publish_up' => date('Y-m-d H:M:S',time() - 86000 ),
			'version' => 7,
			'ordering' => 1,
			'hits' => 7
			);
			$_newId = Core_Sql::setInsert('jos_content', $_arr );
		}
		*/
	}
	
	private function replace( $str ){
		return str_replace(
		array('&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&sup1;','&ordm;','&raquo;',
				'&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;',
				'&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;',
				'&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;','&fnof;'),
		array('¡','¢','£','¤','¥','¦','§','¨','©','ª','«','¬','­','®','¯','°','±','²','³','´','µ','¶','·','¸','¹','º','»','¼','½','¾','¿','À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','×','Ø','Ù','Ú','Û','Ü','Ý','Þ','à','á',
				'â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','÷','ø','ù','ú','û','ü','ý ','þ','ÿ ','ƒ'), $str );
	}
}
?>