<?php

class LDAP_Backend_connectionManager_connectSuccessView extends ICINGALDAPBaseView
{
	public function executeJSON(AgaviRequestDataHolder $rd) {
		try {
			$context = $this->getContext();
			$scope = $rd->getParameter("scope",array("global"));
			$connectionId = $rd->getParameter("connectionId",false);
			if(!$connectionId)
				throw new AgaviException("No connectionId provided!");
				
			$connectionMgr = $context->getModel("LDAPConnectionManager","LDAP");
			$connectionMgr->setScope($scope);
			
			$connection = $connectionMgr->getConnectionById($connectionId);
			if(!$connection)
				throw new AgaviException("Invalid connectionId provided!");
				
			$connManager = $context->getModel("LDAPClient","LDAP",array($connection));
			$connManager->connect();
			return json_encode(array(
							"ConnectionID"=>$connManager->getId(),
							"RootNode"=>$connManager->getCwd())
							);
			
		} catch(Exception $e) {
			$this->getResponse()->setHttpStatusCode('500');
			return $e->getMessage();
		}
	}
	
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'Backend.connectionManager.connect');
	}
}

?>