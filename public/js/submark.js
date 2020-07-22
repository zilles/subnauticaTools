var lastSent = null;
function updatePreview()
{
    var source = $('#source').val();
    if (lastSent == source)
        return;
    lastSent = source;

    console.log("start");
    $.ajax({
        method: "POST",
        url: "/submark/markdown",
        data: { source: source }
    }).done(function( msg ) {
        console.log("end");
        $('#submark').html(msg);
    }).fail(function( jqXHR, textStatus ) {
        alert( "Unable to access server: " + textStatus );
    });
}

var debouncedPreview = _.debounce(updatePreview, 500);

$(function() {
    $('#source').on("change keyup paste", function() {
        debouncedPreview();
    });
});
