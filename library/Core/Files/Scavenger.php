<?php


/**
 * Сборщик мусора чистит файлы и группы файлов
 */
class Core_Files_Scavenger {

	/**
	 * промежуток в течении которого удалённый файлы физически не удаляются и их можно восстановить
	 * 60*60*24*30 (2592000) спустя месяц файлы удаляем физически
	 *
	 * @var integer
	 */
	const INTERVAL=2592000;

	public function run() {
		$_fileGroup=new Core_Files_Group();
		$_fileGroup->del(); // группы и файлы
		$_fileInfo=new Core_Files_Info();
		$_fileInfo->del(); // файлы в оставшихся группах
	}
}
?>