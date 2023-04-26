<?php


/**
 * Mail event management
 * @internal Писано не мной - я выделил в отдельный класс
 */


class Core_Mailer_Events extends Core_Services implements Core_Singleton_Interface {

	private static $_instance=NULL;

	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Core_Mailer_Events();
		}
		return self::$_instance;
	}

	public function getEvents( &$arrRes ) {
		$arrRes=Core_Sql::getKeyVal( 'SELECT id, title FROM mail_event ORDER BY title' );
	}

	public function get_info_by_event( &$arrTo, &$arrFrom, $_strFlg='' ) {
		if ( empty( $_strFlg ) ) {
			return false;
		}
		if ( $this->get_emails_from( $_strFlg, $_arrFrom ) ) {
			$arrFrom=array_merge((array)$arrFrom,(array)$_arrFrom);
		}
		if ( $this->get_emails_to( $_strFlg, $_arrTo ) ) {
			$arrTo=array_merge((array)$arrTo,(array)$_arrTo);
		}
		return true;
	}

	function get_events($filter = null){
		if ($filter){
			$filter = "AND mail_event.sites_id = ".$filter." ";
			}
			return Core_Sql::getAssoc("SELECT " .
				 "mail_event.id, " .
				 "mail_event.sys_name AS event_sys_name, " .
				 "mail_event.title, " .
				 "mail_event.description, " .
				 "sys_site.sys_name " .
				 "FROM mail_event, sys_site " .
				 "WHERE sys_site.id = mail_event.sites_id ".$filter
				 );
	}

	function get_emails($order = 'email'){
		return Core_Sql::getAssoc("SELECT id, email, name, added " .
						 "FROM mail_address " .
						 "ORDER BY ".$order);
	}


	function get_email_info($id){
		if (empty($id)) return false;
		return Core_Sql::getRecord("SELECT " .
							"id, " .
							"email, " .
							"name " .
							"FROM mail_address " .
							"WHERE id = ".$id);
	}

	function get_sys_sites_names (){
		return  Core_Sql::getKeyVal("SELECT id, sys_name " .
						 "FROM sys_site ");
	}

	function get_event_info($id){
		if (empty($id)) return false;
		return Core_Sql::getRecord("SELECT " .
							"id, " .
							"sys_name, " .
							"title, " .
							"description, " .
							"sites_id " .
							"FROM mail_event " .
							"WHERE id =".$id);
	}



	function get_sites_actions(){
		$arrSites = Core_Sql::getAssoc("SELECT id, sys_name FROM sys_site");
		if (!empty($arrSites)){
			foreach ($arrSites as $value){
				$arrSel[$value['sys_name']] = Core_Sql::getKeyVal("SELECT id, title FROM mail_event " .
															   	   "WHERE sites_id = ".$value['id']);
			}
		}
		return $arrSel;
	}


	function get_link_emails($event_id, $flg){
		return Core_Sql::getAssoc("SELECT a.*, l.address_id FROM mail_address a ".
						 "LEFT JOIN mail_link l ON l.address_id = a.id AND l.event_id = ".$event_id." AND l.flg_type = ".$flg);
	}


	function get_link_emails_to($event_id, &$emails_to=null){
		if (empty($event_id)) return false;
		$emails_to = $this->get_link_emails($event_id, 1);
		return $emails_to;
	}


	function get_link_emails_from($event_id, &$emails_from=null){
		if (empty($event_id)) return false;
		$emails_from = $this->get_link_emails($event_id, 2);
		return $emails_from;
	}


	function get_email_name($event_name, $flg_type){
		return Core_Sql::getAssoc("SELECT a.email, a.name FROM mail_address a, mail_link l ".
							"WHERE a.id = l.address_id AND l.flg_type = ".$flg_type." AND l.event_id = ".
							"(SELECT id FROM mail_event WHERE sys_name = '".$event_name."')");
	}


	function get_emails_to($event_name, &$emails){
		if (empty($event_name)) return false;
		$emails = $this->get_email_name($event_name, 1);
		return !empty( $emails );
	}


	function get_emails_from($event_name, &$emails){
		if (empty($event_name)) return false;
		$emails = $this->get_email_name($event_name, 2);
		return !empty( $emails );
	}


	function delete_event($del_list){
		Core_Sql::setExec("DELETE FROM mail_event WHERE id IN (".join(", ", $del_list).")");
			Core_Sql::setExec("DELETE FROM mail_link WHERE event_id IN (".join(", ",$del_list).")");
	}

	function delete_email($del_list){
			Core_Sql::setExec("DELETE FROM mail_address " .
					"WHERE id IN (".join(", ", $del_list).")");
			Core_Sql::setExec("DELETE FROM mail_link " .
					"WHERE address_id IN (".join(", ", $del_list).")");
	}

	function address_attach($address_id, $event_id, $flg) {
		Core_Sql::setInsert("mail_link", array('address_id' => $address_id,
									 'event_id' => $event_id,
									 'flg_type' => $flg));
	}


	function add_update_event($input_data, &$form_data, &$arrErr){
		$form_data = Core_A::array_check($input_data, $this->post_filter);
		if ($form_data['id']){
			if ($this->error_check($form_data, $arrErr, $form_data,
					array ('title'=>empty($form_data['title']),
						   'sites_id'=>empty($form_data['sites_id'])
							))){
				Core_Sql::setUpdate('mail_event', $form_data);
				return true;
			}
		}
		elseif ($this->error_check($form_data, $arrErr, $form_data,
					array ('sys_name'=>empty($form_data['sys_name']),
						   'title'=>empty($form_data['title']),
						   'sites_id'=>empty($form_data['sites_id']),
						   'sys_name_exist'=>(Core_Sql::getRecord("SELECT id ".
						   								"FROM mail_event ".
						   								"WHERE sys_name ='".$form_data['sys_name']."'")!=null)
						   ))){
			Core_Sql::setInsert('mail_event', $form_data);
			return true;
		}
		return false;
	}


	function add_update_email(&$form_data, &$arrErr, $input_data = null, $input_data_list = null){
		if (!empty($input_data)){
			$form_data = Core_A::array_check($input_data, $this->post_filter);
			if ($form_data['id'])
			{
				if ($this->error_check($form_data, $arrErr, $form_data,
								   array ('email' => empty($form_data['email']),
								   ))){
				Core_Sql::setUpdate('mail_address', $form_data);
				return true;
				}
			}
			elseif ($this->error_check($form_data, $arrErr, $form_data,
								   array ('email' => empty($form_data['email']),
								   ))){
				$form_data['added'] = time();
				Core_Sql::setInsert('mail_address', $form_data);
				if (empty($input_data_list)) return true;
			}
		}
		if (!empty($input_data_list)){
			if ($this->sv_check_email_list($arrEmail, trim($input_data_list))){
				foreach($arrEmail as $value){
					Core_Sql::setInsert('mail_address', array('email' => $value,
													'added' => time(),));
				}
				return true;
			}
		}
		return false;
	}
}
?>