<?php

class LConf_Interface_ViewMainEditorSuccessView extends IcingaLConfBaseView
{
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);
		$container = $this->getContainer();
		$actionBarParameters = new AgaviRequestDataHolder();
		$actionBarParameters->setParameter("parentid","east-frame");
		$actionBarContainer = $container->createExecutionContainer("LConf","Interface.ActionBar",$actionBarParameters,"simple");
		
		$DITParameters = new AgaviRequestDataHolder();
		$DITParameters->setParameter("parentid","west-frame");
		$DITContainer = $container->createExecutionContainer("LConf","Interface.DIT",$DITParameters,"simple");

		$PropertyEditorParameters = new AgaviRequestDataHolder();
		$PropertyEditorParameters->setParameter("parentid","center-frame");
		$PropertyEditorContainer= $container->createExecutionContainer("LConf","Interface.PropertyEditor",$PropertyEditorParameters,"simple");
		
		$this->setAttribute("js_actionBarInit",$actionBarContainer->execute()->getContent());
		$this->setAttribute("js_DITinit",$DITContainer->execute()->getContent());
		$this->setAttribute("js_PropertyEditorInit",$PropertyEditorContainer->execute()->getContent());
		
		$this->setAttribute('_title', 'Interface.ViewMainEditor');
	}
	
}

?>