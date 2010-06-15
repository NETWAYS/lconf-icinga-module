<?php

class LConf_Backend_modifyEntrySuccessView extends IcingaLConfBaseView
{
	public function executeJson(AgaviRequestDataHolder $rd)
	{
		try {
			$parameters= $rd->getParameters();
			
			$node = $parameters["node"];
			$connectionId = $parameters["connectionId"];
			$properties = json_decode($parameters["properties"],true);
			$context = $this->getContext();
			$context->getModel("LDAPClient","LDAP");
			$client = LConf_LDAPClientModel::__fromStore($connectionId,$context->getStorage());
			if(!$client) {
				throw new AgaviException("Connetion error. Please reconnect.");
				return null;
			}
			$client->setCwd($node);
			
			switch($parameters["action"]) {
				case 'nodeCreate':
					return "?";
					break;
				case 'propertyCreate':
					$client->addNodeProperty($node, $properties);
					return "Success";
					break;
				case 'alter':
					$client->modifyNode($node, $properties);
					return "Success";
					break;
				case 'nodeDelete':
					break;
				case 'propertyDelete':
					$client->removeNodeProperty($node, $properties);
					return "Success";
					break;
				default:
					throw new AgaviException("Unknown action: ".$parameters["action"]);
			}

		} catch(Exception $e) {
			$this->getResponse()->setHttpStatusCode('500');
			return $e->getMessage();
		}
	}
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'Backend.ModifyEntry');
	}
}

?>