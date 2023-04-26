<?php
class Core_Files_Transfer extends Zend_File_Transfer_Adapter_Http {

	public  function setFiles( $_arrFiles=array() ){
		$this->_files['file']=$_arrFiles;
		$this->_files['file']['options']=$this->_options;
		$this->_files['file']['validated']=false;
		$this->_files['file']['received']=false;
		$this->_files['file']['filtered']=false;
		$mimetype=$this->_detectMimeType($this->_files['file']);
		$this->_files['file']['type']=$mimetype;
		$filesize=$this->_detectFileSize($this->_files['file']);
		$this->_files['file']['size']=$filesize;
		$this->clearValidators();
		return $this;
	}

	protected function _prepareFiles(){
		return $this;
	}
}
?>