<?php
use shopware_bundle\ShopwareBundle\Module\ShopwareModule;

// Frontend modules
// Unter "miscellaneous" sollte ein neuer Reiter namens "Hello World Plugin" erstellt werden, welches dann unser Frontend-Modul lädt.
$GLOBALS['FE_MOD']['Shopware']['produktList'] = ShopwareModule::class;