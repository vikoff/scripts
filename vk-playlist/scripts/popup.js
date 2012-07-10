
function createNode(tag, attrs, text){
	var node = document.createElement(tag);
	for(var i in attrs || {}){
		if (i=='style')
			node.style.cssText = attrs[i];
		else
			node[i] = attrs[i];
	}
	if (text)
		node.appendChild(document.createTextNode(text));
	return node;
}

var Request = {

	_callbackId: 0,
	callbacks: {},

	send: function(topic, data, callback) {

		var sendData = {topic: topic, data: data || null};

		if (typeof callback == 'function') {
			var callbackId = this.getCallbackId();
			sendData['callback'] = callbackId;
			this.callbacks[callbackId] = callback;
		}

		opera.extension.postMessage(sendData);
	},

	getCallbackId: function() {
		return ++this._callbackId;
	},

	callback: function(callbackId, data) {
		this.callbacks[callbackId](data);
	}
};

var Playlist = {

	items: [],
	cur: -1,

	load: function(playlist) {
 		Playlist.items = playlist.items;
 		Playlist.cur = playlist.cur;
 		Playlist.render();
	},

	render: function() {

		var box = document.getElementById('output');
		box.innerHTML = '';

		if (this.items.length) {
			this.items.forEach(function(item, key) {
				var isCur = key == Playlist.cur;
				var rowHtml = createNode('div', {className: 'audio'});
				var click = function(){ isCur ? Playlist.stop() : Playlist.play(key); return false };
				rowHtml.appendChild(createNode('a', {className: 'play ' + (isCur ? 'playing' : ''), onclick: click, href: '#'}));
				rowHtml.appendChild(createNode('div', {className: 'artist'}, item.info[5]));
				rowHtml.appendChild(createNode('div', {className: 'title'}, ' – ' + item.info[6]));
				rowHtml.appendChild(createNode('div', {className: 'duration'}, item.info[4]));
				box.appendChild(rowHtml);
			});
		} else {
			box.appendChild(createNode('div', {className: 'empty-set'}, 'Плейлист пуст.'))
		}
	},

	play: function(index) {
		opera.extension.bgProcess.Playlist.play(index);
		Playlist.load(opera.extension.bgProcess.Playlist.getData());
	},

	stop: function() {
		opera.extension.bgProcess.Playlist.stop();
		Playlist.load(opera.extension.bgProcess.Playlist.getData());
	},

	clearAll: function() {
		if (!Playlist.items.length)
			return;
		// if (!confirm('Уверены?'))
			// return;
		Request.send('clear-all', null, function(response) {
			if (response == 'ok') {
				Playlist.items = [];
				Playlist.cur = -1;
				Playlist.render();
			} else {
				alert('clearing error. ' + response);
			}
		});
	}
}

window.addEventListener('load', function() {

	opera.extension.onmessage = function(event) {

		try { 
			var message = typeof event.data == 'string' ? JSON.parse(event.data) : event.data;
		} catch (e) {
			alert('unable to parse data:\n' + event.data);
			return;
		}

	 	if (message.callback) {
	 		Request.callback(message.callback, message.data);
	 		return;
	 	}

	 	switch (message.topic) {
	 		case 'popup-push-playlist':
	 			Playlist.load(message.data);
	 			break;
	 	}
	};

 	Request.send('popup-load-playlist', null, Playlist.load);

});
