<?php

class LConf_Backend_listDirectorySuccessView extends IcingaLConfBaseView
{
	public function executeJson(AgaviRequestDataHolder $rd) {
		$connectionId = $rd->getParameter("connectionId");
		$context = $this->getContext();
		// Register Class
		$context->getModel("LDAPClient","LConf");
		$client = LConf_LDAPClientModel::__fromStore($connectionId,$context->getStorage());
		$client->setCwd($rd->getParameter("node"));
		$list = $client->listCurrentDir();
		// filter out base node information
		if(!is_array($list))
			return null;
		$nodeList = $this->reformatList($list,$client);	
		
		return json_encode($nodeList);
	}
	
	protected function reformatList($list,LConf_LDAPClientModel $client) {
		$startCWD = $client->getCwd();
		$nodeList = array();
		foreach($list as $key=>$node) {
			// we already have the information about the base node, so skip these
			if(!is_int($key)) 
				continue;
			// check for leafs
			$client->setCwd($node["dn"]);
			$subs = $client->listCurrentDir();
			if(!is_array($subs) || !@$subs["count"])
				$node["isLeaf"] = true;

			$node["parent"] = $startCWD;	
			$nodeList[] = $node;	
		}
		$client->setCwd($startCWD);
		return $nodeList;
	}
	
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		$this->setAttribute('_title', 'Backend.listDirectory');
	}
}

?>