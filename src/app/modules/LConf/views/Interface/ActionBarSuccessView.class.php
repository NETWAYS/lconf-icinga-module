<?php

class LConf_Interface_ActionBarSuccessView extends IcingaLConfBaseView
{
	public function executeSimple(AgaviRequestDataHolder $rd)
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
					'route'=> $ro->gen('lconf.actionbar.connmanager')
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