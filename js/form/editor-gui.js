$.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner = 
    '<div class="loading-spinner" style="width: 200px; margin-left: -100px;">' +
        '<div class="progress progress-striped active">' +
            '<div class="progress-bar" style="width: 100%;"></div>' +
        '</div>' +
    '</div>';

// This function creates the environment for the editor.
// The argument is optional. If called, the editor will
// load with the provided state. Only JSON can be interpreted
// and if no valid argument is given, the editor will be
// instantiated as a blank editor.
// function signature: main([state]) return null
function main( state ){

	// Global variables
	// List of global variables
	window.listOfElements = [];
	idCount	= 0;

	window.toText = {
		text_area : "Text Area",
		text_field : "Text Field",
		checkbox : "Check Box Group",
		radio : "Radio Button Group"
	};

	window.imagePath = {
		text_area : "./images/formGUI/form_input_textarea.png",
		text_field : "./images/formGUI/form_input_text.png",
		checkbox : "./images/formGUI/form_input_checkbox.png",
		radio : "./images/formGUI/form_input_radio.png"
	};

	// This adds functionality to the control buttons.
	newFieldFunc();

	// If the state isn't provided, then setup a blank editor instance
	if( !state ){
		// We're done, prevent the function from further execution.
		return;
	}

	// There was a state provided. First let's make sure it's a valid state.
	try{
		state = jQuery.parseJSON(state);

		// Let's load all the elements in the given state.
		// Because we can't directly query how many elements are in the object, we're going to run while the element exists.
		var i = 0;
		while( state[i] ){
			loadElement( state[i] );
			i++;
		}

	} catch( e ){
		// There was an error trying to process the JSON. Alert the user and load a blank editor instance.
	}
}
// Runs main when the page is done loading
// Setup any type of environment here.
$(document).ready ( function(){
	main();
});

// Debbuging!
function rLog( message ){
	debug = 0;
	if( debug == 1 ){
		document.getElementById("log").style.visibility="visible";
		document.getElementById("log").innerHTML += message+"<br>";
	}
}

// This function prompts the user for additional information to submit the form.
// The requested information is the form name and the form description
function addInfo( check ){
	// If we are checking the form data for completion, then call addInfo(true)
	var form_name = getElement("form_name");
	var form_description = getElement("form_description");

	// Pass Flag
	var errors = ['',''];
	var noErrors = true;

	if( check ){

		// Clear everything out!
		$("#addInfo_error").html("");
		$("#addInfo_error").hide();

		// Check each value
		if( form_name.value === "" ){
			$("#form_name").parent().addClass("has-error");
			errors[0] = "Please fill out the name field.";
		} else {
			$("#form_name").parent().removeClass("has-error");
		}

		if( form_description.value === "" ){
			$("#form_description").parent().addClass("has-error");
			errors[1] = "Please fill out the description field.";
		} else {
			$("#form_description").parent().removeClass("has-error");
		}

		// Add the errors if there are any...
		for( var i = 0; i < errors.length; i++ ){
			if( errors[i] === '' ){
				continue;
			} else {
				noErrors = false;
				$("#addInfo_error").append(errors[i]+"<br>");
			}
		}

		// If there are no errors, submit the form
		if( noErrors ){
			saveForm();
		} else {
			// There are errors, show them!
			$("#addInfo_error").show();
		}

	} else {
		$("#modal_addInfo").modal("show");
	}
}

// This function clears the form completely
// It will reset the form with no elements and remove the count.
function clearForm(){
	warning = confirm("Are you sure you want to clear the form? (Cannot undo)");
	
	// If the user decides NOT to clear, then do nothing!
	if( !warning ){
		return;
	}

	// If they do...
	// Delete all the elements listed in our list of elements
	for( var i = 0; i<listOfElements.length; i++ ){
		if( listOfElements[i] !== undefined ){
			// Delete the HTML first.
			$("#formElement_"+i).remove();

			// Now delete the element from our element list.
			delete listOfElements[i];
		}
	}
}

// This function saves the state of the form.
// It will send the current state to the server.
function saveForm(){

	// Show loading status...
	// In this case, we want to disable the old modal and put up a new one!
	$("#modal_formReview").modal("loading");
	$("#modal_saveFormButton").attr({
		disabled: 'dsiabled'
	});

	// Now display the new modal.
	$("#modal_saveForm").modal("show");


	// Grab the state
	var dataObj = dataExport();

	// Also grab the form details
	var form_name = getElement("form_name").value;
	var form_description = getElement("form_description").value;

	$.ajax({
		url: '/CIE/component/form/processForm.php',
		type: 'POST',
		dataType: 'html',
		data: {dataObject: dataObj,form_name: form_name, form_description: form_description},
	})
	.done(function(response) {
		// We're going to redirect them to the form manager that is open to the current form.
		$("#modal_saveForm").modal("hide");
		$("#modal_formReview").modal("hide");
		$("#modal_addInfo").modal("hide");
		$("#modal_results").modal("show");

		$("#modal_results_title").html("<span class=\"text-success\">Form Saved</span>");
		$("#modal_results_body").html("This form has been saved!<br>Press <strong>\"continue\"</strong> to manage your form. ");
		$("#modal_results_footer").html("<button type=\"button\" class=\"btn btn-primary\" onclick=\"redirectToManage('"+response+"')\">Continue</button>");
		
	})
	.fail(function() {
		// The form could not be saved.
		// Alert User through result modal.
		// Allow the user to save their work so they don't loose it!
		$("#modal_saveForm").modal("hide");
		$("#modal_formReview").modal("hide");
		$("#modal_addInfo").modal("hide");
		$("#modal_results").modal("show");
		
		$("#modal_results_title").html("<span class=\"text-danger\">Error</span>");
		$("#modal_results_body").html("There was an error with your save request. As such, this form cannot be processed. Below is a copy of your current work.<br><br><div class=\"alert alert-danger\"><strong> Please save the information below to a document on your own computer. It cannot be saved to the database and the data will be lost once this page is reloaded. <br><br> Please speak to an administrator to resolve the issue.</strong></div><pre>"+dataExport()+"</pre>");
		$("#modal_results_footer").html("<button type=\"button\" class=\"btn btn-default\" onclick=\"forceReload('Did you save the copy of your work?')\">Refresh This App</button>");
	});
	
}

function redirectToManage(goTo){
	window.location.href = "?path=form/edit&id="+goTo;
}

// Forces the application to refresh if there is an error.
function forceReload( prompt ){
	// If the prompt is empty, then just reload the page.
	if( prompt === "" ){
		prompt = "You are about to reload this page. Make sure any unsaved worked is saved.";
	}

	if( confirm(prompt) ){
		location.reload();
	}

}

// This function collects all the data for the form, preserving the order, and creates an object that can be exported as an object.
function dataExport(){

	// Initialize our export object
	var exportObj = {};

	// Retrieve all the elements needed.
	var elementsCreated = document.getElementById("sortable").getElementsByClassName("formElem");

	// Now parse the element info!
	var elementsInfo = [];

	for( var i = 0; i < elementsCreated.length; i++ ){
		elementsInfo[i] = listOfElements[elementsCreated[i].id.split("_")[1]];
	}

	// Now, for each element, we're going to add it to the export object!
	for( var i = 0; i < elementsInfo.length; i++ ){
		// Gives us access to the current element that we're viewing
		currentElement = elementsInfo[i];
		
		// Save into the export object at index i
		exportObj[i] = {};

		// What type of object is this?
		exportObj[i]['element_type'] = currentElement.type;
		
		// We're going to save the name of the element
		exportObj[i]['element_name'] = getElement("elementName_"+currentElement.id).value;

		// Let's save the Field Description
		exportObj[i]['element_description'] = getElement("elementDescription_"+currentElement.id).value;

		// Save whether or not the field is required
		exportObj[i]['element_required'] = $('input[name="requiredOption_'+currentElement.id+'"]:checked').val();

		// Now, if the element allows options, store them!
		if( currentElement.type == "radio" || currentElement.type == "checkbox" || currentElement.type == "dropdown" ){
			// Create the option object
			exportObj[i]['element_option'] = {};

			// Iterate through all the element options!
			elementOptions = document.getElementById("optionsContainer_"+currentElement.id).getElementsByClassName("elementOption");

			// Go through each one!
			for( var j = 0; j < elementOptions.length; j++ ){
				exportObj[i]['element_option'][j] = getElement(elementOptions[j].id).getElementsByTagName("input")[0].value;
			}
		}
	}

	return JSON.stringify(exportObj);
}

// This function will review the form for any missing fields, or to make sure that the user added options to certain types of elements.
function reviewForm(){
// Next, grab all of the elements created
var elementsCreated = document.getElementById("sortable").getElementsByClassName("formElem");

// Now parse the element info!
var elementsInfo = [];

	for( var i = 0; i < elementsCreated.length; i++ ){
		elementsInfo[i] = listOfElements[elementsCreated[i].id.split("_")[1]];
	}

// Now that we have our list, start validating information!
// We will pull information from the HTML side because the information is more accurate.
var noErrors = true; // This is our error flag. If there is any error, it will be "flagged" and we will alert our user.

	for( var i = 0; i < elementsInfo.length; i++ ){
		currentElement = elementsInfo[i];
		var elementError = false;
		var listOfErrors = []; // An area that saves the errors!

		// For each element, we have to at least check the name and description fields.
		// Field Name
		if( getElement("elementName_"+currentElement.id).value == "" ){
			elementError = true;
			noErrors = false;
			listOfErrors.push("Field Name cannot be empty.");
			$("#elementName_"+currentElement.id).parent().addClass("has-error");
		} else {
			$("#elementName_"+currentElement.id).parent().removeClass("has-error");
		}

		// Field Description
		if( getElement("elementDescription_"+currentElement.id).value == "" ){
			elementError = true;
			noErrors = false;
			listOfErrors.push("Field Description cannot be empty.");
			$("#elementDescription_"+currentElement.id).parent().addClass("has-error");
		} else {
			$("#elementDescription_"+currentElement.id).parent().removeClass("has-error");
		}

		// Now check options for specific elements.
		if( currentElement.type == "radio" || currentElement.type == "checkbox" || currentElement.type == "dropdown" ){
			// Iterate through all the element options!
			elementOptions = document.getElementById("optionsContainer_"+currentElement.id).getElementsByClassName("elementOption");
			elementOptionEmpty = false;

			// Go through each one!
			for( var j = 0; j < elementOptions.length; j++ ){
				if( getElement(elementOptions[j].id).getElementsByTagName("input")[0].value == "" ){
					elementError=true;
					noErrors=false;
					elementOptionEmpty=true;
					$("#"+elementOptions[j].id).addClass("has-error");
				} else {
					$("#"+elementOptions[j].id).removeClass("has-error");
				}
			}

			if( elementOptionEmpty ){
				listOfErrors.push("An Element Option cannot be blank.");
			}

			// Check to see if there are any elements options in the element...
			if( elementOptions.length == 0 ){
				elementError=true;
				noErrors=false;
				elementEmptyError=true;
				listOfErrors.push("There has to be at least one element option.");
			}

		}

		// If there have been any errors, open up this edit box and show user the errors!
		if( elementError ){
			// Is this box already open?
			if( $("#"+currentElement.id+"_collapse").attr("class").split(" ")[2] == "in" ){
			} else {
				// No, so show!
				$("#"+currentElement.id+"_collapse").collapse("show");
			}

			// Now show the error box
			var elementAlert = $("#elementAlert_"+currentElement.id);
			elementAlert.html("");

			for( var k = 0; k < listOfErrors.length; k++ ){
				elementAlert.append(listOfErrors[k]+"<br>");
			}
			
			elementAlert.parent().removeClass("hidden");

		} else {
			$("#"+currentElement.id+"_collapse").collapse("hide");
			$("#elementAlert_"+currentElement.id).parent().addClass("hidden");
		}
	}

	// For this section, if form is validated correctly, without any errors, pass it along to the final review.
	if( noErrors ){
		// First, remove the style and error messages!
		$("#warning").removeClass('alert');
		$("#warning").removeClass('alert-danger');
		$("#warning").html("");
	} else {
		// There was an error in the form, alert the user!
		$("#warning").addClass('alert');
		$("#warning").addClass('alert-danger');
		$("#warning").html("Sorry, this form still needs to be finished. Please fix all the errors.");
	}

	// There are no elements? Don't do anything...
	if( elementsInfo.length == 0 ){
		return false;
	} else {
		// Only show this part if there are elements and there are no errors...
		// Show the save option
		if( noErrors ){
			$("#modal_formReview").modal("show");
		}
	}

}

// Returns the DOM for an element
function getElement( element ){
	return document.getElementById(element);
}

// Allows the user to drag items without having to worry when they're interfacing with the controls of a specific element
$(function() {
	$( "#sortable" ).sortable({cancel: ".noDrag" });
	// $( "#sortable" ).disableSelection();
});

// newFieldFunc -- This section deals with the section that adds new components to the field
function newFieldFunc(){
	// Get the element where the buttons are
	var field	= document.getElementById("newField");
	var buttons	= field.getElementsByTagName('button');

	for (var i = 0; i < buttons.length; i++) {
		buttons[i].onclick = function(){ addElement(this); };
	}
}


// This method listens to all the input elements of the form. It saves the state and also provides options if the input element
// has a defined action.
function elementAction( pointer ){
	// Separate the ID of the element and the role of the input.
	var inputType = pointer.id.split("_")[0];
	var elementID = pointer.id.split("_")[1];

	// Input type functionality
	switch( inputType ){
		case "requiredOption1":
			visual_required( pointer );
			break;
		case "requiredOption2":
			visual_required( pointer );
			break;
		case "elementName":
			visual_updateTitle( pointer );
			break;
		default:
			// The input type does not have an action associated with it.
	}
	rLog("In elementAction() where: "+inputType+" and from "+elementID+" ID.");

}

// visual_updateTitle( pointer )
// Updates the title with the element's name
function visual_updateTitle( pointer ){
	var inputType = pointer.id.split("_")[0];
	var elementID = pointer.id.split("_")[1];

	if( pointer.value === "" ){
		document.getElementById("elementTitle_"+elementID).innerHTML = toText[listOfElements[elementID].type];
	} else {
		document.getElementById("elementTitle_"+elementID).innerHTML = toText[listOfElements[elementID].type]+" - "+pointer.value;
	}

}

// visual_required( pointer )
// Changes the element of focus.
// If the yes required radio button is selected, it will highlight the element red.
// If the no required radio button is selected, it will remove/reset the element to normal.
// Part of the "visual_" methods.
function visual_required( pointer ){
	var inputType = pointer.id.split("_")[0];
	var elementID = pointer.id.split("_")[1];

	// If the input type is requiredOption1, that means that this element is required.
	// If not, then this element is not required.
	elementTitle = document.getElementById("elementTitle_"+elementID);
	elementTypeText = toText[listOfElements[elementID].type];

	if ( inputType == "requiredOption1" ){
		// Change class and the title
		// elementTitle.innerHTML = elementTypeText + " (required)";
		document.getElementById("formElement_"+elementID).className = "formElem panel panel-info"
	}else{
		// elementTitle.innerHTML = elementTypeText;
		document.getElementById("formElement_"+elementID).className = "formElem panel panel-default"
	}

	rLog("In visual_required() where: "+inputType+" and from "+elementID+" ID.");

}

// visual_collapse( mode )
// collapses all the elements, either is shows them or hides them depending on the mode selected
function visual_collapse(mode){
	// "Toggle" all the elements based on the mode!
	for(i=0; i<listOfElements.length;i++){
		// Only collapse the elements that exist
		if ( listOfElements[i] != undefined ) {
			rLog(listOfElements[i].id);
			$("#"+listOfElements[i].id+"_collapse").collapse(mode);
		}
	}
}

// Asks the user to confirm that they want to remove an element
function deleteVisualElement( element ){
	if( confirm("Are you sure you want to remove this element?") ){
		deleteElement(element);
	}
}

// This function will load an element into the editor with text and options
// Format of JSON object
/*
{
	element_type: <STIRNG>,
	element_name: <STIRNG>,
	element_description: <STRING>,
	element_option: <OBJECT>,
		{
			0:<STIRNG>,
			[1:<STIRNG>,...]
		}
}
*/
function loadElement( elementObj ){
	// Add an element, to the field.
	// Just passing along an object with the right attribute
	var newElement = addElement({name:elementObj.element_type});

	// Now populate the new element with the element object's data
	
	// Element Name
	getElement("elementName_"+newElement.id).value = elementObj.element_name;
	elementAction(getElement("elementName_"+newElement.id));

	// Element Description
	getElement("elementDescription_"+newElement.id).value = elementObj.element_description;

	// Element Required
	if( elementObj.element_required == "yes" ){
		// We're using jQuery here to take advantage of the attr function which allows us to toggle the required option
		$("#requiredOption1_"+newElement.id).attr("checked",true);
		elementAction(getElement("requiredOption1_"+newElement.id));
	}

	// Now, if the element has options, we are going to add those.
	if( elementObj.element_option ){
		// Format: deleteElementOption_<ELEMENT ID>-<OPTION ID>
		// Remove the first default option so we can start with an empty section
		deleteElementOption(getElement("deleteElementOption_"+newElement.id+"-"+0));

		// Now, for each element in the option object, add to the element.
		var i = 0;
		while( elementObj.element_option[i] ){
			// Create the HTML element for this option
			var newElementOption = addElementOption(getElement("addElementOption_"+newElement.id));
			// Now add the information needed
			getElement("elementOption_"+newElement.id+"-"+newElementOption).getElementsByTagName("input")[0].value = elementObj.element_option[i];
			i++;
		}

	}
}

// Creates the HTML for an element and appends it to our list
function drawElement( element ){

	// Step 1) Save the information that will go into the form here.
	var elementHTML;

	// Add title to the element we're creating.
	this.elementHTML = "<div class=\"panel-heading clearfix\"> \
		<div data-toggle=\"collapse\" data-parent=\"#accordion\" href=\"#"+element.id+"_collapse\" class=\"pull-left\" style=\"cursor: pointer;\"><img src=\""+imagePath[element.type]+"\" /> \
			<span style=\"padding: 0 0 0 10px;\" id=\"elementTitle_"+element.id+"\">"+toText[element.type]+"</span> \
		</div> \
			<div class=\"pull-right\"> \
				<a id=\"deleteRequest_"+element.id+"\" type=\"button\" class=\"btn btn-danger btn-sm\" onclick=\"deleteVisualElement(this)\">Delete Field</a> \
			</div> \
		</div>";

	// There are some basic properties that all elements need
	// - Element Name
	// - Element Description
	// - Element Required

	this.elementHTML = this.elementHTML+"<div id=\""+element.id+"_collapse\" class=\"panel-body noDrag in collapse \"> \
											<form class=\"form-inline\" role=\"form\">";


	// Add the error container
	this.elementHTML += "<div class=\"alert alert-danger hidden\">\
							<strong>Warning</strong><br>\
							<div id=\"elementAlert_"+element.id+"\"></div>\
						</div>";

	// Element Name
	this.elementHTML += "<div class=\"form-group col-md-6\" style=\"padding: 0 5px 0 0;\"> \
							<label class=\"control-label\" for=\"elementName_"+element.id+"\"> Field Name </label> \
							<input type=\"text\" class=\"form-control input-sm\" id=\"elementName_"+element.id+"\" placeholder=\"First Name, Last Name, etc...\"> \
						</div>";

	// Element Description
	this.elementHTML += "<div class=\"form-group col-md-6\" style=\"padding: 0 5px 0 0;\"> \
							<label class=\"control-label\" for=\"elementDescription_"+element.id+"\"> Field Description (Instructions to user) </label> \
							<input type=\"text\" class=\"form-control input-sm\" id=\"elementDescription_"+element.id+"\" placeholder=\"Enter your first name, Write a short essay about your life, etc... \"> \
						</div>";

	// Element Required?
	this.elementHTML += "<div class=\"form-group col-md-12\" style=\"padding: 15px 0 0 0;\"> \
							<label class=\"control-label\">Required Field?</label> \
							<br> \
								<label class=\"radio-inline\"><input type=\"radio\" name=\"requiredOption_"+element.id+"\" id=\"requiredOption1_"+element.id+"\" value=\"yes\">Yes</label> \
								<label class=\"radio-inline\"><input type=\"radio\" name=\"requiredOption_"+element.id+"\" id=\"requiredOption2_"+element.id+"\" value=\"no\" checked=\"checked\">No</label> \
						</div>";


	// Insert the necessary controls for specific types of elements (i.e checkboxes and radio buttons)
	if( element.type == "radio" || element.type == "checkbox" || element.type == "dropdown"){
	// Set up the data for our options page
	// Add the first option to our element's list.
	element.data.options = {idCount:0,list:[]}
	element.data.options.list.push( {id:0,text:""} );

	// Add the functionality to add options to this type of element
	this.elementHTML += "<div id=\"optionsContainer_"+element.id+"\" class=\"form-group col-md-12\" style=\"padding: 15px 0 0 0;\"> \
							<div style=\"font-size: 14px; font-weight: bold; margin: 0 0 10px 0;\"> \
								Add options \
								<button id=\"addElementOption_"+element.id+"\" type=\"button\" onclick=\"addElementOption(this)\" class=\"btn btn-success btn-xs\">Add New Option</button> \
							</div> \
							\
							<div class=\"elementOption\" id=\"elementOption_"+element.id+"-0\">\
								<div class=\"col-md-9\"> \
									<input class=\"col-md-10 form-control input-sm\" placeholder=\"Option\"> \
								</div> \
								<div class=\"col-md-3\"> \
									<button id=\"deleteElementOption_"+element.id+"-0\" type=\"button\" class=\"btn btn-warning btn-sm\" onclick=\"deleteElementOption(this)\">Remove Option</button> \
								</div> \
							<br><br></div>\
						</div>";
	}


	// Close out the necessary form data
	this.elementHTML = this.elementHTML+"</form></div>";

	// Wrap the information with a form element id.
	this.elementHTML = "<div id=\"formElement_"+element.id+"\" class=\"panel panel-default formElem \">" + this.elementHTML + "</div>";

	// Step 2) Add new HTML to current sortable area
	// Using jQuery append beacuse if you use innerHTML, it destroys the current state of the form and also removes elementActions from all non-new elements.
	$("#sortable").append(this.elementHTML);

	// Step 3) Add listeners to all the new input elements.
	var elementInputs = document.getElementById("formElement_"+element.id).getElementsByTagName("input");

	for (var i = 0; i < elementInputs.length; i++) {
		$(elementInputs[i]).bind("change paste keyup", function(){elementAction(this)} );
	};

}

function addElementOption( pointer ){
	// The format of the pointer ID is addElementOption_<ELEMENT ID>
	elementID = pointer.id.split("_")[1];

	// So first lets create our new option as an object inside the data object of the element
	listOfElements[elementID].data.options.idCount++;
	optionID = listOfElements[elementID].data.options.idCount;

	listOfElements[elementID].data.options.list.push({ id:optionID,text:"" });

	// Now let's add our HTML
	newOptionHTML = "<div class=\"elementOption\" id=\"elementOption_"+elementID+"-"+optionID+"\">\
								<div class=\"col-md-9\"> \
									<input type=\"email\" class=\"col-md-10 form-control input-sm\" placeholder=\"Option\"> \
								</div> \
								<div class=\"col-md-3\"> \
									<button id=\"deleteElementOption_"+elementID+"-"+optionID+"\" type=\"button\" class=\"btn btn-warning btn-sm\" onclick=\"deleteElementOption(this)\">Remove Option</button> \
								</div> \
							<br><br></div>";

	$("#optionsContainer_"+elementID).append(newOptionHTML);

	return optionID;

}

function deleteElementOption(pointer){
	// The format of the pointer ID is deleteElementOption_<ELEMENT ID>-<OPTION ID>
	elementID = pointer.id.split("_")[1].split("-")[0];
	optionID = pointer.id.split("_")[1].split("-")[1];

	// Remove from the element data the option
	delete listOfElements[elementID].data.options.list[optionID];

	// Now remove the option from the HTML
	$("#elementOption_"+elementID+"-"+optionID).remove();

	rLog("#elementOption_"+elementID+"-"+optionID);
	rLog("We are going to delete element option "+optionID+" from element "+elementID);
}

// This method deletes an element from BOTH the html representation and the element list.
function deleteElement( pointer ){
	var elementID = pointer.id.split("_")[1];

	// Delete the HTML first.
	$("#formElement_"+elementID).remove();

	// Now delete the element from our element list.
	delete listOfElements[elementID];
}

function addElement( pointer ){
	// UPDATE==========================
		// Make this into a for loop and define a list of acceptable types. Easy to do.
	// Make sure the type of pointer we're making exists
	if( pointer.name == "text_area" || pointer.name == "text_field" || pointer.name == "checkbox" || pointer.name == "radio" ){
	} else{
		alert("Critical Error - Cannot find specified element type");
		return;
	}

	newData = {}

	// Create our new element object
	var newElement = new formElement(pointer.name,false,newData,idCount);
	listOfElements.push(newElement);

	// Hide all our current elements
	visual_collapse('hide');

	idCount++;
	drawElement(newElement);

	return newElement;

}

/*
Set up the template for form element objects
This object has {X} parameters:
	type		- Desc:		The type of element that this object can be. Currently there are only 4 types supported.
				- Input:	["text_area"|"text_field"|"radio"|"checkbox"]

	required	- Desc:		Whether this element is required to be completed by the user.
				- Input:	[true|false]

	data		- Desc:		Contains the data for this element. Data can be anything from null/empty to an object of data.
				- Input:	[null|data]

	id			- Desc:		This element is assigned an ID to keep the uniqueness of the form intact.
				- Input: 	[int]

*/
function formElement(type,required,data,id){
	this.type = type;
	this.required = required;
	this.data = data;
	this.id = id;
}