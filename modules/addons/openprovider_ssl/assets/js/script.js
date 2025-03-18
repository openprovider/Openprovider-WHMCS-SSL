$(document).ready(function () {

    $(".test-api-form").validate({
        rules: {
            "api_user_name": {
                required: true,
            },
            "api_password": {
                required: true,
            },
            "api_url": {
                required: true,
            },
        },
        messages: {
            "api_user_name": {
                required: "Api UserName is required.",
            },
            "api_password": {
                required: 'Password is required.',
            },
            "api_url": {
                required: "API Url is required.",
            },
        },
        errorPlacement: function (error, element) {
            error.insertAfter(element);
        }
    });

    const secureCall = (data = {}, method = "GET", url = '') => {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function (response) {
                    resolve(response);
                },
                error: function (error) {
                    reject(error);
                }
            });
        });
    }

    $('#togglePassword').on('click', function () {
        const passwordField = $('#password');
        const icon = $('#eyeIcon');

        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });

    $(document).on("click", "#savesetting", async function () {
        try {
            if (!$(".test-api-form").valid()) {
                return;
            }
            $this = $(this);
            $this.prop('disabled', true);
            $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            var formData = $('#api-setting').serialize();

            let response = await secureCall({ "ajaxcall": true, "ajaxaction": "Save Setting", 'data': formData }, 'POST');
            $this.find("i.fa-spinner").remove();
            const parsedResponse = JSON.parse(response);

            if (parsedResponse.status === true) {
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
            }

            $this.prop('disabled', false);
        } catch (error) {
            console.error(error)
        }
    });

    $(document).on("click", "#testconnection", async function () {
        try {
            $this = $(this);
            $this.prop('disabled', true);
            $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            var formData = $('#api-setting').serialize();

            let response = await secureCall({ "ajaxcall": true, "ajaxaction": "Api Test Connection", 'data': formData }, 'POST');
            $this.find("i.fa-spinner").remove();
            const parsedResponse = JSON.parse(response);

            if (parsedResponse.status === true) {
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
                $('.api-btn-footer span').remove();
                $('.api-btn-footer').append('<span class="badge badge-pill badge-success"> <i class="fas fa-check fa-fw"></i>Conected</span>');
            } else {
                $('.api-btn-footer span').remove();
                $('.api-btn-footer').append('<span class="badge badge-pill badge-danger"> <i class="fas fa-times fa-fw"></i> Not Conected</span>');
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
            }
            $this.prop('disabled', false);
        } catch (error) {
            console.error(error)
        }
    });

    $(document).on("click", ".create-product", async function () {
        try {
            $this = $(this);
            $this.prop('disabled', true);
            $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            var formData = $('.all-ssl-product').serialize();
            let response = await secureCall({
                "ajaxcall": true,
                "ajaxaction": "Create Product",
                'data': formData
            }, 'POST');

            $this.find("i.fa-spinner").remove();
            const parsedResponse = JSON.parse(response);

            if (parsedResponse.status === true) {
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
            }
            $this.prop('disabled', false);
        } catch (error) {
            console.error(error);
        }
    });

    $(document).on("click", ".product_sync", async function () {
        try {
            $this = $(this);
            $this.prop('disabled', true);
            $('.allproduct-box').append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            let response = await secureCall({ "ajaxcall": true, "ajaxaction": "Get SSL Product", }, 'POST');
            $('.allproduct-box').find("i.fa-spinner").remove();
            const parsedResponse = JSON.parse(response);

            if (parsedResponse.status === true) {
                $('.errorbox').remove();
                $('.allproduct-box').html('');
                $('.allproduct-box').append(parsedResponse.html);
                // jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
            }
            $this.prop('disabled', false);
        } catch (error) {
            console.error(error)
        }
    });

    $("#tblModuleLogSSl").DataTable({
        processing: true,
        searching: true,
        order: [[0, "desc"]],
        serverSide: true,
        ajax: {
            url: "",
            type: "POST",
            data: { ajaxcall: "true", ajaxaction: "clientLogs" },
            dataSrc: function (json) {
                if (json.status === 'error') {
                    alert('An error occurred: ' + json.description);
                    return [];
                }
                return json.data.map(log => ({
                    date: log.date,
                    module: log.module,
                    action: log.action,
                    request: log.request,
                    response: log.response
                }));
            },
            error: function (xhr, error, thrown) {
                alert('An error occurred: ' + thrown);
            }
        },
        columns: [
            { data: 'date' },
            { data: 'module' },
            { data: 'action' },
            { data: 'request' },
            { data: 'response' }
        ],
        columnDefs: [
            {
                targets: [3, 4],
                orderable: false,
            },
            {
                targets: [0],
                visible: true,
                searchable: true,
            },
        ],
        language: {
            infoFiltered: "",
            emptyTable: "No data available in table",
        },
    });

    function reloadLogs() {
        var table = $("#tblModuleLogSSl").DataTable();
        table.ajax.reload(); 
    }

    $(document).on("click", "#deletelog", async function () {
        try {
            Swal.fire({
                title: "Are you sure?",
                text: "You want to Delete Logs",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes",
            }).then(async (result) => {
                if (result.isConfirmed) {
                    var $this = $(this);
                    $this.prop('disabled', true);
                    $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');

                    // Make the API call
                    let response = await secureCall({ "ajaxcall": true, "ajaxaction": "Delete logs" }, 'POST');
                    $this.find("i.fa-spinner").remove();

                    var parsedResponse = JSON.parse(response);
                    if (parsedResponse.status === true) {
                        jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
                        reloadLogs();
                        // setTimeout(function () {
                        //     location.reload();
                        // }, 100);
                    } else {
                        jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
                    }
                    $this.prop('disabled', false);
                }
            });
        } catch (error) {
            console.error(error);
        }
    });

});
