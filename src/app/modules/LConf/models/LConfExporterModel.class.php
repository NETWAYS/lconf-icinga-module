<?php
/**
 * Exporter task for creating lconf configs from the webinterface
 * 
 * @author jmosshammer
 *
 */

class LConf_LConfExporterModel extends IcingaLConfBaseModel
{
	protected $lconfConsole = null;
	protected $instanceConsole = null;
	protected $statusCounter = 0;

	public function getStatus() {
		return $this->status[$statusCounter];
	}

	public function getConsole($instance) {
		if(!$this->lconfConsole)
			$this->lconfConsole = AgaviContext::getInstance()->getModel('Console.ConsoleInterface',"Api",array("host"=>$instance));
		return $this->lconfConsole;
	}
	
	public function exportConfig(LConf_LDAPConnectionModel $ldap_config) {	
		$satellites = $this->fetchExportSatellites($ldap_config);
		$lconfExportInstance = AgaviConfig::get('modules.lconf.lconfExport.lconfConsoleInstance');
		$console = $this->getConsole($lconfExportInstance);

		$exportCmd =  AgaviContext::getInstance()->getModel(
			'Console.ConsoleCommand',
			"Api",
			array(
				"command" => "lconf_export",
				"connection" => $console, 
				"arguments" => $satellites
			)
			
		);
	
		$console->exec($exportCmd);
		print_r(array($exportCmd->getReturnCode(),$exportCmd->getOutput()));	
	}
	
	protected function fetchExportSatellites(LConf_LDAPConnectionModel $ldap_config) {
		$ctx = $this->getContext();
		$filterGroup = $ctx->getModel('LDAPFilterGroup','LConf');
		$objectClassFilter =  $ctx->getModel("LDAPFilter","LConf",array("objectclass","*",false,"exact"));
		$filter = $ctx->getModel('LDAPFilter','LConf',array(
			'description','LCONF->EXPORT->CLUSTER',null,'contains'
		));
		$filterGroup->addFilter($objectClassFilter);
		$filterGroup->addFilter($filter);
		$client = $ctx->getModel('LDAPClient','LConf',array($ldap_config));
		$client->connect();
		$entries = $client->searchEntries($filterGroup,null,array('description','objectclass'));
		$satellites = array();
	
		foreach($entries as $val=>$cluster) {
			if(!is_numeric($val))
				continue;	
			if(!$this->isStructuralObject($cluster))
				continue;
	
			foreach($cluster['description'] as $key=>$val) {
				if(!is_numeric($key))
					continue;
				$matches = array();
				preg_match_all('/^LCONF->EXPORT->CLUSTER[\t ]*?=[\t ]*?(?P<satellite>\w+?[ \t]*?$)/i',$val,$matches);
				if(is_array($matches['satellite']))
					$satellites = array_merge($satellites,$matches['satellite']);
			}
		}
		return $satellites;
	}

	protected function isStructuralObject($cluster) { 
		
		/**
		*	determine if the objectclass is *structuralobject (objectclasses are internally stored 
		*	as numeric identifiers, so wildcarded filters don't work)
		**/	
		if(!isset($cluster['objectclass'])) {
			return false;	
		}
		foreach($cluster['objectclass'] as $key=>$val) {	
			if(!is_numeric($key))
				continue;
				
			if(preg_match('/\w*StructuralObject/i',$val)) {	
				return true;	
			}
		}
		return false;
	}

	protected function createConfig() {
	}

}
