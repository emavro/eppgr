<?php

function eppgr_processdnames($vars) {
	require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
		. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. 'modules' . DIRECTORY_SEPARATOR . 'registrars' . DIRECTORY_SEPARATOR
		. 'eppgr' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR
		. 'eppgr.dnames.php');
	return true;
}

add_hook("PreCalculateCartTotals",1,"eppgr_processdnames");
