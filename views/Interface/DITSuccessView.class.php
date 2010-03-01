<?php

class LDAP_Interface_DITSuccessView extends ICINGALDAPBaseView
{
	public function executeSIMPLECONTENT(AgaviRequestDataHolder $rd) {

		$this->setupHtml($rd);
		
		$this->setAttribute("parentId",$rd->getParameter("parentid"));
	}
	
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'Interface.DIT');
	}
}

?>