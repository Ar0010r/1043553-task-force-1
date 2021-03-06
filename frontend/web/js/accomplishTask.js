$(document).ready(function () {
    let form = $("#taskAccomplishForm");
    let errorMessageRating = $('#errorMessageRating');
    let alertMessage = 'Ошибка, попробуйте позже';
    let redirectAfterResponseCreation = '/tasks';
    let taskId = window.location.href.substring(window.location.href.lastIndexOf('/') + 1);

    $(document).on("click", "#taskAccomplishFormSubmit", function () {
        $.ajax({
            type: 'POST',
            url: "/task-action/accomplish?taskId=" + taskId,
            data: form.serializeArray(),
        })
        //Если запрос отправлен
            .done(function (data) {
                if (data.result) {
                    window.location.replace(redirectAfterResponseCreation);
                } else {
                    showErrorMessage(errorMessageRating, data.errors.rating);
                }
            })
            //Если запрос не ушел
            .fail(function () {
                alert(alertMessage);
            });
    });
});

function showErrorMessage(element, message) {
    element.css("display", "block");
    element.text(message);
}