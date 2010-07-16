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
		
		if ($user->hasCredential('lconf.user')) {
			
				$icinga_base = AppKitNavItem::create('lconf', 'lconf')
				->setCaption('LConf')
				->addAttributes('extjs-iconcls', 'lconf-logo');
			
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
					->setCaption('LConf Connection Manager')
					->addAttributes('extjs-iconcls', 'silk-user')
				);			
				$icinga->addSubItem(AppKitNavItem::create('lconf.about')
					->setCaption('About')
					->addAttributes('extjs-iconcls', 'silk-help')
					->setJsHandler("
						AppKit.util.contentWindow.createDelegate(null, [{ url: '". AgaviContext::getInstance()->getRouting()->gen('lconf.about') ."' }, 
						{ title: _('About Lconf')}])")
				);		
		}
		
		return true;

	}

}

?>
