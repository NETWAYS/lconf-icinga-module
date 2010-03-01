<?php

class LDAP_Interface_ActionBarSuccessView extends ICINGALDAPBaseView
{
	public function executeSIMPLECONTENT(AgaviRequestDataHolder $rd)
	{
		$panelIds = array(
			'filter' => 'Filter_Overview',
			'connections' => 'Connections_Overview',
			'schema' => 'Schema_Definitions'
		);
		/**
		 * TODO: Parse from config
		 */
		$ro = $this->getContext()->getRouting();
		$navPoints = array(
			'filter' => array(
				'init' => array(
					'route'=> $ro->gen('')
				),
				'jsExtParams' => array(
					'id'=>$panelIds['filter'],
					'title'=>'Filter',
					'xtype'=>'panel'
				)
			),
			'connections' => array(
				'init' => array(
					'route'=> $ro->gen('LDAP.actionBar.connManager')
				),	
				'jsExtParams' => array(
					'id'=>$panelIds['connections'],
					'title'=>'Connections',
					'xtype'=>'panel'
				)
			)
		);
	
		$this->setupHtml($rd);
		$this->setAttribute("parentid",$rd->getParameter("parentid"));
		$this->setAttribute('_menuPoints',$navPoints);
		$this->setAttribute('_panelIds',$panelIds);
		$this->setAttribute('_title', 'Interface.mainMenuBar');
	}
}

?>