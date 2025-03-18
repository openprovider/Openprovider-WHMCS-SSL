
$(document).ready(function () {

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

    $(".csr-token-form").validate({
        rules: {
            "common_name": {
                required: true
            },
            "country": {
                required: true
            },
            "email": {
                required: true,
                email: true
            },
            "locality": {
                required: true
            },
            "organization": {
                required: true
            },
            "signature_hash_algorithm": {
                required: true
            },
            "state": {
                required: true
            },
            "subject_alternative_name": {
                required: true
            },
            // "unit": {
            //     required: true
            // },
            // "with_config": {
            //     required: true
            // }
        },
        messages: {
            "common_name": {
                required: "Common Name is required."
            },
            "country": {
                required: "Country is required."
            },
            "email": {
                required: "Email Address is required.",
                email: "Please enter a valid email address."
            },
            "locality": {
                required: "Locality is required."
            },
            "organization": {
                required: "Organization is required."
            },
            "signature_hash_algorithm": {
                required: "Signature Hash Algorithm is required."
            },
            "state": {
                required: "State is required."
            },
            "subject_alternative_name": {
                required: "Subject Alternative Name is required."
            },
            // "unit": {
            //     required: "Unit is required."
            // },
            // "with_config": {
            //     required: "Include Config is required."
            // }
        },
        errorPlacement: function (error, element) {
            error.insertAfter(element);
        }
    });

    $(document).on("click", ".resend_email_btn", async function () {
        try {
            $this = $(this);
            $("button").prop('disabled', true);
            // $this.prop('disabled', true);
            $(".containers").append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            // $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');

            let response = await secureCall({ "ajaxcall": true, "ajaxaction": "Resend Approval Email", }, 'POST');

            $(".containers").find("i.fa-spinner").remove();
            // $this.find("i.fa-spinner").remove();
            $("button").prop('disabled', false);
            var parsedResponse = JSON.parse(response);
            if (parsedResponse.status === true) {
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });

            }
            // $this.prop('disabled', false);
        } catch (error) {
            console.error(error)
        }
    });
    $(document).on("click", ".reissue_btn", async function () {
        try {
            $this = $(this);
            $this.prop('disabled', true);
            $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            let response = await secureCall({ "ajaxcall": true, "ajaxaction": "Reissue SSL Order", }, 'POST');

            $this.find("i.fa-spinner").remove();
            var parsedResponse = JSON.parse(response);
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
    $(document).on("click", ".update_email_approver", async function () {
        try {
            $this = $(this);

            const selectedVal = $('#emailval').val();
            if (!selectedVal) {
                jQuery.growl.error({ title: "Error", message: "Please select Email approver", duration: 3000 });
                return;
            }

            $this.prop('disabled', true);
            $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');

            let response = await secureCall({ "ajaxcall": true, "ajaxaction": "Update Email", "emailval": selectedVal }, 'POST');

            result = response.split("</style>");
            let parsedResponse;
            if (result.length > 1) {
                parsedResponse = JSON.parse(result[1]);
            } else {
                parsedResponse = JSON.parse(response);
            }
            $this.find("i.fa-spinner").remove();
            // var parsedResponse = JSON.parse(response);
            if (parsedResponse.status === true) {
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
                $('.val-email-tr').next('td').text((selectedVal));
                $('#update_email').modal('hide');
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });

            }
            $this.prop('disabled', false);
        } catch (error) {
            console.error(error)
        }
    });
    $(document).on("click", ".change_method", async function () {
        const obj = $(this);

        try {
            Swal.fire({
                title: "Are you sure?",
                text: "You want to Change Validation Method",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes",
            }).then(async (result) => {
                if (result.isConfirmed) {

                    const selectedVal = $('#methods').val();
                    if (!selectedVal) {
                        jQuery.growl.error({ title: "Error", message: "Please select Validation Method", duration: 3000 });
                        return;
                    }

                    obj.prop('disabled', true).append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');

                    let response = await secureCall({ "ajaxcall": true, "ajaxaction": "Change Domain Validation", "method": selectedVal }, 'POST');

                    result = response.split("</style>");
                    let parsedResponse;
                    if (result.length > 1) {
                        parsedResponse = JSON.parse(result[1]);
                    } else {
                        parsedResponse = JSON.parse(response);
                    }
                    obj.find("i.fa-spinner").remove();
                    if (parsedResponse.status === true) {
                        jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
                        // setTimeout(() => location.reload(), 100);
                        $('.val-method-tr').next('td').text(ucfirst(selectedVal));
                        $('#update_method').modal('hide');
                    } else {
                        jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
                    }

                    obj.prop('disabled', false);
                }
            });
        } catch (error) {
            if (obj) {
                obj.find("i.fa-spinner").remove();
            }
            jQuery.growl.error({ title: "Error", message: "Something went wrong. Please try again.", duration: 3000 });
        }
    });

    function ucfirst(str) {
        if (str.length === 0) return str; // Handle empty strings
        return str.charAt(0).toUpperCase() + str.slice(1); // Capitalize first character and append the rest of the string
    }

    $(document).on("click", ".create_csr_btn", async function () {
        try {
            if (!$(".csr-token-form").valid()) {
                return;
            }
            $this = $(this);
            $this.prop('disabled', true);
            $this.append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
            var formData = $('.csr-token-form').serialize();
            let response = await secureCall({ "ajaxcall": true, "ajaxaction": "create token", 'data': formData }, 'POST');

            $this.find("i.fa-spinner").remove();
            var parsedResponse = JSON.parse(response);
            if (parsedResponse.status === true) {
                if (parsedResponse.fieldId && parsedResponse.fieldId !== "") {
                    var id = "#customfield" + parsedResponse.fieldId;
                    $(id).val(parsedResponse.data.csr);
                } else {
                    // Fallback for adminId
                    $(parsedResponse.adminId).val(parsedResponse.data.csr);
                    // $(parsedResponse.adminId).val(parsedResponse.data.public_key.key);
                }
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
                $('#modalAjaxClose').click();
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });

            }
            $this.prop('disabled', false);
        } catch (error) {
            console.error(error)
        }
    });

    $(document).on("change", "#inputCountry", function () {
        if ($('.state-csr .input-div').find('select').length > 0) {
            $('#inputStateIcon').css('display', 'block');
            $('#stateinput').css('display', 'none');
        }
    });

    $(document).on("click", ".copy-btn", function () {
        copyText();
    });

    function copyText() {
        var copyText = $("textarea[name=\'packageconfigoption[6]\']");
        copyText.focus();
        copyText.select();
        document.execCommand("copy");

        var tooltip = $("#myTooltip");
        tooltip.text("Copied");
    }

});

