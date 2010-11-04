/**
 * @author Ryan Johnson <ryan@livepipe.net>
 * @copyright 2007 LivePipe LLC
 * @package Control.TextArea
 * @license MIT
 * @url http://livepipe.net/projects/control_textarea/
 * @version 1.0.1
 */

if(typeof(Object.Event) == 'undefined'){
	Object.Event = {
		eventHandlers: {},
		observe: function(event_name,observer){
			if(!this.eventHandlers[event_name])
				this.eventHandlers[event_name] = $A([]);
			this.eventHandlers[event_name].push(observer);
		},
		stopObserving: function(event_name,observer){
			this.eventHandlers[event_name] = this.eventHandlers[event_name].without(observer);
		},
		fireEvent: function(event_name){
			if(this.eventHandlers[event_name]){
				this.eventHandlers[event_name].each(function(observer){
					observer(this);
				}.bind(this));
			}
		}
	};
	Object.Event.createEvent = Object.Event.fireEvent;	
}

if(typeof(Control) == "undefined")
	Control = {};
Control.TextArea = Class.create();
Object.extend(Control.TextArea.prototype,Object.Event);
Object.extend(Control.TextArea.prototype,{
	onChangeTimeoutLength: 500,
	textarea: false,
	onChangeTimeout: false,
	initialize: function(textarea){
		this.textarea = $(textarea);
		Event.observe($(this.textarea), 'keyup',this.doOnChange.bindAsEventListener(this));
		Event.observe($(this.textarea), 'paste',this.doOnChange.bindAsEventListener(this));
		Event.observe($(this.textarea), 'input',this.doOnChange.bindAsEventListener(this));
	},
	doOnChange: function(event){
		if(this.onChangeTimeout)
			window.clearTimeout(this.onChangeTimeout);
		this.onChangeTimeout = window.setTimeout(function(){
			this.createEvent('change');
		}.bind(this),this.onChangeTimeoutLength);
	},
	getValue: function(){
		return this.textarea.value;
	},
	getSelection: function(){
		if(typeof(document.selection) != 'undefined')
			return this.textarea.createTextRange().text;
		else if(typeof(this.textarea.setSelectionRange) != 'undefined')
			return this.textarea.value.substring(this.textarea.selectionStart,this.textarea.selectionEnd);
		else
			return false;
	},
	replaceSelection: function(text){
		if(typeof(document.selection) != 'undefined'){
			old = this.textarea.createTextRange().text;
			this.textarea.createTextRange().text = text;
			this.textarea.caretPos -= old.length - text.length;
		}else if(typeof(this.textarea.setSelectionRange) != 'undefined'){
			selection_start = this.textarea.selectionStart;
			this.textarea.value = this.textarea.value.substring(0,selection_start) + text + this.textarea.value.substring(this.textarea.selectionEnd);
			this.textarea.setSelectionRange(selection_start + text.length,selection_start + text.length);
		}
		this.doOnChange();
		this.textarea.focus();
	},
	wrapSelection: function(before,after){
		this.replaceSelection(before + this.getSelection() + after);
	},
	insertBeforeSelection: function(text){
		this.replaceSelection(text + this.getSelection());
	},
	insertAfterSelection: function(text){
		this.replaceSelection(this.getSelection() + text);
	},
	injectEachSelectedLine: function(callback,before,after){
		this.replaceSelection((before || '') + $A(this.getSelection().split("\n")).inject([],callback).join("\n") + (after || ''));
	},
	insertBeforeEachSelectedLine: function(text,before,after){
		this.injectEachSelectedLine(function(lines,line){
			lines.push(text + line);
			return lines;
		},before,after);
	}
});

Control.TextArea.ToolBar = Class.create();
Object.extend(Control.TextArea.ToolBar.prototype,{
	textarea: false,
	toolbar: false,
	initialize: function(textarea,toolbar){
		this.textarea = textarea;
		if(toolbar)
			this.toolbar = $(toolbar);
		else{
			this.toolbar = $(document.createElement('ul'));
			this.textarea.textarea.parentNode.insertBefore(this.toolbar,this.textarea.textarea);
		}
	},
	attachButton: function(node,callback){
		node.onclick = function(){return false;}
		Event.observe($(node), 'click',callback.bindAsEventListener(this.textarea));
	},
	addButton: function(link_text,callback,attrs){
		c = document.createElement('li');
		link = document.createElement('a');
		link.href = '#';
		this.attachButton(link,callback);
		c.appendChild(link);
		if(attrs)
			for(a in attrs)
				link[a] = attrs[a];
		if(link_text){
			span = document.createElement('span');
			span.innerHTML = link_text;
			link.appendChild(span);
		}
		this.toolbar.appendChild(c);
	}
});