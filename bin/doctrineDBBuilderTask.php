<?php
require_once "phing/Task.php";
/**
 * Task to build or remvoe doctrine Models
 * @author jmosshammer <jannis.mosshammer@netways.de>
 *
 */
class doctrineDBBuilderTask extends Task {
	protected $models;
	protected $action;
	protected $ini;
	static protected $AppKitPath = null;
	public function init() {
		
	}
	
	public function main() {
		$this->checkForDoctrine();
		$action = $this->action;
		switch($action) {
			case "delete":
				$this->removeTablesForModels();
				break;
			case "create":
				$this->buildDBFromModels();
				break;
			default:
				throw new BuildException("Unknown db action ".$action."!");
		}	
			 
	}
	/**
	 * Loads Doctrine and sets up the autoloader
	 * 
	 * @throws BuildException on error
	 */
	protected function checkForDoctrine() {
		$icinga = $this->project->getUserProperty("PATH_Icinga");
        self::$APPKIT_LIB_DB = $icinga."/app/modules/AppKit/lib/database/models/";
		$doctrinePath = $icinga."/lib/doctrine/";
		if(!file_exists($doctrinePath."/Doctrine.compiled.php"))
			throw new BuildException("Doctrine.php not found at ".$doctrinePath."Doctrine.compiled.php");
		// setup autoloader
		require_once($doctrinePath."/Doctrine.compiled.php");
		spl_autoload_register("Doctrine::autoload");
		spl_autoload_register("doctrineDBBuilderTask::loadModel");
		$iniData = parse_ini_file($this->ini);
		if(empty($iniData))
			throw new BuildException("Couldn't read db.ini");
		$dsn = $iniData["dbtype"]."://".$iniData["dbuser"].":".$iniData["dbpass"]."@".$iniData["host"].":".$iniData["port"]."/".$iniData["dbname"];
		Doctrine_Manager::connection($dsn,'icinga_web');
	}
	
	/**
	 * Drops all tables described by the loaded models
	 * 
	 */
	protected function removeTablesForModels() {
		$tablesToDelete = file_get_contents($this->models);
		Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh()->query(
		//	"SET FOREIGN_KEY_CHECKS=0;
			 "DROP TABLE ".$tablesToDelete.";
			 ");
		echo "\n Dropping tables $tablesToDelete \n";
	}

	protected static $APPKIT_LIB_DB = "";

	public static function loadModel($name) {
    
		if(preg_match("/^Base/",$name)) {
			include(self::$APPKIT_LIB_DB."/generated/".$name.".php");		
		} else {
			include(self::$APPKIT_LIB_DB."/".$name.".php");		
		}
	}
	
	/**
	 * Rebuilds a db as described by the doctrine models
	 *
	 */
	public function buildDBFromModels() {	

		$icinga = $this->project->getUserProperty("PATH_Icinga");
		$modelPath = $icinga."/app/modules/".$this->project->getUserProperty("MODULE_Name")."/lib/database/";	

		$tables = Doctrine::getLoadedModels();
		$tableList = array();
		foreach($tables as $table) {
			$tableList[] = Doctrine::getTable($table)->getTableName();	
		}

		Doctrine::createTablesFromModels(array($this->models.'/generated',$this->models));	
	}
	
	/**
	 * Sets the action for this task
	 * 
	 * @param String $action The action to perform (create or delete)
	 */
	public function setAction($action) {
		$this->action = $action;
	}
	
	/**
	 * Sets where to search for doctrine models
	 * 
	 * @param String $models the path where the db-models are
	 */
	public function setModels($models) {
		$this->models = $models;
	}
	
	/**
	 * Sets the ini file that describes the database settings
	 * @param String $ini The db.ini to load
	 */
	public function setIni($ini)	{
		$this->ini = $ini;
	}
}

?>
