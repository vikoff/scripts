/**
 * Plugin for little checkbox extending
 * 
 * version 1.0
 * 
 * When clicking on the checkbox plugin adds all the labels associated css-class (default is "checked"),
 * or clear, depending on the state flag.
 * 
 * <description lng="rus">
 * При клике по checkbox плагин добавляет всем связанным лейблам css-класс (по умолчанию "checked"),
 * или убирает, в зависимости от состояния флажка.
 * </description>
 * 
 * Author: Yuriy Novikov
 * Date: 19 sep 2011
 */

(function($){
	
	$.fn.simpleCheckbox = function(o){
		
		o = $.extend({
			'hide': false,
			'class': 'checked',
		}, o);
		
		this.each(function(){
		
			if(!this.labels.length)
				return;
			
			var t = $(this);
			if(o.hide)
				t.css('display', 'none');
				
			var labels = $([]);
			for(var i = 0; i < this.labels.length; i++)
				labels = labels.add(this.labels[i]);
			
			t.data('simpleCheckbox', {labels: labels, class: o.class});
			
			t.change(updateLabels);
			updateLabels.call(this);
		});
		
		function updateLabels(){
			var t = $(this);
			var d = t.data('simpleCheckbox');
			var method = t.attr('checked') ? 'addClass' : 'removeClass';
			d.labels[method](d.class);
		}
		
		return this;
	};
})(jQuery);
