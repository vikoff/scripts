
/**
 * трассировка
 * @param string|Dom-object text - текст или узел дом для отображения
 * @param null|object|[true|1] - параметры отображения.
 * 		[1|true] - синоним для {clear: true}
 *		Допустимые ключи объекта: clear, fix, id, boxCss, contentCss
 */
function trace(text,o){
	o=o==1?{clear:1}:o?o:{};
	var id=o.id||'vik-trace';
	var d=document.getElementById(id)||(function(){
		var d=document.createElement("DIV");
		d.id=id;
		d.style.cssText='position:fixed;font-size:11px;line-height:14px;white-space:pre-wrap;z-index:1000;font-family:monospace;top:3px;right:0px;border:solid 5px #B7BEC4;background-color:#E7ECF0;padding:5px';
		if (o.boxCss) { for (var i in o.boxCss) { d.style[i] = o.boxCss[i]; } }
		document.body.insertBefore(d,document.body.firstChild);
		var x=document.createElement("div");
		x.style.cssText="position:absolute;top:-8px;right:-4px;color:white;font-size:9px;cursor:pointer;";
		x.innerHTML='x';
		x.onclick=function(){d.parentNode.removeChild(d);return false;};
		d.appendChild(x);
		c=document.createElement("div");
		c.style.cssText='overflow:auto;padding-right:20px;padding-bottom:20px;position:relative;-moz-tab-size:4;-o-tab-size:4;';
		c.style.maxHeight=((window.innerHeight||window.outerHeight||700)-40)+'px';
		if (o.contentCss) { for (var i in o.contentCss) { c.style[i] = o.contentCss[i]; } }
		d.appendChild(c);
		return d;
	})();
	d.style.display='block';
	if(o.fix === true || o.fix === 1) d.style.position="fixed";
	if(o.fix === false || o.fix === 0) d.style.position="absolute";
	var c=d.lastChild;
	if(o.clear) c.innerHTML='';
	var t=document.createElement('div');
	if(text === null){
		t.appendChild(document.createTextNode('null'));
	}else if(typeof text=='object' && text.nodeName){
		t.appendChild(text);
	}else{
		t.appendChild(document.createTextNode(text));
	}
	c.appendChild(t);
	c.scrollTop=t.offsetTop;
}

function trace2(text,o){
	o=o==1?{clear:1}:o?o:{};
	o.id='vik-trace-left';
	o.boxCss={right: 'auto', left: '0px'}
	o.contentCss={maxHeight: ((window.innerHeight||window.outerHeight||700)-40) / 2 +'px'}
	trace(text, o);
}

function trace3(text,o){
	o=o==1?{clear:1}:o?o:{};
	o.id='vik-trace-bottom';
	o.boxCss={bottom: '23px'}
	trace(text, o);
}

function trace4(text,o){
	o=o==1?{clear:1}:o?o:{};
	o.id='vik-trace-bottom';
	o.boxCss={fontSize: '10px', top: 'auto', bottom: '3px', left: '0px', right: 'auto'}
	o.contentCss={maxHeight: ((window.innerHeight||window.outerHeight||700)-40) / 2 +'px'}
	trace(text, o);
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

function debug_print_backtrace(){
	
	try{
		throw new Error();
	}catch(e){
		var traceArr = [];
		if(e.stacktrace){
			traceArr = e.stacktrace.split('\n');
			traceArr.shift();
			traceArr.shift();
		}
		else if(e.stack){
			traceArr = e.stack.split('\n');
			traceArr.shift();
		}
		console.log(e);
		var trace = traceArr.join('\n');
		trace = trace.replace(/</g, '&lt;');
		trace = trace.replace(/>/g, '&gt;');
		VikDebug.print(trace, 'stack-trace');
	}
}

/**
 * dump mixed variable
 * @param mixed obj - variable for dump
 * @param object|string options - options.
 * 		if string, lower case means true, upper case means false
 * 		{depth: int}         "d=int"   max depth to dump obj     (default 5)
 * 		{append: true|false} "a|A"     append output, or replace (default false)
 * 		{types: true|false}  "t|T"     print var types           (default true)
 *		{steps: true|false}  "s|S"     open subobjects by steps  (default true)
 * 		{expand: 'a,b,c'}    "e=a,b,c" expand specific objects   (default '')
 * 			allowed items: *, window, document, tags, dom, jquery, function
 * 		{collapse: 'a,b,c/level'}  "E=a,b,c/level" collapse specific objects   (default '')
 * 			allowed items: *, function, array
 * 			level (optional) - depth, from witch collapsing starts ( begins from 1 )
 * 		{output: 'trace|console|return|returnDOM'} "o=[t|c|r|rd]" - output type (default 'trace')
 * @return string|objectDOM dump of variable
 */
function var_dump(obj, params){
	
	var vd = var_dump;
	
	var o = (function(o){
		optObj = {
			depth: 2,	     //d=2
			append: false,   // a|A
			types: true,     // t|T
			highlight: true, // h|H
			steps: true,     // s|S
			expand: [],      // e='a,b,c'
			collapse: [],    // E='a,b,c'
			output: 'trace', // o=[t|c|r|rd]
			consoleTab: 'var_dump',
			_lvl: 0,
			_parent: null
		};
		if(o === 1 || o === true){
			optObj['output'] = 'return';
			return optObj;
		}
		if(typeof o == 'object'){
			for(var i in o)
				optObj[i] = o[i];
			
			o['expand'] = o['expand']?typeof(o['expand'])=='string'?o['expand'].split(','):o['expand']:[];
			for(var i = 0, l = o['expand'].length; i < l; i++)
				o['expand'][o['expand'][i].toLowerCase()] = 1;
			return optObj;
		}
		if(typeof o == 'string' && o.length){
			var arr = o.split(/\s+/);
			var pair;
			for(var i = 0; i < arr.length; i++){
				pair = arr[i].split(/\s*=\s*/);
				switch(pair[0]){
					case 'd': optObj.depth = parseInt(pair[1]) || 5; break;
					case 'a': optObj.append = true; break;
					case 'A': optObj.append = false; break;
					case 't': optObj.types = true; break;
					case 'T': optObj.types = false; break;
					case 'h': optObj.highlight = true; break;
					case 'H': optObj.highlight = false; break;
					case 's': optObj.steps = true; break;
					case 'S': optObj.steps = false; break;
					case 'e':
						var items = (pair[1] || '').replace(/\*/g, 'all').split(',');
						for(var j in items)
							optObj.expand[items[j]] = 1;
						break;
					case 'E':
						var items = (pair[1] || '').replace(/\*/g, 'all').split(',');
						for(var j in items){
							subparams = items[j].split('/');
							optObj.collapse[subparams[0]] = subparams[1] || 1;
						}
						break;
					case 'o':
						var subpair = (pair[1] || '').split('/');
						optObj.output = {t: 'trace', c: 'console', r: 'return', rd: 'returnDOM', 'console': 'console', 'return': 'return'}[subpair[0]] || subpair[0];
						if(subpair[1])
							optObj.consoleTab = subpair[1];
						break;
				}
			}
		}
		return optObj;
	})(params);
	
	vd.getOpts = function(){
		return o;
	}
	vd.extend = vd.extend || function(){
		var output = {};
		for(var i in arguments)
			for(var j in arguments[i])
				output[j] = arguments[i][j];
		return output;
	};
	vd.node = vd.node || function(tag, attrs, text){
		var node = document.createElement(tag);
		var k;
		for(var i in attrs || {}){
			if(i=='style')
				for(var j in attrs[i])
					node.style[j] = attrs[i][j];
			else
				node[i] = attrs[i];
		}
		if (text)
			node.appendChild(document.createTextNode(text));
		return node;
	};
	vd.textNode = vd.textNode || function(text){
		return document.createTextNode(text);
	};
	vd.strType = vd.strType || function(type){
		return vd.getOpts().types
			? vd.node('span', {style: {color: '#C44', fontStyle: 'italic', fontSize: '10px'}}, type + ' ')
			: vd.node('span');
	};
	vd.simpleRow = vd.simpleRow || function(type, text){
		var n = vd.node('span');
		n.appendChild(vd.strType(type));
		n.appendChild(vd.textNode(text));
		return n;
	}
	vd.isArray = vd.isArray || function(obj){
		return Object.prototype.toString.call(obj) == '[object Array]';
	};
	vd.htmlspecialchars = vd.htmlspecialchars || function (str){
		str = str.replace(/</gi, '&lt;');
		str = str.replace(/>/gi, '&gt;');
		return str;
	};
	vd.detectSpecObj = vd.detectSpecObj || function (obj, expand, specObj){
		var specObj = {type: null, text: 0};
		var isObj = typeof obj == 'object';
		if(obj === null){
			specObj.type = 'null';
			specObj.text = 'null';
		}else if(obj === window){
			specObj.type = 'DOM window';
			specObj.text = expand.all || expand.dom || expand.window ? null : '( window )';
		}else if(obj === document){
			specObj.type = 'DOM document';
			specObj.text = expand.all || expand.dom || expand.document ? null : '( document )';
		}else if(isObj && obj.hasOwnProperty && obj.hasOwnProperty('nodeName') && obj.hasOwnProperty('innerHTML')){
			specObj.type = 'DOM tag';
			specObj.text = expand.all || expand.dom || expand.tags ? null : obj.toString();
		}else if(isObj && obj.jquery){
			specObj.type = 'jquery';
			specObj.text = expand.all || expand.jquery ? null : '$(' + (obj.selector ? '"'+obj.selector+'"' : obj[0] ? '"' + obj[0].toString() + '"' : 'null') + ') [length: ' + obj.length + ']';
		}else if(typeof obj === 'function'){
			specObj.type = 'function';
			specObj.text = expand.all || expand['function'] ? null : '()';
		}
		return specObj;
	}
	
	var parentNode = o._parent ? o._parent.parent : vd.node('div');
	var node = o._parent ? o._parent.node : vd.node('div');
	parentNode.appendChild(node);
	
	var specObj = vd.detectSpecObj(obj, o['expand']);
	if (specObj.text) {
		node.appendChild(vd.simpleRow(specObj.type, specObj.text));
	} else {
		switch(typeof(obj)){
		case 'string': node.appendChild(vd.simpleRow('string', '"' + obj + '"')); break;
		case 'number': node.appendChild(vd.simpleRow('number', obj || '0')); break;
		case 'boolean': node.appendChild(vd.simpleRow('bool', obj ? 'true' : 'false')); break;
		case 'undefined': node.appendChild(vd.simpleRow('undefined', 'undefined')); break;
		case 'function': node.appendChild(vd.simpleRow('function', obj.toString())); break;
		case 'object':
			var isArr = vd.isArray(obj);
			if(isArr && ( o.collapse['all'] || (o.collapse['array'] && o._lvl + 1 >= o.collapse['array']) ) ){
				node.appendChild(vd.simpleRow('array', '[ ' + obj.join(', ') + ' ]'));
			}else{
				var type = isArr ? 'array' : specObj.type ? specObj.type : 'object';
				var brackets = isArr ? ['[', ']'] : ['{', '}'];
				if(o._lvl >= o.depth){
					var ph = vd.node('span');
					ph.appendChild(vd.strType(type));
					ph.appendChild(vd.node('span', {style: {color: '#2998ED', cursor: 'pointer', fontWeight: 'bold'}, title: 'click to expand', onclick: function(){
						node.removeChild(ph);
						var_dump(obj, vd.extend(o, {depth: (o.depth + 2), _lvl: (o._lvl + 1), _parent: {node: node, parent: parentNode}}));}
					}, '{..}'));
					node.appendChild(ph);
				}else{
					node.appendChild(vd.strType(type));
					node.appendChild(vd.textNode(brackets[0]));
					var subs = [], sub, subsub;
					var subopts = {style: {'borderLeft': 'solid 1px #DDD', 'marginLeft': '16px'}};
					if(o.highlight){
						subopts.onmouseover = function(){this.style.backgroundColor = '#DBE6F2'; this.style.borderLeftColor = '#E57777';}
						subopts.onmouseout = function(){this.style.backgroundColor = 'transparent'; this.style.borderLeftColor = '#DDD';}
					}
					for(var i in obj){
						sub = vd.node('div', subopts);
						subsub = vd.node('div');
						subsub.appendChild(vd.textNode(i + ' => '));
						try{
							if(obj[i] === obj)
								subsub.appendChild(vd.node('span', {style: {color: '#B99'}}, '*RECURSION*'));
							else
								var_dump(obj[i], vd.extend(o, {_lvl: (o._lvl + 1), _parent: {node: subsub, parent: sub}}));
						}catch(e){subsub.appendChild(vd.node('span', {style: {color: '#F55'}}, '*ERROR [' + e + ']*'));}
						subs.push(sub);
					}
					if(subs.length){
						for(var i = 0; i < subs.length; i++)
							parentNode.appendChild(subs[i]);
						var bottom = vd.node('div');
						bottom.appendChild(vd.textNode(brackets[1]));
						parentNode.appendChild(bottom);
					}else{
						node.appendChild(vd.textNode(brackets[1]));
					}
				}
			}
			break;
		default:
			node.appendChild(vd.strType(typeof(obj)));
			if(obj.toString){
				try{
					node.appendChild(vd.node('span', {}, obj.toString()));
				}catch(e){node.appendChild(vd.node('span', {style: {color: '#F55'}}, '*ERROR [' + e + ']*'));}
			}
		}
	}
	
	// если узел вложенный, ничего возвращать не надо
	if(o._parent)
		return null;
	
	switch(o.output){
		case 'returnDOM': return parentNode; break;
		case 'return': return parentNode.innerHTML; break;
		case 'console': VikDebug.print(parentNode, optObj.consoleTab, (o['append'] ? null : 1)); break;
		default: trace(parentNode, (o['append'] ? null : 1));
		// default: trace(parentNode);
	}
}
