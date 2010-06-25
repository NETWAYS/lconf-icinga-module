<?php

class LConf_Backend_LDAPObjectsSuccessView extends IcingaLConfBaseView
{
	static protected $staticFields = array("objectclass","properties");
	
	public function executeJson(AgaviRequestDataHolder $rd) {
		$field = $rd->getParameter("field");
		$ctx = $this->getContext();
		$result;
		if(in_array($field,self::$staticFields)) {
			$definitions = null;//$ctx->getStorage()->read("lconf.ldap.entites");
			if(!$definitions)
				$definitions = $this->loadStaticLDAPDefinitions();			
			$result = $definitions[$field];
			foreach($result as &$entry)
				$entry = array("entry"=>$entry);
		}
		
		$response = $this->buildResponse($field,$result);
		return json_encode($response);
	}
	
	protected function buildResponse($field,$result) {
		$response = array(
			"metaData" => array(
				"idProperty" => "entry",
				"root" => "result",
				"fields" => array(
					"entry"  
				)
			),
			"result" => $result
		);
		return $response;
	}
	
	public function loadStaticLDAPDefinitions() {
		$ctx = $this->getContext();
		$cfg = AgaviConfig::get("modules.lconf.ldap_definition_ini");
		$data = parse_ini_file($cfg);
		$ctx->getStorage()->write("lconf.ldap.entites",$data);
		return $data;
	}
	
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'Backend.LDAPObjects');
	}
}

?>