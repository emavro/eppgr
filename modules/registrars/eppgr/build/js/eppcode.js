/*
 *  File: build/js/eppcode.js
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

function execSubmit(button) {
	var form = getForm(button);
	if (jQuery('#datafile').val()) {
		var counter = 0;
		jQuery(form).find('option:selected').each(function() {
			if (parseInt(jQuery(this).val()) && jQuery(this).parent().prop('name').toLowerCase() == 'client[]') {
				counter++;
			}
			else {
				jQuery(this).attr('selected', false);
			}
		});
		if (!counter) {
			alert('You must select at least one client.');
		}
		else if (counter > 1) {
			alert('You must select only one client.');
		}
		else {
			jQuery(form).submit();
		}
	}
	else {
		jQuery(form).submit();
	}
}

function execClear(button) {
	var form = getForm(button);
	var select = jQuery(form).find('select');
	var input = jQuery(form).find('input');
	jQuery(form).find('*').each(function() {
		if (jQuery(this).prop('tagName').toLowerCase() == 'input' && jQuery(this).prop('type').toLowerCase() != 'button' && jQuery(this).prop('type').toLowerCase() != 'submit') {
			jQuery(this).val('');
		}
		if (jQuery(this).prop('tagName').toLowerCase() == 'option') {
			jQuery(this).attr('selected', false);
		}
	});
	jQuery('#results').empty();
}

function getForm(item) {
	return jQuery(item).parents('form');
}