<?php

require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
	. 'modules' . DIRECTORY_SEPARATOR . 'registrars' . DIRECTORY_SEPARATOR
	. 'eppgr' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR
	. 'eppgr.checkregdetails.php');

add_hook("PreShoppingCartCheckout",1,"eppgrCheckRegDetails");
add_hook("AfterShoppingCartCheckout",1,"eppgrCancelOrder");
add_hook("ClientAreaPage",1,"eppgrShowError");
