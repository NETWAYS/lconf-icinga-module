<?php

class LConfMenuExtender extends AppKitEventHandler implements AppKitEventHandlerInterface {

	public function checkObjectType(AppKitEvent &$e) {
		if (!$e->getObject() instanceof AppKit_NavigationContainerModel) {
			throw new AppKitEventException('Object should be AppKit_NavigationContainerModel');
		}
			
		return true;
	}

	public function handleEvent(AppKitEvent &$event) {

		$nav = $event->getObject();

		$user = $nav->getContext()->getUser();
		
		if ($user->hasCredential('icinga.user')) {
			
				$icinga_base = AppKitNavItem::create('lconf', 'lconf')
				->setCaption('LConf')
				->addAttributes('extjs-iconcls', 'silk-plugin');
			
				// Throws exception if the admin is not there ...
				if ($nav->getNavItemByName('appkit.admin')) {
					// Navigation for "icinga"
					$icinga = $nav->getContainer()->addItemBefore('appkit.admin', $icinga_base);
				}
				else {
					$icinga = $nav->getContainer()->addItem($icinga_base);
				}
				
				$icinga->addSubItem(AppKitNavItem::create('lconf.main', 'lconf.main')
					->setCaption('LDAP Editor')
					->addAttributes('extjs-iconcls', 'silk-chart-organisation')
				);
				
				$icinga->addSubItem(AppKitNavItem::create('lconf.admin', 'lconf.admin')
					->setCaption('LConf Admin')
					->addAttributes('extjs-iconcls', 'silk-user')
				);				
		}
		
		return true;

	}

}

?>
