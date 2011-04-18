<?php
/**
 * Exporter task for creating lconf configs from the webinterface
 * 
 * @author jmosshammer
 *
 */
class LConfExporterErrorException extends AgaviException {};

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
		$this->tm = AgaviContext::getInstance()->getTranslationManager();
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
		if($exportCmd->getReturnCode() != 0) { 
			throw new LConfExporterErrorException($this->getCommandError($exportCmd));	
		} else {
			return $this->parseSuccessfulOutput($exportCmd);
		}
	}
	
	protected function getCommandError(Api_Console_ConsoleCommandModel $exportCmd) {

		switch($exportCmd->getReturnCode()) {
			case 126: //execution error
				return $this->tm->_("Cannot execute exporter, please check your permissions");
				break;
			case 127: //command not found
				return $this->tm->_("Exporter not found - check your configuration");
				break;
			default:
				return $this->getErrorFromCommandOutput($exportCmd);
			
		}
	}

	protected function parseSuccessfulOutput(Api_Console_ConsoleCommandModel $exportCmd) {
		$output = utf8_encode($exportCmd->getOutput());
		$matches = array();
		$result = array();
		preg_match_all("/[\t ]*?Checked[\t ]*?(?P<number>\d+)[\t ]*?(?P<category>[ \w]+)\./",$output,$matches);
		for($i=0;$i<count($matches["number"]);$i++) {
			$result[] = array(
				"type" => trim($matches["category"][$i]),
				"count" => intval($matches["number"][$i])
			);
		}
		return $result;
	}

	protected function getErrorFromCommandOutput(Api_Console_ConsoleCommandModel $exportCmd) {
		$output = $exportCmd->getOutput();
			
		if(($err = $this->checkForLDAPErrors($output)) != false)
			return $err;
		if(($err = $this->checkForIcingaErrors($output)) != false)
			return $err;	
		return $this->tm->_("An unknown error occured, check your server logs");
	}

	protected function checkForLDAPErrors($output) {
		if(preg_match("/.*Export config from LDAP\nOK - No errors/",$output))
			return false;
		if(preg_match("/.*Can't connect to ldap server/",$output)) 
			return $this->tm->_("Exporter couldn't connect to ldap db. Please check your config.");	
	}

	protected function checkForIcingaErrors($output) {
		$errors = array();
		$errStr = "";
		if(preg_match_all("/Error: (.*)/",$output,$errors)) {
			foreach($errors[0] as $error) 
				$errStr .= $error."\n";
			return $this->tm->_("Config verification failed: \n".$errStr);	
		}
		return false;
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



}
