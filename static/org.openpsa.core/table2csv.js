/* Based on Alan Knowles' neat trick on turning tables to CSV,
   namespaced and slightly improved */
function table2csv_flatten(children) {
    var ret = '';
    for (var i=0;i<children.length;i++)
    {
        // 2006.07.17, Rambo: skip invisible nodes
        if (   children[i].style
            && (   (   children[i].style.display
                    && children[i].style.display == 'none')
                || (   children[i].style.visibility
                    && children[i].style.display == 'hidden')
                )
            )
        {
            continue;
        }
        if (children[i].nodeType == 3)
        {
            ret += " " + children[i].nodeValue;
            continue;
        }
        if (children[i].childNodes)
        {
            ret += " " + table2csv_flatten(children[i].childNodes);
        }
    }
    return ret;

}

function table2csv_TrimString(sInString)
{
  sInString = sInString.replace( /\n|\r/g, " " ); // remove line breaks
  /*
  sInString = sInString.replace( /\r/g, " " ); // remove line breaks
  */
  sInString = sInString.replace( /\s+/g, " " ); // Shorten long whitespace
  sInString = sInString.replace( /^\s+/g, "" ); // strip leading ws

  return sInString.replace( /\s+$/g, "" ); // strip trailing ws
}

function table2csv(table)
{
    var tab = document.getElementById(table);
    var str = '';
    //TODO: configurable separator
    //var separator='\t';
    var separator=';';

    for (var r =0; r < tab.rows.length; r++)
    {
        var rstr = '';
        for (var c=0;c < tab.rows[r].cells.length; c++)
        {
            rstr += table2csv_TrimString(table2csv_flatten(tab.rows[r].cells[c].childNodes)) + separator;
            //alert('colspan:'+tab.rows[r].cells[c].colSpan);
            //2005.04.19, Rambo: Added check for colspans.
            if (tab.rows[r].cells[c].colSpan > 1)
            {
                //alert('Found larger colspan: '+tab.rows[r].cells[c].colSpan);
                for (var cs=1;cs < tab.rows[r].cells[c].colSpan;cs++)
                {
                    rstr += separator;
                }
                //alert(rstr);
            }
        }
        str  = str + rstr + "\n";
    }
    //alert(str);
    //return false;
    document.getElementById('csvdata').value += str;
    //alert(document.getElementById('csvdata').value);
    //return false;
    return true;
}

//2005.04.19, Rambo: A quick wqay to add table separators (empthy lines) to csvdata
function table2csv_lineSep()
{
    document.getElementById('csvdata').value += '\n\n';
}