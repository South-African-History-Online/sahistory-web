(function($) {
$( "#tdihdate").datepicker(
{changeYear: false, dateFormat: 'dd/mm',
}).focus(function () {$(".ui-datepicker-year").hide();
} );
}(jQuery));



function tdihdateURL(){
var input = document.getElementById("tdihdate").value;
window.location = "/thisday/" + input;
}

(function($) {
var $myGroup = $('#accordion');
$myGroup.on('show.bs.collapse','.collapse', function() {
    $myGroup.find('.collapse.in').collapse('hide');
});}(jQuery));

(function($) {
$(window).scroll(function() {
    var height = $(window).scrollTop();
    if (height > 1800 && $('#back2Top')) {
        $('#back2Top').fadeIn();
    } else {
        $('#back2Top').fadeOut();
    }
});
$(document).ready(function() {
    $("#back2Top").click(function(event) {
        event.preventDefault();
        $("html, body").animate({ scrollTop: 0 }, "slow");
        return false;
    });

});}(jQuery));
