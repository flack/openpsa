/**
 * AJAX table-to-form handler
 * Henri Bergius, henri.bergius@iki.fi
 */
function ooAjaxTableFormHandler(viewId, fieldPrefix)
{
    // This is the constructor

    // Define HTML IDs of the relevant elements
    this.instanceName = '',
    this.formId = viewId+'_editor',
    this.saveButtonId = viewId+'_savebutton';
    this.tableId = viewId+'_table';
    this.tableDataId = viewId+'_data';

    // Field prefix for the editing form
    this.fieldPrefix = fieldPrefix;

    // Some default settings
    this.hiddenFields = Array();
    this.allowCreate = false;
    this.focusField = '';
    this.URL = '';
    this.loadedToEditor = false;
    this.tableShown = false;
    this.ajaxResultElement = '';
    this.dataPopulated = false;
    this.evenRow = false;
    this.evenColor = '#eeeeee';

    /**
     * Calculate sum of numeric values in specific column of the table
     * @param string column Name of the column
     */
    this.calculateColumn = function(column)
    {
        var calculatedSum = 0;
        var tableBody = document.getElementById(this.tableDataId);
        var rows = tableBody.getElementsByTagName('tr');
        var totalRows = rows.length;
        for (var i=0; i < totalRows; i++)
        {
            var cells = rows[i].getElementsByTagName('td');
            for (var ii=0; ii < cells.length; ii++)
            {
                if (cells[ii].className == column)
                {
                    calculatedSum += Number(cells[ii].innerHTML);
                }
            }
        }

        // Save the field to the footer
        var wholeTable = document.getElementById(this.tableId);
        var footer = wholeTable.getElementsByTagName('tfoot');
        if (footer.length > 0)
        {
            var cells = footer[0].getElementsByTagName('td');
            for (var i=0; i < cells.length; i++)
            {
                if (cells[i].className == column)
                {
                    cells[i].innerHTML = Math.round(Number(calculatedSum)*100)/100;
                }
            }
        }
    }

    /**
     * Method for adding a row of data to the table
     */
    this.addTableRow = function(parentElement, dataArray, existingRows, editable)
    {
        // Create and style the table row
        newRow = document.createElement('tr');

        if (editable != false)
        {
            ooAjaxSetClass(newRow, 'ajax_editable_row', false);
            if (newRow.addEventListener)
            {
                // This is the correct DOM method
                newRow.addEventListener('click', this.convertRowToEditorEventhandler, false);
            }
            else if (newRow.attachEvent)
            {
                // IE compatibility
                newRow.attachEvent('onclick', this.convertRowToEditorEventhandler);
            }
        }

        // "Generate" a GUID
        if (!dataArray[this.fieldPrefix+'guid'])
        {
            dataArray[this.fieldPrefix+'guid'] = existingRows + 1;
        }
        newRow.id = 'ooAjaxFormTableRow_'+dataArray[this.fieldPrefix+'guid'];

        if (this.evenRow)
        {
            //newRow.style.background = '#eeeeee';
            newRow.style.background = this.evenColor;
            this.evenRow = false;
        }
        else
        {
            this.evenRow = true;
        }

        wholeTable = document.getElementById(this.tableId);

        // Populate cells in same order as the table headers
        var headers = wholeTable.getElementsByTagName('th');
        for (var i=0; i < headers.length; i++)
        {
            newCell = document.createElement('td');
            //newCell.setAttribute('class', headers[i].className);
            newCell.className = headers[i].className;

            if (headers[i].style.display == 'none')
            {
                newCell.style.display = 'none';
            }

            if (dataArray[headers[i].className])
            {
                newCell.innerHTML = dataArray[headers[i].className];
            }
            else
            {
                newCell.innerHTML = '';
            }

            newRow.appendChild(newCell);
        }

        // Populate cells
        /*
        for (item in dataArray)
        {
            newCell = document.createElement('td');
            newCell.setAttribute('class',item);

            if (this.hiddenFields)
            {
                for (var i=0; i < this.hiddenFields.length; i++)
                {
                    if (this.hiddenFields[i] == item)
                    {
                        newCell.style.display = 'none';
                    }
                }
            }

            newCell.innerHTML = dataArray[item];

            newRow.appendChild(newCell);
        }
        */

        // Add the row to the table
        parentElement.appendChild(newRow);

        // Update sum
        this.calculateColumn(this.focusField);

        // Reveal the table if needed
        if (   !this.tableShown
            || wholeTable.style.display == 'none')
        {
            wholeTable.style.display = 'block';
            this.tableShown = true;
        }

        return newRow;
    }

    /*
     * Register event for key press. Override in instance
     */
    this.convertEditorToRowKeyhandler = function(event)
    {
        var pressedKey;
        if (!event)
        {
            event = window.event;
        }
        pressedKey = event['keyCode'];
        if (pressedKey == 13)
        {
            // Enter pressed, convert to save button press
            this.convertEditorToRow();
        }
    }

    /*
     * Convert fields of the editor to a data row in table
     */
    this.convertEditorToRow = function()
    {
        // Get the values from the form
        var form = document.getElementById(this.formId);
        var values = Array();
        var names = Array();
        var formSaved = false;

        var inputs = form.getElementsByTagName('input');
        for (var i=0; i < inputs.length; i++)
        {
            if (inputs[i].type == 'checkbox')
            {
                if (inputs[i].checked == true)
                {
                    values[inputs[i].name] = 'yes';
                }
                else
                {
                    values[inputs[i].name] = 'no';
                }
            }
            else if (   inputs[i].type != 'submit'
                && inputs[i].type != 'button')
            {
                values[inputs[i].name] = inputs[i].value;
                names[i] = inputs[i].name;
            }
        }
        var inputs = form.getElementsByTagName('textarea');
        for (var i=0; i < inputs.length; i++)
        {
            values[inputs[i].name] = inputs[i].value;
            names[i] = inputs[i].name;
        }
        var inputs = form.getElementsByTagName('select');
        for (var i=0; i < inputs.length; i++)
        {
            //TODO: Make use selectedindex, I doubt IE can use this form
            values[inputs[i].name] = inputs[i].value;
            names[i] = inputs[i].name;
        }
        // TODO: Support other form field types

        // Consistency checks
        values = this.consistencyChecks(values);
        if (!values)
        {
            return;
        }

        // Find the row and alter it
        var tableBody = document.getElementById(this.tableDataId);
        var rows = tableBody.getElementsByTagName('tr');
        var rowFound = false;
        var selectedRow = false;
        var totalRows = rows.length;
        for (var i=0; i < totalRows; i++)
        {
            var cells = rows[i].getElementsByTagName('td');
            for (var ii=0; ii < cells.length; ii++)
            {
                if (  cells[ii].className == this.fieldPrefix+'guid'
                    && cells[ii].innerHTML == values[this.fieldPrefix+'guid'])
                {
                    rowFound = true;
                    selectedRow = rows[i];
                    break;
                }
            }
            if (rowFound)
            {
                // Update the values in the table
                var cells = selectedRow.getElementsByTagName('td');
                for (var iii=0; iii < cells.length; iii++)
                {
                    cells[iii].innerHTML = values[cells[iii].className];
                }
                ooAjaxSetClass(selectedRow, 'ajax_editable_row', false);
                //formSaved = true;
                // Update sum
                this.calculateColumn(this.focusField);

                // Update record via AJAX
                // TODO: Visualize status
                submitStr = '';
                shown = 0;
                for (field in values)
                {
                    if (shown > 0)
                    {
                        submitStr += '&'
                    }
                    fieldNormalized = new String(field).replace(this.fieldPrefix, 'midcom_helper_datamanager_field_');
                    submitStr += fieldNormalized + '=' + values[field];
                    shown++;
                }
                //ooDisplayMessage(submitStr, 'error');
                formSaved = ooAjaxPost(this.URL + 'update/', submitStr, selectedRow, false, this.instanceName+'.convertEditorToRowCleanUp');
                break;
            }
        }

        if (!rowFound)
        {
            // TODO: Figure out how to pass the GUID from the created report to the row and enable editing
            newRow = this.addTableRow(tableBody, values, totalRows, false);
            //formSaved = true;

            // Create record via AJAX
            submitStr = '';
            shown = 0;
            for (field in values)
            {
                if (shown > 0)
                {
                    submitStr += '&'
                }
                fieldNormalized = new String(field).replace(this.fieldPrefix, 'midcom_helper_datamanager_field_');
                submitStr += fieldNormalized + '=' + values[field];
                shown++;
            }
            formSaved = ooAjaxPost(this.URL + 'create/', submitStr, newRow, false, this.instanceName+'.convertEditorToRowCleanUp');
        }
    }

    /**
     * Clean up the editor after conversion to data table
     */
    this.convertEditorToRowCleanUp = function()
    {
        form = document.getElementById(this.formId);

        // Data saved, empty the form
        var inputs = form.getElementsByTagName('input');
        for (var i=0; i < inputs.length; i++)
        {
            if (   inputs[i].type != 'submit'
                && inputs[i].type != 'button')
            {
                if (inputs[i].name == this.fieldPrefix + 'date')
                {
                    var date = new Date()
                    // Adjust month
                    var monthString = new String(date.getMonth()+1);
                    if (monthString.length == 1)
                    {
                        monthString = '0'+monthString;
                    }
                    // Adjust day
                    var dayString = new String(date.getDate());
                    if (dayString.length == 1)
                    {
                        dayString = '0'+dayString;
                    }
                    inputs[i].value = date.getFullYear() + '-' + monthString + '-' + dayString;
                }
                else
                {
                    // Mozilla bug #236791, autocompletion causes an error here
                    inputs[i].value = '';
                }
            }
        }
        var inputs = form.getElementsByTagName('textarea');
        for (var i=0; i < inputs.length; i++)
        {
            inputs[i].value = '';
        }

        if (!this.allowCreate)
        {
            // Hide the form as creating new items is not allowed
            form.style.display = 'none';
            saveButton = document.getElementById(this.saveButtonId);
            saveButton.style.display = 'none';
        }

        this.loadedToEditor = false;
    }

    /*
     * Event handler for clicked content rows. Stub, override in instance to change "this" to actual instance as
     * within the EventListener interface, "this" would be the actual HTML element
     */
    this.convertRowToEditorEventhandler = function(event)
    {
        this.convertRowToEditor(event);
    }

    /**
     * Convert data row to editor
     */
    this.convertRowToEditor = function(event)
    {
        if (event.currentTarget)
        {
            element = event.currentTarget;
        }
        else
        {
            // IE compatibility
            element = event.srcElement.parentNode;
        }

        if (this.loadedToEditor == true)
        {
            // Save the previous first
            this.convertEditorToRow();
        }

        // Copy values from the cells
        var cells = element.getElementsByTagName('td');
        var values = new Array();
        for (var i=0; i < cells.length; i++)
        {
            values[cells[i].className] = cells[i].innerHTML;
        }

        // Set the values to the form

        var form = document.getElementById(this.formId);
        var inputs = form.getElementsByTagName('input');
        for (var i=0; i < inputs.length; i++)
        {
            if (inputs[i].type == 'checkbox')
            {
                if (values[inputs[i].name] == 'yes')
                {
                    inputs[i].checked = true;
                }
                else
                {
                    inputs[i].checked = false;
                }
            }
            else if (   inputs[i].type != 'submit'
                     && inputs[i].type != 'button')
            {
                inputs[i].value = values[inputs[i].name];

                if (inputs[i].name == this.focusField)
                {
                    inputs[i].focus();
                }
            }
        }
        var inputs = form.getElementsByTagName('textarea');
        for (var i=0; i < inputs.length; i++)
        {
            inputs[i].value = values[inputs[i].name];
        }
        var inputs = form.getElementsByTagName('select');
        for (var i=0; i < inputs.length; i++)
        {
            //TODO: make a selectedindex by value helper (I doubt IE can live with this form)
            inputs[i].value = values[inputs[i].name];
        }
        // TODO: Support other form field types

        ooAjaxSetClass(element, 'ajax_editable_row_editing', false);

        // Reveal the hidden form
        // TODO: Do this more elegantly
        form.style.display = 'block';
        saveButton = document.getElementById(this.saveButtonId);
        saveButton.style.display = 'block';

        this.loadedToEditor = true;
    }

    /**
     * Register keypress listeners and fetch content via AJAX
     */
    this.populateData = function()
    {
        if (this.dataPopulated == true)
        {
            // We have this data already
            return false;
        }

        // Populate the data items via AJAX
        if (this.URL)
        {
            //var me = this;
            var table = document.getElementById(this.tableDataId);
            ooAjaxGet(this.URL, false, table, this.instanceName+'.handleAjaxResults');
            this.dataPopulated = true;
        }

        // Hide the form if creating new items is not allowed
        if (!this.allowCreate)
        {
            form.style.display = 'none';
            saveButton = document.getElementById(this.saveButtonId);
            saveButton.style.display = 'none';
        }

        // Make Enter key submit the form
        var form = document.getElementById(this.formId);
        if (form.addEventListener)
        {
            // This is the correct DOM method
            form.addEventListener('keydown', this.convertEditorToRowKeyhandler, false);
        }
        else if (form.attachEvent)
        {
            // IE compatibility
            form.attachEvent('onkeydown', this.convertEditorToRowKeyhandler);
        }

        //alert(form);
        //form.addEventListener('keydown', this.convertEditorToRowKeyhandler, false);

    }

    /*
     * Abstract method for consistency checking of data. Override in child classes
     */
    this.consistencyChecks = function(values)
    {
        return values;
    }

    /*
     * Abstract method for parsing AJAX results of data and placing into table. Override in child classes
     */
    this.handleAjaxResults = function(resultList, element)
    {
        items = resultList.getElementsByTagName(this.ajaxResultElement);

        if (items.length == 0)
        {
            //No results, do something
            return false;
        }

        for (var i=0;i < items.length; i++)
        {
            // Iterate based on schema
            var item = new Array();
            for (var ii=0; ii < items[i].childNodes.length; ii++)
            {
                //ooAjaxDebugWalk(items[i].childNodes[ii]);
                if (items[i].childNodes[ii].nodeType == 1)
                {
                    if (items[i].childNodes[ii].firstChild)
                    {
                        item[this.fieldPrefix + items[i].childNodes[ii].nodeName] = items[i].childNodes[ii].firstChild.data;
                    }
                }
            }

            if (items[i].getAttribute('editable') == 'true')
            {
                this.addTableRow(document.getElementById(this.tableDataId), item);
            }
            else
            {
                this.addTableRow(document.getElementById(this.tableDataId), item, i, false);
            }
        }
    }
}