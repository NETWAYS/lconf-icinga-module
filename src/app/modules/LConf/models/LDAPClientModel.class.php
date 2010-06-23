<?php
/**
 * Client Model class for ldap 
 * Builds a connection, handles filtering and provides an interface
 * for communication with the ldap Server.
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
	 * @var ldapFilterGroupModel
	 */
	private $filter = false;
	/**
	 * Schema Validator - not implemented yet
	 * @var unknown_type
	 */
	private $schemaValidator = false;
	/**
	 * ConnectionModel class which holds information about the connection
	 * like Host, BaseDN, credentials, etc.
	 * @var ldapConnectionModel
	 */
	private $connectionModel = false;

	/**
	 * Instance of LDAPHelperModel for misc. result formatting operations 
	 * @var LConf_LDAPHelperModel
	 */
	private $helper = null;
	/**
	 * The connection resource over which communication is handled
	 * @var resource
	 */
	private $connection		= false;
	/**
	 * ldap_options, see @link http://de2.php.net/manual/en/ref.ldap.php 
	 * @var array
	 */
	private $ldap_options	= array (
		LDAP_OPT_REFERRALS			=> 0,
		LDAP_OPT_DEREF				=> LDAP_DEREF_NEVER,
		LDAP_OPT_PROTOCOL_VERSION	=> 3
	); 
	/**
	 * The current working dir
	 * @var string
	 */
	private $cwd = false;
	/**
	 * BaseDN 
	 * TODO: Check if needed
	 * @var string
	 */
	private $baseDN = false;
	/**
	 * Flag that indicates whether the class should store itself when destructed
	 * @var boolean
	 */
	private $dontStoreFlag = false;
	
	public function setId($id) {
		$this->id = $id; 
	}
	public function setFilter(lConf_LDAPFilterGroupModel  $filter) {
		$this->filter = $filter;
	}
	public function setSchemaValidator(LConf_LDAPConnectionModel $schemaValidator) {
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
			ldap_close($this->getConnection());
	}
	
	/**
	 * Connects (or reconnects if from store) to the ldap server 
	 * @return void
	 */
	public function connect() {
		$connConf = $this->getConnectionModel();
		$this->helper = AgaviContext::getInstance()->getModel("LDAPHelper","LConf");
		$connection = ldap_connect($connConf->getHost(),$connConf->getPort());
		if(!is_resource($connection))
			throw new AgaviException("Could not connect to ".$connConf->getConnectionName());
		
		$this->setConnection($connection);
		$this->applyldapOptions();
		if($connConf->usesTLS()) { //enable TLS if marked
			if(!@ldap_start_tls($connection))
				throw new Exception("Connection via TLS could not be established!");
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
		$this->setId(AgaviToolkit::uniqid("ldap_conn_"));
	}
	
	private function applyldapOptions() {
		foreach ($this->ldap_options as $opt_id=>$opt_val) {
			ldap_set_option($this->getConnection(), $opt_id, $opt_val);
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

		if(@ldap_bind($connection,$dn,$pass) == false) {
			throw new AgaviException("Bind to ".$dn." failed: ".$this->getError());
		}
	}
	
	
	/**
	 * Sets the default CWD from the connectionModel
	 * @return void
	 */
	public function setDefaultCwd() {
		$connConf = $this->getConnectionModel();
		$dn = $connConf->getBaseDN();  
		if(!$dn) // no BaseDN given, guess the Base dir
			$dn = $this->helper->suggestBaseDNFromName($connConf->getBindDN());
		$this->setBaseDN($dn);
		$this->setCwd($dn);
	}
		
	/**
	 * Adds a new Property $newParams to $dn. 
	 * $newParams must be an associative array with the fields 
	 * 'property' and 'value' 
	 * 
	 * returns an array with the new properties on success, else throws an
	 * Instance of AgaviException with the Errormessage.
	 * 
	 * @TODO: Yep. ldap_mod_add would make life easier. Reading the complete api, too.
	 * @param string $dn
	 * @param array $newParams
	 * @return array $properties
	 */
	public function addNodeProperty($dn,$newParams) {
		// if we only have a single entry, encapsulate it in an array
		// so we don't need to differ between them and multiple entries
		if(@$newParams["property"]) 
			$newParams = array($newParams);
	
		$connId = $this->getConnection();
		$properties = $this->getNodeProperties($dn);
		$this->helper->cleanResult($properties);
		foreach($newParams as $parameter) {
			$newProperty = $parameter["property"];
			$newValue = $parameter["value"];
			
			if(!isset($properties[$newProperty])) { // property doesn't exist
				$properties[$newProperty] = array();
			} else if(!is_array($properties[$newProperty])) { 
				// property already exists
				$swap = $properties[$newProperty];
				$properties[$newProperty] = array($swap);
			} 
			$properties[$newProperty][] = $newValue;
		}

		if(!@ldap_modify($connId,$dn,$properties)) {
			throw new AgaviException("Could not modify ".$dn. ":".$this->getError());
		}
		return $properties;
	}
	
	
	
	/**
	 * returns the ldap_entries for cwd
	 * @return array
	 */
	public function listCurrentDir() {
		$connConf = $this->getConnectionModel();
		$basedn = str_replace("ALIAS=Alias of:","",$this->getCwd());

		$result = ldap_list($this->getConnection(),$basedn,"objectClass=*");
		$entries = ldap_get_entries($this->getConnection(),$result);
		return $this->helper->resolveAliases($entries);
	}	

	/**
	 * Returns the properties of a node $dn
	 * 
	 * @param string $dn
	 * @return array
	 */
	public function getNodeProperties($dn) {
		$connection = $this->getConnection();
		$result = @ldap_read($connection,$dn,"objectclass=*");
		if(!$result)
			return array();
		$entries = ldap_get_entries($connection,$result);
		return $entries[0];
	}
	
	/**
	 * Modifies a node $dn so that it's parameters will match $newParams
	 * $newParams must be an associative array with the fields
	 * 'id'	 		an id with the format %KEYNAME%_%ENTRYNR% 
	 * 'property' 	the new property name
	 * 'value' 		the new value name
	 * 
	 * @TODO: Yep. like in the add function ldap_mod_replace would make life easier.
	 * @param string $dn
	 * @param string $newParams
	 * @return array 
	 */
	public function modifyNode($dn, $newParams) {
		if($newParams["id"])
			$newParams = array($newParams);
			
		$connId = $this->getConnection();
		$properties = $this->getNodeProperties($dn);
		$properties = $this->helper->formatToPropertyArray($properties);
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
		
		if(!@ldap_modify($connId,$dn,$properties)) {
			throw new AgaviException("Could not modify ".$dn. ":".$this->getError());
		}
		return $properties;
	}
	
	/**
     * @TODO: Yep. like in the add function ldap_mod_replace would make life easier. 
	 * @param unknown_type $dn
	 * @param unknown_type $remParams
	 */
	public function removeNodeProperty($dn,$remParams) {
		if(!is_array($remParams))
			$remParams = array($remParams);
			
		$connId = $this->getConnection();
		$properties = $this->getNodeProperties($dn);
		
		$this->helper->cleanResult($properties);
		$idRegexp = "/^(.*)_(\d*)$/";
		foreach($remParams as $parameter) {
			$idElems = array();
			preg_match($idRegexp,$parameter,&$idElems);
			if(count($idElems) != 3) {
				throw new AppKitException("Invalid ID given to removeProperty ".$parameter);
			}
			$curProperty = $idElems[1];
			$curIndex = $idElems[2];
			if(is_array($properties[$curProperty])) {
				$properties[$curProperty][$curIndex] = array();
				if(count($properties[$curProperty]) == 1)
					$properties[$curProperty] = array();
			} else 
				$properties[$curProperty] = array();
		}

		if(!@ldap_modify($connId,$dn,$properties)) {
			throw new AgaviException("Could not modify ".$dn. ":".$this->getError());
		}
		return null;
	}
	
	
	public function toStore() {
		$clSerialized = serialize($this);
		$storage = $this->getContext()->getStorage();
		$storage->write("Icinga.ldap.client.".$this->getId(),$clSerialized);
	
	}
	
	static public function __fromStore($id,AgaviStorage $storage) {
		$clSerialized = $storage->read("Icinga.ldap.client.".$id);
		return unserialize($clSerialized);
	}
	
	public function disableStoring() {
		$this->dontStoreFlag = true;
	}
	public function enableStoring() {
		$this->dontStoreFlag = true;
	}
	
	public function __sleep() {
		$this->_connectionModel = false;
		if($this->getConnectionModel())
			$this->_connectionModel = $this->connectionModel->__toArray(true);
			
		/* AgaviModel __sleep()*/
		$this->_contextName = $this->context->getName();
		
		return array('id','baseDN','_connectionModel','_contextName','connection','ldap_options','cwd');
		
	}
		
	public function __wakeup() {
		$this->context = AgaviContext::getInstance($this->_contextName);
		unset($this->_contextName);	
		// rebuild filters
	
		// rebuild connection-class
	
		if($this->_connectionModel) {
			$this->connectionModel = $this->getContext()
				->getModel("LDAPConnection","LConf",array($this->_connectionModel));
			$this->_connectionModel = null;
		}
		// and finally (and hopefuly)- reconnect!
		$this->connect();
	}
	
	public function getError() {
		if(is_resource($this->getConnection()))
			return "<br/>LDAP Error:<br/><pre style='margin:10px;width:400px;font-size:10px;padding:5px;border:1px solid #dedede;-moz-border-radius:5px;-webkit-border-radius:5px;background:white;cursor:text;color:red'><code>".ldap_error($this->getConnection())."</code></div>";
	}
	
	
}


?>