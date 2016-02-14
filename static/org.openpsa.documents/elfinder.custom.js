"use strict";

elFinder.prototype.commands.details = function() {

    this.init = function() {
        this.title = this.fm.i18n('cmdopen');
    };
    this.exec = function() {
        var hashes = this.fm.selected();
        window.location.href = window.location.pathname + 'connector/goto/' + hashes[0] + '/';
    };

    this.getstate = function() {
	return this.fm.selected().length == 1 ? 0 : -1;
    };
}

