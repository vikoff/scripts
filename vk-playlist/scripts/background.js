
/**
 * @TODO
 * - main icon
 * - extension icon
 */

function loadInjectedCSS(event, path) {
	
	var req = new XMLHttpRequest();
	req.open('GET', path, false);
	req.send();
	
	if (!req.responseText) {
		opera.postError('EXTENSION ERROR: Can\'t read ' + path);
		return;
	}
	
	event.source.postMessage({
		callback: event.data.callback,
		data: req.responseText
	});
}

function createButton() {

	var btnProperties = {
		disabled: false,
		title: "vk playlist",
		icon: "icons/button-play.png",
		popup: {
			href: "popup.html",
			width: 400,
			height: 400
		},
		badge: {
			display: 'none',
			backgroundColor: '#597DA3',
			color: '#ffffff',
			textContent: '0'
		}
	};

	window.button = opera.contexts.toolbar.createItem( btnProperties );
	opera.contexts.toolbar.addItem(window.button);
}

function updateButtonBadge() {

	var len = Playlist.itemIds.length;
	window.button.badge.textContent = len;
	window.button.badge.display = len ? 'block' : 'none';
}

function updateButtonPlayStatus() {

	window.button.icon = Playlist.globalPaused ? 'icons/button-pause.png' : 'icons/button-play.png';
}

function print_r(obj, o) {
	o = o || {};
	var maxDepth = o.depth || 3;
	var depth = o._depth || 0
	var indent = o.tabs || 0;
	var exclude = o.exclude || [];
	var expand = o.expand || [];
	print_r.tabify = print_r.tabify || function(num) {
		var tab = new Array( num + 1 ).join( "|---" );
		if (tab.length) tab = tab.substr(0, tab.length - 1) + ' ';
		return tab;
	}
	var isExcluded = function(elm) {
		return exclude.indexOf(elm) > -1;
	}
	var tab = print_r.tabify(indent);
	var tab2 = print_r.tabify(indent + 1);
	var output = '';

	if (obj === null) {
		output += "null\n";
	} else if (typeof obj == 'object') {
		output += (depth == 0 ? tab : '') + obj.toString() + " {\n";
		for (var i in obj) {
			if (isExcluded(obj[i])) {
					output += tab2 + i + ': ' + obj[i].toString() + " [EXCLUDED]\n"
			} else {
				if (typeof obj[i] == 'object' && obj[i] !== null && depth < maxDepth) {
					oCopy = {};
					for (var i in o) oCopy[i] = o[i];
					oCopy['tabs'] = indent + 1;
					oCopy['_depth'] = depth + 1;
					output += tab2 + i + ': ' + print_r(obj[i], oCopy);
				} else if (typeof obj[i] == 'function') {
					output += tab2 + i + ': ' + (expand.indexOf('function') > -1 ? obj[i].toString() : "function()") + "\n";
				} else if (typeof obj[i] == 'string') {
					output += tab2 + i + ': ' + '"' + obj[i] + '"' + "\n"
				} else {
					output += tab2 + i + ': ' + obj[i] + "\n";
				}
			}
		}
		output += tab + "}\n";
	} else {
		output += obj + "\n";
	}
	return output;
}

var Request = {

	_callbackId: 0,
	callbacks: {},

	curTabSource: null,
	tabSources: [],

	popupSource: null,
	popupOrigin: null, // popup url

	send: function(topic, data, callback) {

		var sendData = {topic: topic, data: data || null};

		if (typeof callback == 'function') {
			var callbackId = this.getCallbackId();
			sendData['callback'] = callbackId;
			this.callbacks[callbackId] = callback;
		}

		opera.extension.postMessage(sendData);
	},

	sendTab: function(topic, data) {

		if (this.curTabSource)
			this.curTabSource.postMessage({topic: topic, data: data});
		else
			console.log('Send data to tabs failed! No vk tabs (' + this.tabSources.length + ')');
	},

	sendPopup: function(topic, data) {

		try {
			if (this.popupSource) {
				this.popupSource.postMessage({topic: topic, data: data});
			}
		} catch (e) {
			// console.error('Send data to popup failed! ' + e.message);
		}
	},

	hasTabSource: function(source) {

		for (var i = 0, len = this.tabSources.length; i < len; i++)
			if (this.tabSources[i] === source)
				return true;

		return false;
	},

	addTabSource: function(source, activate) {

		if (activate || !this.tabSources.length) {
			this.curTabSource = source;
		}

		if (!this.hasTabSource(source)) {
			this.tabSources.push(source);
			Request.sendPopup('popup-num-tabs', this.tabSources.length);
			// console.debug('tab source added');
		} else {
			// console.debug('tab source already exists');
		}

	},

	removeSource: function(source) {

		// search from tab sources
		var index = this.tabSources.indexOf(source);
		if (index > -1) {
			this.tabSources.splice(index, 1);
			if (source === this.curTabSource) {
				this.curTabSource = this.tabSources.length
					? this.tabSources[0]
					: null;
			}
			Request.sendPopup('popup-num-tabs', this.tabSources.length);
		}
	},

	setPopupSource: function(source, origin) {
		this.popupSource = source;
		this.popupOrigin = origin;
	},

	getCallbackId: function() {
		return ++this._callbackId;
	},

	callback: function(callbackId, data) {
		this.callbacks[callbackId](data);
		delete this.callbacks[callbackId];
	}
}

var Playlist = {

	itemIds: [],
	itemsInfo: {},
	cur: -1,
	globalId: null,
	globalPaused: true,
	volume: 0,
	repeat: 'no', // no|one|all
	repeatTitles: {'no': 'нет', 'one': 'трек', 'all': 'все'},
	listInvolved: true, // участвовать в списке воспроизводимых аудиозаписей
	
	init: function(volume) {

		this.setVolume(volume);
	},

	add: function(id, info) {

		this.itemIds.push(id);
		this.itemsInfo[id] = info;

		updateButtonBadge();
		Request.sendPopup('popup-push-playlist', this.getData());
	},

	play: function(id) {

		this.cur = this.itemIds.indexOf(id);
		Request.sendTab('vp-play', id);
	},

	stop: function() {

		this.cur = -1;
		Request.sendTab('vp-stop');
	},


	del: function(id) {

		var index = this.itemIds.indexOf(id);
		this.itemIds.splice(index, 1);
		delete this.itemsInfo[id];
		
		if (index == this.cur || !this.itemIds[this.cur])
			this.cur = -1;

		updateButtonBadge();

		Request.sendPopup('popup-push-playlist', this.getData());
		Request.sendTab('vp-del', id);
	},

	clearAll: function() {

		this.itemIds = [];
		this.itemsInfo = {};
		this.cur = -1;
		updateButtonBadge();

		Request.sendTab('vp-clear-all');

		return 'ok';
	},

	getNext: function(onPlayFinish) {
		var id = null;

		if (this.repeat == 'one' && onPlayFinish)
			return this.itemIds[this.cur];

		if (!this.listInvolved)
			return null;

		this.cur++;
		if (this.cur >= this.itemIds.length) {
			if (this.repeat == 'all')
				this.cur = 0;
			else
				return null;
		}
		if (this.cur > -1 && this.repeat != 'one')
			this.cur++;

		if (this.itemIds[this.cur]) {
			id = this.itemIds[this.cur];
		} else if (this.repeat == 'all' && this.itemIds.length) {
			this.cur = 0;
			id = this.itemIds[this.cur];
		} else {
			this.cur = -1;
		}

		return id;
	},

	getPrev: function() {
		var id = null;
		if (this.cur > -1 && this.itemIds.length) {
			this.cur--;
			if (this.itemIds[this.cur]) {
				id = this.itemIds[this.cur];
			} else {
				if (this.repeat == 'all') {
					this.cur = this.itemIds.length - 1;
					id = this.itemIds[this.cur];
				} else {
					this.cur = -1;
				}
			}
		}

		return id;
	},

	getData: function() {

		return {
			itemIds: this.itemIds,
			itemsInfo: this.itemsInfo,
			cur: this.cur,
			globalId: this.globalId,
			globalPaused: this.globalPaused,
			volume: this.volume,
			numTabs: Request.tabSources.length,
			listInvolved: this.listInvolved
		};
	},

	getItemIds: function(){

		return this.itemIds;
	},

	setIsPaused: function(curId, paused) {

		this.globalId = curId;
		this.globalPaused = paused;
		this.cur = this.itemIds.indexOf(curId);

		updateButtonPlayStatus();

		Request.sendPopup('popup-push-playlist', this.getData());
	},

	setOrder: function(order) {

		var newItems = [];
		for (var i = 0, len = order.length; i < len; i++)
			newItems[i] = this.itemIds[ order[i] ];

		this.itemIds = newItems;
		if (this.cur > -1)
			this.cur = order[this.cur];

		Request.sendPopup('popup-push-playlist', this.getData());
	},

	setVolume: function(vol, fromPopup) {

		this.volume = vol;

		if (fromPopup) {
			Request.sendTab('vp-set-volume', this.volume);
		} else {
			Request.sendPopup('popup-set-volume', this.volume);
		}
	},

	repeatToggle: function() {

		var switches = {'no': 'one', 'one': 'all', 'all': 'no'};
		
		this.repeat = switches[this.repeat];
		return this.repeatTitles[this.repeat];
	},

	updateRepeat: function(repeat) {

		this.repeat = repeat;
		Request.sendPopup('popup-update-repeat', this.repeatTitles[this.repeat]);
	}
}

window.addEventListener('load', function() {

	createButton();

	opera.extension.onmessage = function(event) {

		var message = event.data;
		switch (message.topic) {
			case 'vp-load-css':
				loadInjectedCSS(event, 'css/style.css');
				break;
			case 'vp-init':
				Playlist.init(message.data.volume);
				Request.addTabSource(event.source);
				event.source.postMessage({callback: message.callback, data: {itemIds: Playlist.getItemIds()}});
				break;
			case 'vp-add':
				Playlist.add(message.data.id, message.data.info);
				if (Request.curTabSource && Request.curTabSource !== event.source) {
					Request.sendTab('vp-load-audio-info', message.data);
					console.log('add to another tab');
				} else {
					console.log('add to same tab');
				}
				break;
			case 'vp-del':
				Playlist.del(message.data);
				break;
			case 'vp-update-vol':
				Request.addTabSource(event.source, true);
				Playlist.setVolume(message.data);
				break;
			case 'vp-update-repeat':
				Playlist.updateRepeat(message.data);
				break;
			case 'vp-get-next':
				var id = Playlist.getNext(message.data.onPlayFinish);
				Request.sendPopup('popup-push-playlist', Playlist.getData());
				event.source.postMessage({callback: message.callback, data: id});
				break;
			case 'vp-get-prev':
				var id = Playlist.getPrev();
				Request.sendPopup('popup-push-playlist', Playlist.getData());
				event.source.postMessage({callback: message.callback, data: id});
				break;
			case 'vp-set-is-paused':
				Request.addTabSource(event.source, true);
				Playlist.setIsPaused(message.data.curId, message.data.paused);
				break;
			case 'popup-load-playlist':
				Request.setPopupSource(event.source, event.origin);
				event.source.postMessage({callback: message.callback, data: Playlist.getData()});
				break;
			case 'popup-clear-all':
				event.source.postMessage({callback: message.callback, data: Playlist.clearAll()});
				break;
			// case '___':
				// break;
		}
	};
});

opera.extension.onconnect = function(event) {};

opera.extension.ondisconnect = function(event) {

	if (event.origin === Request.popupOrigin) {
		Request.popupSource = null;
		return;
	}

	Request.removeSource(event.source);
};
