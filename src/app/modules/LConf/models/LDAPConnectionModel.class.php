<?php



class LConf_LDAPConnectionModel extends IcingaLConfBaseModel
{
	protected $id;
	protected $connectionName;
	protected $connectionDescription;
	protected $bindDN;
	protected $bindPass;
	protected $baseDN;
	protected $host;
	protected $port;
	protected $authType = "simple";
	protected $TLS = false;
	// Getter and Setter
	
	static public $supportedAuthTypes = array("none","simple","sasl");
	
	public function getConnectionId()	{
		return $this->id;
	}
	
	public function getConnectionName() {
		return $this->connectionName;
	}
	
	public function getConnectionDescription() {
		return $this->connectionDescription;
	}
	
	public function getBindDN()	{
		return $this->bindDN;
	}
	
	public function getBindPass()  {
		return $this->bindPass;
	}
	
	public function getBaseDN() {
		return $this->baseDN;
	}
	
	public function getHost()	{
		return $this->host;
	}
	
	public function getPort()	{
		return $this->port;
	}
	
	public function getAuthType()	{
		return $this->authType;
	}
	
	public function usesTLS()	{
		return $this->TLS;
	}
	
	public function setConnectionId($id) {
		$this->id = $id;
	}
	public function setConnectionName($connName) {
		$this->connectionName = $connName;
	}
	
	public function setConnectionDescription($desc) {
		$this->connectionDescription = $desc;
	}
	
	public function setBindDN($dn)	{
		$this->bindDN = $dn;
	}
		
	public function setBindPass($pass) {
		$this->bindPass = $pass;
	}
	
	public function setBaseDN($dn)	{
		$this->baseDN = $dn;
	}
	
	public function setHost($host)	{
		$this->host = $host;
	}
	
	public function setPort($port)	{
		$this->port = $port;
	}

	public function setAuthType($authType) {
		if(in_array($authType,self::$supportedAuthTypes)) {
			$this->authType = $authType;
		} else {
			throw new AgaviException("Authtype ".$authType." is currently not supported for lconf.");
		}
	}
	
	public function setTLS($bool) {
		$this->TLS = (boolean) $bool;
	}
	
	public function __construct(array $parameter = null) {
		// Parse parameter if exist
		//print_r($parameter);
	
		if(!empty($parameter)) {
			if(isset($parameter["id"]))
				$this->setConnectionId($parameter["id"]);
			if(isset($parameter["connectionName"]))
				$this->setConnectionName($parameter["connectionName"]);
			if(isset($parameter["connectionDescription"]))
				$this->setConnectionDescription($parameter["connectionDescription"]);
			if(isset($parameter["bindDN"]))
				$this->setBindDN($parameter["bindDN"]);
			if(isset($parameter["bindPass"]))
				$this->setBindPass($parameter["bindPass"]);
			if(isset($parameter["host"]))
				$this->setHost($parameter["host"]);
			if(isset($parameter["port"]))
				$this->setPort($parameter["port"]);
			if(isset($parameter["baseDN"]))
				$this->setBaseDN($parameter["baseDN"]);
			if(isset($parameter["TLS"]))
				$this->setTLS($parameter["TLS"]);	
		}
		
	}
	public function __toArray() {
		$arr = array(
			"id" => $this->getConnectionId(),
			"connectionName" => $this->getConnectionName(),
			"connectionDescription" => $this->getConnectionDescription(), 
			"bindDN" => $this->getBindDN(),
			"bindPass" => $this->getBindPass(),
			"baseDN" => $this->getBaseDN(),
			"host" => $this->getHost(),
			"port" => $this->getPort(),
			"authType" =>$this->getAuthType(),
			"TLS" => $this->usesTLS()
		);
		return $arr;
	}
}



?>