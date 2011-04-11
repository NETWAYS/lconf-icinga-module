<?php

class LConf_Backend_LConfExportTaskAction extends IcingaLConfBaseAction
{
	/**
	 * Returns the default view if the action does not serve the request
	 * method used.
	 *
	 * @return     mixed <ul>
	 *                     <li>A string containing the view name associated
	 *                     with this action; or</li>
	 *                     <li>An array with two indices: the parent module
	 *                     of the view to be executed and the view to be
	 *                     executed.</li>
	 *                   </ul>
	 */
	public function executeRead(AgaviRequestDataHolder $rd) {
		$connManager = AgaviContext::getInstance()->getModel('LDAPConnectionManager','LConf');
		$connManager->getConnectionsFromDB();
		$conn = $connManager->getConnectionById(1);
		$confExporter = AgaviContext::getInstance()->getModel('LConfExporter','LConf');
		$confExporter->exportConfig($conn);
	}
	
	public function getDefaultViewName()
	{
		return 'Success';
	}
}

?>
