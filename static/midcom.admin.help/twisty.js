var slide_speed = 0.4;

function toggle_twisty(id) {
    var element = document.getElementById(id);

    if (!element) {
        return;
    }

    var twisties = document.getElementsByClassName('twisty', element.parentNode);

    for (var i = 0; i < twisties.length; i++) {
        var source = twisties[i].src;

        switch (source.match(/\/twisty-(.+)\.gif$/)[1]) {
            case 'down':
            case 'do-down':
                twisties[i].src = source.replace(/twisty-(.+)\.gif$/, 'twisty-do-hidden.gif');

                if (element.style.display != 'none') {
                    self.setTimeout('document.getElementById("' + id + '").style.display = "none";', slide_speed * 1000);
                }

                // Remove the anchor
                remove_anchor(id);
                break;
            case 'hidden':
            case 'do-hidden':
                twisties[i].src = source.replace(/twisty-(.+)\.gif$/, 'twisty-do-down.gif');
                if (element.style.display == 'none') {
                    self.setTimeout('document.getElementById("' + id + '").style.display = "block";', slide_speed * 1000);
                }

                // Add an anchor
                add_anchor(id);
                break;
            default:
        }

        if (i > 100) {
            return;
        }
    }
}

function remove_anchor(id) {
    if (!window.location.hash) {
        return;
    }

    id = id.replace(/_contents[,]*/, '');

    var string = window.location.hash,
        regexp = new RegExp(id, 'g');
    string = string.replace(regexp, '');

    string = string.replace(/#[,]*/, '#');
    string = string.replace(/[,]*$/, '');
    string = string.replace(/,,/g, ',');

    window.location.hash = string;
}

function add_anchor(id) {
    window.location.hash = '#' + id.replace(/_contents$/, '');
}
