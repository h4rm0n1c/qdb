window.onload = function() {
	obj = new domAjax('./vote.php', 'vote');
}

function vote(id, type) {
	obj.addParam('callback', 'vote_callback');
	obj.addParam('qid', id);
	obj.addParam('type', type);
	obj.query();
	obj.clearParams();
}

function adminvote(id, type) {
	var xhr = new XMLHttpRequest();
	var params = 'qid=' + encodeURIComponent(id) + '&type=' + encodeURIComponent(type) + '&csrf=' + encodeURIComponent(window.qdbCsrfToken || '');
	xhr.open('POST', './vote.php', true);
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.onreadystatechange = function() {
		if (xhr.readyState != 4) {
			return;
		}

		try {
			var data = JSON.parse(xhr.responseText);
			if (xhr.status >= 200 && xhr.status < 300) {
				adminvote_callback(data);
			} else {
				alert(data.errormsg || 'Admin action failed');
			}
		} catch (e) {
			alert('Admin action failed');
		}
	};
	xhr.send(params);
}

function rox(id) {
	vote(id, 'rox');
	return false;
}

function sox(id) {
	vote(id, 'sox');
	return false;
}

function sux(id) {
	if(confirm('Flag quote for review?')) {
		vote(id, 'sux');
	}
	return false;
}

function approve(id) {
	adminvote(id, 'approve');
	return false;
}

function reject(id) {
	adminvote(id, 'reject');
	return false;
}

function kill(id) {
	adminvote(id, 'kill');
	return false;
}

function unflag(id) {
	adminvote(id, 'unflag');
	return false;
}

function vote_callback(js_data) {
	if(js_data['newscore'] != 'false' && js_data['newscore'] != 'none') {
		score = document.getElementById("score" + js_data['qid']);
		score.innerHTML = js_data['newscore'];
	}
	
	if(js_data['error'] == true) {
		alert(js_data['errormsg']);
	}
}

function adminvote_callback(js_data) {
	if(js_data['error'] == false) {
		var penddiv = document.getElementById(js_data['newscore'] + js_data['qid']);
		penddiv.parentNode.removeChild(penddiv);
	} else {
		alert(js_data['errormsg'] || 'Admin action failed');
	}
}

function validateadd() {
	var quotetext = document.getElementById('quotetext');
	
	if(quotetext.value.isEmpty()) {
		alert("Please Enter a Quote before Submitting");
		return false;
	} else {
		return true;
	}
}
