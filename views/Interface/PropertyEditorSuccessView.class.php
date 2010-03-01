<?php

class LDAP_Interface_PropertyEditorSuccessView extends ICINGALDAPBaseView
{
	public function executeSimplecontent(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);
		$this->setAttribute("parentId",$rd->getParameter("parentid"));
	}
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);
		$this->setAttribute("parentId",$rd->getParameter("parentid"));
	}
}

?>