<?php
/**
 * Client Model class for LDAP 
 * Builds a connection, handles filtering and provides an interface
 * for communication with the LDAP Server.
 * Automatically saves itself to the store on destruction.
 * 
 * TODO: Validation, Filtering and node creation and deletion functions
 * @author jmosshammer
 *
 */
class LConf_LDAPClientModel extends IcingaLConfBaseModel
{
	/**
	 * ID of the connection, needed for storing
	 * @var string
	 */
	private $id = 0;
	/**
	 * Filtergroup class for search queries
	 * @var LDAPFilterGroupModel
	 */
	private $filter = null;
	/**
	 * Schema Validator - not implemented yet
	 * @var unknown_type
	 */
	private $schemaValidator = null;
	/**
	 * ConnectionModel class which holds information about the connection
	 * like Host, BaseDN, credentials, etc.
	 * @var LDAPConnectionModel
	 */
	private $connectionModel = null;
	/**
	 * The connection resource over which communication is handled
	 * @var resource
	 */
	private $connection		= null;
	/**
	 * LConf_options, see @link http://de2.php.net/manual/en/ref.ldap.php 
	 * @var array
	 */
	private $LConf_options	= array (
		LConf_OPT_REFERRALS			=> 0,
		LConf_OPT_DEREF				=> LConf_DEREF_ALWAYS,
		LConf_OPT_PROTOCOL_VERSION	=> 3
	); 
	/**
	 * The current working dir
	 * @var string
	 */
	private $cwd = null;
	/**
	 * BaseDN 
	 * TODO: Check if needed
	 * @var string
	 */
	private $baseDN = null;
	/**
	 * Flag that indicates whether the class should store itself when destructed
	 * @var boolean
	 */
	private $dontStoreFlag = false;
	
	public function setId($id) {
		$this->id = $id; 
	}
	public function setFilter(LConf_LDAPFilterGroupModel  $filter) {
		$this->filter = $filter;
	}
	public function setSchemaValidator(LConf_LDAPSchemaValidatorModel $schemaValidator) {
		$this->validator = $validator;
	}
	public function setConnectionModel(LConf_LDAPConnectionModel $connection) {
		$this->connectionModel = $connection;
	}	
	public function setConnection($connection) {
		$this->connection = $connection;	
	}
	public function setCwd($cwd) {
		return $this->cwd = $cwd;
	}
	public function setBaseDN($dn) {
		$this->baseDN = $dn;
	}
	
	public function getId()	{
		return $this->id;
	}
	public function getFilter() {
		return $this->filter;
	}
	public function getSchemaValidator() {
		return $this->validator;
	}
	public function getConnectionModel()	{
		return $this->connectionModel;	
	}
	public function getConnection()	{
		return $this->connection;
	}
	public function getCwd() {
		return $this->cwd;
	}
	public function getBaseDN() {
		return $this->baseDN;
	}
	
	public function __construct(LConf_LDAPConnectionModel $connection = null) {
		if($connection)	
			$this->setConnectionModel($connection);
	}
	/**
	 * Destroys the class and stores it if the dontStoreFlag is not set
	 * @return void
	 */
	public function __destruct() {
		if(!$this->dontStoreFlag)
			$this->toStore();
			 
		if(is_resource($this->getConnection()))
			LConf_close($this->getConnection());
	}
	
	/**
	 * Connects (or reconnects if from store) to the LDAP server 
	 * @return void
	 */
	public function connect() {
		$connConf = $this->getConnectionModel();

		$connection = LConf_connect($connConf->getHost(),$connConf->getPort());
		if(!is_resource($connection))
			throw new AgaviException("Could not connect to ".$connConf->getConnectionName());
		
		$this->setConnection($connection);
		$this->applyLdapOptions();
		if($connConf->usesTLS()) { //enable TLS if marked
			LConf_start_tls($connection);
		}
		$this->doDefaultBind();
		
		// if the class is unserialized we don't want to set the cwd
		if(!$this->getCwd()) 
			$this->setDefaultCwd();
		
		if(!$this->getId()) 
			$this->generateId();
	}
	
	/**
	 * Generates an unique id for the client
	 * @return string
	 */
	private function generateId() {
		$this->setId(AgaviToolkit::uniqid("LConf_conn_"));
	}
	
	private function applyLdapOptions() {
		foreach ($this->LConf_options as $opt_id=>$opt_val) {
			LConf_set_option($this->getConnection(), $opt_id, $opt_val);
		}
	}
	
	private function doDefaultBind()	{
		$connConf = $this->getConnectionModel();
		$this->bindTo($connConf->getBindDN(),$connConf->getBindPass());
	}
	
	public function bindTo($dn,$pass) {
		$connection = $this->getConnection();
		if(!is_resource($connection))
			throw new AgaviException("Connection is not a resource");			

		if(@LConf_bind($connection,$dn,$pass) == false) {
			throw new AgaviException("Bind to ".$dn." failed: ".$this->getError());
		}
	}
	
	/**
	 * Method that tries to guess the baseDN if none is set in the options
	 * @param string $dn
	 * @return string
	 */
	private function suggestBaseDNFromName($dn) {
		$explodedDN = LConf_explode_dn($dn,0);
		$dn = "";
		foreach($explodedDN as $key=>$val) {
			if($key == "dc") {
				$dn .= $key."=".$val;
			}
		}
		return $dn;
	}
	
	/**
	 * Sets the default CWD from the connectionModel
	 * @return void
	 */
	public function setDefaultCwd() {
		$connConf = $this->getConnectionModel();
		$dn = $connConf->getBaseDN();  
		if(!$dn) // no BaseDN given, guess the Base dir
			$dn = $this->suggestBaseDNFromName($connConf->getBindDN());
		$this->setBaseDN($dn);
		$this->setCwd($dn);
	}
	
	/**
	 * returns the LConf_entries for cwd
	 * @return array
	 */
	public function listCurrentDir() {
		$connConf = $this->getConnectionModel();
		$basedn = $this->getCwd();
		$result = LConf_list($this->getConnection(),$basedn,"objectClass=*");
		return LConf_get_entries($this->getConnection(),$result);
	}	
	
	/**
	 * Adds a new Property $newParams to $dn. 
	 * $newParams must be an associative array with the fields 
	 * 'property' and 'value' 
	 * 
	 * returns an array with the new properties on success, else throws an
	 * Instance of AgaviException with the Errormessage.
	 * 
	 * @param string $dn
	 * @param array $newParams
	 * @return array $properties
	 */
	public function addNodeProperty($dn,$newParams) {
		// if we only have a single entry, encapsulate it in an array
		// so we don't need to differ between them and multiple entries
		if($newParams["id"]) 
			$newParams = array($newParams);
	
		$connId = $this->getConnection();
		$properties = $this->getNodeProperties($dn);
		$properties = $this->formatToPropertyArray($properties);
		
		foreach($newParams as $parameter) {
			$newProperty = $parameter["property"];
			$newValue = $parameter["value"];
			
			if(!$properties[$newProperty]) { // property doesn't exist
				$properties[$newProperty] = array();
			} else if(!is_array($properties[$newProperty])) { 
				// property already exists
				$swap = $properties[$newProperty];
				$properties[$newProperty] = array($swap);
			} 
			$properties[$newProperty][] = $newValue;
		}

		if(!@LConf_modify($connId,$dn,$properties)) {
			throw new AgaviException("Could not modify ".$dn. ":".$this->getError());
		}
		return $properties;
	}
	
	/**
	 * Returns the properties of a node $dn
	 * 
	 * @param string $dn
	 * @return array
	 */
	public function getNodeProperties($dn) {
		$connection = $this->getConnection();
		$result = LConf_read($connection,$dn,"objectclass=*");
		$entries = LConf_get_entries($connection,$result);
		return $entries[0];
	}
	
	/**
	 * Modifies a node $dn so that it's parameters will match $newParams
	 * $newParams must be an associative array with the fields
	 * 'id'	 		an id with the format %KEYNAME%_%ENTRYNR% 
	 * 'property' 	the new property name
	 * 'value' 		the new value name
	 * 
	 * @param string $dn
	 * @param string $newParams
	 * @return array 
	 */
	public function modifyNode($dn, $newParams) {
		if($newParams["id"])
			$newParams = array($newParams);
			
		$connId = $this->getConnection();
		$properties = $this->getNodeProperties($dn);
		$properties = $this->formatToPropertyArray($properties);
		$idRegexp = "/^(.*)_(\d*)$/";

		foreach($newParams as $parameter) {
			$idElems = array();
			preg_match($idRegexp,$parameter["id"],&$idElems);
			if(count($idElems) != 3) {
				throw new AppKitException("Invalid ID given to modifyNode ".$parameter["id"]);
			}
			$curProperty = $idElems[1];
			$curIndex = $idElems[2];
			if(is_array($properties[$curProperty]))
				$properties[$curProperty][$curIndex] = $parameter["value"];
			else 
				$properties[$curProperty] = $parameter["value"];
		}	
		
		if(!@LConf_modify($connId,$dn,$properties)) {
			throw new AgaviException("Could not modify ".$dn. ":".$this->getError());
		}
		return $properties;
	}
	
	public function removeNodeProperty($dn,$remParams) {
		if(!is_array($remParams))
			$remParams = array($remParams);
			
		$connId = $this->getConnection();
		$properties = $this->getNodeProperties($dn);
		$properties = $this->formatToPropertyArray($properties);
		$idRegexp = "/^(.*)_(\d*)$/";
		foreach($remParams as $parameter) {
			$idElems = array();
			preg_match($idRegexp,$parameter,&$idElems);
			if(count($idElems) != 3) {
				throw new AppKitException("Invalid ID given to removeProperty ".$parameter);
			}
			$curProperty = $idElems[1];
			if(is_array($properties[$curProperty]))
				$properties[$curProperty][$curIndex] = array();
			else 
				$properties[$curProperty] = array();
		}

		if(!@LConf_modify($connId,$dn,$properties)) {
			throw new AgaviException("Could not modify ".$dn. ":".$this->getError());
		}
		return null;
	}
	
	protected function formatToPropertyArray(array $arr) {
		$returnArray = array();
		foreach($arr as $attribute=>$value) {
			if(!is_array($value)) 
				continue;
			
			$valueCount = $value["count"];
			if($valueCount == 1) {
				$returnArray[$attribute] = $value[0];
			} else {
				$returnArray[$attribute] = array();
				for($i=0;$i<$valueCount;$i++) {
					$returnArray[$attribute][] = $value[$i];
				}
			}			
 		}
 		return $returnArray;
	}
	
	public function toStore() {
		$clSerialized = serialize($this);
		$storage = $this->getContext()->getStorage();
		$storage->write("Icinga.LDAP.client.".$this->getId(),$clSerialized);
	
	}
	
	static public function __fromStore($id,AgaviStorage $storage) {
		$clSerialized = $storage->read("Icinga.LDAP.client.".$id);
		return unserialize($clSerialized);
	}
	
	public function disableStoring() {
		$this->dontStoreFlag = true;
	}
	public function enableStoring() {
		$this->dontStoreFlag = true;
	}
	
	public function __sleep() {
		if($this->getFilter())
			$this->_filter = $this->filter->__toArray();
			
		if($this->getConnectionModel())
			$this->_connectionModel = $this->connectionModel->__toArray();
		/* AgaviModel __sleep()*/
		$this->_contextName = $this->context->getName();
		
		return array('id','_filter','baseDN','schemaValidator','_connectionModel','_contextName','connection','LConf_options','cwd');
		
	}
		
	public function __wakeup() {
		$this->context = AgaviContext::getInstance($this->_contextName);
		unset($this->_contextName);	
		// rebuild filters
		if($this->_filter) {
			$this->filter = LConf_LDAPFilterGroupModel::__fromArray($this->_filter);
			$this->_filter = null;
		}
		// rebuild connection-class
	
		if($this->_connectionModel) {
			$this->connectionModel = $this->getContext()
				->getModel("LDAPConnection","LDAP",array($this->_connectionModel));
			$this->_connectionModel = null;
		}
		// and finally (and hopefuly)- reconnect!
		$this->connect();
	}
	
	public function getError() {
		if(is_resource($this->getConnection()))
			return LConf_error($this->getConnection());
	}
}


?>