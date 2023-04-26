<?php
class Project_Ccs_Facebook extends Core_Data_Storage {

	/**
	 * Адрес обработчика приложений
	 * @var string
	 */
	 
	protected $apiUrl='https://graph.facebook.com/v2.8/';

	public static $_appId='2055626591118006'; // APP ID
	protected static $_appSecret='df80c70618dd1a331831df9d5c367400'; // APP Secret
	protected static $_appToken='EAAdNlNzBIrYBAGOpvsjGaFFlpMWZBEwRjJhxNZAcEydI2STWhIcBzKxRYbxqZCZAkdz8EBh7ZCnpC5FWyDcqxoC9p0HGkfRy23WdWi87WOTD7BZCuLEX5Pw2Gk8pyE1vI2RrScskYWznmvIvpAcAwHVx2NrjhdykwTiTZBdWYvE3AZDZD'; // Page access token

	public $flgTest=false;
	public $_withLogger=true;
	public $_logger=false;
	
	protected $_table='log_facebook';
	protected $_fields=array('id','page_id','user_id','body','edited','added');
	
	public function install(){	
		Core_Sql::setExec('DROP TABLE IF EXISTS log_facebook');
		Core_Sql::setExec('CREATE TABLE `log_facebook` (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`page_id` int(11) unsigned NOT NULL DEFAULT \'0\',
				`user_id` int(11) unsigned NOT NULL DEFAULT \'0\',
				`body` TEXT NULL,
				`edited` int(11) unsigned NOT NULL DEFAULT \'0\',
				`added` int(11) unsigned NOT NULL DEFAULT \'0\',
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8'
		);
	}

	private $_withPage=false;

	public function withPage( $_mixIds ){
		if( !empty($_mixIds)){
			$this->_withPage=$_mixIds;
		}
		return $this;
	}

	private $_withUserId=false;

	public function withUserId( $_mixIds ){
		if( isset($_mixIds) ){
			$this->_withUserId=$_mixIds;
		}
		return $this;
	}
	
	protected function init() {
		parent::init();
		$this->_withPage=false;
		$this->_withUserId=false;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if( $this->_withPage ){
			$this->_crawler->set_where('d.page_id IN ('. Core_Sql::fixInjection($this->_withPage) .')');
		}
		if( $this->_withUserId ){
			$this->_crawler->set_where('d.user_id IN ('. Core_Sql::fixInjection($this->_withUserId) .')');
		}
	}
	
	public function run( $call ){
		if (!empty($call['entry'][0]['messaging'])) {
			foreach ($call['entry'][0]['messaging'] as $message) {
				// Пропускаем сообщение о доставке
				if( isset($message['delivery']) ){
					continue;
				}
				// пропускаем ответы
				if( ($message['message']['is_echo'] == "true") ){
					continue;
				}
				$arrUser=array();
				$_fbUserArray=$this->call( $message['sender']['id'], array( 'fields'=>'first_name,last_name' ), 'get' );
				$command=$action="";
				// когда пользователь что-то ввел и мы получаем сообщение
				if (!empty($message['message'])) {
					$command=trim($message['message']['text']);
				}
				
				// когда пользователь нажал кнопку
				if (!empty($message['postback'])) {
					$action=trim($message['postback']['payload']);
				}
				
				$_users=new Project_Users_Management();
				// привязка пользователя по email если несколько пользователей с одинаковым именем
				preg_match_all('/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i', $command, $_emailMatches);
				if( isset( $_emailMatches[0] ) ){
					$_users->withEmail( $_emailMatches[0] );
				}
				if( @$_SERVER['HTTP_HOST'] != 'cnm.local' ){
					$_users->withFacebookMessengerId( $message['sender']['id'] )->getList( $arrUser );
				}else{
					$_users->withIds( 39180 )->getList( $arrUser );
					$_fbUserArray['first_name']=$_fbUserArray['last_name']='Dev';
				}
				if( empty( $arrUser[0]['fb_messenger_id'] ) ){
					$_users2=new Project_Users_Management();
					$_fbUserId=md5( $_fbUserArray['first_name'].$_fbUserArray['last_name'] );
					$_users2->withFacebookId( $_fbUserId )->getList( $arrUser );
					if( count( $arrUser ) > 1 ){
						$this->send($message['sender']['id'], "What is your CNM email address?" );
						$this->setEntered( array(
							'page_id'=>$call['entry'][0]['id'],
							'body'=>base64_encode( serialize( $call ) ),
						) )->set();
						continue;
					}
					if( isset( $arrUser[0]['id'] ) ){
						$arrUser[0]['fb_messenger_id']=$message['sender']['id'];
						unset( $arrUser[0]['passwd']);
						$_users3=new Project_Users_Management();
						$_users3->setEntered( $arrUser[0] )->set();
					}
				}
				// open for facebook test
				if( !isset( $arrUser[0]['id'] ) ){
					$_users4=new Project_Users_Management();
					$_users4->withIds( 1 )->getList( $arrUser );
				}
				// open for facebook test */
				if( !isset( $arrUser[0]['id'] ) ){
					$this->send($message['sender']['id'], "Please activate your Facebook Account in AzonFunnels settings. You may also watch video tutorial at: http://help.zonterest.com" );
					continue;
				}else{
					$this->setEntered( array(
						'page_id'=>$call['entry'][0]['id'],
						'user_id'=>$arrUser[0]['id'],
						'body'=>base64_encode( serialize( $call ) ),
					) )->set();
				}
				if( $this->_withLogger ){
					$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Facebook_Messanger/user_'.$arrUser[0]['id'].'.log' );
					$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
					$this->_logger=new Zend_Log( $_writer );
					$this->_logger->info('Command : '.$command );
				}
				// Запускаем обработчик команд
				Core_Users::getInstance()->setById( $arrUser[0]['id'] );
				if( !Core_Acs::haveRight( array( 'ccs'=>array( 'facebook' )) ) ){
					$this->send($message['sender']['id'], "This functional is not available. Please check your access settings and upgrade. Error:".$arrUser[0]['id'] );
					continue;
				}
				$_answer=false;
				if( !empty( $command ) && strpos( $command, ' ' )===false && strpos( $command, '.' )!==false ){ // слабая проверка на вводимый домен
					$_botAI=new Project_Bot( $arrUser[0]['id'] );
					$_botAI->getLastCommand( $_lastCommand );
					if( $_lastCommand['question'] == 'domain_name#set' ){
						if( Project_Wizard_Domain::check( $command ) === true ){
							$_botAI=new Project_Bot( $arrUser[0]['id'] );
							$_botAI->removeCommand( $_lastCommand['id'] );
							$_botAI->updateCatch( array('domain'=>$command) );
							$_botAI->getAnswer( $_answer, $_catch );
							$_answer.=' Site will be created on the domain '.$command.'.';
						}else{
							$_answer='This domain is not available. Try with another one.';
						}
						$action='#';
						$command='';
					}
				}
				$_keyword=$_category=false;
				if (!empty($action)){
					try{
						if( strpos( strtolower( $action ), 'site_page' )!==false ){ // выбор сайта для изменения шаблона
							$_actionTemp=explode( '#', $action );
							$_curentPage=1;
							if( isset( $_actionTemp[1] ) ){
								$_curentPage=(int)$_actionTemp[1];
							}
							$model=new Project_Sites( Project_Sites::NCSB );
							$model
								->withCategory( 'Zonterest' ) // 641
								->getList( $_arrSites );
							$_answer=array();
							if( $_curentPage != 1 ){
								$_answer[]=array(
									'title'=>'Page',
									'buttons'=>array(
										array(
											"type"=>"postback",
											"title"=>"Prev Page",
											"payload"=>"site_page#".($_curentPage-1)
										)
									)
								);
							}
							$_buble=array_shift( $_arrSites );
							$_arrayChunk=array_chunk( $_arrSites, 8 );
							$_arrayChunk[0][]=$_buble;
							foreach( $_arrayChunk[$_curentPage-1] as $_key=>$_template ){
								if( count( $_answer ) < 9 ){
									$_answer[]=array(
										'title'=>$_template['main_keyword'],
										'subtitle'=>'Added: '.gmdate( "Y-m-d", $_template['added'] ),
										'buttons'=>array(
											array(
												'type'=>'web_url',
												'url'=>$_template['url'],
												"title"=>'View'
											),
											array(
												"type"=>"postback",
												"title"=>"Select ".$_template['main_keyword'],
												"payload"=>"template_page#1#".$_template['id']
											)
										)
									);
								}
							}
							if( isset( $_arrayChunk[$_curentPage+1] ) ){
								$_answer[]=array(
									'title'=>'Page',
									'buttons'=>array(
										array(
											"type"=>"postback",
											"title"=>"Next Page",
											"payload"=>"site_page#".($_curentPage+1)
										)
									)
								);
							}
						}elseif( strpos( strtolower( $action ), 'template_page' )!==false ){ // изменение шаблона для выбранного сайта
							$_actionTemp=explode( '#', $action );
							$_curentPage=1;
							if( isset( $_actionTemp[1] ) ){
								$_curentPage=(int)$_actionTemp[1];
							}
							$_templates=new Project_Sites_Templates( Project_Sites::NCSB );
							$_templates->withRight()->toSelect()->getList( $_arrTemplates );
							$_templates->withPreview()->withIds( array_keys($_arrTemplates) )->getList( $_strTemplatesInfo );
							$_answer=array();
							if( $_curentPage != 1 ){
								$_answer[]=array(
									'title'=>'Page',
									'buttons'=>array(
										array(
											"type"=>"postback",
											"title"=>"Prev Page",
											"payload"=>"template_page#".($_curentPage-1)."#".$_actionTemp[2]
										)
									)
								);
							}
							$_buble=array_shift( $_strTemplatesInfo );
							$_arrayChunk=array_chunk( $_strTemplatesInfo, 8 );
							$_arrayChunk[0][]=$_buble;
							foreach( $_arrayChunk[$_curentPage-1] as $_key=>$_template ){
								if( count( $_answer ) < 9 ){
									$_answer[]=array(
										'title'=>$_template['title'],
										'subtitle'=>$_template['description'],
										"image_url"=>'https://'.$_SERVER['HTTP_HOST'].'/'.str_replace( '\\', '/', trim( $_template['image'], '.' )),
										'buttons'=>array(
											array(
												"type"=>"postback",
												"title"=>"Select ".$_template['title'],
												"payload"=>"change_template#".$_template['id']."#".$_actionTemp[2]
											)
										)
									);
								}
							}
							if( isset( $_arrayChunk[$_curentPage] ) ){
								$_answer[]=array(
									'title'=>'Page',
									'buttons'=>array(
										array(
											"type"=>"postback",
											"title"=>"Next Page",
											"payload"=>"template_page#".($_curentPage+1)."#".$_actionTemp[2]
										)
									)
								);
							}
						}elseif( strpos( strtolower( $action ), 'change_template' )!==false ){
							$_actionTemp=explode( '#', $action );
							if( !isset( $_actionTemp[1] ) || !isset( $_actionTemp[2] ) ){
								$_answer='No site or template exist';
							}else{
								$_model=new Project_Sites( Project_Sites::NCSB );
								$_model->getSite( $_arrSiteData, $_actionTemp[2] );
								if( $_arrSiteData['arrNcsb']['template_id'] == $_actionTemp[2] ){
									$_answer='Site have this template';
								}else{
									$_settings=new Project_Content_Settings();
									$_settings->onlyOne()->withFlgDefault()->onlySource( '9' )->getContent( $_amazonSettings );
									if( $_model->setEntered( array (
										'arrNcsb' => array (
											'id' => $_arrSiteData['arrNcsb']['id'],
											'placement_id' => $_arrSiteData['arrNcsb']['placement_id'],
											'url' => $_arrSiteData['arrNcsb']['url'],
											'ftp_directory' => $_arrSiteData['arrNcsb']['ftp_directory'],
											'template_id' => $_actionTemp[1],
											'category_id' => $_arrSiteData['arrNcsb']['category_id'],
											'google_analytics' => $_arrSiteData['arrNcsb']['google_analytics'],
											'main_keyword' => $_arrSiteData['arrNcsb']['main_keyword'],
											'navigation_length' => $_arrSiteData['arrNcsb']['navigation_length'],
											'flg_snippet' => 'no',
										),
										'multibox_ids_content_wizard' => ''
									))->setAmazonSettings( $_amazonSettings['settings'] )->set() ){
										$_answer='Template has been updated for site '.$_arrSiteData['arrNcsb']['url'];
									}
								}
							}
						}elseif( strpos( strtolower( $action ), 'use_domain' )!==false ){
							$_actionTemp=explode( '#', $action );
							if( !isset( $_actionTemp[1] ) ){
								$_answer=false;
							}else{
								$_panswer=array();
								$_panswer[]=array(
									"type"=>"postback",
									"title"=>"Yes",
									"payload"=>"cofirm_domain#".$_actionTemp[1]
								);
								$_panswer[]=array(
									"type"=>"postback",
									"title"=>"No ",
									"payload"=>"select_domain_list"
								);
								$this->sendButtonTemplate( $message['sender']['id'], 'Do you want to select '.$_actionTemp[1].'?', $_panswer );
								$_answer=false;
							}
						}elseif( strpos( strtolower( $action ), 'select_domain_list' )!==false ){
							$_actionTemp=explode( '#', $action );
							$_botAI=new Project_Bot( $arrUser[0]['id'] );
							$_botAI->getCatch( $_catch );
							$_answer=false;
							$_panswer=array();
							if( isset( $_catch['keyword'] ) ){
								$_domain=new Project_Wizard_Domain( Project_Wizard_Domain_Rules::R_AMAZON );
								$_strTld='';
								if( isset( $_actionTemp[2] ) && !empty($_actionTemp[2]) ){
									$_domain->setTLD( $_actionTemp[2] );
									$_strTld="#".$_actionTemp[2];
								}
								$_domains=$_domain->setWord( $_catch['keyword'] )->get();
								$_count=0;
								$_getFromDomain=false;
								if( isset( $_actionTemp[1] ) && !empty( $_actionTemp[1] ) ){
									$_getFromDomain=$_actionTemp[1];
								}
								foreach( $_domains as $_domainCounter ){
									$_domain4Check='';
									foreach( $_domainCounter as $_domain4Check ){
										if( Project_Wizard_Domain::check( $_domain4Check ) === true ){
											if( $_getFromDomain===false ){
												$_panswer[]=array(
													'title'=>$_domain4Check,
													'buttons'=>array(
														array(
															"type"=>"postback",
															"title"=>"Select ".$_domain4Check,
															"payload"=>"use_domain#".$_domain4Check
														)
													)
												);
												$_count++;
											}
											if( $_getFromDomain == $_domain4Check ){
												$_getFromDomain=false;
											}
										}
										if( $_count == 5 ){
											break;
										}
									}
									if( $_count == 5 ){
										$_panswer[]=array(
											'title'=>'Search more available',
											'buttons'=>array(
												array(
													"type"=>"postback",
													"title"=>"Search more",
													"payload"=>"select_domain_list#".$_domain4Check.$_strTld
												),
												array(
													"type"=>"postback",
													"title"=>"Search specific TLD",
													"payload"=>"select_tld_list#"
												),
												array(
													"type"=>"postback",
													"title"=>"Enter custom domain",
													"payload"=>"enter_domain_name#"
												)
											)
										);
										break;
									}
								}
							}
							if( count( $_panswer )==0 ){
								$_panswer=array();
								$_panswer[]=array(
									"type"=>"postback",
									"title"=>"Search more",
									"payload"=>"select_domain_list#".$_actionTemp[1].$_strTld
								);
								$this->sendButtonTemplate( $message['sender']['id'], 'No more results are found, but you can check again in 5 seconds, or select from the previous list.', $_panswer );
							}else{
								$this->sendGenericTemplate( $message['sender']['id'], $_panswer );
							}
						}elseif( strpos( strtolower( $action ), 'select_tld_list' )!==false ){
							$_actionTemp=explode( '#', $action );
							$_answer=false;
							$_panswer=array();
							foreach( array('com', 'info', 'org', 'net', 'biz', 'us', 'online') as $_tld ){
								$_panswer[]=array(
									'title'=>$_tld,
									'buttons'=>array(
										array(
											"type"=>"postback",
											"title"=>"Select ".$_tld,
											"payload"=>"select_domain_list##.".$_tld
										)
									)
								);
							}
							$this->sendGenericTemplate($message['sender']['id'], $_panswer);
						}elseif( strpos( strtolower( $action ), 'cofirm_domain' )!==false ){
							$_actionTemp=explode( '#', $action );
							if( !isset( $_actionTemp[1] ) ){
								$_answer='No domain exist';
							}else{
								$_botAI=new Project_Bot( $arrUser[0]['id'] );
								$_botAI->updateCatch( array('domain'=>$_actionTemp[1]) );
								$_botAI->getAnswer( $_answer, $_catch );
								$_answer.=' Site will be created on the domain '.$_actionTemp[1].'.';
							}
						}elseif( strpos( strtolower( $action ), 'enter_domain_name' )!==false ){
							$_answer=false;
							$_botAI=new Project_Bot( $arrUser[0]['id'] );
							$_botAI->setCommand( 'domain_name#set' );
						}
						$_check=false;
						if( $_answer !== false ){
							if( empty( $_answer ) ){
								$_check=$this->send($message['sender']['id'], "Sorry, I'm still a young bot, and your last message confused me, please retry");
								$_botAI=new Project_Bot( $arrUser[0]['id'] );
								$_botAI->cleanUserDialog();
							}elseif( is_string( $_answer ) ){
								$_check=$this->send($message['sender']['id'], $_answer);
							}elseif( is_array( $_answer ) ){
								$_check=$this->sendGenericTemplate($message['sender']['id'], $_answer);
							}
						}
						if( isset( $_check['error'] ) ){
							$this->send($message['sender']['id'], $_check['error']['message']);
						}
					}catch( Exception $e ){
						$this->send( $message['sender']['id'], 'Error:' );
					}
				}
				if (!empty($command)){
					$_answer=false;
					try{
						$_tmpReturn=array();
						if( strpos( strtolower( $command ), 'test bot ai' )!==false ){
							$_answer='Hi. I’m a bot.';
						}elseif( strtolower( $command )=='restart' ){
							$_botAI=new Project_Bot( $arrUser[0]['id'] );
							$_botAI->cleanUserDialog();
							$_answer='Sorry, I’m just a bot.';
						}elseif( strtolower( $command )=='who i am' ){
							$_answer='I know you like as '.$arrUser[0]['email'];
						}elseif( preg_match( Project_Bot::getQueryPattern("(I would like to |I want to |I'd like to |)(change|update) template(s|)", $_tmpReturn), $command, $_preg ) && $_preg[0] == $command ){
							$model=new Project_Sites( Project_Sites::NCSB );
							$model
								->withCategory( 'Zonterest' ) // 641
								->getList( $_arrSites );
							$_answer=array();
							$_buble=array_shift( $_arrSites );
							$_arrayChunk=array_chunk( $_arrSites, 8 );
							$_arrayChunk[0][]=$_buble;
							foreach( $_arrayChunk[0] as $_key=>$_template ){
								$_answer[]=array(
									'title'=>$_template['main_keyword'].' '.$_template['url'],
									'subtitle'=>'Added: '.gmdate( "Y-m-d", $_template['added'] ),
									'buttons'=>array(
										array(
											'type'=>'web_url',
											'url'=>$_template['url'],
											"title"=>'View'
										),
										array(
											"type"=>"postback",
											"title"=>"Select ".$_template['main_keyword'],
											"payload"=>"template_page#1#".$_template['id']
										)
									)
								);
							}
							if( isset( $_arrayChunk[1] ) ){
								$_answer[]=array(
									'title'=>'Page',
									'buttons'=>array(
										array(
											"type"=>"postback",
											"title"=>"Next Page",
											"payload"=>"site_page#2"
										)
									)
								);
							}
						}else{
							if( preg_match( Project_Bot::getQueryPattern("(using |in )(a )custom domain", $_tmpReturn), $command, $_preg ) ){
								if( Core_Acs::haveAccess( array('Zonterest Custom 2.0') ) ){
									$command=str_replace( ' '.$_preg[0], '', $command );
									// 
									$_buns=new Core_Payment_Buns();
									$_buns->withSysName('Project_Placement_Hosting')->onlyOne()->getList( $arrHosting );
									$_buns->withSysName('Project_Placement_Domen')->onlyOne()->getList( $arrDomain );
									$_purse=new Core_Payment_Purse();
									if( Core_Payment_Purse::getAmount()<($arrDomain['credits']+$arrHosting['credits']) ){
										$this->send($message['sender']['id'], "You don't have enough credits on your balance for this project.");
									}else{
										$_botAI=new Project_Bot( $arrUser[0]['id'] );
										$_botAI->setCommand( $command )->getCatch( $_catch );
										$_panswer=array();
										if( isset( $_catch['keyword'] ) ){
											$_domain=new Project_Wizard_Domain( Project_Wizard_Domain_Rules::R_AMAZON );
											$_domains=$_domain->setWord( $_catch['keyword'] )->get();
											$_count=0;
											foreach( $_domains as $_domainCounter ){
												$_domain4Check=false;
												foreach( $_domainCounter as $_domain4Check ){
													if( Project_Wizard_Domain::check( $_domain4Check ) === true ){
														$_panswer[]=array(
															'title'=>$_domain4Check,
															'buttons'=>array(
																array(
																	"type"=>"postback",
																	"title"=>"Select ".$_domain4Check,
																	"payload"=>"use_domain#".$_domain4Check
																)
															)
														);
														$_count++;
													}
													if( $_count == 5 ){
														break;
													}
												}
												if( $_count == 5 ){
													$_panswer[]=array(
														'title'=>'Search more available',
														'buttons'=>array(
															array(
																"type"=>"postback",
																"title"=>"Search more",
																"payload"=>"select_domain_list#".$_domain4Check
															),
															array(
																"type"=>"postback",
																"title"=>"Search specific TLD",
																"payload"=>"select_tld_list#"
															),
															array(
																"type"=>"postback",
																"title"=>"Enter custom domain",
																"payload"=>"enter_domain_name#"
															)
														)
													);
													break;
												}
											}
										}
										if( count( $_panswer )>0 ){
											$this->sendGenericTemplate($message['sender']['id'], $_panswer);
										}
									}
								}
							}else{
								$_botAI=new Project_Bot( $arrUser[0]['id'] );
								$_botAI->setCommand( $command )->getAnswer( $_answer, $_catch );
							}
						}
						if( isset( $_catch['action'] ) && $_catch['action'] == 'create_site' ){
							if( !empty( $_catch['keyword'] ) && !empty( $_catch['category'] ) ){
								//$_answer.=' I will create for you a site with "'.$_catch['keyword'].'" keyword in category "'.$_catch['category'].'". Please wait.';
								$this->createSite( $_catch['keyword'], $_catch['category'], $arrUser[0]['id'], $_catch['domain'] );
								$_botAI=new Project_Bot( $arrUser[0]['id'] );
								$_botAI->cleanUserDialog();
							}
						}
						if( isset( $_catch['action'] ) && $_catch['action'] == 'clear_dialog' ){
							$_botAI=new Project_Bot( $arrUser[0]['id'] );
							$_botAI->cleanUserDialog();
						}
						$_check=false;
						if( $_answer !== false ){
							if( empty( $_answer ) ){
								$_check=$this->send($message['sender']['id'], "Sorry, I'm still a young bot, and your last message confused me, please retry");
								$_botAI=new Project_Bot( $arrUser[0]['id'] );
								$_botAI->cleanUserDialog();
							}elseif( is_string( $_answer ) ){
								$_check=$this->send($message['sender']['id'], $_answer);
							}elseif( is_array( $_answer ) ){
								$_check=$this->sendGenericTemplate($message['sender']['id'], $_answer);
							}
						}
						if( isset( $_check['error'] ) ){
							$this->send($message['sender']['id'], $_check['error']['message']);
						}
					}catch( Exception $e ){
						$this->send( $message['sender']['id'], 'Error:' );
					}
				}
			}
		}
	}

	public function send($recipient, $message){
		return $this->call('me/messages', array(
            'recipient' => array( 'id' => $recipient ),
            'message' => array(
                'text' => $message,
            ),
            'tag' => null,
            'notification_type' => "REGULAR"
        ));
	}

	public function sendGenericTemplate($recipient, $arrElements){
		return $this->call('me/messages', array(
            'recipient' => array( 'id' => $recipient ),
            'message' => array(
				'attachment'=> array(
					'type'=>'template',
					'payload'=>array(
						'template_type'=>'generic',
						'image_aspect_ratio'=>'square',
						'elements'=>$arrElements
					),
				),
            )
        ), 'post');
	}

	public function sendListTemplate($recipient, $arrButtons){
		return $this->call('me/messages', array(
            'recipient' => array( 'id' => $recipient ),
            'message' => array(
				'attachment'=> array(
					'type'=>'template',
					'payload'=>array(
						'template_type'=>'list',
						'top_element_style'=>'COMPACT',
						'buttons'=>$arrButtons
					),
				),
            )
        ), 'post');
	}

	public function sendButtonTemplate($recipient, $_topText, $arrButtons){
		return $this->call('me/messages', array(
            'recipient' => array( 'id' => $recipient ),
            'message' => array(
				'attachment'=> array(
					'type'=>'template',
					'payload'=>array(
						'template_type'=>'button',
						'text'=>$_topText,
						'buttons'=>$arrButtons
					),
				),
            )
        ), 'post');
	}

    public function call( $url, $data, $type='post' ){
		if( $this->_withLogger && $this->_logger!==false ){
			$this->_logger->info('Answer : '.serialize($data) );
		}
		$data['access_token']=self::$_appToken;
		$headers=array(
			'Content-Type: application/json',
		);
		if ($type == 'get') {
			$url.='?'.http_build_query($data);
		}
		if( !$this->flgTest ){
			$process=curl_init($this->apiUrl.$url);
			curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($process, CURLOPT_HEADER, false);
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			if($type == 'post' || $type == 'delete'){
				curl_setopt($process, CURLOPT_POST, 1);
				curl_setopt($process, CURLOPT_POSTFIELDS, json_encode($data));
			}
			if ($type == 'delete' ){
				curl_setopt($process, CURLOPT_CUSTOMREQUEST, "DELETE");
			}
			curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
			$return=curl_exec($process);
			curl_close($process);
		}else{
			var_dump( array( 'url'=>$this->apiUrl.$url, 'data'=>$data ) );
			$return=json_encode(true);
		}
		return json_decode($return, true);
	}
	
	public function createSite( $keyword, $category, $userId, $domain='' ){
		if( !isset( $keyword ) || !isset( $category ) || !isset( $userId ) ){
			return;
		}
		shell_exec( '/usr/bin/nohup /usr/bin/php -f /data/www/'.Zend_Registry::get( 'config' )->domain->host.'/html/services/create_zonterest.php "'.$keyword.'['.$category.']" '.$userId.' zonterest20 facebook '.$domain.'>> /data/www/'.Zend_Registry::get( 'config' )->domain->host.'/html/services/create_zonterest.log 2>&1 &' );
		return;
	}
	
	private $_settings=array();
	
	public function setSettings( $arrSettings=array() ){
		$this->_settings=$arrSettings;
		return $this;
	}
	
	public function setCalled( $_userId ){
		if( isset( $_userId ) ){
			$this->withUserId( $_userId );
		}
		return $this;
	}
	
	public function sendSMS( &$return ){
		$_userData=array();
		$_oldUser=$this->_withUserId;
		if( $this->_withUserId ){
			$_users=new Project_Users_Management();
			$_users->withIds( $this->_withUserId )->getList( $_userData );
		}
		if( count( $_userData ) > 0 && isset( $_userData[0]['fb_messenger_id'] ) && !empty( $_userData[0]['fb_messenger_id'] ) ){
			$return=$this->_settings['body'];
			$this->send($_userData[0]['fb_messenger_id'], $this->_settings['body']);
		}
		return $this;
	}
	
}
?>