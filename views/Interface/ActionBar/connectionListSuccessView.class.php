<?php

class LDAP_Interface_ActionBar_connectionListSuccessView extends ICINGALDAPBaseView
{
	public function executeSIMPLECONTENT(AgaviRequestDataHolder $rd) {
		$context = $this->getContext();
		
		$eventId = $rd->getParameter("eventId");
		$parentId = $rd->getParameter("parentid");
		$this->setAttribute("eventId",$eventId);
		$this->setAttribute("parentid",$parentId);
		
		$this->setupHtml($rd);	
	}
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'Interface.ActionBar.connectionList');
	}
}

?>