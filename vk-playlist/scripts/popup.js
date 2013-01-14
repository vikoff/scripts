
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

function toggleContextMenu(btn){
	var btnPos = $(btn).offset();
	var m = $('.context-menu');
	if (m.is(':visible')) {
		m.hide();
	} else {
		m.show();
		m.css('top', btnPos.top - 2);
	}
}

function _log(msg){
	var box = $('.debug-body');
	box.append('<div>' + msg + '</div>');
	var h = box[0].scrollHeight;
	box.scrollTop(h);
}

function runCode(code) {
}
var Debugger = {
	history: [],
	histOffset: -1,
	
	inpKeyPress: function(inp, e) {
		var resetHistOffset = true;
		if (e.keyCode == 13) { // enter
			this.run(inp.value);
			inp.value='';
		} else if (e.keyCode == 38) { // arrow up
			e.stopPropagation();
			e.preventDefault();
			this.prevCommand(inp);
			resetHistOffset = false;
		} else if (e.keyCode == 40) { // arrow down
			e.stopPropagation();
			e.preventDefault();
			this.nextCommand(inp);
			resetHistOffset = false;
		}
		if (resetHistOffset) {
			this.histOffset = -1;
			alert('reset hist offset');
		}
	},
	
	run: function(code) {
		if (code.length)
			this.history.unshift(code);
		var msg = 'run code: <pre style="color: #555; display: inline;">' + code + '</pre><br />';
		try {
			var result = eval(code);
			msg += 'result: <pre style="color: #555; display: inline;">' + result + '</pre>';
		} catch (e) {
			msg += 'error: <pre style="color: #555; display: inline;">' + e + '</pre>';
		}
		_log(msg);
	},
	
	prevCommand: function(inp) {
		var code = this.history[++this.histOffset] || '';
		inp.value = code;
	},
	
	nextCommand: function(inp) {
		var code = this.history[--this.histOffset] || '';
		inp.value = code;
	}
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
	globalInfo: null,
	volume: 0,
	playingTime: 0,
	numTabs: 0,
	progressLocked: false,
	listInvolved: true,

	load: function(playlist) {

 		Playlist.itemIds = playlist.itemIds;
 		Playlist.itemsInfo = playlist.itemsInfo;
 		Playlist.cur = playlist.cur;
 		Playlist.globalId = playlist.globalId;
		Playlist.globalPaused = playlist.globalPaused;
 		Playlist.globalInfo = playlist.globalInfo;
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

		if (this.globalInfo) {
			// name
			document.getElementById('song-name').innerHTML = "";
			var songName = document.createDocumentFragment();
			songName.appendChild(createNode('span', {className: 'artist'}, decodeStr(this.globalInfo[5])));
			songName.appendChild(createNode('span', {className: 'title'}, ' – ' + decodeStr(this.globalInfo[6])));
			document.getElementById('song-name').appendChild(songName);
			// duration
			this.updateProgress(this.playingTime);
		}

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
	},

	progressBarSlideStart: function(ui) {
		this.progressLocked = true;
	},

	progressBarSlide: function(ui) {
		var percents = ui.value;
		if (this.globalInfo) {
			var time = Math.round(this.globalInfo[3] * percents / 100);
			this.updatePlayTime(time, true);
		}
	},

	progressBarSlideStop: function(ui) {

		var percents = ui.value;
		this.progressLocked = false;

		if (this.globalInfo) {
			this.playingTime = Math.round(this.globalInfo[3] * percents / 100);
			this.updatePlayTime(this.playingTime);
			opera.extension.bgProcess.Playlist.setPlayTime(this.playingTime);
		}
	},

	updateProgress: function(time) {

		if (this.progressLocked)
			return;
		
		this.playingTime = time;
		this.updatePlayTime(this.playingTime);
	},

	updatePlayTime: function(time, noProgressUpdate) {
		
		if (this.globalInfo) {
			document.getElementById('duration-box').innerHTML = ''
				+ this.formatTime(time)
				+ ' / ' + this.globalInfo[4];
			if (!noProgressUpdate) {
				var progress = Math.round(time / this.globalInfo[3] * 100);
				$('#progress-bar').slider( "option", "value", progress);
			}
		} else {
			document.getElementById('duration-box').innerHTML = '-';
			if (!noProgressUpdate) {
				$('#progress-bar').slider( "option", "value", 0 );
			}
		}
	},

	formatTime: function(t) {
		var res, sec, min, hour;
		sec = t % 60;
		res = (sec < 10) ? '0'+sec : sec;
		t = Math.floor(t / 60);
		min = t % 60;
		res = min+':'+res;
		t = Math.floor(t / 60);
		if (t > 0) res = t+':'+res;
		return res;
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
	 		case 'popup-update-time':
	 			Playlist.updateProgress(message.data);
	 			break;
	 	}
	};

	$('#progress-bar').slider({
		start: function(event, ui){ Playlist.progressBarSlideStart(ui); },
		slide: function(event, ui){ Playlist.progressBarSlide(ui); },
		stop: function(event, ui){ Playlist.progressBarSlideStop(ui); }
	});
	
	$('.context-menu a').click(function(e) {
		e.preventDefault();
		$('.context-menu').hide();
		
		var act = $(this).attr('rel');
		switch (act) {
			case 'debug': $('#debug').toggle(); break;
		}
	});
	
 	Request.send('popup-load-playlist', null, Playlist.load);

});
