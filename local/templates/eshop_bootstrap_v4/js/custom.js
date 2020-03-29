$(document).ready(function() {

    function timer() {
        $.ajax({
            url: "/ajax/getViewUser.php",
            method: 'POST',
            data: {element_id: id},
        });
        console.info('g')
    }
    var id = $('#productCustomId').val();
    if (id !== undefined) {
        let timerId = setInterval(timer, 1000);
        setTimeout(() => { clearInterval(timerId);}, 5000);
    }

});
