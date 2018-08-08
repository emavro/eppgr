<?php
/*
 *  File: eppgrdiscount.php
 *  
 *  EPPGR: A registrar module for WHMCS to serve the .gr Registry
 *  Copyright (C) 2018 Efthimios Mavrogeorgiadis
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

function eppgr_processdiscount($vars) {
	require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
		. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. 'modules' . DIRECTORY_SEPARATOR . 'registrars' . DIRECTORY_SEPARATOR
		. 'eppgr' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR
		. 'eppgr.discount.php');
	return true;
}

add_hook("PreCalculateCartTotals",2,"eppgr_processdiscount");
