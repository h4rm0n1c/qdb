String.prototype.trim = function() {
  return this.replace(/^\s*|\s*$/g, "");
}

String.prototype.isEmpty = function() {
	if(/^\s*$/.test(this)) return true;
	return false;
}

function isEmpty(string) {
	var emptyString = /^\s*$/
	if(emptyString.test(string)) return true;
	return false;
}

function domAjax(url, id) {
	this.sid = id;
	this.params = new Array();
	this.surl = url;
	
	this.query = function() {
		var headTag = document.getElementsByTagName("head").item(0);
		var oldTag = document.getElementById(this.sid);
		
		if(oldTag) headTag.removeChild(oldTag);
		
		var scriptTag = document.createElement('script');
		scriptTag.type = 'text/javascript';
		scriptTag.src = this.surl + this.generateParams();
		scriptTag.id = this.sid;
		
		headTag.appendChild(scriptTag);
	}
	
	this.generateParams = function() {
		if(this.params.length == 0) {
			return false;
		}
		
		var returnString = '';
		var i;
		var pChar = '?';
		
		for(i = 0; i < this.params.length; i++) {
			
			pChar = (i == 0)?'?':'&'; // set pChar to ampersand after first parameter
			
			returnString += pChar + this.params[i]['name'].trim(); //output GET var name
			
			returnString += (this.params[i]['data'].isEmpty())?'':'=' + this.params[i]['data'].trim(); //if data is empty, don't output it
		}
		
		return returnString;
	}
	
	this.addParam = function(name, data) {
		if(name.isEmpty()) {
			return -1;
		}
		
		data += '';
		
		this.params.push({'name': name.trim(), 'data': data.trim()});
		
		return (this.params.length - 1);
	}
	
	this.removeParam = function(key) {
		if(key > (this.params.length - 1) || key < 0) return false;
		this.params.splice(key, 1);
	}
	
	this.clearParams = function() {
		this.params = new Array();
	}
}

function isDomAjax() {
	if (typeof arguments[0] == 'object') {
		var criterion = arguments[0].constructor.toString().match(/domAjax/i);
		return (criterion != null);
	}
	return false;
}