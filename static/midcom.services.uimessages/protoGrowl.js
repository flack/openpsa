/*
    Class: protoGrowl
    Version: 1.0b (Work in progress)
    License: GNU LGPL

    Jerry Jalava, jerry.jalava@incognito.fi
    2006

    --------------------------
    To create a growl:
    var growl = new protoGrowl({type: 'info', title: 'title', content: 'content'});
    --------------------------

    --------------------------
    Hint: if you have html coded growls
    do this on body onLoad:
    new BodyReader( {} );

    It reads the whole document and transforms every element
    with attribute id="midcom-service-uimessages-protogrowl"
    to growl windows (read more from BodyReader description)
    --------------------------

    Updated documentation will reside in:
    http://www.midgard-project.org/documentation/protogrowl
    
    ** Warning **
    This is still a big work in progress.
*/

var protoGrowl = Class.create();
protoGrowl.prototype = {

    /*
        Initializes protoGrowl Class.
        This is where we'll set some defaults and parse all user input
        to creation process.

        Available parameters:
        id:     growl id (this you don't need to set, but can be overrided if wanted)
        type:   growl type (notfication, warning, error, message)
        title:  growls title (this gets populated to div with class $className+'-title')
        message: growls content (Can be html or just plain text. $className+'-content')

        width:   growl width (defaults to 350 (px))
        height:  growl height (defaults to 80 (px))
        minWidth:   minimum width (defaults to 350 (px))
        minHeight:  minimum height (defaults to 80 (px))
        maxWidth:   maximum width
        maxHeight:   maximum height

        - You can adjust the effect growls are revealed/hidden
        showEffect:         not yet in use
        showEffectOptions:  not yet in use
        hideEffect:         not yet in use
        hideEffectOptions:  not yet in use
        
        maxprotoGrowls:     this sets the maximum for how many growl can be displayed at once,
                            if the count is exceeded it will hide the oldest automatically

        lifeTime:   sets growls lifeTime in seconds (default to 3600 seconds)

        closable:   true/false (does he growl have the close button on it...)

        className:  defaults to 'midcom-service-uimessages-protogrowl'
        
        padding:    array of growl padding. Defaults to [10,0,0,0] (top,right,bottom,left)

    */
    initialize: function(parameters)
    {
        this.id = parameters.id || this.nextID();
        this.type = parameters.type || 'info';
        this.title = parameters.title;
        this.content = parameters.message;

        this.minWidth = parameters.minWidth || 312;
        this.minHeight = parameters.minHeight || 108;
        this.maxWidth = parameters.maxWidth;
        this.maxHeight = parameters.maxHeight;

        this.hasEffectLib = String.prototype.parseColor != null;
        this.showEffectOptions = parameters.showEffectOptions || (parameters.effectOptions || { duration:0.50 });
        this.hideEffectOptions = parameters.hideEffectOptions || (parameters.effectOptions || { duration:0.80, afterFinish: function(){ this.destroy(); }.bind(this) } );
        //afterFinish: .bind(this)

        this.showEffect = parameters.showEffect || (this.hasEffectLib ? Effect.Appear : Element.show);
        this.hideEffect = parameters.hideEffect || (this.hasEffectLib ? Effect.Fade : Element.hide);

        var maxprotoGrowls = parameters.maxprotoGrowls || 3;
        protoGrowls.setMaxprotoGrowls( maxprotoGrowls );
        this.lifetime = parameters.lifeTime || 3600;

        this.closable = parameters.closable != null ? parameters.closable : true;
        this.destroyOnClose = true;

        var className = parameters.className != null ? parameters.className : "midcom-service-uimessages-protogrowl";
        this.className = className;

        var parent = parameters.parent || document.getElementsByTagName("body")[0];//.item(0);

        if(parameters.element != null && parameters.element != undefined) {
            this.element = this.collectElement( parameters.element, parent );
        } else {
            this.element = this.create( parent );
        }

        for(var k in this.element) {
//            debug("k: "+k+" val:"+this.element[k]);
        }


        this.eventMouseUp = this.hide.bindAsEventListener(this);
        this.eventOnLoad = this.getWindowMeasurements.bindAsEventListener(this);
        var closeBtnClass = this.className + "-closebutton";
        this.closeBtn = document.getElementsByClassName(closeBtnClass);
        //debug("this.closeBtn "+this.closeBtn);
        //debug("this.element "+this.element);
        Event.observe(this.closeBtn, "mouseup", this.eventMouseUp);
        Event.observe(window, "load", this.eventOnLoad);

        var offset = [0,0];
        this.padding = parameters.padding || [10,0,0,0];
        var width = parseFloat(parameters.width) || this.minWidth;
        var height = parseFloat(parameters.height) || this.minHeight;

        if (parameters.left != null) {
            this.setStyle({left: parseFloat(parameters.left) + offset[0] + 'px'});
            this.useLeft = true;
        }
        if (parameters.right != null) {
            this.setStyle({right: parseFloat(parameters.right) + 'px'});
            this.useLeft = false;
        }
        if (parameters.top != null) {
            this.setStyle({top: parseFloat(parameters.top) + 'px'});
            this.useTop = true;
        }
        if (parameters.bottom != null) {
            this.setStyle({bottom: parseFloat(parameters.bottom) + 'px'});
            this.useTop = false;
        }
        if (parameters.zIndex) {
            this.setZIndex(parameters.zIndex);
        }

        this.getWindowMeasurements();

        this.setSize(width, height);
        //this.placeprotoGrowl();

        if(this.content != null && this.content != undefined) {
            this.getContent().innerHTML = this.content;
        }

        protoGrowls.register(this);
        this.destroyed = false;

        if(parameters.dontShow == false || parameters.dontShow == undefined) {
            this.show();
        }
    },

    create: function( parent )
    {
        var growl_div = document.createElement("div");
        var growl_id = this.id;

        growl_div.setAttribute('id', growl_id);
        growl_div.className = this.className + ' ' + this.className + '-type-' + this.type;

        if(!this.title) {
            this.title = "&nbsp;";
        }
        //debug("id: "+growl_id);
        growl_div.innerHTML = "\
        <div class='"+ this.className +"-contenthelper-type-"+this.type+"'>\
         <div class='"+ this.className +"-closebutton' id='"+ this.className +"-closebutton' onclick='protoGrowls.close(\""+ growl_id +"\");'></div>\
         <div class='"+ this.className +"-title' id='"+ this.className +"-title'>"+ this.title +"</div>\
         <div class='"+ this.className +"-content' id='"+ this.className +"-content-" + this.id +"'> </div>\
        </div>\
        ";
        Element.hide(growl_div);

        parent.insertBefore(growl_div, parent.firstChild);

        if(!this.closable) {
            Element.hide(this.className + "-closebutton");
        }

        //debug(growl_div.innerHTML);

        return growl_div;
    },

    collectElement: function( elmId, def_parent )
    {
        var growl_div = document.getElementById(elmId);
        var growl_id = this.id;
        //debug('collect elmid: '+elmId);
        //debug('collect id: '+growl_id);
        if(!growl_div) {
            //debug( 'element not found!' );
            this.create( def_parent );
        }

        //var growl_div_content = document.getElementById(elmId+'-content');
        //growl_div_content.setAttribute('id', elmId+'-content-'+growl_id);

        growl_div.setAttribute('id', growl_id);
        var growl_div_real = document.getElementById(growl_id);

        //debug('collect afterid: '+growl_div_real.id);

        //if(this.title != '') {
            //this.setTitle(this.title);
        //}

        Element.hide(growl_div_real);
        return growl_div_real;
    },

    placeprotoGrowl: function()
    {
        var element = this.element;
        if(element == undefined || isNull(element)) {
            return false;
        }

        var windowScroll = WindowUtilities.getWindowScroll();
        var pageSize = WindowUtilities.getPageSize();

        var X = 0;
        var Y = 0;
        var middle = getViewportSize()[0]/2 + getScrollXY()[0] - this.width/2;
        //var growlCnt = activeprotoGrowlList.length-1;

        Y = ((getViewportSize()[1] + getScrollXY()[1]) - this.padding[0] - this.height);
        // - (growlCnt*gH) - (growlCnt*gPadding);
        X = windowScroll.left + (pageSize.windowWidth - (this.width + this.widthW + this.widthE))/2;//middle;

        //debug('x:'+X+' y:'+Y);

        this.setStyle({position: 'absolute'});
        this.setLocation( Y, X );
    },

    moveUp: function()
    {
        //debug('move up: '+this.id);
        var Y = 0;
        Y = this.top - this.height - this.padding[0];
        this.setLocation( Y );
    },

    setLocation: function(top, left, bottom, right)
    {
        if(top == null || top == undefined) {
            var top = '';
        }
        if(left == null || left == undefined) {
            var left = '';
        }
        if(right == null || right == undefined) {
            var right = '';
        }
        if(bottom == null || bottom == undefined) {
            var bottom = '';
        }

        if(top != '') {
            this.top = top;
            this.setStyle({top: top + 'px'});
            this.useTop = true;
            this.useBottom = false;
        } else if(bottom != '') {
            this.bottom = bottom;
            this.setStyle({bottom: bottom + 'px'});
            this.useTop = false;
            this.useBottom = true;
        }

        if(left != '') {
            this.left = left;
            this.setStyle({left: left + 'px'});
            this.useLeft = true;
            this.useRight = false;
        } else if(right != '') {
            this.right = right;
            this.setStyle({right: right + 'px'});
            this.useLeft = false;
            this.useRight = true;
        }

    },

    setSize: function(width, height)
    {
        if(width < this.minWidth) {
            width = this.minWidth;
        }

        if(height < this.minHeight) {
            height = this.minHeight;
        }

        if(this.maxHeight && height > this.maxHeight) {
            height = this.maxHeight;
        }

        if(this.maxWidth && width > this.maxWidth) {
            width = this.maxWidth;
        }

        this.width = width;
        this.height = height;

        this.setStyle({width: width + this.widthW + this.widthE + "px"});
        this.setStyle({height: height + this.heightN + this.heightS + "px"});
    },

    show: function()
    {
        //debug('show: '+this.id);
        //debug('show x:'+this.element.style.left+' y:'+this.element.style.top);
        this.placeprotoGrowl();
        this.setSize(this.width, this.height);

        if(this.showEffect != Element.show && this.showEffectOptions ) {
            //debug('this.showEffect: '+this.showEffect);
            this.showEffect(this.element, this.showEffectOptions);
        } else {
            //debug('this.showEffect (no options): '+this.showEffect);
            this.showEffect(this.element);
        }
    },

    hide: function( id )
    {
        //debug('hide: '+this.id);
        if(this.hideEffectOptions) {
            this.hideEffect(this.element, this.hideEffectOptions);
        } else {
            this.hideEffect(this.element);
        }

        //if(this.iefix)
        //    this.iefix.hide();
    },
    
    center: function()
    {
        var windowScroll = WindowUtilities.getWindowScroll();
        var pageSize = WindowUtilities.getPageSize();

        this.setLocation(windowScroll.top + (pageSize.windowHeight - (this.height + this.heightN + this.heightS))/2, windowScroll.left + (pageSize.windowWidth - (this.width + this.widthW + this.widthE))/2);
    },

    destroy: function( id )
    {
        if(this.destroyed) {
            return false;
        }
        protoGrowls.notify("onDestroy", this);

        Event.stopObserving(this.closeBtn, "mouseup", this.eventMouseUp);
        Event.stopObserving(window, "load", this.eventOnLoad);

        if(this.iefix) {
            Element.hide(this.iefix);
        }

        var objBody = document.getElementsByTagName("body").item(0);
        objBody.removeChild(this.element);
        this.destroyed = true;
        protoGrowls.unregister(this);
    },

    setDelegate: function(delegate)
    {
        this.delegate = delegate;
    },

    getDelegate: function()
    {
        return this.delegate;
    },

    getContent: function ()
    {
        //alert(document.getElementById(this.id).innerHTML);
        //var elid = this.className + "-content-" + this.id;
        //alert(elid);
        //alert(document.getElementById(elid));
        //alert(document.getElementsByClassName(this.className + "-content-" + this.id));
        return document.getElementById(this.className + "-content-" + this.id);
    },

    setContent: function(id, autoposition)
    {
        var d = null;
        var p = null;
        
        if(autoposition) {
            p = Position.cumulativeOffset($(id));
        }

        var content = this.getContent()
        content.appendChild($(id));

        if(autoposition) {
            this.setLocation(p[1] - this.heightN, p[0] - this.widthW);
        }
    },
    
    setStyle: function(style)
    {
        for (name in style) {
            this.element.style[name.camelize()] = style[name];
        }
    },
	
    getId: function()
    {
        return this.element.id;
    },

    closeObject: function()
    {
        if(this.closeBtn) {
            return;
        }
    },
    
    getWindowMeasurements: function(event)
    {
        var div = this.createHiddenDiv(this.className + "_n")
        this.heightN = Element.getDimensions(div).height;
        div.parentNode.removeChild(div)

        var div = this.createHiddenDiv(this.className + "_s")
        this.heightS = Element.getDimensions(div).height;
        div.parentNode.removeChild(div)
        
        var div = this.createHiddenDiv(this.className + "_e")
        this.widthE = Element.getDimensions(div).width;
        div.parentNode.removeChild(div)
        
        var div = this.createHiddenDiv(this.className + "_w")
        this.widthW = Element.getDimensions(div).width;
        div.parentNode.removeChild(div);
    },
    
    createHiddenDiv: function(className)
    {
        var objBody = document.getElementsByTagName("body").item(0);

        var growl_hidden_div = document.createElement("div");
        growl_hidden_div.setAttribute('id', this.id + "-tmp");
        growl_hidden_div.className = className;
        growl_hidden_div.style.display = 'none';
        growl_hidden_div.innerHTML = '';

        objBody.insertBefore(growl_hidden_div, objBody.firstChild)

        return growl_hidden_div;
    },

    nextID: function()
    {
        return protoGrowls.getNextId();
    },

    setZIndex: function(zindex)
    {
        this.setStyle({zIndex: zindex});
        protoGrowls.updateZindex(zindex, this);
    },

    setTitle: function(newTitle)
    {
        if(!newTitle) {
            newTitle = "&nbsp;";
            Element.update(this.className + '-title', newTitle);
        }
    }

};

var protoGrowls = {
    growls: [],
    observers: [],
    focusedWindow: null,
    maxZIndex: 0,
    maxprotoGrowls: 3,
    listenFrequency: 0.8,
    nextid: 1,

    addObserver: function(observer) {
        this.observers.push(observer);
    },

    removeObserver: function(observer) {
        this.observers = this.observers.reject( function(o) { return o==observer });
    },

    notify: function(eventName, growl) {
        this.observers.each( function(o) {if(o[eventName]) o[eventName](eventName, growl);});
    },

    growlExists: function(id)
    {
        return this.growls.detect(function(d) { return d.getId() ==id });
    },

    getprotoGrowl: function(id)
    {
        return this.growls.detect(function(d) { if(d.getId()==id){return d;} });
    },

    setMaxprotoGrowls: function( max )
    {
        this.maxprotoGrowls = max;
    },

    getNextId: function()
    {
        return this.nextid;
    },

    register: function(growl) {
        var growlCnt = this.growls.length;
        this.nextid = growl.id+1;
        if(growlCnt < this.maxprotoGrowls) {
            this.moveUp();
            growl.registered = new Date().getTime();
            this.growls.push(growl);
        } else {
            this.removeFirst();
            this.moveUp();
            growl.registered = new Date().getTime();
            this.growls.push(growl);
        }
    },

    removeFirst: function()
    {
        var oldest = this.growls[0];
        //debug('oldest id: '+oldest.id);
        this.close(oldest.id);
    },

    unregister: function(growl) {
        this.growls = this.growls.reject(function(d) { return d==growl });
    },

    close: function(id) {
        growl = this.growlExists(id);
        if (growl) {
            if(growl.getDelegate() && ! growl.getDelegate().canClose(growl)) {
                return;
            }

            this.notify("onClose", growl);
            growl.hide();
        }
    },

    isExpired: function( id )
    {
        var growl = this.getprotoGrowl(id);
        
        if (growl.type == 'error')
        {
            // Error messages must be disposed by clicking the close button
            return false;
        }
        
        var timestamp = new Date().getTime();
        var expires = growl.registered + growl.lifetime;
        if(expires <= timestamp) {
            return true;
        }
        return false;
    },

    moveUp: function() {
        this.growls.each( function(g) {g.moveUp()} );
    },

    closeAll: function() {
        this.growls.each( function(g) {protoGrowls.close(g.getId())} );
    },

    updateZindex: function(zindex, growl) {
        if(zindex > this.maxZIndex) {
            this.maxZIndex = zindex;
        }
        this.focusedWindow = growl;
    }
};

var BodyReader = Class.create();
BodyReader.prototype = {

    initialize: function( params ) {
        this.deftype = 'class';

        this.start = params.body || 'body';
        this.autotransform = params.autotransf || true;

        this.name = params.name || 'midcom-service-uimessages-protogrowl';
        this.growl_type = params.type || 'info';

        if(!params.type) {
            this.collect( this.name, this.deftype );
        }
    },
    collect: function( p_var, p_type ) {
        var element = '';
        var elements = '';

        if(p_type == 'id') {
            element = document.getElementById(p_var);
            var growl_type = this.growl_type || 'info';
            if(this.autotransform && element) {
                this.transform( p_var, growl_type );
            }
        }
        if(p_type == 'class') {
            elements = document.getElementsByClassName(p_var);
            if(elements.length>0) {
                for(var i=0;i<elements.length;i++) {
                    var growl_type = this.growl_type || 'info';
                    if(this.autotransform && elements[i]) {
                        var tempid = 'midcom-service-uimessages-protogrowl_tmp_obj_'+i;
                        elements[i].setAttribute('id', tempid);
                        this.transform( tempid, growl_type );
                    }
                }
            }
        }
    },
    transform: function( p_name, p_type, p_readtype ) {
        new protoGrowl({type: p_type, element: p_name});
    }
};
//var BodyReader = new BodyReader( {} );

var LifeWatcher = Class.create();
LifeWatcher.prototype = {
    initialize: function() {
        this.defaultLifetime = 8;

        //this.execute();
    },

    execute: function() {
        var growlCnt = protoGrowls.growls.length;
        //debug('lw execute: '+new Date().getTime());
        if(growlCnt>=1) {
            protoGrowls.growls.each( function(g) {
                                    var gID = g.getId();
                                    if(protoGrowls.isExpired(gID)) {
                                        protoGrowls.close(gID);
                                    }
                                } );
        }
    }
};
var LifeGuard = new LifeWatcher();
new PeriodicalExecuter(LifeGuard.execute,protoGrowls.listenFrequency);




var isIE = navigator.appVersion.match(/MSIE/) == "MSIE";

var WindowUtilities = {

  getWindowScroll: function() {
    var w = window;
      var T, L, W, H;
      with (w.document) {
        if (w.document.documentElement && documentElement.scrollTop) {
          T = documentElement.scrollTop;
          L = documentElement.scrollLeft;
        } else if (w.document.body) {
          T = body.scrollTop;
          L = body.scrollLeft;
        }
        if (w.innerWidth) {
          W = w.innerWidth;
          H = w.innerHeight;
        } else if (w.document.documentElement && documentElement.clientWidth) {
          W = documentElement.clientWidth;
          H = documentElement.clientHeight;
        } else {
          W = body.offsetWidth;
          H = body.offsetHeight
        }
      }
      return { top: T, left: L, width: W, height: H };

  },

  getPageSize: function(){
  	var xScroll, yScroll;

  	if (window.innerHeight && window.scrollMaxY) {
  		xScroll = document.body.scrollWidth;
  		yScroll = window.innerHeight + window.scrollMaxY;
  	} else if (document.body.scrollHeight > document.body.offsetHeight){
  		xScroll = document.body.scrollWidth;
  		yScroll = document.body.scrollHeight;
  	} else {
  		xScroll = document.body.offsetWidth;
  		yScroll = document.body.offsetHeight;
  	}

  	var windowWidth, windowHeight;

  	if (self.innerHeight) {
  		windowWidth = self.innerWidth;
  		windowHeight = self.innerHeight;
  	} else if (document.documentElement && document.documentElement.clientHeight) {
  		windowWidth = document.documentElement.clientWidth;
  		windowHeight = document.documentElement.clientHeight;
  	} else if (document.body) {
  		windowWidth = document.body.clientWidth;
  		windowHeight = document.body.clientHeight;
  	}
  	var pageHeight, pageWidth;

  	if(yScroll < windowHeight){
  		pageHeight = windowHeight;
  	} else {
  		pageHeight = yScroll;
  	}

  	if(xScroll < windowWidth){
  		pageWidth = windowWidth;
  	} else {
  		pageWidth = xScroll;
  	}

  	return {pageWidth: pageWidth ,pageHeight: pageHeight , windowWidth: windowWidth, windowHeight: windowHeight};
  }

};

/* Midgard common */

function isNull(a) {
    return typeof a == 'object' && !a;
}

function getScrollXY() {
	var scrOfX = 0, scrOfY = 0;

	if(typeof(window.pageYOffset) == 'number') {
		//Netscape compliant
		scrOfY = window.pageYOffset;
		scrOfX = window.pageXOffset;

	} else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) {
		//DOM compliant
		scrOfY = document.body.scrollTop;
		scrOfX = document.body.scrollLeft;

	} else if (document.documentElement &&
		(document.documentElement.scrollLeft || document.documentElement.scrollTop)) {
		//IE6 standards compliant mode
		scrOfY = document.documentElement.scrollTop;
		scrOfX = document.documentElement.scrollLeft;
	}

	return [scrOfX, scrOfY];
}

function getViewportSize() {
	var myWidth = 0, myHeight = 0;

	if (typeof(window.innerWidth ) == 'number') {
		//Non-IE
		myWidth = window.innerWidth;
		myHeight = window.innerHeight;

	} else if (document.documentElement &&
		(document.documentElement.clientWidth || document.documentElement.clientHeight)) {
		//IE 6+ in 'standards compliant mode'
		myWidth = document.documentElement.clientWidth;
		myHeight = document.documentElement.clientHeight;

	} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
		//IE 4 compatible
		myWidth = document.body.clientWidth;
		myHeight = document.body.clientHeight;
	}

	return [myWidth, myHeight];
}
