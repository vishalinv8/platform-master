/*------------------------------------------
 Contact form
 ------------------------------------------*/

$(document).ready(function () {

    $("#contact_form").submit(function(e){

        e.preventDefault();
        var $ = jQuery;

        var postData 		= $(this).serializeArray(),
            formURL 		= $(this).attr("action"),
            $cfResponse 	= $('#response'),
            $cfsubmit 		= $("#submit"),
            cfsubmitText 	= $cfsubmit.text();

        $cfsubmit.text("Sending...");

        $.ajax(
            {
                url : formURL,
                type: "POST",
                data : postData,
                success:function(data)
                {
                    $cfResponse.html(data);
                    $cfsubmit.text(cfsubmitText);
                    $("#contact_form")[0].reset();
                },
                error: function(data)
                {
                    alert("An error occured. Please try again.");
                    $cfsubmit.text(cfsubmitText);
                }
            });

        return false;

    });
    //email subscription 
    $('#subscription-form').submit(function(e) {

        e.preventDefault();
        var $ = jQuery;

        var $form           = $('#subscription-form');
        var $submit          = $('#subscribe-button');
        var $ajaxResponse    = $('#subscription-response');
        var email           = $('.subscriber-email').val();
        var cfsubmitText 	= "Subscribe";

        var postData 		= $(this).serializeArray();
        var formURL 		= $(this).attr("action");

        //$ajaxResponse.text("Sending...");

        $.ajax({
            type: 'POST',
            url : formURL,
            //dataType: 'json',
            data: postData,
            cache: false,

//            beforeSend: function(result) {
//                $submit.val("Joining...");
//            },

            success: function(result) 
            {
                if(result.sendstatus == 1) {  // FIXME: This is wrong/broke, never true
                    $ajaxResponse.html(result.message);
                    //$form.fadeOut(500);
                } else {
                    $form.fadeOut(500, function() {
                      $ajaxResponse.html("Subscribed! Thanks.");
                    });
                    //$submit.val("Join");
//$('.subscriber-email').val("Subscribed! Thanks.");
                }
            },
            error: function(jqXHR, exception)
            {
              $ajaxResponse.html(jqXHR.responseText + " " + exception);
              //alert(JSON.stringify(jqXHR));
              //alert(JSON.stringify(exception));
            }
        });

        return false;

    });



});


