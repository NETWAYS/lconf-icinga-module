<?php

class LDAP_Backend_connectionManager_connectionListingSuccessView extends ICINGALDAPBaseView
{
	public function executeJSON(AgaviRequestDataHolder $rd) {
		$context = $this->getContext();
		$user = $context->getUser();
		$scope = $rd->getParameter("scope",array("global"));
		$connManager = $context->getModel("LDAPConnectionManager","LDAP");
		$connManager->setScope($scope);
		
		return $connManager->__toJSON();
	}
	
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'Backend.connectionManager.connectionListing');
	}
}

?>