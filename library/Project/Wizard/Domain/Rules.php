<?php

class Project_Wizard_Domain_Rules {

	const   R_AMAZON=1,
			R_ZONTEREST=2,
			R_CONTENT=3,
			R_VIDEO=4,
			R_AUTHORITY=5,
			R_CLICKBANK=6,
			R_CLICKBANKPRO=7,
			R_CONTENTPRO=8;

	public static function factory( $_type ){
		switch( $_type ){
			case self::R_AMAZON : return new Project_Wizard_Domain_Rules_Amazon(); break;
			case self::R_ZONTEREST : return new Project_Wizard_Domain_Rules_Zonterest(); break;
			case self::R_CONTENT : return new Project_Wizard_Domain_Rules_Content(); break;
			case self::R_CONTENTPRO : return new Project_Wizard_Domain_Rules_ContentPro(); break;
			case self::R_VIDEO : return new Project_Wizard_Domain_Rules_Video(); break;
			case self::R_AUTHORITY : return new Project_Wizard_Domain_Rules_Authority(); break;
			case self::R_CLICKBANK : return new Project_Wizard_Domain_Rules_Clickbank(); break;
			case self::R_CLICKBANKPRO : return new Project_Wizard_Domain_Rules_ClickbankPro(); break;
			default :
				throw new Project_Wizard_Exception('Can not define type Rules::factory');
				break;
		}
	}
}
?>