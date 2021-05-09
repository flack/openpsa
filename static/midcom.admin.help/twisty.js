const slide_speed = 0.4;

function toggle_twisty(id) {
    var element = document.getElementById(id);

    if (!element) {
        return;
    }
    let twisty = element.previousSibling.previousSibling.firstChild.nextSibling;
    twisty.classList.toggle('fa-caret-up');
    twisty.classList.toggle('fa-caret-down');

    if (element.parentNode.classList.contains('open')) {
        remove_anchor(id);
    } else {
        add_anchor(id);
    }
    element.parentNode.classList.toggle('open')
    element.parentNode.classList.toggle('closed')
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
