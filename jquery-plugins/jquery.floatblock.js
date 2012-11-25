(function($){
	$.fn.floatblock = function(options, param){
		
		if(options == 'stop'){
			this.each(stop);
			return this;
		}
		
		if(options == 'restart'){
			this.each(function(){restart.call(this);});
			return this;
		}
		
		if(options == 'set'){
			this.each(function(){applyOptions.call(this, param);});
			return this;
		}
		
		if(options == 'updatePosition'){
			this.each(function(){checkPosition.call($(this));});
			return this;
		}
		
		var o = $.extend({
			/** расстояние до верха браузера */
			'topSpace': 5,
			/** тип плейсхолдера: клон элемента с visibility: hidden; или пустой div с visibility: hidden. */
			'placeholder': 'div', // div|clone
			/** принудительная установка floatblock (для элементов, к которым уже применен floatblock */
			'force': false
			
		}, options);
		
		this.each(function(){init.call(this, o);});
		return this;
	}
			
	function init(o){
	
		var $this = $(this);
		
		// если floatblock уже применен к элементу
		if($this.data('floatblock-enabled')){
			if(o.force)
				stop.call(this);
			else
				return;
		}
		
		var params = {
			index: $.floatblock._getIncrement(),
			scrollTopSpace: $this.offset().top,
			topSub: parseFloat($this.css('marginTop').replace(/auto/, 0)),
		};
		params.topOrigin = params.scrollTopSpace - params.topSub;
		params.leftOrigin = $this.offset().left -  parseFloat($this.css('marginLeft').replace(/auto/, 0));
		
		var placeholder;
		
		if(o.placeholder == 'div'){
			placeholder = $('<div class="floatblock-placeholder"></div>').css({
				'width': $this.outerWidth(true),
				'height': $this.outerHeight(true),
				'float': $this.css('float'),
				'visibility': 'hidden',
			})
		}else{
			placeholder = $this.clone().css({
				'width': $this.width(),
				'height': $this.height(),
				'visibility': 'hidden'
			})
		}
		
		// присваивание необходимых свойств текущему элементу
		$this
			.data('floatblock', params)
			.data('floatblock-enabled', true)
			.data('floatblock-options', o)
			.data('floatblock-fixed', false)
			.data('floatblock-placeholder', placeholder)
			.data('floatblock-origin-css', {
				'width':    this.style.width || 'auto',
				'height':   this.style.height || 'auto',
				'position': this.style.position,
				'left':     this.style.left,
				'top':      this.style.top,
				'margin':   this.style.margin,
				'padding':  this.style.padding,
				'border':   this.style.border,
			})
			.css({
				'width': $this.width(),
				'height': $this.height(),
				'position': 'absolute',
				'left': params.leftOrigin,
				'top': params.topOrigin,
				'margin': $this.css('margin'),
				'padding': $this.css('padding'),
				'border': $this.css('border'),
			});
		
		// помещение плейсхолдера в DOM
		placeholder.insertBefore($this);
		
		appendOriginToBody.call(this);
		checkPosition.call($this);
		
		$(window).bind('scroll.floatblock-item-' + params.index, function(){checkPosition.call($this);});
			
	}
	
	// перемещение исходного элемента в body
	function appendOriginToBody(){
		
		var $this = $(this);
		var o = $this.data('floatblock-options');
		
		// тег thead
		if(this.tagName.toLowerCase() == 'thead'){
			// зафиксируем ширину всех элементов шапки
			$this.find('tr').children().each(function(){
				$(this).css({
					'width': $(this).width(),
					'height': $(this).height()
				});
			});
			// зафиксируем ширину всех ячеек первой строки таблицы
			$this.next().find('tr:first').children().each(function(){
				$(this).css({
					'width': $(this).width(),
					'height': $(this).height()
				});
			});
			// поместим элемент в пустую таблицу в body
			var wrap = $('<table></table>')
				.attr('id', $this.parent().attr('id'))
				.attr('class', $this.parent().attr('class'))
				.append($this)
				.appendTo('body');
			
			o.topSpace = 0;
		}
		// другие элементы
		else{
			$this.appendTo('body');
		}
		
	}
	
	function checkPosition(){
		
		var params = this.data('floatblock');
		var o = this.data('floatblock-options');
		var fixed = this.data('floatblock-fixed');
		
		var top = params.scrollTopSpace - $(window).scrollTop(); // DEBUG
		if(params.scrollTopSpace - $(window).scrollTop() <= o.topSpace){
			if(!fixed){
				this.css({'position': 'fixed', 'top': o.topSpace - params.topSub});
				this.data('floatblock-fixed', true);
			}
		}
		else{
			if(fixed){
				this.css({'position': 'absolute', 'top': params.topOrigin});
				this.data('floatblock-fixed', false);
			}
		}
	}
			
	function applyOptions(options){
		
		var $this = $(this);
		$this.data('floatblock-options', $.extend($this.data('floatblock-options'), options));
		
		if(options.topSpace && $this.data('floatblock-fixed')){
			$this.css('top', options.topSpace - $this.data('floatblock').topSub);
		}
		
		checkPosition.call($this);
	}
	
	function stop(){
		$this = $(this);
		if($this.data('floatblock-enabled')){
			var placeholder = $this.data('floatblock-placeholder');
			$this.insertAfter(placeholder)
			placeholder.remove();
			$(window).unbind('scroll.floatblock-item-' + $this.data('floatblock').index);
			$this.css($this.data('floatblock-origin-css'));
			$this.data('floatblock-enabled', false);
		}
	}
	
	function restart(){
		
		$this = $(this);
		
		if($this.data('floatblock-enabled')){
			
			var params = $this.data('floatblock');
			var o = $this.data('floatblock-options');
			var placeholder = $this.data('floatblock-placeholder');
			
			$this.insertAfter(placeholder)
			placeholder.css('display', 'none');
			$this.css($this.data('floatblock-origin-css'));
			
			params.scrollTopSpace = $this.offset().top;
			params.topSub =  parseFloat($this.css('marginTop').replace(/auto/, 0));
			params.topOrigin = params.scrollTopSpace - params.topSub;
			params.leftOrigin = $this.offset().left -  parseFloat($this.css('marginLeft').replace(/auto/, 0));
			
			if(o.placeholder == 'div'){
				placeholder.css({
					'width': $this.outerWidth(true),
					'height': $this.outerHeight(true),
					'float': $this.css('float'),
				})
			}else{
				placeholder = $this.clone().css({
					'width': $this.width(),
					'height': $this.height(),
				})
			}
			
			$this
				.data('floatblock-fixed', false)
				.data('floatblock-origin-css', {
					'width':    this.style.width || 'auto',
					'height':   this.style.height || 'auto',
					'position': this.style.position,
					'left':     this.style.left,
					'top':      this.style.top,
					'margin':   this.style.margin,
					'padding':  this.style.padding,
					'border':   this.style.border,
				})
				.css({
					'width': $this.width(),
					'height': $this.height(),
					'position': 'absolute',
					'left': params.leftOrigin,
					'top': params.topOrigin,
					'margin': $this.css('margin'),
					'padding': $this.css('padding'),
					'border': $this.css('border'),
				})
			
			placeholder.css('display', 'block');
			appendOriginToBody.call(this);
			checkPosition.call($this);
		}
	}
	
	$.floatblock = {
		_increment: 0,
		_getIncrement: function(){
			return ++$.floatblock._increment;
		}
	}

})(jQuery);