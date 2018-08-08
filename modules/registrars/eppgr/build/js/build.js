/*
 *  File: build/js/build.js
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

var buildColor, buildBackground, cellnum, mdir, cells, colors, tail, interval, contacts;
var refreshIntervalId, stopdomains, domains, ns, error, request_file, nextdomain;

function resetVariables() {
	buildColor = "#0000FF";
	buildBackground = "#FFFFFF";
	cellnum = 30;
	mdir = "right";
	cells = "";
	colors = "";
	tail = 8;
	interval = 100;
	refreshIntervalId = '';
	stopdomains = false;
	domains = new Array();
	contacts = new Array();
	ns = new sack();
	error = "";
	request_file = getHREF();
	nextdomain = 0;
}

function buildFunc() {
	colors = buildGetColors();
	document.getElementsByTagName("body")[0].style.backgroundColor = colors[tail+1];
	var par = document.getElementById("build_center");
	var w = parseInt(par.offsetWidth / cellnum);
	for (var i = 0; i < cellnum; i++) {
		var newdiv = document.createElement("div");
		newdiv.className = "build_cell";
		newdiv.style.width = w+"px";
		newdiv.style.backgroundColor = colors[tail+1];
		par.appendChild(newdiv);
	}
	cells = par.getElementsByTagName("div");
	refreshIntervalId = setInterval("buildImplement()", interval);
}

function buildStopInterval() {
	clearInterval(refreshIntervalId);
	var par = document.getElementById("build_center");
	removeChildNodes(par);
	var par = document.getElementById("build_buttons");
	var buttons = par.getElementsByTagName("input");
	for (var i = 0; i < buttons.length; i++) {
		if ((buttons[i].id == "stop")) {
			buttons[i].disabled = true;
		}
		else {
			buttons[i].disabled = false;
		}
	}
}

function buildImplement() {
	var first = buildFindCell(colors[0]);
	if (first < cells.length) {
		buildRecolor(first);
	}
	else {
		cells[0].style.backgroundColor = colors[0];
	}
}


function buildRecolor(first) {
	if (first == cells.length - 1) mdir = "left";
	else if (first == 0) mdir = "right";
	if (mdir == "left") add = -1;
	else add = 1;
	first += add;
	for (var i = 0; i <= tail+1; i++) {
		var num = parseInt(first)-i*parseInt(add);
		if (num >= 0 && num < cells.length) {
			cells[num].style.backgroundColor = colors[i];
		}
		else if (num < 0 && (num + first) < 0) {
			num = 0 - num;
			if (cells[num].style.backgroundColor && RGB2HTML(String(cells[num].style.backgroundColor).toUpperCase()) == String(colors[tail+1]).toUpperCase()) {
				continue;
			}
			cells[num].style.backgroundColor = colors[i];
		}
		else if (num >= cells.length && (cells.length - first) < (num - cells.length + 1)) {
			num = 2 * cells.length - num - 2;
			cells[num].style.backgroundColor = colors[i];
		}
	}
}

function buildFindCell(color) {
	for (var i = 0; i < cells.length; i++) {
		if (cells[i].style.backgroundColor && RGB2HTML(String(cells[i].style.backgroundColor).toUpperCase()) == String(color).toUpperCase()) {
			return i;
		}
	}
	return cells.length;
}

function RGB2HTML(color) {
	if (color.substr(0,1) == "#") return color;
	var vals = color.match(/\d+/g);
	if (parseInt(vals[0]) < 16) red = "0" + parseInt(vals[0]).toString(16);
	else red = parseInt(vals[0]).toString(16);
	if (parseInt(vals[1]) < 16) green = "0" + parseInt(vals[1]).toString(16);
	else green = parseInt(vals[1]).toString(16);
	if (parseInt(vals[2]) < 16) blue = "0" + parseInt(vals[2]).toString(16);
	else blue = parseInt(vals[2]).toString(16);
    var decColor = red + green + blue;
    return "#" + decColor.toUpperCase();
}

function buildGetColors() {
	var diffred = parseInt(buildColor.substr(1,2), 16) - parseInt(buildBackground.substr(1,2), 16);
	var diffgreen = parseInt(buildColor.substr(3,2), 16) - parseInt(buildBackground.substr(3,2), 16);
	var diffblue = parseInt(buildColor.substr(5,2), 16) - parseInt(buildBackground.substr(5,2), 16);
	var colors = new Array();
	colors[0] = buildColor;
	colors[tail+1] = buildBackground;
	for (var i = 1; i <= tail; i++) {
		var red = parseInt(buildColor.substr(1,2), 16) + parseInt(diffred*i/tail);
		var green = parseInt(buildColor.substr(3,2), 16) + parseInt(diffgreen*i/tail);
		var blue = parseInt(buildColor.substr(5,2), 16) + parseInt(diffblue*i/tail);
		red = red < 0 ? 0 - red : red;
		green = green < 0 ? 0 - green : green;
		blue = blue < 0 ? 0 - blue : blue;
		red = parseInt(red) < 16 ? "0" + parseInt(red).toString(16) : parseInt(red).toString(16);
		green = parseInt(green) < 16 ? "0" + parseInt(green).toString(16) : parseInt(green).toString(16);
		blue = parseInt(blue) < 16 ? "0" + parseInt(blue).toString(16) : parseInt(blue).toString(16);
		colors[i] = "#"+red.toUpperCase()+green.toUpperCase()+blue.toUpperCase();
	}
	return colors;
}

function performTasks(task) {
	resetVariables();
	var par = document.getElementById("build_buttons");
	var buttons = par.getElementsByTagName("input");
	for (var i = 0; i < buttons.length; i++) {
		if ((task == "stop" && buttons[i].id == "stop") || (task != "stop" && buttons[i].id != "stop")) {
			buttons[i].disabled = true;
		}
		else {
			buttons[i].disabled = false;
		}
	}
	if (task != "stop") buildFunc();
	getDomains(task);
}

function removeChildNodes(par) {
	while(par.hasChildNodes()) {
		par.removeChild(par.childNodes[0]);
	}
}

function getHREF() {
	var href = document.location.href;
	if (href.substr(-1) != "/") {
		var slash = document.location.href.lastIndexOf("/");
		var dot = document.location.href.substr(slash).lastIndexOf(".");
		if (dot > 0) {
			href = document.location.href.substr(0,slash+1);
		}
		else {
			href += "/";
		}
	}
	href += "build.php";
	return href;
}

function getDomains(task) {
	var msg = document.getElementById("build_messages");
	msg.style.display = "";
	ns.URLString = "";
	ns.setVar("task", task);
	ns.requestFile = request_file;
	ns.method = 'POST';
	ns.element = 'build_messages';
	ns.onLoading = whenLoadingGetDomains;
	ns.onLoaded = whenLoadedGetDomains;
	ns.onInteractive = whenLoadingGetDomains;
	ns.onCompletion = whenCompletedGetDomains;
	ns.runAJAX();
}

function saveDomain(domain) {
	var msg = document.getElementById("build_messages");
	msg.style.display = "";
	ns.URLString = "";
	ns.setVar("task", "save");
	ns.setVar("domain", domain[1]);
	ns.setVar("id", domain[0]);
	ns.requestFile = request_file;
	ns.method = 'POST';
	ns.element = 'build_messages';
	ns.onLoading = whenLoadingSaveDomain;
	ns.onLoaded = whenLoadedSaveDomain;
	ns.onInteractive = whenLoadingSaveDomain;
	ns.onCompletion = whenCompletedSaveDomain;
	ns.runAJAX();
}

function whenLoadingGetDomains(){
    var msg = document.getElementById(ns.element);
	removeChildNodes(msg);
	var newtext = document.createTextNode("Populating domains list...");
	msg.appendChild(newtext);
}

function whenLoadedGetDomains(){
    var msg = document.getElementById(ns.element);
	removeChildNodes(msg);
	var newtext = document.createTextNode("Populating domains list...");
	msg.appendChild(newtext);
}

function whenInteractiveGetDomains(){
    var msg = document.getElementById(ns.element);
	removeChildNodes(msg);
	var newtext = document.createTextNode("Populating domains list...");
	msg.appendChild(newtext);
}

function whenCompletedGetDomains() {
	getCleanResponse();
    var msg = document.getElementById(ns.element);
	removeChildNodes(msg);
}

function setDomainsList() {
    var msg = document.getElementById(ns.element);
	var total = document.getElementById("build_total");
	var messages = document.getElementById("build_messages");
	if (error) {
		var newtext = document.createTextNode("Error: "+error);
		msg.appendChild(newtext);
		buildStopInterval();
	}
	else {
		var newtext = document.createTextNode("Domains list populated!");
		msg.appendChild(newtext);
		if (domains.length) getNextDomain();
		else buildStopInterval();
	}
}

function getNextDomain() {
	var total = document.getElementById("build_total");
	var messages = document.getElementById("build_messages");
	if (stopdomains) {
		buildStopInterval();
		stopdomains = false;
	}
	else {
		removeChildNodes(total);
		removeChildNodes(messages);
		if (nextdomain == domains.length) var txt = "Finished!";
		else var txt = "Processing domain "+domains[nextdomain][1]+" ("+parseInt(nextdomain+1)+"/"+parseInt(domains.length)+").\n";
		var newtext = document.createTextNode(txt);
		total.appendChild(newtext);
		if (nextdomain == domains.length) buildStopInterval();
		else saveDomain(domains[nextdomain]);
		nextdomain++;
	}
}

function whenLoadingSaveDomain(){
}

function whenLoadedSaveDomain(){
}

function whenInteractiveSaveDomain(){
}

function whenCompletedSaveDomain() {
	getCleanResponse();
    var msg = document.getElementById(ns.element);
	if (error) {
		var newtext = document.createTextNode("Error: "+error);
		msg.appendChild(newtext);
	}
}

function getCleanResponse() {
	var bef = '<script language="javascript" type="text/javascript">';
	var aft = '<\/script>';
	var regline = new RegExp("\\n", "g");
	var nr = ns.response.replace(regline, '');
	var bef = new RegExp('^.*<script language="javascript" type="text/javascript">', 'g');
	var aft = new RegExp('<\/script>.*$', 'g');
	nr = nr.replace(bef, '');
	nr = nr.replace(aft, '');
	eval(nr);
}

function delPenOrd() {
	var values = new Array();
	$('#penordtable :input').each(function() {
		if (this.checked && this.value && this.value.match(/^\d+$/)) {
			values.push(this.value);
		}
	})
	values = values.join(',', values);
	jQuery.post(request_file, {'eppgrdeletependingorder[]': eval('['+values+']'), task: 'delorders'}, function(res){
		var bef = '<script language="javascript" type="text/javascript">';
		var aft = '<\/script>';
		var regline = new RegExp("\\n", "g");
		var nr = res.replace(regline, '');
		var bef = new RegExp('^.*<script language="javascript" type="text/javascript">', 'g');
		var aft = new RegExp('<\/script>.*$', 'g');
		nr = nr.replace(bef, '');
		nr = nr.replace(aft, '');
		eval(nr);
	});
}

function execPenOrd() {
	jQuery.post(request_file, {task: 'execorders'}, function(res){
		var bef = '<script language="javascript" type="text/javascript">';
		var aft = '<\/script>';
		var regline = new RegExp("\\n", "g");
		var nr = res.replace(regline, '');
		var bef = new RegExp('^.*<script language="javascript" type="text/javascript">', 'g');
		var aft = new RegExp('<\/script>.*$', 'g');
		nr = nr.replace(bef, '');
		nr = nr.replace(aft, '');
		eval(nr);
	});
}

function selPenOrd() {
	var first = true;
	var value = true;
	$('#penordtable :input').each(function() {
		if (first && !this.checked) {
			value = false;
		}
		first = false;
		this.checked = value;
	})
}

function getContactData(a) {
	$('#contacts_info').html(''); 
	resetVariables();
	var task = 'contacts';
	if (a) {
		task = 'delcontacts';
	}
	jQuery.post(request_file, {contacts: $('#contactstextarea').val(), task: task}, function(res){
		var bef = '<script language="javascript" type="text/javascript">';
		var aft = '<\/script>';
		var regline = new RegExp("\\n", "g");
		var nr = res.replace(regline, '');
		var bef = new RegExp('^.*<script language="javascript" type="text/javascript">', 'g');
		var aft = new RegExp('<\/script>.*$', 'g');
		nr = nr.replace(bef, '');
		nr = nr.replace(aft, '');
		eval(nr);
	});
}

function setContactsList() {
	var z = '<table><tr><th>ID</th><th>Type</th><th>Domain</th><th>Created</th><th>Values</th></tr>';
	var c = 0;
	$(contacts).each(function (i, selected) {
		z += '<tr class="mytr'+c+'">';
		$(selected).each(function (j, sel) {
			z += '<td class="mytd'+c+'">'+sel+'</td>';
		});
		z += '</tr>';
		c = 1 - c;
	});
	z += '</table>';
	$('#contacts_info').html(z);
}

$(document).ready(function() {
	$('#penordtable :input').each(function() {
		this.checked = false;
	})
});