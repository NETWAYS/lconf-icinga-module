<?php

class LDAP_Interface_ViewMainEditorSuccessView extends ICINGALDAPBaseView
{
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);
		$container = $this->getContainer();
		$actionBarParameters = new AgaviRequestDataHolder();
		$actionBarParameters->setParameter("parentid","east-frame");
		$actionBarContainer = $container->createExecutionContainer("LDAP","Interface.ActionBar",$actionBarParameters,"simplecontent");
		
		$DITParameters = new AgaviRequestDataHolder();
		$DITParameters->setParameter("parentid","west-frame");
		$DITContainer = $container->createExecutionContainer("LDAP","Interface.DIT",$DITParameters,"simplecontent");

		$PropertyEditorParameters = new AgaviRequestDataHolder();
		$PropertyEditorParameters->setParameter("parentid","center-frame");
		$PropertyEditorContainer= $container->createExecutionContainer("LDAP","Interface.PropertyEditor",$PropertyEditorParameters,"simplecontent");
		
		$this->setAttribute("js_actionBarInit",$actionBarContainer->execute()->getContent());
		$this->setAttribute("js_DITinit",$DITContainer->execute()->getContent());
		$this->setAttribute("js_PropertyEditorInit",$PropertyEditorContainer->execute()->getContent());
		
		$this->setAttribute('_title', 'Interface.ViewMainEditor');
	}
	
}

?>