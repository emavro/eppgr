/*
 *  File: js/punycode.php
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

var eppgrbuttons = new Array('#examplebuttonid');
var eppgrhide = -1;
var eppgropacspeed = 10;
var eppgrinterval = 100;
var eppgrIntervalId;
var eppgrinput;

function eppgrWaitMsg() {
	if (!jQuery('#eppgrwaitmsg').length) {
		var par = jQuery(eppgrinput).parent();
		jQuery(par).css('display', 'none');
		jQuery(par).parent().append('<div id="eppgrwaitmsg">&nbsp;</div>')
		var el = jQuery('#eppgrwaitmsg');
		if (typeof(eppgrhttp) == 'undefined') {
			eppgrhttp = '';
		}
		jQuery(el).css('background', 'url('+eppgrhttp+'modules/registrars/eppgr/images/loader.gif) no-repeat center center');
		jQuery(el).css('width', '100%');
		jQuery(el).css('height', '100px');
		jQuery(el).css('text-align', 'center');
		jQuery(el).css('opacity', 1);
		jQuery(el).css('filter', 'alpha(opacity=100)');
	}
	else {
		var el = jQuery('#eppgrwaitmsg');
		jQuery(el).css('opacity', (parseFloat(jQuery(el).css('opacity')) * 100 + parseInt(eppgropacspeed) * parseInt(eppgrhide)) / 100);
		jQuery(el).css('filter', 'alpha(opacity='+(jQuery(el).css('opacity')*100)+')');
		if ((parseFloat(jQuery(el).css('opacity')) * 100 + parseInt(eppgropacspeed) * parseInt(eppgrhide)) / 100 < 0) {
			jQuery(el).css('opacity', 0);
			jQuery(el).css('filter', 'alpha(opacity=0)');
			eppgrhide = 1;
		}
		else if ((parseFloat(jQuery(el).css('opacity')) * 100 + parseInt(eppgropacspeed) * parseInt(eppgrhide)) / 100 > 1) {
			jQuery(el).css('opacity', 1);
			jQuery(el).css('filter', 'alpha(opacity=100)');
			eppgrhide = -1;
		}
	}
}

function strrpos (haystack, needle, offset) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   input by: saulius
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: strrpos('Kevin van Zonneveld', 'e');
    // *     returns 1: 16
    // *     example 2: strrpos('somepage.com', '.', false);
    // *     returns 2: 8
    // *     example 3: strrpos('baa', 'a', 3);
    // *     returns 3: false
    // *     example 4: strrpos('baa', 'a', 2);
    // *     returns 4: 2

    var i = -1;
    if (offset) {
        i = (haystack+'').slice(offset).lastIndexOf(needle); // strrpos' offset indicates starting point of range till end,
        // while lastIndexOf's optional 2nd argument indicates ending point of range from the beginning
        if (i !== -1) {
            i += offset;
        }
    }
    else {
        i = (haystack+'').lastIndexOf(needle);
    }
    return i >= 0 ? i : false;
}

//http://stackoverflow.com/questions/183485/can-anyone-recommend-a-good-free-javascript-for-punycode-to-unicode-conversion
//Javascript UTF16 converter created by some@domain.name
//This implementation is released to public domain
var utf16 = {
    decode:function(input){
    	var output = [], i=0, len=input.length,value,extra;
    	while (i < len) {
    		value = input.charCodeAt(i++);
    		if ((value & 0xF800) === 0xD800) {
    			extra = input.charCodeAt(i++);
    			if ( ((value & 0xFC00) !== 0xD800) || ((extra & 0xFC00) !== 0xDC00) ) {
    				throw new RangeError("UTF-16(decode): Illegal UTF-16 sequence");
    			}
    			value = ((value & 0x3FF) << 10) + (extra & 0x3FF) + 0x10000;
    		}
    		output.push(value);
    	}
    	return output;
    },
    encode:function(input){
    	var output = [], i=0, len=input.length,value;
    	while (i < len) {
    		value = input[i++];
    		if ( (value & 0xF800) === 0xD800 ) {
    			throw new RangeError("UTF-16(encode): Illegal UTF-16 value");
    		}
    		if (value > 0xFFFF) {
    			value -= 0x10000;
    			output.push(String.fromCharCode(((value >>>10) & 0x3FF) | 0xD800));
    			value = 0xDC00 | (value & 0x3FF);
    		}
    		output.push(String.fromCharCode(value));
    	}
    	return output.join("");
    }
}

//Javascript Punycode converter derived from example in RFC3492.
//This implementation is created by some@domain.name and released to public domain    
var punycode = new function Punycode() {
    var initial_n = 0x80;
    var initial_bias = 72;
        var delimiter = "\x2D";
    var base = 36;
    var damp = 700;
    var tmin=1;
    var tmax=26;
    var skew=38;

    var maxint = 0x7FFFFFFF;
    // decode_digit(cp) returns the numeric value of a basic code 
    // point (for use in representing integers) in the range 0 to
    // base-1, or base if cp is does not represent a value.

    function decode_digit(cp) {
    	return  cp - 48 < 10 ? cp - 22 :  cp - 65 < 26 ? cp - 65 : cp - 97 < 26 ? cp - 97 : base;
    }

    // encode_digit(d,flag) returns the basic code point whose value      
    // (when used for representing integers) is d, which needs to be in   
    // the range 0 to base-1.  The lowercase form is used unless flag is  
    // nonzero, in which case the uppercase form is used.  The behavior   
    // is undefined if flag is nonzero and digit d has no uppercase form. 

    function encode_digit(d, flag) {
    	return d + 22 + 75 * (d < 26) - ((flag != 0) << 5);
    	//  0..25 map to ASCII a..z or A..Z 
    	// 26..35 map to ASCII 0..9         
    }
    //** Bias adaptation function **
    function adapt(delta, numpoints, firsttime ) {
    	var k;
    	delta = firsttime ? Math.floor(delta / damp) : (delta >> 1);
    	delta += Math.floor(delta / numpoints);

    	for (k = 0;  delta > (((base - tmin) * tmax) >> 1);  k += base) {
    		delta = Math.floor(delta / ( base - tmin ));
    	}
    	return Math.floor(k + (base - tmin + 1) * delta / (delta + skew));
    }

    // encode_basic(bcp,flag) forces a basic code point to lowercase if flag is zero,
    // uppercase if flag is nonzero, and returns the resulting code point.
    // The code point is unchanged if it  is caseless.
    // The behavior is undefined if bcp is not a basic code point.                                                   

    function encode_basic(bcp, flag) {
    	bcp -= (bcp - 97 < 26) << 5;
    	return bcp + ((!flag && (bcp - 65 < 26)) << 5);
    }

    // Main decode
    this.decode=function(input,preserveCase) {
    	// Dont use uft16
    	var output=[];
    	var case_flags=[];
    	var input_length = input.length;

    	var n, out, i, bias, basic, j, ic, oldi, w, k, digit, t, len;

    	// Initialize the state: 

    	n = initial_n;
    	i = 0;
    	bias = initial_bias;

    	// Handle the basic code points:  Let basic be the number of input code 
    	// points before the last delimiter, or 0 if there is none, then    
    	// copy the first basic code points to the output.                      

    	basic = input.lastIndexOf(delimiter);
    	if (basic < 0) basic = 0;

    	for (j = 0;  j < basic;  ++j) {
    		if(preserveCase) case_flags[output.length] = ( input.charCodeAt(j) -65 < 26);
    		if ( input.charCodeAt(j) >= 0x80) {
    			throw new RangeError("Illegal input >= 0x80");
    		}
    		output.push( input.charCodeAt(j) );
    	}

    	// Main decoding loop:  Start just after the last delimiter if any  
    	// basic code points were copied; start at the beginning otherwise. 

    	for (ic = basic > 0 ? basic + 1 : 0;  ic < input_length; ) {

    		// ic is the index of the next character to be consumed,

    		// Decode a generalized variable-length integer into delta,  
    		// which gets added to i.  The overflow checking is easier   
    		// if we increase i as we go, then subtract off its starting 
    		// value at the end to obtain delta.
    		for (oldi = i, w = 1, k = base;  ;  k += base) {
    			if (ic >= input_length) {
    				throw RangeError ("punycode_bad_input(1)");
    			}
    			digit = decode_digit(input.charCodeAt(ic++));

    			if (digit >= base) {
    				throw RangeError("punycode_bad_input(2)");
    			}
    			if (digit > Math.floor((maxint - i) / w)) {
    				throw RangeError ("punycode_overflow(1)");
    			}
    			i += digit * w;
    			t = k <= bias ? tmin : k >= bias + tmax ? tmax : k - bias;
    			if (digit < t) { break; }
    			if (w > Math.floor(maxint / (base - t))) {
    				throw RangeError("punycode_overflow(2)");
    			}
    			w *= (base - t);
    		}

    		out = output.length + 1;
    		bias = adapt(i - oldi, out, oldi === 0);

    		// i was supposed to wrap around from out to 0,   
    		// incrementing n each time, so we'll fix that now: 
    		if ( Math.floor(i / out) > maxint - n) {
    			throw RangeError("punycode_overflow(3)");
    		}
    		n += Math.floor( i / out ) ;
    		i %= out;

    		// Insert n at position i of the output: 
    		// Case of last character determines uppercase flag: 
    		if (preserveCase) { case_flags.splice(i, 0, input.charCodeAt(ic -1)  -65 < 26);}

    		output.splice(i, 0, n);
    		i++;
    	}
    	if (preserveCase) {
    		for (i = 0, len = output.length; i < len; i++) {
    			if (case_flags[i]) {
    				output[i] = (String.fromCharCode(output[i]).toUpperCase()).charCodeAt(0);
    			}
    		}
    	}
    	return utf16.encode(output);		
    };

    //** Main encode function **

    this.encode = function (input,preserveCase) {
    	//** Bias adaptation function **

    	var n, delta, h, b, bias, j, m, q, k, t, ijv, case_flags;

    	if (preserveCase) {
    		// Preserve case, step1 of 2: Get a list of the unaltered string
    		case_flags = utf16.decode(input);
    	}
    	// Converts the input in UTF-16 to Unicode
    	input = utf16.decode(input.toLowerCase());
    	//input = utf16.decode(input);

    	var input_length = input.length; // Cache the length

    	if (preserveCase) {
    		// Preserve case, step2 of 2: Modify the list to true/false
    		for (j=0; j < input_length; j++) {
    			case_flags[j] = input[j] != case_flags[j];
    		}
           	}

    	var output=[];


    	// Initialize the state: 
    	n = initial_n;
    	delta = 0;
    	bias = initial_bias;

    	// Handle the basic code points: 
    	for (j = 0;  j < input_length;  ++j) {
    		if ( input[j] < 0x80) {
    			output.push(
    				String.fromCharCode(
    					case_flags ? encode_basic(input[j], case_flags[j]) : input[j]
    				)
    			);
    		}
    	}

    	h = b = output.length;

    	// h is the number of code points that have been handled, b is the  
    	// number of basic code points 

    	if (b > 0) output.push(delimiter);

    	// Main encoding loop: 
    	//
    	while (h < input_length) {
    		// All non-basic code points < n have been     
    		// handled already. Find the next larger one: 

    		for (m = maxint, j = 0;  j < input_length;  ++j) {
    			ijv = input[j];
    			if (ijv >= n && ijv < m) m = ijv;
    		}

    		// Increase delta enough to advance the decoder's    
    		// <n,i> state to <m,0>, but guard against overflow: 

    		if (m - n > Math.floor((maxint - delta) / (h + 1))) {
    			throw RangeError("punycode_overflow (1)");
    		}
    		delta += (m - n) * (h + 1);
    		n = m;

    		for (j = 0;  j < input_length;  ++j) {
    			ijv = input[j];

    			if (ijv < n ) {
    				if (++delta > maxint) return Error("punycode_overflow(2)");
    			}

    			if (ijv == n) {
    				// Represent delta as a generalized variable-length integer: 
    				for (q = delta, k = base;  ;  k += base) {
    					t = k <= bias ? tmin : k >= bias + tmax ? tmax : k - bias;
    					if (q < t) break;
    					output.push( String.fromCharCode(encode_digit(t + (q - t) % (base - t), 0)) );
    					q = Math.floor( (q - t) / (base - t) );
    				}
    				output.push( String.fromCharCode(encode_digit(q, preserveCase && case_flags[j] ? 1:0 )));
    				bias = adapt(delta, h + 1, h == b);
    				delta = 0;
    				++h;
    			}
    		}

    		++delta, ++n;
    	}
    	return output.join("");
    }
}();

function eppgrGetInputEl() {
	var el = jQuery('textarea[name=bulkdomains]');
	if (!jQuery(el).length) {
		el = jQuery('input[name=domain]');
	}
	if (!jQuery(el).length) {
		el = jQuery('input[name=sld]');
	}
	return el;
}

function eppgrEncDecDomainName(dn, enc) {
	if (dn.match('.')) {
		var parts = dn.split('.');
	}
	else {
		var parts = new Array(dn);
	}
	var np = new Array();
	jQuery(parts).each(function(j, sel) {
		if (enc) {
			var cleanlen = sel.replace(/[^0-9\-\.\x80-\xFF\u0080-\uFFFF]+/, '').length;
			if (cleanlen && sel.length == cleanlen) {
				np.push('xn--'+punycode.encode(sel.replace(/\s/, '')));
			}
			else {
				np.push(sel.replace(/\s/, ''));
			}
		}
		else {
			if (sel.match(/^xn--/)) {
				sel = sel.replace(/^xn--/, '');
				np.push(punycode.decode(sel.replace(/\s/, '')));
			}
			else {
				np.push(sel.replace(/\s/, ''));
			}
		}
	});
	return np.join('.');
}

function eppgrProcessInput(selected, regexp) {
	var type = jQuery(selected).attr('type');
	if (type.toLowerCase() == 'submit') {
		var parform = jQuery(selected).parents('form');
		var par = jQuery(selected).parent();
		if (jQuery(parform)[0].tagName.toLowerCase() == 'form' && jQuery(parform).attr('action').match(regexp)) {
			var total = 0;
			total += jQuery(parform).find('textarea[name="bulkdomains"]').length;
			total += jQuery(parform).find('input[name="sld"]').length;
			total += jQuery(parform).find('input[name="domain"]').length;
			if (!total) {
				return;
			}
			var clone = jQuery(selected).clone();
			jQuery(clone).attr('id', 'eppgrbutton');
			jQuery('eppgrbutton').attr('type', 'button');
			jQuery(clone).click(function() {
				eppgrExecPunycode(this);
			});
			jQuery(clone).appendTo(par);
			jQuery(selected).css('display', 'none');
			jQuery(parform).find('input').each(function(j, sel) {
				if (jQuery(sel).attr('type') == 'text') {
					jQuery(sel).keypress(function(evt) {
					    evt = (evt) ? evt : event;
					    var target = (evt.target) ? evt.target : evt.srcElement;
					    var form = target.form;
					    var charCode = (evt.charCode) ? evt.charCode :
   						    ((evt.which) ? evt.which : evt.keyCode);
					    if (charCode == 13) {
							jQuery('#eppgrbutton').click();
						    return false;
					    }
					    return true;
					});
				}
			});
		}
	}
}

jQuery(document).ready(function() {
	var href = window.location.href;
	var rx = new Array(/domainchecker\.php/, /cart\.php\?a=add&(amp;)*domain=register/, /cart\.php\?a=add&(amp;)*domain=transfer/, /whois\.php\?domain=/);
	var regexp = '';
	for (var i = 0; i < rx.length; i++) {
		if (href.match(rx[i])) {
			regexp = rx[i];
			break;
		}
	}
	if (regexp) {
		if (jQuery('body').html().match(/xn--[\w\-]+(\.\w+)*(\.\w+)*/gi)) {
			var xns = jQuery('body').html().match(/xn--[\w\-]+(\.\w+)*(\.\w+)*/gi);
			jQuery(xns).each(function(i, selected) {
				var orig = eppgrEncDecDomainName(selected, false);
				var html = jQuery('body').html();
				jQuery('body').html(html.replace(selected, orig));
				jQuery('body').html(jQuery('body').html().replace('whois.php?domain='+orig, 'whois.php?domain='+selected));
				jQuery('td').each(function(j, sel) {
					var txt = jQuery(sel).text();
					if (txt.match(selected) || txt.match(orig)) {
						if (txt.substr(strrpos(txt, '.') + 1).toLowerCase() != 'gr') {
							jQuery(sel).parents('tr').remove();
						}
					}
				});
			});
		}
		jQuery('input').each(function(i, selected) {
			eppgrProcessInput(selected, regexp);
		});
	}
	else {
		jQuery(eppgrbuttons).each(function(i, selected) {
			if (jQuery(selected).length) {
				jQuery(selected).each(function(j, sel) {
					var parform = jQuery(sel).parents('form');
					var regexp = jQuery(parform).attr('action');
					eppgrProcessInput(sel, regexp);
				});
			}
		});
	}
});

function eppgrExecPunycode(input) {
	eppgrinput = input;
	var parform = jQuery(input).parents('form');
	var el = eppgrGetInputEl();
	if (jQuery(el).length) {
		var val = jQuery(el).val();
		if (val.match(/[\n\r]/)) {
			var values = val.split(/[\n\r]+/);
		}
		else {
			var values = new Array(val);
		}
		var nv = new Array();
		jQuery(values).each(function(i, selected) {
			nv.push(eppgrEncDecDomainName(selected, true));
		});
		eppgrWaitMsg();
		jQuery(el).val(nv.join("\n"));
	}
	else {
		eppgrWaitMsg()
	}
	jQuery(parform).submit();
}