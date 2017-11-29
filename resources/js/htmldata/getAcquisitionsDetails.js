$(document).ready(function(){
    $('.ilsOrderStatus').each(function() {
       var id = $(this).attr('id').substr(9);
	   $.ajax({
        type:       "GET",
        url:        "ajax_htmldata.php",
        cache:      false,
        data:       "action=getIlsOrderStatus&orderid=" + id,
        success:    function(html) {
            $('#ilsStatus' + id).html(html);
        }
    });

    });
});
