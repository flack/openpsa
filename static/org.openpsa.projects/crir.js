/*
    Prototyped 26.9.2006
    Edvard Immonen jemi[at]iki.fi
    jemi.iki.fi

    ****

    Based on:

    ****

    CRIR - Checkbox & Radio Input Replacement
    Author: Chris Erwin (me[at]chriserwin.com)
    www.chriserwin.com/scripts/crir/

    Updated July 27, 2006.
    Jesse Gavin added the AddEvent function to initialize
    the script. He also converted the script to JSON format.

    Updated July 30, 2006.
    Added the ability to tab to elements and use the spacebar
    to check the input element. This bit of functionality was
    based on a tip from Adam Burmister.

    *****
*/

crir = {

    // initialize it
    init: function() {
        // all the label tags in this document
        var labels = document.getElementsByTagName('label');

        // prototype arraying the labels
        labels = $A(labels);

        // arraying the inputs
        var inputs = new Array();

        // find all the links that have their labels set
        labels = labels.findAll( function(node){
            // get the input element based on the for attribute of the label tag
            if (node.getAttributeNode('for') && node.getAttributeNode('for').value != '') {
                // the link to the form element
                var linkEl = node.getAttributeNode('for').value;
                // the form element
                var inEl = $(linkEl);

                // add the element to the inputs array
                inputs.push(inEl);

                return true;
            }
            else {
                return false;
            }
        });

        // prototype arraying the inputs
        inputs = $A(inputs);

        /*
        labels = array with label elements
        inputs = array with input elements
        */

        // now we have a array with inputs that have the for attribute
        inputs.each( function(node)
        {
            // find all the elements that are supposed to be hidden
            // this means that their class is "crirHiddenJS"
            if (node.className == 'crirHiddenJS') {

                // changing the classname
                node.className = 'crirHidden';

                // get the type of the element (radio or checkbox) [no checking of other]
                inputElementType = node.getAttributeNode('type').value;

                // add the appropriate event listener to the input element
                if (inputElementType == "checkbox") {
                    Event.observe(node, 'click', crir.toggleCheckboxLabel, false);
                }
                else {
                    Event.observe(node, 'click', crir.toggleRadioLabel, false);
                }

                // returns the label element
                var hit = labels.find( function(label){
                    return (label.getAttributeNode('for').value == node.id);
                });

                // change the labels according to the checkbox value
                if (node.checked) {
                    if (inputElementType == 'checkbox') {
                        hit.className = 'checkbox_checked';
                    }
                    else {
                        hit.className = 'radio_checked';
                    }
                }
                else {
                    if (inputElementType == 'checkbox') {
                        hit.className = 'checkbox_unchecked';
                    }
                    else {
                        hit.className = 'radio_unchecked';
                    }
                }
            }
            // this so even if a radio is not hidden but belongs to a group of hidden radios it will still work.
            else if (node.getAttributeNode('type').value == 'radio') {

                // get the inputs id
                var inputID = node.getAttribute("id");

                // get the label with the for attribute that matches inputID
                // returns the label element
                var hit = labels.find( function(label){
                    return (label.getAttributeNode('for').value == inputID);
                });

                // label element
                hit.onclick = crir.toggleRadioLabel;

                // input element
                node.onclick = crir.toggleRadioLabel;
            }
        });
    },

    /*
    * findLabel
    *
    * returns the label element with its for == inputElementID
    */
    findLabel: function (inputElementID) {
        arrLabels = $A( document.getElementsByTagName('label') );

        // get the label with the for attribute that matches inputID
        // returns the label element
        var hit = arrLabels.find( function(label){
            return (label.getAttributeNode('for').value == inputElementID);
        });

        return hit;
    },

    /*
    * toggleCheckboxLabel
    *
    * toggles the checkbox elements labels class
    */
    toggleCheckboxLabel: function () {

        labelElement = crir.findLabel( this.getAttributeNode('id').value );

        if(labelElement.className == 'checkbox_checked') {
            labelElement.className = "checkbox_unchecked";
        }
        else {
            labelElement.className = "checkbox_checked";
        }
    },

    /*
    * toggleRadioLabel
    *
    * toggles the radio elements labels class
    */
    toggleRadioLabel: function () {
        clickedLabelElement = crir.findLabel(this.getAttributeNode('id').value);

        clickedInputElement = this;
        clickedInputElementName = clickedInputElement.getAttributeNode('name').value;

        var arrInputs = $A( document.getElementsByTagName('input') );

        // uncheck (label class) all radios in the same group
        //for (var i=0; i<arrInputs.length; i++) {
        arrInputs.each( function(node){

            var inputElementType = node.getAttributeNode('type').value;
            var inputElementName = node.getAttributeNode('name').value;
            var inputElementClass = node.className;

            // find radio buttons with the same 'name' as the one we've changed and have a class of chkHidden
            // and then set them to unchecked
            if (inputElementType == 'radio' &&  inputElementName == clickedInputElementName && inputElementClass == 'crirHidden') {
                inputElementID = node.getAttributeNode('id').value;
                labelElement = crir.findLabel(inputElementID);
                labelElement.className = 'radio_unchecked';
            }
        });

        // if the radio clicked is hidden set the label to checked
        if (clickedInputElement.className == 'crirHidden') {
            clickedLabelElement.className = 'radio_checked';
        }
    }
}

Event.observe(window, 'load', crir.init, false);