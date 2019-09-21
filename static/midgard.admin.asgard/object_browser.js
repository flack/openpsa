$(document).ready(function() {
    $('#content').on('click', 'a.thickbox', function(event) {
        if (!this.dataset.initialized) {
            event.preventDefault();

            // Add ajax to each colorbox link. This will tell to Asgard that it
            // should show only the object view style and nothing else.
            var link = this.href;

            if (!link) {
                link = '';
            }

            if (link.match(/\?/)) {
                link = link.replace(/\?/, '?ajax&');
            } else {
                link += '?ajax';
            }

            // Convert the link to use thickbox
            this.href = link;
            this.target = '_self';

            $(this).colorbox({maxHeight: '90%', maxWidth: '90%', fixed: true});
            this.dataset.initialized = true;
        }
    });
});
