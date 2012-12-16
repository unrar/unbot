<?php
class UDB_connector {
	// @var $cachedb -> Lugar donde se guarda la consulta (por defecto = cache.ucb)
	var $cachedb ="cache.ucb";
	// @var $udb_ac ARRAY -> Conexiones activas: $udb_ac['udbd.udd'] = BOOL
	var $udb_ac = array();
	// rac: read and cache
	// @param pdb File to rac
	function rac ($pdb) {
		//Open file for reading
		$rpdb = fopen($pdb, 'rb');
		//Open cache database 
		$rcachedb = fopen($this->cachedb, 'w');
		while (!feof($rpdb)) {
			fwrite($rcachedb, fgets($rpdb));
		}
		fclose($rcachedb);
		fclose($rpdb);
		return true;
	}
	// wac: write and cache
	// @param: pdb File to wac
	function wac ($pdb) {
		//Open file for writing
		$rpdb = fopen($pdb, 'wb');
		//Open cache database
		$rcachedb = fopen($this->cachedb, 'rb');
		while (!feof($rcachedb)) {
			fwrite($rpdb, fgets($rcachedb));
		}
		fclose($rcachedb);
		fclose($rpdb);
		return true;
	}
	
	// save: alias for wac
	// @param: pdb File to wac
	function save ($pdb) {
		$this->wac($pdb);
	}
	
	// connect: Conecta a una UDB
	// @param fdb File DataBase
	// @param nocache bool No guardar en la caché
	function connect ($fdb, $nocache = false) {
		if ($nocache == true) {
			if (file_exists($fdb)) {
				$this->udb_ac[$fdb] = true;
				return true;
			} else {
				$this->udb_ac[$fdb] = false;
				return false;
			}
		} else {
			if (!file_exists($fdb)) {
				$this->udb_ac[$fdb] = false;
				return false;
			} else {
				$this->rac($fdb);
				$this->udb_ac[$fdb] = true;
				return true;
			}
		}
	}
	
	// function: desconecta de una UDB
}
