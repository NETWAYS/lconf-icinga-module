<?php

class LConf_Backend_modifyNodeSuccessView extends IcingaLConfBaseView
{
	public function executeJson(AgaviRequestDataHolder $rd) {
		try {
			$action = $rd->getParameter("xaction");
			$properties = json_decode($rd->getParameter("properties"),true);
			$parentDN = $rd->getParameter("parentNode");
			$connectionId = $rd->getParameter("connectionId");
			$context = $this->getContext();
			$context->getModel("LDAPClient","LConf");

			$client = LConf_LDAPClientModel::__fromStore($connectionId,$context->getStorage());
			
			if(!$client) {
				throw new AgaviException("Connection error. Please reconnect.");
				return null;
			}
			$client->setCwd($parentDN);
			
		
			switch($rd->getParameter("xaction")) {
				case 'update':
				case 'create':
					$client->addNode($parentDN, $properties);
					return "Success";
					break;
				case 'destroy':
					$client->removeNodes($properties);
					return "Success";
					break;
			}

		} catch(Exception $e) {
			$this->getResponse()->setHttpStatusCode('500');
			return $e->getMessage();
		}
	}

	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'Backend.modifyNode');
	}
}

?>