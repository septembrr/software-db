/* Manage Cost Rows on Edit Page ------------------------------- */

// Remove cost buttons
let costButtons = document.getElementsByClassName("remove-cost");

// Listen for clicks on remove cost buttons
for(let i = 0; i < costButtons.length; i++) {
    costButtons[i].addEventListener("click", function(event) {
        removeCost(event, i);
    });
}

// REMOVE COST
function removeCost(event, index) {

    // Don't let the click reload the page
    event.preventDefault();

    // Find row with appropriate ID
    let elemId = "cost-" + index;
    var elem = document.getElementById(elemId);
    elem.parentNode.removeChild(elem); // Delete row

    // Update number of remove cost buttons
    costButtons = document.getElementsByClassName("remove-cost");

}

// ADD COST
document.getElementById("add-cost").addEventListener("click", function(event) {

    // Don't let the click reload the page
    event.preventDefault();

    // Create a new cost row with appropriate IDs
    var row = document.createElement('div');
    row.setAttribute('class', 'row');
    row.setAttribute('id', 'cost-'+costButtons.length );
    row.innerHTML = `
        <div class="col-sm-3 field-choice">
            <label for="record_cost">Cost</label>
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text">$</span>
                </div>
                <input id="record_cost" class="form-control" name="record_cost[]" value="" type="number" step="0.01"/>
            </div>
        </div>
        <div class="col-sm-3 field-choice">
            <label for="record_freq">Payment Frequency</label>
            <select id="record_freq" class="form-control" name="record_freq[]">
                <option></option>
                <option value="One-Time">One-Time</option>
                <option value="Monthly">Monthly</option>
                <option value="Yearly">Yearly</option>
            </select>
        </div>
        <div class="col-sm-4 field-choice">
            <label for="record_unit">Unit</label>
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text">per</span>
                </div>
                <input id="record_unit" class="form-control" name="record_unit[]" value="" type="text" />
            </div>
        </div>
        <div class="col-sm-2">
            <label>Action</label>
            <div class="input-group">
                <a class="remove-cost" href=""><button type="button" class="btn btn-danger">Delete Cost</button></a>
            </div>
        </div>
    `;

    // Set next element index
    var nextElement = costButtons.length;

    // Add event listener
    row.getElementsByClassName("remove-cost")[0].addEventListener("click", function(event) {
        removeCost(event, nextElement);
    });

    // Add this element to the DOM
    document.getElementById("add-cost-row").parentNode.insertBefore(row, document.getElementById("add-cost-row"));
});
