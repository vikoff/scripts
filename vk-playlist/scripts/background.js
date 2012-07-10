
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

function createButton(){

	var btnProperties = {
		disabled: false,
		title: "vk playlist",
		icon: "icons/hello-button.png",
		popup: {
			href: "popup.html",
			width: 500,
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

var Playlist = {

	items: [],
	cur: -1,
	
	add: function(item) {

		this.items.push(item);

		window.button.badge.display = 'block';
		window.button.badge.textContent = this.items.length;

		opera.extension.broadcastMessage(JSON.stringify({
			topic: 'popup-push-playlist',
			data: this.getData()
		}));
	},

	play: function(index) {

		// if (!this.items[index])
			// return;

		this.cur = index;
		opera.extension.broadcastMessage(JSON.stringify({
			topic: 'vp-play',
			data: this.items[index].id
		}));
	},

	stop: function() {

	},

	getNext: function() {

		this.cur++;
		return this.items[this.cur] ? this.items[this.cur].id : null;
	},

	getData: function() {

		return {
			items: this.items,
			cur: this.cur
		};
	},

	clearAll: function() {

		this.items = [];
		this.cur = -1;
		return 'ok';
	}
}

window.addEventListener('load', function() {

	createButton();

	opera.extension.onmessage = function(event) {

		var message = event.data;
		switch (message.topic) {
			case 'loadCss':
				loadInjectedCSS(event, 'includes/style.css');
				break;
			case 'vp-add':
				Playlist.add(message.data);
				event.source.postMessage({callback: message.callback, data: 'ok'});
				break;
			case 'vp-get-next':
				event.source.postMessage({callback: message.callback, data: Playlist.getNext()});
				break;
			case 'vp-has-next':
				event.source.postMessage({callback: message.callback, data: Playlist.hasNext()});
				break;
			case 'popup-load-playlist':
				event.source.postMessage({callback: message.callback, data: Playlist.getData()});
				break;
			case 'clear-all':
				event.source.postMessage({callback: message.callback, data: Playlist.clearAll()});
				break;
			// case '___':
				// break;
		}
	};

	// opera.extension.onconnect = function(event) {
		// event.source.postMessage({topic: 'debug', data: 'playlist length: ' + Playlist.items.length});
		// event.source.postMessage({topic: 'debug', data: JSON.stringify(event)});
	// }

});
