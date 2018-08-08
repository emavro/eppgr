<?php
/*
 *  File: build/patch.php
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

foreach ($files as $filename => $values) {
	$files[$filename]['file'] = eppgr_getfile($tmplfolder, $filename);
	if (!$files[$filename]['file']) {
		continue;
	}
	eppgr_check('file', $files[$filename]['file']);
	$patched = eppgr_patched($files[$filename]['find'], $files[$filename]['file']);
	if ($patched) {
		$files[$filename]['contents'] = '';
		echo "<span style=\"color: blue;\">File ".$files[$filename]['file']." has already been patched.</span><br />";
	}
	else {
		$files[$filename]['contents'] = eppgr_patch($files[$filename], $files[$filename]['file']);
	}
}

echo '<br /><br />';

foreach ($files as $filename => $values) {
	if ($files[$filename]['contents']) {
		eppgr_writefile($files[$filename]);
	}
}

echo "<br /><br />Finished patching!";

exit;

?>
			</div>
		</div>
	</body>
</html>