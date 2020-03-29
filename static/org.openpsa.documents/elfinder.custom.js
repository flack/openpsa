"use strict";

elFinder.prototype.commands.details = function() {

    this.init = function() {
        this.title = this.fm.i18n('cmdopen');
    };
    this.exec = function(hashes) {
        hashes = this.files(hashes);
        window.location.href = window.location.pathname + 'connector/goto/' + hashes[0].hash + '/';
        return jQuery.Deferred().resolve();
    };

    this.getstate = function(sel) {
        var selected = this.files(sel);
        return (selected.length !== 1) ? -1 : 0;
    };
}
