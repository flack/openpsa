/**
 * epiClock 2.0 - Create Epic Clocks Easily
 *
 * Copyright (c) 2008 Eric Garside (http://eric.garside.name)
 * Dual licensed under:
 * 	MIT: http://www.opensource.org/licenses/mit-license.php
 *	GPLv3: http://www.opensource.org/licenses/gpl-3.0.html
 */

	// Manager States
var EC_HALT = 'disable',
	EC_RUN = 'enable',
	EC_KILL = 'destroy',
	// Clock Types
	EC_CLOCK = 0,
	EC_COUNTDOWN = 1,
	EC_COUNTUP = 2,
	EC_ROLLOVER = 3,
	EC_EXPIRE = 4,
	EC_LOOP = 5,
	EC_STOPWATCH = 6,
	EC_HOLDUP = 7;
	
;(function($){
	var defaults = {
		epiClock: {
			offset: {
				hours: 0,
				minutes: 0,
				seconds: 0,
				days: 0,
				years: 0
			},
			arbitrary: {
				days: 0,
				years: 0
			},
			gmt: false,
			target: null,
			onTimer: null,
			onKill: null,
			onRender: function(v,val){v.html(val)},
			format: null,
			frame: {},
			dead: false,
			displace: 0,
			modifier: 0,
			variance: 0,
			daysadded: 0,
			paused: 0,
			tolerance: 0,
			selfLoc: -1,
			mode: EC_CLOCK			
		},
		formats: [
			'F j, Y, g:i:s a',			// EC_CLOCK
			'V{d} x{h} i{m} s{s}',		// EC_COUNTDOWN
			'Q{y} K{d} x{h} i{m} s{s}',	// EC_COUNTUP
			'V{d} x{h} i{m} s{s}',		// EC_ROLLOVER
			'x{h} i{m} s{s}',			// EC_EXPIRE
			'i{m} s{s}',				// EC_LOOP
			'x{h} i{m} s{s}',			// EC_STOPWATCH
			'Q{y} K{d} x{h} i{m} s{s}'	// EC_HOLDUP
		]
	},
		// The current mode the clock manager is in
	current = null,
		// The interval timer for the clock
	loop = null,
		// The clocks we're managing
	clocks = [];
	
	/** 
	 * jQuery Entry Point - Clock Manager
	 * 
	 * Provides an interface for the user to pause, destroy, or resume/start all clocks.
	 */
	$.epiclock = function(mode, precision){
		mode = mode || EC_RUN;
		precision = precision || 5e2;
		if (mode == current) return;
		
		switch (mode){
			case EC_KILL:
				$.each(clocks, function(){
					this.removeData('epiClock');
				})
				clocks = [];
			case EC_HALT:
				if (loop){
					clearInterval(loop);
					loop = null;
				} 
				current = mode;
				break;
			case EC_RUN:
				if (!loop){
					cycleClocks();
					loop = setInterval(cycleClocks, precision);
				}
				current = mode;
				break;
		}
		
		return this;
	}
	
	function cycleClocks(){
		$.each(clocks, function(i){
			this.data('epiClock').render();
		})
	}
	
	/** 
	 * jQuery Entry Point
	 * 
	 * Creates the clock displays
	 */
	$.fn.epiclock = function(options){
		switch (options){
			case 'destroy':
				return this.each(function(){
					var jQ = $(this);
					if (jQ.data('epiClock') instanceof epiClock)
						jQ.data('epiClock').kill();
				})
			case 'disable':
				return this.each(function(){
					var jQ = $(this);
					if (jQ.data('epiClock') instanceof epiClock)
						jQ.data('epiClock').pause();
				})
			case 'enable':
				return this.each(function(){
					var jQ = $(this);
					if (jQ.data('epiClock') instanceof epiClock)
						jQ.data('epiClock').resume();
				})
			default:
				options = $.extend(true, {}, defaults.epiClock, options);
				break;
		}
		
		this.each(function(){
			var object = $(this),
				format = (options.format || defaults.formats[options.mode]).split(''),
				isBuffering = false,
				label = '<label class="epiClock"></label>',
				span = '<span class="epiClock"></span>',
				buffer = '',
				clock = new epiClock(options, object);
				
			object.data('epiClock', clock);

			$.each(format, function(){
				x = this+'';
				switch (x){
					case ' ':
						if (!isBuffering)
							$(span).addClass('ecSpacer').appendTo(object);
						else buffer += x;
						break;
					case '{':
						isBuffering = true;
						break;
					case '}':
						isBuffering = false;
						$(label).html(buffer).appendTo(object);
						buffer = '';
						break;
					default:
							// If we're buffering, this is label text
						if (isBuffering) buffer += x;
							// If it's a special character, it will be span updated
						else if (Date.prototype[x] || clock[x]) {
							clock.frame[x] = $(span).attr('ref', x).appendTo(object);
						}
						// If it's anything else, it's a single char label seperator
						else 
							$(label).addClass('ecSeparator').html(x).appendTo(object);
						break;
				}
			});
			
			clock.selfLoc = clocks.push(object) - 1;
		})
		
		return this;
	}
	
	function epiClock(options, element){
		if (this instanceof epiClock)
			return this.init(options, element);
		else return new epiClock(options, element);
	}
	
	epiClock.prototype = {
		Q:	function() { return this.arbitrary.years },
		E:	function() { return this.arbitrary.days },
		e:  function() { return this.arbitrary.days.pad(0) },
		zero: new Date(0),
		pause:	function(){
			if (this.dead) return;
			this.paused = new Date().valueOf();
			this.dead = true;
		},
		resume:	function(){
			if (!this.dead) return;
			if (this.mode == EC_STOPWATCH)
				this.displace += (this.paused - new Date().valueOf());
			this.paused = 0;
			this.dead = false;
		},
		kill:	function(){
			// Remove and Renumber Clocks Array
			clocks.splice(this.selfLoc,1);
			$.each(clocks, function(i){this.data('epiClock').selfLoc = i});
			
			// Call on kill, set dead
			if ($.isFunction(this.onKill)) this.onKill();
			this.dead = true;
		},
		init:	function(options, element){
			if (options.mode < EC_CLOCK || options.mode > EC_HOLDUP) 
				throw new Exception( 'Invalid Clock Mode.' );
				
			var clock = this;
			$.each(options, function(k, v){
				clock[k] = v;
			});
			
			switch (this.mode){
				case EC_LOOP:
				case EC_EXPIRE:
					this.target = this.target || new Date();
				case EC_COUNTDOWN:
				case EC_ROLLOVER:
					this.modifier = -1;
					this.variance = 1;
					break;
				case EC_STOPWATCH:
					this.displace += -1 * new Date().valueOf();
					return;
				case EC_HOLDUP:
					this.variance = -1;
					this.modifier = 1;
					break;
				default:
					this.modifier = 1;
					this.variance = 0;
					break;
			}
			
			if (this.gmt)
				this.normalize();
			
			switch (true){
				case this.target instanceof Date:
					this.target = this.target.valueOf();
					break;
				case typeof this.target == 'string':
					this.target = new Date(this.target).valueOf();
					break;
			}
			
			this.displace += this.modifier * this.calculateOffset();
		},
		calculateOffset:	function(offset){
			offset = offset || this.offset;

			return (
				offset.years * 3157056e4 +
				offset.days * 864e5 +
				offset.hours * 36e5 +
				offset.minutes * 6e4 +
				(this.variance + offset.seconds) * 1e3
			);
		},
		normalize:	function(){
			this.displace += new Date().getTimezoneOffset()*6e4;
		},
		render:		function(){
			if (!this.tick()) return;
			var clock = this,
				time = (this.mode == EC_HOLDUP) ? this.zero : this.now;

			$.each(this.frame, function(k,v){
				var val = ($.isFunction(time[k]) ? time[k]() : clock[k]()) + '';
				if (v.data('last') != val) clock.onRender(v, val);
				v.data('last', val)
			})
		},
		tick:	function(){
			if (this.dead) return false;
			var now = new Date().valueOf() + this.displace;
			
			switch (this.mode){
				case EC_HOLDUP:
					if (this.target < now) this.mode = EC_COUNTUP;
				case EC_COUNTUP:
					now -= this.target;
					break;
				case EC_ROLLOVER:
					if (now > this.target) now = now - this.target;
					else now = this.target - now;
					break;
				case EC_COUNTDOWN:
				case EC_EXPIRE:
				case EC_LOOP:
					now = this.target - now;
					if (now < this.tolerance) return this.timerEnd();
					break;
			}
			
			this.now = new Date(now);
			
			var days = this.now.V();
			if (days <= this.daysadded) return true;
			
			this.daysadded = days;
			this.arbitrary.days += days;
			
			if (this.arbitrary.days < 365) return true;
			this.arbitrary.years += Math.floor(this.arbitrary.days/365.4 % 365.4);
			this.arbitrary.days = Math.floor(this.arbitrary.days % 365.4);
			
			return true;
		},
		timerEnd:	function(){
			if ($.isFunction(this.onTimer)) this.onTimer();
			
			switch (this.mode){
				case EC_COUNTDOWN:
				case EC_EXPIRE:
					this.kill();
					break;
				case EC_LOOP:
					this.displace += this.modifier * this.calculateOffset();
					return this.render();
				case EC_ROLLOVER:
					this.mode = EC_COUNTUP;
					return true;
			}
			
			this.now = new Date(0);
			return true;
		}
	};
	
	$.extend(String.prototype, {
		pad: function(s,l){ l=l||2; return this.length < l ? new Array(1+l-this.length).join(s) + this : this },
		rpad: function(s,l){ l=l||2; return this.length < l ? this + new Array(1+l-this.length).join(s) : this }
	})
	
	$.extend(Number.prototype, {
		pad: function(s,l){ return (this+'').pad(s,l) },
		rpad: function(s,l){ return (this+'').rpad(s,l) }
	})

	/** Prototype the Date function **/
	$.extend(Date.prototype, {
		// Assistance Definitions
		modCalc: function(mod1,mod2){return (Math.floor(Math.floor(this.valueOf()/1e3)/mod1)%mod2)},
		months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
		suffix: [null, 'st', 'nd', 'rd'],
		// Timer Functions
		V: function(){return this.modCalc(864e2,1e5)},		// Days
		v: function(){return this.V().pad(0)},				// Paded Days
		K: function(){return this.V()%365},					// Days Offset for Years
		k: function(){return this.K().pad(0)},				// Padded Offset Days
		X: function(){return this.modCalc(36e2,24)},		// Hours
		x: function(){return this.X().pad(0)},				// Padded Hours
		// Day
		d: function() { return this.getDate().pad('0') },
		D: function() { return this.days[this.getDay()].substring(0,3) },
		j: function() { return this.getDate() },
		l: function() { return this.days[this.getDay()] },
		N: function() { return this.getDay() + 1 },
		S: function() { return this.suffix[this.getDate()] || 'th' },
		w: function() { return this.getDay() },
		z: function() { return Math.round((this-this.f())/864e5) },
		// Week
		W: function() { return Math.ceil(((((this-this.f())/864e5) + this.f().w())/7)) },
		// Month
		F: function() { return this.months[this.getMonth()]; },
		m: function() { return (this.getMonth()+1).pad(0) },
		M: function() { return this.months[this.getMonth()].substring(0,3) },
		n: function() { return this.getMonth() + 1 },
		// Year
		L: function() { var Y = this.Y(); return Y%4 ? false : Y%100 ? true : Y%400 ? false : true },
		f: function() { return new Date(this.getFullYear(),0,1) },
		Y: function() { return this.getFullYear() },
		y: function() { return ('' + this.getFullYear()).substr(2) },
		// Time
		a: function() { return this.getHours() < 12 ? 'am' : 'pm' },
		A: function() { return this.a().toUpperCase() },
		B: function() { return Math.floor((((this.getHours()) * 36e5) + (this.getMinutes() * 6e4) + (this.getSeconds() * 1e3))/864e2).pad(0,3) },
		g: function() { return this.getHours()%12 || 12 },
		G: function() { return this.getHours() },
		h: function() { return this.g().pad('0') },
		H: function() { return this.getHours().pad('0') },
		i: function() { return this.getMinutes().pad(0) },
		s: function() { return this.getSeconds().pad('0') },
		u: function() { return this.getTime()%1000 },
		// Timezone
		O: function() { var t = this.getTimezoneOffset() / 60; return (t >= 0 ? '+' : '-') + Math.abs(t).pad(0).rpad(0,4) },
		P: function() { var t = this.O(); return t.substr(0,3) + ':' + t.substr(3)},
		Z: function() { return this.getTimezoneOffset() * 60;},
		// Full Date/Time
		c: function() { return this.Y()+'-'+this.m()+'-'+this.d()+'T'+this.H()+':'+this.i()+':'+this.s()+this.P()},
		r: function() { return this.toString() },
		U: function() { return this.getTime() / 1000 }
	});
	
})(jQuery);
