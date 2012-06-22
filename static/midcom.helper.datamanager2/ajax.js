/**
 * Datamanager 2 AJAX on-site editing system
 * Henri Bergius, henri.bergius@iki.fi
 */
var dm2AjaxEditor = Class.create();

dm2AjaxEditor.prototype = {

    initialize: function(formId, creationMode, windowMode, wideMode)
    {
        // This is the constructor
        
        // Set sensible defaults    
        this.oldState = 'view';
        this.newState = 'view';
        
        this.formId = formId;
        this.windowMode = windowMode;
        this.wideMode = wideMode;
        
        // Temporary storage for form values
        this.formValues = {};
        
        // Dimensions of the AJAX fields
        this.fieldDimensions = {};

        // Dimensions of the whole editable area
        this.formDimensions = {};
        this.emptyDimensions();
                
        this.blinder = false;
        this.formArea = $(this.formId + '_area');
        
        if (   creationMode
            && this.formArea)
        {
            this.creationMode = true;
            this.reserveIdentifier = this.formId + '_reserve';
            this.enableCreation();
        }  
        else
        {
            this.makeEditable();
        }
    },

    /**
     * Register a listener to the creation button
     */
    enableCreation: function()
    {
        this.formArea.style.display = 'none';
        var button = $(this.formId + '_button');
        if (button.length === 0)
        {
            return;
        }
        button.onclick = this.showCreation.bindAsEventListener(this);
    },

    /**
     * Display the creation area and fetch fields into it
     */
    showCreation: function()
    {
        if (!this.formArea)
        {
            return;
        }
        //Effect.Appear(area);
        this.cloneCreationFields();
        if (this.formArea.tagName === 'div')
        {
            this.formArea.style.display = 'block';
        }
        else
        {
            this.formArea.style.display = '';
        }
        this.emptyDimensions();
        this.makeEditable();
        this.fetchFields();
    },

    /**
     * Clone the creation form into reserve
     */
    cloneCreationFields: function()
    {
        var newArea = this.formArea.cloneNode(true),
        id,
        editableFields,
        i;
        
        // We rename the new area as a "holding" area
        newArea.id = this.reserveIdentifier + '_area';
        newArea.style.display = 'none';
        editableFields = document.getElementsByClassName(this.formId, newArea);
        for (i = 0; i < editableFields.length; i++) 
        {
            id = editableFields[i].getAttribute('id');
            editableFields[i].className = this.reserveIdentifier;
            eval("editableFields[i].id = id.replace(/" + this.formId + "/, this.reserveIdentifier);");
        }
        try
        {
            this.formArea.parentNode.appendChild(newArea);
        }
        catch (error)
        {
            console.log(error);
        }
    },
    
    /**
     * Switch the exiting creation form into a regular editing form, and make
     * the reserve creation form the new creation form
     */
    switchCreationFields: function(newIdentifier)
    {
        // Change the current creation form into editing form IDs
        this.formArea.id = newIdentifier + '_area'; 
        var editableFields = document.getElementsByClassName(this.formId);
        for (var i = 0; i < editableFields.length; i++) 
        {
            id = editableFields[i].getAttribute('id');
            editableFields[i].className = newIdentifier;
            eval("editableFields[i].id = id.replace(/" + this.formId + "/, newIdentifier);");
        }
        
        // And register a new AJAX handler for it
        eval("var dm2AjaxEditor_" + newIdentifier + " = new dm2AjaxEditor('" + newIdentifier + "', false, " + this.windowMode + ");");

        // Change the reserve form into a creation form
        var reserveArea = $(this.reserveIdentifier + '_area');
        reserveArea.id = this.formId + '_area'; 
        editableFields = document.getElementsByClassName(this.reserveIdentifier);
        for (i = 0; i < editableFields.length; i++) 
        {
            id = editableFields[i].getAttribute('id');
            editableFields[i].className = this.formId;
            eval("editableFields[i].id = id.replace(/" +this.reserveIdentifier + "/, this.formId);");
        }
                
        // And then start watching the cloned area instead
        this.formArea = $(this.formId + '_area');
        this.enableCreation();
    },
    
    /**
     * Find all places where data from this DM2 instance is shown, mark them editable and
     * register a listener to them
     */
    makeEditable: function()
    {
        editableFields = document.getElementsByClassName(this.formId);
        
        for (i = 0; i < editableFields.length; i++) 
        {
            id = editableFields[i].getAttribute('id');
            
            //ooAjaxDebugWalk(editableFields[i]);
            
            this.calculateDimensions(editableFields[i]);

            // Make the field editable
            editableFields[i].ondblclick = this.fetchFields.bindAsEventListener(this);
            Element.addClassName(editableFields[i], 'ajax_editable');
            
            /*// Add an edit marker to the field
            var editMarker = '<span class="ajax_edit_button" style="display: none;" id="' + id + '_editbutton">Edit</span>';
            new Insertion.Bottom(editableFields[i], editMarker);  
            $(id + '_editbutton').onclick = this.fetchFields.bindAsEventListener(this);
            Event.observe(editableFields[i], 'mouseover', function(){toggleEditMarker($(id))}, false);
            Event.observe(editableFields[i], 'mouseout', function(){toggleEditMarker($(id))}, false);
            //editableFields[i].onmouseover = this.toggleEditMarker.bindAsEventListener(editableFields[i]);
            //editableFields[i].onmouseout = this.toggleEditMarker.bindAsEventListener(editableFields[i]);
            */
        }
        
        if (   this.formArea
            && !this.creationMode)
        {   
            // We're making a composite editable, we could add styling here
        }
        
        var formBlinder = '<div id="' + this.formId + '_blind" />';
        new Insertion.Bottom(document.lastChild.lastChild, formBlinder);
        this.blinder = $( this.formId + '_blind');        
        blinderStyles = { 
            'position'  : 'absolute',
            'left'      : this.formDimensions.x1 + 'px',
            'top'       : this.formDimensions.y1 + 'px',
            'width'     : (this.formDimensions.x2 - this.formDimensions.x1) + 'px',
            'height'    : (this.formDimensions.y2 - this.formDimensions.y1) + 'px',
            'background-color': '#000000',
            'color'     : 'white',
            'text-align': 'center',
            'font-family': 'Helvetica, sans-serif',
            'padding-top'           : '40px',
            'background-image'  : 'url("/midcom-static/stock-icons/32x32/ajax-loading-black.gif")',
            'background-repeat' : 'no-repeat',
            'background-position': 'top center',
            '-moz-border-radius': '8px',
            'border-radius': '8px',
            'z-index'   : '999999',
            'display'   : 'none'           
        };
        stylesHash = $H(blinderStyles);
        Element.setStyle(this.blinder, stylesHash);
        this.blinder.innerHTML = 'Loading...';
    },
    
    /**
     * Empty the field dimensions
     */
    emptyDimensions: function()
    {
        this.formDimensions.x1 = false;
        this.formDimensions.y1 = false;
        this.formDimensions.x2 = false;
        this.formDimensions.y2 = false;
    },

    /**
     * Read original dimensions of the fields
     */
    getFieldDimensions: function(field)
    {
        fieldId = field.getAttribute('id');
        if (!this.fieldDimensions[fieldId])
        {
            // Get dimension so we can ensure the fields don't overlap it
            field.style.display = 'block';          
            dimensions = Element.getDimensions(field);
            this.fieldDimensions[fieldId] = dimensions;
            field.style.display = 'inline';
        }
        return this.fieldDimensions[fieldId];
    },
    
    /**
     * Calculate dimensions for the whole form
     */
    calculateDimensions: function(field)
    {
        realOffsets = Position.cumulativeOffset(field);
        var x1 = realOffsets[0];
        var y1 = realOffsets[1];
        fieldSizes = this.getFieldDimensions(field);
        var x2 = x1 + fieldSizes.width;
        var y2 = y1 + fieldSizes.height;
        
        if (   !this.formDimensions.x1
            || x1 < this.formDimensions.x1)
        {
            this.formDimensions.x1 = x1;
        }   
        if (   !this.formDimensions.y1
            || y1 < this.formDimensions.y1)
        {
            this.formDimensions.y1 = y1;
        }
        if (   !this.formDimensions.x2
            || x2 > this.formDimensions.x2)
        {
            this.formDimensions.x2 = x2;
        }
        if (   !this.formDimensions.y2
            || y2 > this.formDimensions.y2)
        {
            this.formDimensions.y2 = y2;
        }
    },

    /**
     * Read the values of all form fields
     */
    getFieldValues: function()
    {
        formFields = document.getElementsByClassName(this.formId);
    
        for (i = 0; i < formFields.length; i++) 
        {
            // Handle the visual identifiers
            Element.removeClassName(formFields[i], 'ajax_editable');
            Element.addClassName(formFields[i], 'ajax_saving');
            
            // Load the contents
            for (ii = 0; ii < formFields[i].childNodes.length; ii++) 
            {
                if (formFields[i].childNodes[ii].tagName == 'INPUT') 
                {
                    if (formFields[i].childNodes[ii].type == 'text') 
                    {
                        this.formValues[formFields[i].childNodes[ii].name] = formFields[i].childNodes[ii].value;
                    }
                    if (formFields[i].childNodes[ii].type == 'checkbox') 
                    {
                        if (formFields[i].childNodes[ii].checked) 
                        {
                            this.formValues[formFields[i].childNodes[ii].name] = 1;
                        } 
                        else 
                        {
                            this.formValues[formFields[i].childNodes[ii].name] = 0;
                        }
                    }
                    if (formFields[i].childNodes[ii].type == 'radio') 
                    {
                        if (formFields[i].childNodes[ii].checked) 
                        {
                            this.formValues[formFields[i].childNodes[ii].name] = formFields[i].childNodes[ii].value;
                        } 
                    }
                }
                if (formFields[i].childNodes[ii].tagName == 'SELECT') 
                {
                    this.formValues[formFields[i].childNodes[ii].name] = formFields[i].childNodes[ii].options[formFields[i].childNodes[ii].selectedIndex].value;
                }
                if (formFields[i].childNodes[ii].tagName == 'TEXTAREA') 
                {
                    if (formFields[i].childNodes[ii].className == 'tinymce')
                    {
                        tinyMCE.triggerSave(true,true);
                    }
                    this.formValues[formFields[i].childNodes[ii].name] = formFields[i].childNodes[ii].value;
                }
            }
        }    
        this.disableTinyMCE();            
    },

    /**
     * Set the values of all form fields into the ones in memory
     */
    setFieldValues: function()
    {
        formFields = document.getElementsByClassName(this.formId);
        
        for (i = 0; i < formFields.length; i++) 
        {            
            for (ii = 0; ii < formFields[i].childNodes.length; ii++) 
            {
                if (formFields[i].childNodes[ii].tagName == 'INPUT') 
                {
                    if (formFields[i].childNodes[ii].type == 'text') 
                    {
                        formFields[i].childNodes[ii].value = this.formValues[formFields[i].childNodes[ii].name];
                    }
                    if (formFields[i].childNodes[ii].type == 'checkbox') 
                    {
                        if (this.formValues[formFields[i].childNodes[ii].name])
                        {
                            formFields[i].childNodes[ii].checked = true;
                        }
                        else 
                        {
                            formFields[i].childNodes[ii].checked = false;
                        }
                    }
                }
                if (formFields[i].childNodes[ii].tagName == 'SELECT') 
                {
                    for (iii = 0; iii < formFields[i].childNodes[ii].options.length; iii++) 
                    {
                        if (formFields[i].childNodes[ii].options[iii].value == this.formValues[formFields[i].childNodes[ii].name])
                        {
                            formFields[i].childNodes[ii].selectedIndex = iii;                           
                        }
                    }
                }
                if (formFields[i].childNodes[ii].tagName == 'TEXTAREA') 
                {
                    formFields[i].childNodes[ii].value = this.formValues[formFields[i].childNodes[ii].name];
                }
            }
        }
    },
    
    /**
     * Make the form values into a hash, and then convert to properly escaped HTTP query string
     */
    fieldValuesToQueryString: function()
    {
        // Add the request type here so DM2 will notice it
        this.formValues[this.formId + '_' + this.newState] = 1;        
        this.formValues['midcom_helper_datamanager2_' + this.newState] = 1;
        
        // And remove the old state if set so DM2 doesn't get confused by them
        if (this.newState != this.oldState)
        {
            delete this.formValues['midcom_helper_datamanager2_' + this.oldState];    
            delete this.formValues[this.formId + '_' + this.oldState];
        }
                
        // QuickForm requires its own identifier to be present
        this.formValues['_qf__' + this.formId + '_qf'] = 1; 
    
        // Then convert the whole set of values to query string
        var h = $H(this.formValues);
        return h.toQueryString();
    },
    
    /**
     * Handle a failed AJAX request
     */
    failedRequest: function()
    {
        new protoGrowl({type: 'error', title: 'Datamanager', message: 'HTTP request failed'});                    
        this.cancelFields();
    },

    /**
     * Post contents to server and get the preview version back. Content is not saved at this point so we must keep the temp storage
     */
    previewFields: function()
    {
        this.removeToolbar();
            
        if (this.windowMode)
        {
            Element.removeClassName(this.formId + '_area', 'ajax_editable_window');
        }
            
        this.blinder.innerHTML = 'Rendering preview...';  
        Effect.Appear(this.blinder);    
        
        this.newState = 'preview';
        
        // Read values from form
        this.getFieldValues();
        
        // Send the form values to server for preview rendering
        queryString = this.fieldValuesToQueryString();
        var ajaxRequest = new Ajax.Request(
            location.href, 
            {
                method: 'post', 
                parameters: queryString, 
                onComplete: this.renderFields.bind(this)
            }
        );
    },

    /**
     * Save the form fields into server and get rendered version back.
     */
    saveFields: function()
    {
        this.removeToolbar();
            
        if (this.windowMode)
        {
            Element.removeClassName(this.formId + '_area', 'ajax_editable_window');
        }
            
        this.blinder.innerHTML = 'Saving...';
        Effect.Appear(this.blinder); 

        this.newState = 'save';
        
        if (this.oldState == 'preview')
        {
            // We got here from preview, set query string values from previewed content instead of content fetched from DM2
            queryString = this.fieldValuesToQueryString();
        }   
        else
        {     
            // Regular save, read form fields and generate query string
            this.getFieldValues();
            queryString = this.fieldValuesToQueryString();           
        }
        var ajaxRequest = new Ajax.Request(
            location.href, 
            {
                method: 'post', 
                parameters: queryString, 
                onComplete: this.renderFields.bind(this),
                onFailure: this.failedRequest.bind(this)
            }
        );    
    },
    
    /**
     * Get the display fields from the server
     */
    cancelFields: function()
    {
        this.removeToolbar();
        if (this.windowMode)
        {
            Element.removeClassName(this.formId + '_area', 'ajax_editable_window');
        }
    
        this.blinder.innerHTML = 'Restoring content...';
            
        Effect.Appear(this.blinder);    
        this.newState = 'view';
        var ajaxRequest = new Ajax.Request(location.href, {method: 'get', parameters: this.formId + '_cancel=1', onComplete: this.renderFields.bind(this)});
    },

    /**
     * Get the form fields from the server
     */
    fetchFields: function()
    {
        if (this.newState == 'edit')
        {
            // Don't load the editor again if we already have it
            return;
        }
        this.removeToolbar();
        this.blinder.innerHTML = 'Loading...';    
    
        Effect.Appear(this.blinder);
        this.newState = 'edit';

        var ajaxRequest = new Ajax.Request(location.href, {method: 'get', parameters: this.formId + '_edit=1', onComplete: this.renderFields.bind(this)});
    },
    
    /**
     * This is where we end up after an AJAX call, render view or form accordingly to this.oldState
     */
    renderFields: function(ajaxRequest)
    {
        Effect.Fade(this.blinder);
        forms =  ajaxRequest.responseXML.getElementsByTagName('form');
        form = forms[0];

        exitCode = form.getAttribute('exitcode');
        if (exitCode == 'save')
        {
            new protoGrowl({type: 'ok', title: 'Datamanager', message: 'Form saved successfully'});                    
        }
        
        formIdentifier = form.getAttribute('id');
        newIdentifier = form.getAttribute('new_identifier');
        
        // Handle form validation errors
        errors = form.getElementsByTagName('error');
        if (errors.length > 0)
        {
            if (this.newState == 'save')
            {
                // Saving failed and we had errors, switch back to editing
                this.newState = 'edit';
            }
            for (var i=0;i < errors.length; i++)
            {
                new protoGrowl({type: 'error', title: 'Datamanager', message: errors[i].firstChild.nodeValue});
            }
        }

        // Process fields
        fields = form.getElementsByTagName('field');    
        lastFormField = null;
        for (var i=0; i < fields.length; i++)
        {
            
            // Match the form field gotten from AJAX request to field in HTML page
            formField = $(formIdentifier + '_' + fields[i].getAttribute('name'));
            if (formField)
            {
                // Which state did we have prior to this call, set it to the new state
                switch (this.newState)
                {
                    case 'edit':                         
                        // We have rendered regular content that was being listed to, stop listening
                        if (this.oldState != 'save')
                        {
                            formField.onclick = null;
                        }
                        break
                    case 'preview':                        
                    case 'save':
                    case 'cancel':
                        // We've saved the content, go back to view
                        formField.ondblclick = this.fetchFields.bindAsEventListener(this);
                        //Event.observe(formField, 'click', this.editListener.bind(this), false);
                        Element.addClassName(formField, 'ajax_editable');
                        break
                }
                
                // Get dimension so we can ensure the fields don't overlap it
                var dimensions = this.getFieldDimensions(formField);
                                
                // Override the field in page with field from AJAX call
                formField.innerHTML = '';
                formField.innerHTML += fields[i].firstChild.nodeValue;

                // Set up fields so that they aren't too big
                for (ii = 0; ii < formField.childNodes.length; ii++) 
                {
                    switch (formField.childNodes[ii].tagName)
                    {
                        case 'TEXTAREA':
                            if (   this.windowMode)
                            {
                                formField.childNodes[ii].style.width = 460 + 'px';
                            }
                            else if(this.wideMode)
                            {
                                formField.childNodes[ii].style.width = 200 + 'px';
                            }
                            else
                            {
                                formField.childNodes[ii].style.width = dimensions.width + 'px';
                                //formField.childNodes[ii].style.height = dimensions.height + 'px;';
                            }
                            //formField.childNodes[ii].cols = '';
                            break
                        case 'INPUT':
                            if (formField.childNodes[ii].className == 'date')
                            {
                                if (   this.windowMode
                                    || this.wideMode)
                                {
                                    formField.childNodes[ii].style.width = 90 + 'px';
                                }
                                else if (formField.childNodes[ii].name == 'date')
                                {
                                    formField.childNodes[ii].style.width = dimensions.width - 30 + 'px';
                                }
                            }
                            else if (   formField.childNodes[ii].className != 'checkbox'
                                     && formField.childNodes[ii].className != 'radiobutton')
                            {
                                if (dimensions.width < 10)
                                {
                                    dimensions.width = 40;
                                }                               
                                formField.childNodes[ii].style.width = dimensions.width + 'px';
                            }
                            break
                    }
                }
                lastFormField = formField;
            }
        }
      
        if (lastFormField)
        {
            if (   this.creationMode
                && exitCode == 'save')
            {
                // Now we have populated the data, switch the forms around
                this.switchCreationFields(newIdentifier);
                this.newState = 'view';
                return;
            }
            // Populate toolbar accordingly to the state
            switch (this.newState)
            {
                case 'preview':      
                    // We're in preview state, options are "Edit" and "Save
                    var buttons = '<div class="midcom_helper_datamanager2_ajax_toolbar" id="' + formIdentifier + '_toolbar">';
                    buttons    += '  <input id="' + formIdentifier + '_edit" class="edit" type="button" value="Edit" />';
                    buttons    += '  <input id="' + formIdentifier + '_save" class="save" accesskey="s" type="button" value="Save" />';
                    buttons    += '</div>';
                    new Insertion.After(lastFormField, buttons);    
                    $(formIdentifier + '_edit').onclick = this.fetchFields.bindAsEventListener(this);
                    $(formIdentifier + '_save').onclick = this.saveFields.bindAsEventListener(this);           
                    
                    // Position the toolbar
                    toolbar = $(formIdentifier + '_toolbar');
                    if (toolbar)
                    {
                        toolbarStyles = { 
                            'position': 'absolute',
                            'left'    : this.formDimensions.x1 + 'px',
                            'top'     : this.formDimensions.y1 - (toolbar.offsetHeight + 6) +  'px',
                            'display' : 'none',
                            'z-index' : '999999'
                        };
                        stylesHash = $H(toolbarStyles);
                        Element.setStyle(toolbar, stylesHash);
                        Effect.Appear(toolbar);
                    }                    
                    
                    break
                case 'edit':
                    // We're in edit state, options are "Preview" and "Save"
                    if (this.creationMode)
                    {
                        var buttons = '<div class="midcom_helper_datamanager2_ajax_toolbar" id="' + formIdentifier + '_toolbar">';
                        buttons    += '  <input id="' + formIdentifier + '_save" class="save" accesskey="s" type="button" value="Save" />';
                        buttons    += '</div>';
                        
                        if (this.windowMode)
                        {
                            // With windows insert the toolbar in the area
                            new Insertion.Top(this.formId + '_area', buttons);
                        }
                        else
                        {                        
                            new Insertion.Bottom(document.lastChild.lastChild, buttons);
                        }
                        $(formIdentifier + '_save').onclick = this.saveFields.bindAsEventListener(this);
                    }
                    else
                    {
                        var buttons = '<div class="midcom_helper_datamanager2_ajax_toolbar" id="' + formIdentifier + '_toolbar">';
                        buttons    += '  <input id="' + formIdentifier + '_preview" class="preview" accesskey="r" type="button" value="Preview" />';
                        buttons    += '  <input id="' + formIdentifier + '_save" class="save" accesskey="s" type="button" value="Save" />';
                        buttons    += '  <input id="' + formIdentifier + '_cancel" class="cancel" accesskey="c" type="button" value="Cancel" />';
                        buttons    += '  <input id="' + formIdentifier + '_delete" class="delete" type="button" value="Delete" />';                        
                        buttons    += '</div>';
                        
                        if (this.windowMode)
                        {
                            // With windows insert the toolbar in the area
                            new Insertion.Top(this.formId + '_area', buttons);
                        }
                        else
                        {
                            // Insert the toolbar after last form element
                            new Insertion.Bottom(document.lastChild.lastChild, buttons);
                        }

                        $(formIdentifier + '_preview').onclick = this.previewFields.bindAsEventListener(this);
                        $(formIdentifier + '_save').onclick = this.saveFields.bindAsEventListener(this);
                        $(formIdentifier + '_cancel').onclick = this.cancelFields.bindAsEventListener(this);
                        $(formIdentifier + '_delete').onclick = this.deleteFields.bindAsEventListener(this); 
                    }
                    
                    // We got here from preview, set form values from previewed content instead of content fetched from DM2
                    if (   this.oldState == 'preview'
                        || this.oldState == 'save')
                    {
                        this.setFieldValues();
                    }

                    // Position the toolbar
                    toolbar = $(formIdentifier + '_toolbar');
                    if (toolbar)
                    {
                        if (this.windowMode)
                        {
                            toolbarStyles = { 
                                'display' : 'none'
                            };                        
                        }
                        else
                        {
                            toolbarStyles = { 
                                'position': 'absolute',
                                'left'    : this.formDimensions.x1 + 'px',
                                'top'     : this.formDimensions.y1 - (toolbar.offsetHeight + 6) +  'px',
                                'display' : 'none',
                                'z-index' : '999999'
                            };
                        }
                        stylesHash = $H(toolbarStyles);
                        Element.setStyle(toolbar, stylesHash);
                        Effect.Appear(toolbar);
                    }
                    
                    // Load TinyMCE if applicable
                    if (tinyMCE)
                    {
                        this.enableTinyMCE();
                    }
                    
                    // Make the editor a window if needed
                    if (this.windowMode)
                    {
                        Element.addClassName(this.formId + '_area', 'ajax_editable_window');
                        /*
                        contentWin = new Window('content_win', {
                            className: 'alphacube',
                            resizable: true, 
                            hideEffect: Element.hide, 
                            showEffect: Element.show, 
                            minWidth: 10
                        });
                        contentWin.setContent(formIdentifier, true, true);
                        contentWin.toFront(); 
                        contentWin.setDestroyOnClose(); 
                        contentWin.show();
                        */                   
                    }
                    
                    break         
            }
        }
        
        // Move into the new correct state
        this.oldState = this.newState;
    },

    /**
     * Delete the object
     */
    deleteFields: function()
    {
        this.removeToolbar();
            
        if (this.windowMode)
        {
            Element.removeClassName(this.formId + '_area', 'ajax_editable_window');
        }
            
        this.blinder.innerHTML = 'Deleting...';
            
        Effect.Appear(this.blinder);    
        this.newState = 'delete';
        var ajaxRequest = new Ajax.Request(location.href, {method: 'post', parameters: this.formId + '_delete=1', onComplete: this.renderDeleteFields.bind(this)});
    },
    
    /**
     * This is where we end up after an AJAX call, render view or form accordingly to this.oldState
     */
    renderDeleteFields: function(ajaxRequest)
    {
        Effect.Fade(this.blinder);
        response =  ajaxRequest.responseText;
        if (response == 'Object deleted')
        {
            new protoGrowl({type: 'ok', title: 'Datamanager', message: 'Object deleted successfully'});
            Effect.Fade(this.formArea);
        }
        else
        {
            new protoGrowl({type: 'error', title: 'Datamanager', message: 'Deletion failed, reason: '+response});
        }
    },

    /**
     * Remove old toolbar
     */
    removeToolbar: function()
    {
        // Remove old toolbars
        oldToolbar = $(this.formId + '_toolbar');
        if (oldToolbar)
        {
            Element.remove(oldToolbar);
        }    
    },
    
    /**
     * Start up TinyMCE editors for all fields that need them
     */
    enableTinyMCE: function()
    {
        tinyFields = document.getElementsByClassName('tinymce');
        for (i = 0; i < tinyFields.length; i++) 
        {
            parentNode = tinyFields[i].parentNode;
            
            if (!this.windowMode)
            {
                dimensions = this.getFieldDimensions(parentNode);
                tinyMCE.settings['width'] = dimensions.width;
            }
            //tinyMCE.settings['theme'] = 'simple';
            tinyMCE.execCommand('mceAddControl',false, tinyFields[i].getAttribute('id'));

            if (!this.windowMode)
            {
                editors = document.getElementsByClassName('mceEditor');
                for (ii = 0; ii < editors.length; ii++) 
                {            
                    editors[ii].width = dimensions.width;
                    editors[ii].width = dimensions.width+'px';
                }            
            }
        }    
    },

    /**
     * End the TinyMCE editors of all fields
     */
    disableTinyMCE: function()
    {
        tinyFields = document.getElementsByClassName('tinymce');
        for (i = 0; i < tinyFields.length; i++) 
        {
            if (tinyMCE.get(tinyFields[i].getAttribute('id')))
            {
                tinyMCE.execCommand('mceRemoveControl', true, tinyFields[i].getAttribute('id'));
            }
        }
    }
};