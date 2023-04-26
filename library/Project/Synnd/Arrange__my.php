<?php


/**
 * Работа с synnd по крону
 */

class Project_Synnd_Arrange {

	public static $_logger=false;

	public function run() {
		self::$_logger=new Zend_Log( );
		$_writer=new Zend_Log_Writer_Stream('php://output');
		$_writer->setFormatter( new Zend_Log_Formatter_Simple( Zend_Log_Formatter_Simple::DEFAULT_FORMAT.(php_sapi_name()=='cli'?PHP_EOL:'<br />')) );
		self::$_logger->addWriter( $_writer );
		if( !Zend_Registry::isRegistered('rewriter') ) {
			Zend_Registry::set('rewriter',new Core_Rewrite() );
		}
		self::$_logger->info( 'Start Project_Synnd_Arrange by crontab at '.date( 'r' ) );
		$_reports=new Project_Synnd_Reports();
		$_reports->onlyActive()->getList( $arrList );
		if ( empty( $arrList ) ) {
			self::$_logger->info( 'Stop Project_Synnd_Arrange::run - no campaigns exists' );
			return false;
		}
		$_synnd=new Project_Synnd();
		$arrCampaigns=array();
		foreach( $arrList as $v ) {
			if( !isset( $arrCampaigns[$v['campaign_id']] ) ) {
				$_synnd->withIds( $v['campaign_id'] )->onlyOne()->getList( $arrCampaigns[$v['campaign_id']] );
				if( $arrCampaigns[$v['campaign_id']]['settings']==false ) {
					unset($arrCampaigns[$v['campaign_id']]);
					continue;
				}
				$arrCampaigns[$v['campaign_id']]['node']=array();
				$arrCampaigns[$v['campaign_id']]['promote_count']=0;
			}
			$arrCampaigns[$v['campaign_id']]['node'][]=$v;
			$arrCampaigns[$v['campaign_id']]['promote_count']+=$v['promote_count']*Project_Synnd::$promotionTypes[$v['flg_type']]['amount'];
		}
		$_arrange=new Project_Synnd_Arrange();
		foreach( $arrCampaigns as $v ) {
			$_arrange->process( $v );
		}
		self::$_logger->info( 'Finish Project_Synnd_Arrange by crontab at '.date( 'r' ) );
		return true;
	}

	private function process( $_arrCampaign=array() ) {
		self::$_logger->info( 'Process "'.$_arrCampaign['settings']['title'].'" ['.$_arrCampaign['id'].'] campaign start' );
		// Test user amount before campaign creation
		$amount=(int)ceil( $_arrCampaign['promote_count'] );
		Core_Users::getInstance()->setById( $_arrCampaign['user_id'] );
		$_reports=new Project_Synnd_Reports();
		if( Core_Payment_Purse::getAmount()<$amount ) {
			self::$_logger->info( 'Process "'.$_arrCampaign['settings']['title'].'" ['.$_arrCampaign['id'].'] no money' );
			foreach( $_arrCampaign['node'] as $promotion ) {
				$_reports->withIds($promotion['id'])->setStatus( Project_Synnd_Reports::$promotionStatus['error'], 'No money. Purchase extra credits' );
			}
			return false;
		}
		$_synndApi=new Project_Synnd_Api();
		if( !$_synndApi
			->setProject( array( 'domen'=>$_arrCampaign['settings']['url'] ))
			->setContent( $_arrCampaign['settings'] )
			->createCampaignSetup()
			->createCampaign()
		){
			$_synndApi
				->getContent($_arrCampaign['settings']);
			$_synnd=new Project_Synnd();
			$_synnd->withIds( $_arrCampaign['id'] )->setSettings( $_arrCampaign['settings'] );
			$_synndApi->getErrors( $_arrErr );
			foreach( $_arrCampaign['node'] as $promotion ) {
				$_reports->withIds($promotion['id'])->setStatus( Project_Synnd_Reports::$promotionStatus['error'], $_arrErr[0] );
			}
			self::$_logger->info( 'Process "'.$_arrCampaign['settings']['title'].'" error: '.implode( ' ', $_arrErr ) );
			return false;
		}
		$_synndApi
			->getContent($_arrCampaign['settings']);
		$_synnd=new Project_Synnd();
		$_synnd->withIds( $_arrCampaign['id'] )->setSettings( $_arrCampaign['settings'] );
		self::$_logger->info( 'Create promotions:' );
		$amount=0;
		foreach( $_arrCampaign['node'] as $promotion ) {
			if( !$_synndApi->createPromotion( $promotion ) ) {
				$_synndApi->getErrors( $_arrErr );
				self::$_logger->info( 'Promotion "'.Project_Synnd::$promotionTypes[$promotion['flg_type']]['name'].'" error: '.implode( ' ', $_arrErr ) );
				$_reports->withIds($promotion['id'])->setStatus( Project_Synnd_Reports::$promotionStatus['error'] );
				return false;
			}else{
				self::$_logger->info( 'Promotion "'.Project_Synnd::$promotionTypes[$promotion['flg_type']]['name'].'" create succesful!' );
				$amount+=$promotion['promote_count']*Project_Synnd::$promotionTypes[$promotion['flg_type']]['amount'];
				$_reports->withIds($promotion['id'])->setRestart( $promotion, $_arrCampaign['flg_type'] )->setStatus( Project_Synnd_Reports::$promotionStatus['completed'] );
			}
		}
		$_purse=new Core_Payment_Purse();
		$_purse->setAmount( ceil($amount) )
			->setUserId( $_arrCampaign['user_id'] )
			->setType( Core_Payment_Purse::TYPE_INTERNAL )
			->setMessage('Promotion campaign "'.$_arrCampaign['settings']['title'].'" activated.')
			->expenditure();
		$_reports->withCampaignId( $_arrCampaign['id'] )->delCorrupted();
		self::$_logger->info( 'Process "'.$_arrCampaign['settings']['title'].'" ['.$_arrCampaign['id'].'] campaign finish' );
		return true;
	}
}
?>