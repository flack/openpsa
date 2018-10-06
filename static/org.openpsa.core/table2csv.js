/* Based on Alan Knowles' neat trick on turning tables to CSV,
   namespaced and slightly improved */
function table2csv(table) {
    if (document.getElementById('csvdata').value) {
        return true;
    }

    var tab = document.getElementById(table),
        str = '',
        //TODO: configurable separator
        //separator='\t',
        separator=';';

    function flatten(children) {
        var ret = '';
        for (var i = 0; i < children.length; i++) {
            // 2006.07.17, Rambo: skip invisible nodes
            if (   children[i].style
                && (   (   children[i].style.display
                        && children[i].style.display == 'none')
                    || (   children[i].style.visibility
                        && children[i].style.display == 'hidden'))) {
                continue;
            }
            if (children[i].nodeType == 3) {
                ret += " " + children[i].nodeValue;
                continue;
            }
            if (children[i].childNodes) {
                ret += " " + flatten(children[i].childNodes);
            }
        }
        return ret;
    }

    function trim(sInString) {
        sInString = sInString.replace( /\n|\r/g, " " ); // remove line breaks
        /*
         sInString = sInString.replace( /\r/g, " " ); // remove line breaks
         */
        sInString = sInString.replace( /\s+/g, " " ); // Shorten long whitespace
        sInString = sInString.replace( /^\s+/g, "" ); // strip leading ws

        return sInString.replace( /\s+$/g, "" ); // strip trailing ws
    }

    for (var r = 0; r < tab.rows.length; r++) {
        var rstr = '';
        for (var c = 0; c < tab.rows[r].cells.length; c++) {
            rstr += trim(flatten(tab.rows[r].cells[c].childNodes)) + separator;
            //2005.04.19, Rambo: Added check for colspans.
            if (tab.rows[r].cells[c].colSpan > 1) {
                for (var cs = 1; cs < tab.rows[r].cells[c].colSpan; cs++) {
                    rstr += separator;
                }
            }
        }
        str  = str + rstr + "\n";
    }
    document.getElementById('csvdata').value += str;
    return true;
}
