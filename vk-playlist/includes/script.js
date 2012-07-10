// ==UserScript==
// @include http://vkontakte.ru/*
// @include http://vk.com/*
// ==/UserScript==

(function(){
	
	function hasClass(ele,cls) {
		return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
	}

	function addClass(ele,cls) {
		if (!hasClass(ele,cls)) ele.className += " "+cls;
	}

	function removeClass(ele,cls) {
		if (hasClass(ele,cls)) {
	    	var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
			ele.className=ele.className.replace(reg,' ');
		}

	}

	function modifyVkScripts() {

		var pl = window.VikOffPlaylist;

		if (!window.audioPlayer) {
			console.error('audio player not found');
			return;
		}

		window.audioPlayer.onPlayFinish_origin = window.audioPlayer.onPlayFinish;

		window.audioPlayer.onPlayFinish = function() {

			pl.getNext(function(id) {
				if (id) {
					console.log('VikOffPlaylist play next');
					pl.play(id);
				} else {
					console.log('origin play next');
					window.audioPlayer.onPlayFinish_origin();
				}
			});
		}
	}

	function onmessage(event) {

		try { 
			var message = typeof event.data == 'string' ? JSON.parse(event.data) : event.data;
		} catch (e) {
			alert('unable to parse data:\n' + event.data);
			return;
		}

	 	// var_dump(event, 'a');

	 	if (message.callback) {
	 		Request.callback(message.callback, message.data);
	 		return;
	 	}

	 	switch (message.topic) {
	 		case 'loadedCss':
	 			break;
	 		case 'vp-play':
	 			alert('play!');
	 			window.VikOffPlaylist.play(message.data);
	 			break;
	 		case 'debug':
				console.debug('vikoffPlaylist debug: ');
				console.debug(message.data);
	 			break;
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
		}
	}

	window.VikOffPlaylist = {

		items: [],
		cur: -1,

		searchAudio: function() {

			var knownDiv;
			var boxes = document.getElementsByClassName('audio');
			for (var i = 0, len = boxes.length; i < len; i++) {

				if (hasClass(boxes[i], 'vikoff-playlist')) continue;
				else addClass(boxes[i], 'vikoff-playlist');

				knownDiv = boxes[i].getElementsByClassName('play_new')[0] || null;
				if (knownDiv) { (function(box, id){

					var matches = /^play(.+)$/.exec(id);
					if (!matches) {
						console.error('unknown audio id format: ' + id);
						alert('unknown audio id format: ' + id);
						return;
					}
					var clearId = matches[1];

					var playBtn = box.children[0];
					var extBtn = document.createElement('div');
					extBtn.className = 'vk-simple-playlist';
					extBtn.innerHTML = 'p';
					extBtn.onclick = function(){
						window.VikOffPlaylist.add(clearId);
					};
					
					if (box.children.length)
						box.insertBefore(extBtn, box.children[0]);
					else
						box.appendChild(extBtn);

					// console.log('vik-off playlist added id: ' + id);

				})(knownDiv.parentNode.parentNode, knownDiv.id); }
			}

		},

		add: function(audioId) {

			Request.send('vp-add', {
				id: audioId,
				info: this.getAudioInfo(audioId),
			}, function(response) { 
				if (response == 'ok') {
					console.info('audio ' + audioId + ' added');
				} else {
					console.error(response);
					alert('audio add error: ' + response);
				}
			})

			// this.log(item);
		},

		getAudioInfo: function(audioId) {

			var audioInfo;

			if (window.audioPlaylist && window.audioPlaylist[audioId]) {
				audioInfo = window.audioPlaylist[audioId];
			} else if (window.audioPlayer && window.audioPlayer.songInfos[audioId]) {
				audioInfo = window.audioPlayer.songInfos[audioId];
			} else {
				var art, title, nfo = window.geByClass1('info', window.ge('audio'+audioId));
				art = window.geByTag1('b', nfo);
				l = window.geByTag1('a', art);
				if (l) art = l;
				var reArr = ['<span>', '</span>', '<span class="match">'];
				var re = new RegExp(reArr.join('|'), "gi");
				art = art.innerHTML.replace(re, '');
				title = window.geByClass1('title', nfo);
				if (!title) title = window.ge('title'+audioId);
				l = window.geByTag1('a', title);
				if (l) title = l.innerHTML;
				else title = title.innerHTML;
				title = title.replace(re, '');
				dur = window.geByClass1('duration', nfo).innerHTML;
				var data=window.ge('audio_info'+audioId).value.split(',');
				var url=data[0];
				var duration=parseInt(data[1]);
				data = audioId.split('_');
				var uid = data[0];
				var aid = data[1];
				audioInfo = {0: uid, 1:aid, 2:url, 3:duration, 4:dur, 5: art, 6:title};
			}

			return audioInfo;
		},

		getNext: function(callback) {

			Request.send('vp-get-next', null, function(next){ callback(next); });
		},

		play: function(id) {

			if (id === window.audioPlayer.id) {
				this.log(id +' already playing');
			} else {
				this.log('play next audio ' + id);
				window.audioPlayer.operate(id);
			}
		},

		log: function(msg){
			console.log(msg);
		}
	};

	window.addEventListener('load', function() {

		Request.send('loadCss', null, function(css){
			var style = document.createElement('style');
			style.setAttribute('type', 'text/css');
			style.appendChild(document.createTextNode(css));
			document.getElementsByTagName('head')[0].appendChild(style);
		});

		opera.extension.addEventListener('message', onmessage);

		window.VikOffPlaylist.searchAudio();
		setInterval(function(){ window.VikOffPlaylist.searchAudio(); }, 3000);

		if (window.audioPlayer)
			modifyVkScripts();
		else if (window.stManager)
			window.stManager.add(['new_player.js', 'new_player.css'], modifyVkScripts);

	});

})();
