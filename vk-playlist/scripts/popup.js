
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
function decodeStr(str) {
	str = str.replace(/\&amp;/g, '&');
	str = str.replace(/\&gt;/g, '>');
	str = str.replace(/\&lt;/g, '<');
	str = str.replace(/\&quot;/g, '"');
	str = str.replace(/\&\#39;/g, "'");
	str = str.replace(/\&\#33;/g, "!");
	return str;
}
function ge(id) {
	return document.getElementById(id);
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
			delete this.callbacks[callbackId];
	}
};

var Playlist = {

	itemIds: [],
	itemsInfo: {},
	cur: -1,
	globalId: null,
	globalPaused: true,
	volume: 0,
	numTabs: 0,
	listInvolved: true,

	load: function(playlist) {

 		Playlist.itemIds = playlist.itemIds;
 		Playlist.itemsInfo = playlist.itemsInfo;
 		Playlist.cur = playlist.cur;
 		Playlist.globalId = playlist.globalId;
 		Playlist.globalPaused = playlist.globalPaused;
 		Playlist.volume = playlist.volume;
 		Playlist.numTabs = playlist.numTabs;
 		Playlist.listInvolved = playlist.listInvolved;

 		Playlist.render();
	},

	render: function() {

		document.getElementById('btn-play').className = this.globalPaused ? '' : 'playing';

		var box = document.getElementById('elements');
		box.innerHTML = '';

		this.updateVolume(this.volume);
		this.setListInvolved(this.listInvolved);
		document.getElementById('num-tabs').innerHTML = this.numTabs;

		if (this.itemIds.length) {
			this.itemIds.forEach(function(id, key) {

				var itemInfo = Playlist.itemsInfo[id];
				var playing = !Playlist.globalPaused && id == Playlist.globalId;
				var rowHtml = createNode('li', {className: 'audio', id: key + '-item'});
				var delCallback = function(){ Playlist.del(id); return false; };
				var playCallback = function(){ playing ? Playlist.stop() : Playlist.play(id); return false };
				var delBox = createNode('div', {className: 'delete-box'});
				delBox.appendChild(createNode('a', {className: 'delete ', onclick: delCallback, href: '#'}))
				
				rowHtml.appendChild(delBox);
				rowHtml.appendChild(createNode('div', {className: 'duration'}, decodeStr(itemInfo[4])));
				rowHtml.appendChild(createNode('div', {className: 'move '}));
				rowHtml.appendChild(createNode('a', {className: 'play ' + (playing ? 'playing' : ''), onclick: playCallback, href: '#'}));
				rowHtml.appendChild(createNode('span', {className: 'artist'}, decodeStr(itemInfo[5])));
				rowHtml.appendChild(createNode('span', {className: 'title'}, ' – ' + decodeStr(itemInfo[6])));

				box.appendChild(rowHtml);
			});
			$(box).sortable({axis: 'y', handle: '.move', update: function(event, ui){
				var order = $(box).sortable("toArray");
				for (var i in order) order[i] = parseInt(order[i], 10);
				opera.extension.bgProcess.Playlist.setOrder(order);
			}});
		} else {
			box.appendChild(createNode('li', {className: 'empty-set'}, 'Плейлист пуст.'))
		}
	},

	play: function(id) {
		opera.extension.bgProcess.Playlist.play(id);
	},

	stop: function() {
		opera.extension.bgProcess.Playlist.stop();
	},

	playNext: function() {
		var bg = opera.extension.bgProcess
		var id = bg.Playlist.getNext();
		bg.Request.sendTab('vp-play-next', id);
	},

	playPrev: function() {
		var bg = opera.extension.bgProcess
		var id = bg.Playlist.getPrev();
		bg.Request.sendTab('vp-play-prev', id);
	},

	del: function(id) {
		opera.extension.bgProcess.Playlist.del(id);
		Playlist.load(opera.extension.bgProcess.Playlist.getData());
	},

	clearAll: function() {
		if (!Playlist.itemIds.length)
			return;
		if (!confirm('Уверены?'))
			return;
		Request.send('popup-clear-all', null, function(response) {
			if (response == 'ok') {
				Playlist.itemIds = [];
				Playlist.cur = -1;
				Playlist.render();
			} else {
				alert('clearing error. ' + response);
			}
		});
	},

	playToggle: function() {

		if (this.globalPaused) {
			if (this.globalId)
				this.play(this.globalId);
		} else {
			this.stop();
		}
	},

	setVolume: function(vol) {
		this.volume = vol;
		opera.extension.bgProcess.Playlist.setVolume(vol, true);
	},

	updateVolume: function(vol) {
		this.volume = vol;
		document.getElementById('volume').value = vol;
	},

	/**
	 * Задать вовлеченность плейлиста в общий порядок вопроизведения
	 * @param {bool} val      участвовать или нет
	 * @param {bool} fromHtml изменения пришли из html попапа, или из background-процесса
	 */
	setListInvolved: function(val, fromHtml) {
		this.listInvolved = val ? true : false;
		if (fromHtml)
			opera.extension.bgProcess.Playlist.listInvolved = this.listInvolved;
		else
			ge('list-involved-btn').checked = this.listInvolved;
	},

	setNumTabs: function(num) {
		this.numTabs = num;
		document.getElementById('num-tabs').innerHTML = num;
	},

	repeatToggle: function(link) {
		
		var title = opera.extension.bgProcess.Playlist.repeatToggle();
		document.getElementById('repeat-btn').innerHTML = title;
	},

	updateRepeat: function(repeat) {
		
		document.getElementById('repeat-btn').innerHTML = repeat;
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
	 		case 'popup-num-tabs':
	 			Playlist.setNumTabs(message.data);
	 			break;
	 		case 'popup-set-volume':
	 			Playlist.updateVolume(message.data);
	 			break;
	 		case 'popup-update-repeat':
	 			Playlist.updateRepeat(message.data);
	 			break;
	 	}
	};

	$('#progress-bar').slider();
	
 	Request.send('popup-load-playlist', null, Playlist.load);

});
