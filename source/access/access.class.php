<?php
/**
 * WorkHorse Framework
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2012, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 22.11.2011
 * @version 2.0
 */


/**
 * Access rights administration
 *
 * @category WorkHorse
 * @package ProjectSource
 * @copyright Copyright (c) 2005-2012, Rodion Konnov
 * @license http://opensource.org/licenses/ MIT License
 */
class access extends Core_Module {

	public function set_cfg() {
		$this->inst_script=array(
			'module'=>array(
				'title'=>'Access rights',
			),
			'actions'=>array( 
				array( 'action'=>'groups', 'title'=>'Manage groups' ),
				array( 'action'=>'rights', 'title'=>'Manage rights' ),
				array( 'action'=>'rights2group', 'title'=>'Assign rights to group' ),
				array( 'action'=>'groups2right', 'title'=>'Assign groups to right' ),
				array( 'action'=>'content2group', 'title'=>'Assign content to groups' ),
				array( 'action'=>'template2group', 'title'=>'Assign template to groups' ),
				array( 'action'=>'hosting2group', 'title'=>'Assign hosting to groups' ),
			),
		);
	}

	public function template2group(){
		if ( !empty( $_POST['change_group'] ) ) {
			$this->location( array( 'w'=>'group_id='.$_POST['arrR']['group_id'] ) );
		}
		$_model=new Project_Acs_Template();
		if(!empty($_GET['group_id'])){
			$this->out['templateIds']['ncsb']=$_model->setType(Project_Sites::NCSB)->get2group( $_GET['group_id'] );
			$this->out['templateIds']['nvsb']=$_model->setType(Project_Sites::NVSB)->get2group( $_GET['group_id'] );
			$this->out['templateIds']['wp']=$_model->setType(Project_Sites::BF)->get2group( $_GET['group_id'] );
		}
		if( (!empty($_POST['nvsb'])||!empty($_POST['ncsb'])) ){
			$_model->setType(Project_Sites::NVSB)->addLink($_POST['arrR']['group_id'],$_POST['nvsb']);
			$_model->setType(Project_Sites::NCSB)->addLink($_POST['arrR']['group_id'],$_POST['ncsb']);
			$_model->setType(Project_Sites::BF)->addLink($_POST['arrR']['group_id'],$_POST['wp']);
			$this->location( array( 'w'=>'group_id='.$_POST['arrR']['group_id'] ) );
		}
		$_groups=new Core_Acs_Groups();
		$_groups->toSelect()->getList( $this->out['arrG'] );
		$_template=new Project_Sites_Templates(Project_Sites::NCSB);
		$_template->withPreview()->onlyCommon()->getList($this->out['templates']['ncsb']);
		$_template=new Project_Sites_Templates(Project_Sites::NVSB);
		$_template->withPreview()->onlyCommon()->getList($this->out['templates']['nvsb']);
		$_wpTemplates=new Project_Wpress_Theme();
		$_wpTemplates->withPreview()->onlyCommon()->getList($this->out['templates']['wp']);
	}

	public function content2group(){
		if ( !empty( $_POST['change_group'] ) ) {
			$this->location( array( 'w'=>'group_id='.$_POST['arrR']['group_id'] ) );
		}
		$_model=new Project_Acs_Source();
		if(!empty($_GET['group_id'])){
			$this->out['sourceIds']=$_model->get2group( $_GET['group_id'] );
		}
		if( !empty($_POST['data'])&&$_model->addLink($_POST['arrR']['group_id'],$_POST['data'])){
			$this->location( array( 'w'=>'group_id='.$_POST['arrR']['group_id'] ) );
		}
		$_groups=new Core_Acs_Groups();
		$_groups->toSelect()->getList( $this->out['arrG'] );
		$this->out['arrSource']=Project_Content::$source;
	}

	public function hosting2group(){
		if ( !empty( $_POST['change_group'] ) ) {
			$this->location( array( 'w'=>'group_id='.$_POST['arrR']['group_id'] ) );
		}
		$_model=new Project_Acs_Hosting();
		if(!empty($_GET['group_id'])){
			$this->out['hostingIds']=$_model->get2group( $_GET['group_id'] );
		}
		if( !empty($_POST['data'])&&$_model->addLink($_POST['arrR']['group_id'],$_POST['data'])){
			$this->location( array( 'w'=>'group_id='.$_POST['arrR']['group_id'] ) );
		}
		$_groups=new Core_Acs_Groups();
		$_groups->toSelect()->getList( $this->out['arrG'] );
	}

	public function rights() {
		$_rights=new Core_Acs_Rights();
		if ( !empty( $_POST['arrRights'] ) ) {
			if ( $_rights->setEntered( $_POST['arrRights'] )->setMass() ) {
				$this->location();
			}
			$_rights->getErrors( $this->out['arrErr'] );
		}
		$_rights
			->withPaging( array( 'url'=>$_GET ) )
			->getList( $this->out['arrList'] )
			->getPaging( $this->out['arrPg'] );
	}

	public function groups() {
		$_groups=new Core_Acs_Groups();
		if ( !empty( $_POST['arrGroups'] ) ) {
			if ( $_groups->setEntered( $_POST['arrGroups'] )->setMass() ) {
				$this->location();
			}
			$_groups->getErrors( $this->out['arrErr'] );
		}
		$_groups
			->withPaging( array( 'url'=>$_GET ) )
			->getList( $this->out['arrList'] )
			->getPaging( $this->out['arrPg'] );
	}

	public function rights2group() {
		if ( !empty( $_POST['change_group'] ) ) {
			$this->location( array( 'w'=>'group_id='.$_POST['arrR']['group_id'] ) );
		}
		$_rights=new Core_Acs_Rights();
		if ( !empty( $_POST['arrR'] )&&$_rights->setEntered( $_POST['arrR'] )->rights2group() ) {
			$this->location( array( 'wg'=>true ) );
		}
		if ( !empty( $_REQUEST['group_id'] ) ) {
			$_rights->withGroup( $_REQUEST['group_id'] )->getRights2group( $this->out['arrL'] );
		}
		$_rights->getRightWithModule( $this->out['arrR'] );
		$_groups=new Core_Acs_Groups();
		$_groups->toSelect()->getList( $this->out['arrG'] );
		$this->out['arrM']=Core_Sql::getKeyVal( 'SELECT id, title FROM sys_module' );
	}

	public function groups2right() {
		if ( !empty( $_POST['change_right'] ) ) {
			$this->location( array( 'w'=>'right_id='.$_POST['arrG']['right_id'] ) );
		}
		if ( !empty( $_POST['change_sys'] ) ) {
			$this->location( array( 'w'=>'sys_id='.$_POST['arrG']['system_id'] ) );
		}
		$_rights=new Core_Acs_Rights();
		if ( !empty( $_POST['arrG'] ) ){
			$_POST['arrG']['right_id']=( isset( $_POST['arrG']['system_id'] )?$_POST['arrG']['system_id']:$_POST['arrG']['right_id'] );
			if($_rights->setEntered( $_POST['arrG'] )->groups2right() ) {
				$this->location( array( 'wg'=>true ) );
			}
		}
		if ( !empty( $_REQUEST['right_id'] ) ) {
			$_rights->withRight( $_REQUEST['right_id'] )->getGroups2right( $this->out['arrL'] );
		}
		if ( !empty( $_REQUEST['sys_id'] ) ) {
			$_rights->withRight( $_REQUEST['sys_id'] )->getGroups2right( $this->out['arrL'] );
		}
		$_rights->toSelect()->getList( $this->out['arrR'] );
		$_rights->getList( $_arrSys );
		$this->out['arrS']=array();
		foreach( $_arrSys as $_dataSys ){
			$this->out['arrS'][$_dataSys['id']]=$_dataSys['sys_name'];
		}
		asort( $this->out['arrS'] );
		$_groups=new Core_Acs_Groups();
		$_groups->toSelect()->getList( $this->out['arrG'] );
	}
}
?>