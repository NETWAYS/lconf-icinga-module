<?php

class LConf_PropertyEditor_PropertyWizardHostgroupView extends IcingaLConfBaseView
{
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'PropertyEditor.PropertyWizard');
	}
}

?>