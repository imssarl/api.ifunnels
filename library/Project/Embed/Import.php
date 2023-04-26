<?php


/**
 * Массовая загрузка Embed видео
 */
class Project_Embed_Import {

	private $_log=array();

	private function setLogLineError( $_str ) {
		$this->_log[]['r']=$_str;
	}

	private function setLogLineSucc( $_str ) {
		$this->_log[]['g']=$_str;
	}

	public function getLogLine() {
		return $this->_log;
	}

	private function xml2phpArray($xml,$arr){
		$iter = 0;
		foreach($xml->children() as $b){
			$a = $b->getName();
			if(!$b->children()){
					$arr[$a] = trim($b[0]);
			}
			else{
					$arr[$a][$iter] = array();
					$arr[$a][$iter] = $this->xml2phpArray($b,$arr[$a][$iter]);
			}
		$iter++;
		}
		return $arr;
	}

	public function massImport( $_arrData=array(), $_arrFile=array() ) {
		if ( empty( $_arrData['source'] ) ) {
			$this->setLogLineError( '"Source" field must be not empty' );
			return;
		}
		if ( !empty( $_arrFile['error'] )||$_arrFile['size']==0 ) {
			$this->setLogLineError( '"'.$_arrFile['name'].'" upload fail' );
			return;
		}
		// по имени файла делаем название категории
		$_strCatName=Core_Files::getFileName( $_arrFile['name'] );
		if ( empty( $_strCatName ) ) {
			$this->setLogLineError( 'empty file name for "'.$_arrFile['name'].'" file' );
			return;
		}
		// перемещаем в пользовательскую папку (если надо разархивируем)
		$_strTmp='Project_Embed_Import@massImport';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strTmp ) ) {
			return;
		}
		if ( $_arrFile['type']=='application/zip' ) {
			if ( !$this->getZip()->setDir( $_strTmp )->extractZip( $_arrFile['tmp_name'] ) ) {
				$this->setLogLineError( '"'.$_arrFile['name'].'" upload fail' );
				return;
			}
		} else {
			if ( !move_uploaded_file( $_arrFile['tmp_name'], $_strTmp.$_arrFile['name'] ) ) {
				$this->setLogLineError( '"'.$_arrFile['name'].'" upload fail' );
				return;
			}
		}
		// получаем список файлов
		Core_Files::dirScan( $_arrList, $_strTmp );
		$_arrFiles=array_pop( $_arrList );
		if ( empty( $_arrFiles ) ) {
			$this->setLogLineError( '"'.$_arrFile['name'].'" upload fail' );
			return;
		}
		$this->setLogLineSucc( '"'.$_arrFile['name'].'" uploaded successfully' );
		// проверяем есть ли категория (если надо создаём)
		$cat=new Project_Embed_Category();
		if ( !$cat->set( $_arrTmp1, $_arrTmp2, array( array( 'title'=>$_strCatName ) ) ) ) {
			$this->setLogLineError( '"'.$_strCatName.'" category created fail' );
			return;
		}
		if ( !$cat->byTitle( $_strCatName )->get( $_arrCat, $_arrTmp3 ) ) {
			$this->setLogLineError( '"'.$_strCatName.'" category created fail' );
			return;
		}
		$_intCatId=$_arrCat['id'];
		// каждый файл импортируем в айтемы по стандартной схеме (желательно через $this->add)
		$i=0;
		$embed=new Project_Embed();
		foreach( $_arrFiles as $v ) {
			if ( !Core_Files::getContent( $_strContent, $_strTmp.$v ) ) {
				$this->setLogLineError( '"'.$v.'" file read fail' );
				continue;
			}
			if ( !mb_check_encoding( $_strContent, 'UTF-8' ) ) {
				$_strContent=mb_convert_encoding($_strContent, 'UTF-8');
			}
			$xml=@simplexml_load_string( $_strContent );
			if ($xml === false) {
				$this->setLogLineError( '"'.$v.'" file is not in the correct format' );
				continue;
			}
			$_arrRes=$this->xml2phpArray( $xml,array() );
			if ( empty( $_arrRes['video'] ) ) {
				$this->setLogLineError( '"'.$v.'" file is not in the correct format' );
				continue;
			}
			foreach( $_arrRes['video'] as $fields ) {
				if ( !$embed->setData( array(
					'category_id'=>$_intCatId,
					'source_id'=>$_arrData['source'],
					'title'=>( empty( $fields['title'] )?'':$fields['title'] ),
					'body'=>( empty( $fields['embed'] )?'':$fields['embed'] ),
					'url_of_video'=>( empty( $fields['url'] )?'':$fields['url'] ),
				) )->set() ) {
					$this->setLogLineError( '"'.$fields['title'].'" video from "'.$v.'" file created fail' );
					continue;
				}
				$this->setLogLineSucc( '"'.$fields['title'].'" video from "'.$v.'" file created successfully' );
				$i++;
			}
		}
		if ( empty( $i ) ) {
			$this->setLogLineError( 'No new embed video created' );
		} else {
			$this->setLogLineSucc( $i.' new embed video are created' );
		}
	}
}
?>