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

	function onmessage(event) {
		try { 
			var message = typeof event.data == 'string' ? JSON.parse(event.data) : event.data;
		} catch (e) {
			alert('unable to parse data:\n' + event.data);
			return;
		}

	 	// console.log(event, 'a');

	 	if (message.callback) {
	 		Request.callback(message.callback, message.data);
	 		return;
	 	}

	 	switch (message.topic) {
	 		case 'vp-play':
	 			Playlist.play(message.data);
	 			break;
	 		case 'vp-play-next':
	 			Playlist.playNext(message.data);
	 			break;
	 		case 'vp-play-prev':
	 			Playlist.playPrev(message.data);
	 			break;
	 		case 'vp-del':
	 			Playlist.delExternal(message.data);
	 			break;
	 		case 'vp-clear-all':
	 			Playlist.clearAll();
	 			break;
	 		case 'vp-stop':
	 			Playlist.stop();
	 			break;
	 		case 'vp-set-volume':
	 			Playlist.setVolume(message.data);
	 			break;
	 		case 'vp-load-audio-info':
	 			Playlist.loadAudioInfo(message.data.id, message.data.info);
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
			try {
				this.callbacks[callbackId](data);
				delete this.callbacks[callbackId];
			} catch (e) {
				throw new Error('Callback id=' + callbackId + ' not found.');
				console.debug(data);
			}
		}
	}

	var Playlist = {

		itemIds: [],
		paused: true,

		init: function(itemIds) {

			this.itemIds = itemIds;
			
			Playlist.searchAudio();
			setInterval(function(){ Playlist.searchAudio(); }, 2500);
		},

		modifyVkScripts: function() {

			with (window) {

				if (!audioPlayer) {
					console.error('audio player not found');
					return;
				}
				// operate
				audioPlayer.operate_origin = audioPlayer.operate;
				audioPlayer.operate = function() {
					audioPlayer.operate_origin.apply(this, arguments);
					var isPaused = audioPlayer.player ? audioPlayer.player.paused() : true;
					Playlist.setIsPaused(audioPlayer.id, isPaused);
				};

				// onPlayFinish
				audioPlayer.onPlayFinish_origin = audioPlayer.onPlayFinish;
				audioPlayer.onPlayFinish = function() {
					Playlist.getNext(true, function(id) {
						console.log('next id: ' + id);
						if (id) { Playlist.play(id); }
						else { audioPlayer.onPlayFinish_origin.apply(this, arguments); }
					});
				}

				// nextTrack
				audioPlayer.nextTrack_origin = audioPlayer.nextTrack;
				audioPlayer.nextTrack = function(){
					Playlist.getNext(false, function(id) {
						console.log('next id: ' + id);
						if (id) { Playlist.play(id); }
						else { audioPlayer.nextTrack_origin.apply(this, arguments); }
					});
				};
				audioPlayer.nextTrack.bind = audioPlayer.nextTrack_origin.bind;
				audioPlayer.nextTrack.pbind = audioPlayer.nextTrack_origin.pbind;

				// prevTrack
				audioPlayer.prevTrack_origin = audioPlayer.prevTrack;
				audioPlayer.prevTrack = function(){
					Playlist.getPrev(function(id) {
						if (id) { Playlist.play(id); }
						else { audioPlayer.prevTrack_origin.apply(this, arguments); }
					});
				};
				audioPlayer.prevTrack.bind = audioPlayer.prevTrack_origin.bind;
				audioPlayer.prevTrack.pbind = audioPlayer.prevTrack_origin.pbind;

				// volClick
				audioPlayer.volClick_origin = audioPlayer.volClick;
				audioPlayer.volClick = function(){
					audioPlayer.volClick_origin.apply(this, arguments);
					Playlist.updateVolume(getCookie('audio_vol'));
				}

				// toggleRepeat
				audioPlayer.toggleRepeat_origin = audioPlayer.toggleRepeat;
				audioPlayer.toggleRepeat = function() {
					audioPlayer.toggleRepeat_origin.apply(this, arguments);
					Playlist.updateRepeat(audioPlayer.repeat ? 'one' : 'no');
				}
				audioPlayer.toggleRepeat.bind = audioPlayer.toggleRepeat_origin.bind;
				audioPlayer.toggleRepeat.pbind = audioPlayer.toggleRepeat_origin.pbind;
			}
		},

		searchAudio: function() {

			var knownDiv;
			var boxes = document.getElementsByClassName('audio');
			for (var i = 0, len = boxes.length; i < len; i++) {

				if (hasClass(boxes[i], 'vikoff-playlist-box')) continue;
				else addClass(boxes[i], 'vikoff-playlist-box');

				knownDiv = boxes[i].getElementsByClassName('play_new')[0];
				if (!knownDiv) continue;

				(function(rawId, durationBox, infoBox){

					var clearId = Playlist.getClearId(rawId);

					var btnBox = document.createElement('div');
					btnBox.className = 'vikoff-playlist-item-box';
					
					var btn = document.createElement('a');
					btn.className = 'vik-off-' + clearId + ' vikoff-playlist-item' + (Playlist.has(clearId) ? ' vikoff-added' : '');
					btn.onclick = function(e) {
						e = e || window.event;
						e.stopPropagation ? e.stopPropagation() : (e.cancelBubble=true);
						if (hasClass(this, 'vikoff-added')) {
							removeClass(this, 'vikoff-added');
							Playlist.del(clearId);
						} else {
							addClass(this, 'vikoff-added');
							Playlist.add(clearId);
						}
					};

					btnBox.appendChild(btn);
					infoBox.insertBefore(btnBox, infoBox.firstChild);

					return; // DEBUG

					var btn = document.createElement('a');
					btn.className = 'vik-off-' + clearId + ' vikoff-playlist-item' + (Playlist.has(clearId) ? ' vikoff-added' : '');
					btn.onclick = function() {
						if (hasClass(this, 'vikoff-added')) {
							removeClass(this, 'vikoff-added');
							Playlist.del(clearId);
						} else {
							addClass(this, 'vikoff-added');
							Playlist.add(clearId);
						}
					};

					var dur = document.createElement('span');
					dur.className = 'vikoff-duration';
					dur.innerHTML = durationBox.innerHTML;

					durationBox.innerHTML = "";
					durationBox.appendChild(dur);
					durationBox.appendChild(btn);

				})(knownDiv.id, boxes[i].getElementsByClassName('duration')[0], boxes[i].getElementsByClassName('info')[0]);
			}

		},

		add: function(audioId) {

			Request.send('vp-add', {
				id: audioId,
				info: this.getAudioInfo(audioId),
			});

		},

		del: function(id) {

			Request.send('vp-del', id);
			var index = this.itemIds.indexOf(id);
			if (index > -1)
				this.itemIds.splice(index, 1);
		},

		delExternal: function(id) {

			Array.prototype.slice.call(document.getElementsByClassName('vik-off-' + id), 0).forEach(function(elm){
				removeClass(elm, 'vikoff-added');
			});
		},

		clearAll: function() {

			Array.prototype.slice.call(document.getElementsByClassName('vikoff-added'), 0).forEach(function(elm){
				removeClass(elm, 'vikoff-added');
			});
		},

		has: function(id) {

			return this.itemIds.indexOf(id) > -1;
		},

		getAudioInfo: function(audioId) {

			var audioInfo;

			if (window.audioPlaylist && window.audioPlaylist[audioId]) {
				audioInfo = window.audioPlaylist[audioId];
			} else if (window.audioPlayer.songInfos[audioId]) {
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
				if (window.audioPlaylist)
					window.audioPlaylist[audioId] = audioInfo;
				else
					window.audioPlayer.songInfos[audioId] = audioInfo;
			}

			return audioInfo;
		},

		loadAudioInfo: function(audioId, audioInfo) {
			if (window.audioPlaylist)
				window.audioPlaylist[audioId] = audioInfo;
			else
				window.audioPlayer.songInfos[audioId] = audioInfo;
		},

		getNext: function(onPlayFinish, callback) {
			alert(onPlayFinish);
			Request.send('vp-get-next', {onPlayFinish: onPlayFinish}, function(next){ callback(next); });
		},

		getPrev: function(callback) {

			Request.send('vp-get-prev', null, function(prev){ callback(prev); });
		},

		play: function(id) {

			if (id !== window.audioPlayer.id || this.paused) {
				window.audioPlayer.operate(id);
			}
		},

		stop: function(){
			window.audioPlayer.pauseTrack();
			Playlist.setIsPaused(window.audioPlayer.id, true);
		},

		playNext: function(id) {

			if (id) this.play(id);
			else window.audioPlayer.nextTrack_origin();
		},

		playPrev: function(id) {
			
			if (id) this.play(id);
			else window.audioPlayer.prevTrack_origin();
		},

		setIsPaused: function(curId, paused) {
			
			this.paused = paused;
			Request.send('vp-set-is-paused', {curId: curId, paused: this.paused});
		},

		setVolume: function(vol) {

			with (window) {
				// css
				if (ge('ac_vol_line'))
					ge('ac_vol_line').style.width = Math.round(vol) + '%';

				// player
				if (window.audioPlayer.player)
					window.audioPlayer.player.setVolume(vol / 100);
			}
		},

		updateVolume: function(vol) {
			Request.send('vp-update-vol', vol)
		},

		updateRepeat: function(repeat) {
			Request.send('vp-update-repeat', repeat)
		},

		getClearId: function(rawId) {

			var matches = /^play(.+)$/.exec(rawId);
			if (!matches) {
				console.error('unknown audio id format: ' + rawId);
				alert('unknown audio id format: ' + rawId);
				return;
			}
			return matches[1];
		},

		log: function(msg){
			console.log(msg);
		}
	};

	window.addEventListener('load', function() {

		Request.send('vp-load-css', null, function(css) {
			var style = document.createElement('style');
			style.setAttribute('type', 'text/css');
			style.appendChild(document.createTextNode(css));
			document.getElementsByTagName('head')[0].appendChild(style);
		});

		var volume = window.getCookie('audio_vol');
		if (!volume && window.audioPlayer.player)
			volume = Math.round(window.audioPlayer.player.getVolume() * 100);
		
		Request.send('vp-init', {volume: volume}, function(data){
 			Playlist.init(data.itemIds);
		});

		opera.extension.addEventListener('message', onmessage);

		if (window.audioPlayer)
			Playlist.modifyVkScripts();
		else if (window.stManager)
			stManager.add(['audioplayer.js', 'audioplayer.css'], Playlist.modifyVkScripts);

	});

})();
