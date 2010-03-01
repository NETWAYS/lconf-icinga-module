<?php

class LDAP_LDAPConnectionManagerModel extends ICINGALDAPBaseModel 
{
	protected $connectionArray = array();
	protected $scope = array();
	
	static private $allModels = null;
	
	public function addToConnectionArray(LDAP_LDAPConnectionModel $conn) {
		$this->availableConnections[$conn->getConnectionId()] = $conn;
	}	
	public function removeFromConnectionArray($conn) {
		$connectionArray = &$this->getConnectionArray();
		$idToRemove = null;
		
		// check whether an object to remove is given or just an id
		// in both cases, we want to end with the id in idToRemove
		if($conn instanceof LDAP_LDAPConnectionModel) {
			if(array_key_exists($conn->getConnectionId(),$connectionArray))
				$idToRemove = $conn->getConnectionId();
		} else {
			if(array_key_exists($conn,$connectionArray))
				$idToRemove = $conn;
		}
		
		// if entry doesn't exist, return with false;
		if(!$idToRemove)
			return false;	
		unset($connectionArray[$idToRemove]);
	}
	
	public function getConnectionArray() {
		if(empty($this->connectionArray)) {
			$this->loadConnectionsFromConfig();
		}
		return $this->connectionArray;
	}
	
	public function getConnectionById($nr) {
		$connections = $this->getConnectionArray();
		if(array_key_exists($nr,$connections))			
			return $connections[$nr];
		else 
			return null;
	}
	
	public function getScope()	{
		return $this->scope;
	}
	
	public function setConnectionArray(array $connectionArray) {
		$this->connectionArray = $connectionArray;
	}

	public function addScope($scope)	{
		$this->setConnectionArray(array());
		$this->scope[] = $scope;
	}
	public function setScope(array $scope) {
		$this->setConnectionArray(array());
		$this->scope = $scope;
	}
	
	protected function loadConnectionsFromConfig() {
		$connections = self::$allModels;
		$context = $this->getContext();
		$models = array();
		$model = null;
		// If the connections aren't already saved globally, fetch them and save it
		if(!$connections) {
			$connections = AgaviConfig::get("de.icinga.ldap",null);
			if(!is_null($connections))
				$connections = $connections["connections"];
				
			self::$allModels = $models;
		}
		foreach($connections as $connection) {
			if(!$this->checkScope($connection))
				continue;
			$model = $context->getModel("LDAPConnection","LDAP",array($connection));
			$models[$model->getConnectionId()] = $model;
		}
		$this->setConnectionArray($models);	
	}
	
	protected function checkScope($connection) {
		$scope = ($connection["scope"]);
		if(!is_array($scope))
			$scope = array($connection["scope"]);
		$scopeFilter = $this->getScope();
		if(array_intersect($scope,$scopeFilter)) 
			return true;
		else
			return false;
	}
	
	public function __toJSON() {
		$arr = array();
		foreach($this->getConnectionArray() as $connection) {	
			$arr[] = $connection->__toArray();
		}
		return json_encode(array("connections" => $arr));
	}


}

?>