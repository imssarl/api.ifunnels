<?php

class Project_Statistics {

	public function getTotalNumberOfMembers( &$arrRes ){
		$arrRes=Core_Sql::getCell('SELECT COUNT(*) FROM u_users');
	}

	public function getNumberDomainsPurchased( &$int ){
		$_place=new Project_Placement();
		return $_place
				->onlyCount()
				->withType( Project_Placement::LOCAL_HOSTING_DOMEN )
				->getList( $int )
				->checkEmpty();
	}

	public function getNumberDomainHosted( &$int ){
		$_place=new Project_Placement();
		return $_place
				->onlyCount()
				->withType( array(Project_Placement::LOCAL_HOSTING,Project_Placement::LOCAL_HOSTING_DOMEN) )
				->getList( $int )
				->checkEmpty();
	}

	public function getNumberMembersWhoBoughtCredits( &$int ){
		$_order=new Core_Payment_Service();
		return $_order->onlyCount()->onlyConfirmed()->withCredits()->getList( $int )->checkEmpty();
	}

	public function getWhoUsingHosting( &$arrRes ){
		$_place=new Project_Placement();
		$_place
				->withType( array(Project_Placement::LOCAL_HOSTING, Project_Placement::LOCAL_HOSTING_DOMEN ) )
				->getList($arrPlace);
		if(empty($arrPlace)){
			return false;
		}
		foreach( $arrPlace as $_item ){
			$_arrIds[]=$_item['user_id'];
		}
		$_users=new Project_Users_Management();
		if( !$_users->withOrder($_GET['order'])->withPaging(array('url'=>$_GET))->withIds( $_arrIds )->getList( $arrRes )->checkEmpty() ){
			return false;
		}
		$_users->getPaging( $arrPag );
		foreach( $arrRes as &$_item ){
			$_item['hosting']=Core_Sql::getAssoc('SELECT * FROM site_placement WHERE user_id='.$_item['id']
					.' AND flg_type IN ('.Core_Sql::fixInjection(array(Project_Placement::LOCAL_HOSTING,Project_Placement::LOCAL_HOSTING_DOMEN)) .')');
			$_item['count_hosting']=count($_item['hosting']);
		}
		return $arrPag;
	}

	public function getNumberCreditsConsumed( &$int ){
		$int=Core_Sql::getCell('SELECT SUM(amount) as summ FROM p_history WHERE flg_type='.Core_Payment_Purse::TYPE_INTERNAL );
	}

	public function getNumberCreditsPurchased( &$int ){
		$int=Core_Sql::getCell('SELECT SUM(amount) as summ FROM p_history WHERE flg_type='.Core_Payment_Purse::TYPE_EXTERNAL );
	}

	public function getGrossSalesCredits( &$arrRes ){
	}
}
?>