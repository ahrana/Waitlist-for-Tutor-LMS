jQuery(document).ready(function($) {
    $('.wftl-join-waitlist').on('click', function(e) {
        e.preventDefault();
        $(this).next('.wftl-waitlist-form').slideToggle();
    });

    $('.wftl-waitlist-form-inner').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var courseId = $form.closest('.wftl-waitlist-container').find('.wftl-join-waitlist').data('course-id');
        var email = $form.find('input[name="waitlist_email"]').val();

        $.ajax({
            url: wftl_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'wftl_join_waitlist',
                course_id: courseId,
                email: email,
                nonce: wftl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $form.slideUp();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});