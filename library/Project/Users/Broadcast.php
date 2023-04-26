<?php


/**
 * Send Messages to Users
 */

class Project_Users_Broadcast {

	/**
	 * Core_Data
	 *
	 * @var Core_Data object
	 */
	private $_data=false;

	/**
	 * Errors
	 * @var array
	 */
	private $_error=array();

	/**
	 * Users to send message
	 * @var array
	 */
	private $_to=array();

	/**
	 * Create Core_Data object
	 * @param $_arr
	 * @return Project_Users_Broadcast
	 */
	public function setEntered( $_arr ){
		$this->_data=new Core_Data( ((empty($_arr))?array():$_arr) );
		return $this;
	}

	/**
	 * Set users by ids
	 * @param $_ids
	 * @return Project_Users_Broadcast
	 */
	public function setToByUsersId( $_ids ){
		if(!empty($_ids) ){
			$_users=new Project_Users_Management();
			$_users->onlyActive()->withoutUnsubscribe()->withIds( $_ids )->getList( $this->_to );
		}
		return $this;
	}

	/**
	 * Set users by group sys_name
	 * @param $_mix
	 * @return Project_Users_Broadcast
	 */
	public function setToByGroups( $_mix ){
		if(!is_array($_mix)){
			$_mix=array($_mix);
		}
		if(!empty($_mix)){
			$_user=new Project_Users_Management();
			$_user->onlyActive()->withoutUnsubscribe()->withGroups( $_mix )->getList( $this->_to );
		}
		return $this;
	}

	/**
	 * Get errors
	 * @return array
	 */
	public function getErrors(){
		return $this->_error;
	}

	/**
	 * Set error
	 * @param $_str
	 * @return bool
	 */
	private function setError($_str){
		$this->_error[]=$_str;
		return false;
	}

	/**
	 * Send message
	 * @return bool
	 */
	public function send(){
		if(empty($this->_to)){
			return $this->setError('Can\'t find users to send message');
		}
		$_arrReplacePattern=array(
			"{SEND_EMAIL}",
			"{SEND_NAME}",
			"{SEND_AMOUNT}",
			"\n"
		);
		if(!$this->_data->setFilter(array( 'trim', 'clear' ))->setChecker(array(
			'message'=>empty( $this->_data->filtered['text'] )&&empty( $this->_data->filtered['html'] ),
			'from'=>empty( $this->_data->filtered['from'] ),
			'theme'=>empty( $this->_data->filtered['theme'] ),
		))){
			return $this->setError('Incorrect input data');
		}
		$this->_data->setElement('message',(empty($this->_data->filtered['text'])?$this->_data->filtered['html']:$this->_data->filtered['text']));
		foreach( $this->_to as $v ){
			if( !Core_Mailer::getInstance()
				->setTemplate( 'broadcast' )
				->setSubject( $this->_data->filtered['theme'] )
				->setPeopleFrom( array('email'=>$this->_data->filtered['email'],'name'=>$this->_data->filtered['name']) )
				->setVariables( array(
					'message'=>str_replace( $_arrReplacePattern, array( $v['email'], $v['nickname'], $v['amount'], "<br/>" ), $this->_data->filtered['message'] ).
						'<br/><a target="_blank" href="https://'.Zend_Registry::get( 'config' )->engine->project_domain.'/unsubscribe/'./*Core_Module_Router::getCurrentUrl( array('name'=>'site1_accounts', 'action'=>'unsubscribe' ) )*/'?params='.Core_Payment_Encode::encode( array( 'id'=>$v['id'], 'flg_unsubscribe'=> 1 ) ).'">Unsubscribe</a>'
				) )
				->setPeopleTo( array( 'email'=>$v['email'], 'name'=>$v['nickname'] ) )
				->sendOneToMany() ){
				$_arrMessage[]='False sended to '. $v['nickname'];
			}
			Core_Mailer::getInstance()->clearSubject()->clearFrom()->clearReplyTo()->clearHeader('X-Mailer')->clearRecipients();
		}
		return true;
	}
}
?>