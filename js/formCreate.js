// Debbuging!
function rLog( message ){
	debug = 0;
	if( debug == 1 ){
		document.getElementById("log").style.visibility="visible";
		document.getElementById("log").innerHTML += message+"<br>";
	}
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

		console.log(elementsInfo);
		console.log(elementsInfo.length);

// Now that we have our list, start validating information!
// We will pull information from the HTML side because the information is more accurate.
var noErrors = true; // This is our error flag. If there is any error, it will be "flagged" and we will alert our user.

	for( var i = 0; i < elementsInfo.length; i++ ){
		console.log("On step: "+i);
		console.log("===========");

		currentElement = elementsInfo[i];
		var elementError = false;
		var listOfErrors = []; // An area that saves the errors!

		// For each element, we have to at least check the name and description fields.
		// Field Name
		if( getElement("elementName_"+currentElement.id).value == "" ){
			elementError = true;
			noErrors = false;
			listOfErrors.push("Field Name cannot be empty.");
			$("#elementName_"+currentElement.id).parent().addClass("has-error")
		} else {
			$("#elementName_"+currentElement.id).parent().removeClass("has-error")
		}

		// Field Description
		if( getElement("elementDescription_"+currentElement.id).value == "" ){
			elementError = true;
			noErrors = false;
			listOfErrors.push("Field Description cannot be empty.");
			$("#elementDescription_"+currentElement.id).parent().addClass("has-error")
		} else {
			$("#elementDescription_"+currentElement.id).parent().removeClass("has-error")
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
					$("#"+elementOptions[j].id).addClass("has-error")
				} else {
					$("#"+elementOptions[j].id).removeClass("has-error")
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

			for( var i = 0; i < listOfErrors.length; i++ ){
				elementAlert.append(listOfErrors[i]+"<br>");
			}
			
			elementAlert.parent().removeClass("hidden");

		} else {
			$("#"+currentElement.id+"_collapse").collapse("hide");
			$("#elementAlert_"+currentElement.id).parent().addClass("hidden");
		}
	}
}

function getElement( element ){
	return document.getElementById(element)
}

// What creates help for users!
// $("#help").tooltip({placement: "auto top"});

// Returns current state
// $( "#sortable" ).sortable( "widget");
	$(function() {
		$( "#sortable" ).sortable({cancel: ".noDrag" });
		$( "#sortable" ).disableSelection();
	});

// newFieldFunc -- This section deals with the section that adds new components to the field
function newFieldFunc(){
	// Get the element where the buttons are
	var field	= document.getElementById("newField");
	var buttons	= field.getElementsByTagName('button');

	for (var i = 0; i < buttons.length; i++) {
		buttons[i].onclick = function(){ addElement(this) };
	};

}
newFieldFunc();

// List of global variables
var listOfElements = [];
idCount	= 0;

var toText = {
	text_area : "Text Area",
	text_field : "Text Field",
	checkbox : "Check Box Group",
	radio : "Radio Button Group"
}

var imagePath = {
	text_area : "./images/formGUI/form_input_textarea.png",
	text_field : "./images/formGUI/form_input_text.png",
	checkbox : "./images/formGUI/form_input_checkbox.png",
	radio : "./images/formGUI/form_input_radio.png"
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

	if( pointer.value == "" ){
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
		document.getElementById("formElement_"+elementID).className = "formElem panel panel-danger"
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
			$("#"+listOfElements[i].id+"_collapse").collapse(mode)
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
				<button id=\"deleteRequest_"+element.id+"\" type=\"button\" class=\"btn btn-danger btn-sm\">Delete Field</button> \
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
							<label class=\"control-label\" for=\"elementDescription_"+element.id+"\"> Field Description </label> \
							<input type=\"text\" class=\"form-control input-sm\" id=\"elementDescription_"+element.id+"\" placeholder=\"Your first name, Your last name, etc... \"> \
						</div>";

	// Element Required?
	this.elementHTML += "<div class=\"form-group col-md-12\" style=\"padding: 15px 0 0 0;\"> \
							<label class=\"control-label\">Required Field?</label> \
							<br> \
								<label class=\"radio-inline\"><input type=\"radio\" name=\"requiredOption_"+element.id+"\" id=\"requiredOption1_"+element.id+"\" value=\"option2\">Yes</label> \
								<label class=\"radio-inline\"><input type=\"radio\" name=\"requiredOption_"+element.id+"\" id=\"requiredOption2_"+element.id+"\" value=\"option2\" checked=\"checked\">No</label> \
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

	$("#deleteRequest_"+element.id).popover({
		animation: true,
		placement: "auto",
		title: "Are you sure?",
		html: true,
		content: "<b>Delete this form element?</b><br><button type=\"button\" class=\"btn btn-default btn-block\" onclick=\"$('#deleteRequest_"+element.id+"').popover('hide')\">No</button> <button type=\"button\" class=\"btn btn-danger btn-block\" onclick=\"deleteElement(this)\" id=\"deleteElement_"+element.id+"\">Yes</button>"
	});
	
asd-d 

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
									<input type=\"email\" class=\"col-md-10 form-control input-sm\" id=\"inputEmail3\" placeholder=\"Option\"> \
								</div> \
								<div class=\"col-md-3\"> \
									<button id=\"deleteElementOption_"+elementID+"-"+optionID+"\" type=\"button\" class=\"btn btn-warning btn-sm\" onclick=\"deleteElementOption(this)\">Remove Option</button> \
								</div> \
							<br><br></div>";

	$("#optionsContainer_"+elementID).append(newOptionHTML);

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