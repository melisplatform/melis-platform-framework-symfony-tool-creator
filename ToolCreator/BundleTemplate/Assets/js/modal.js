$(document).ready(function(){
    var $body = $('body');
    var selectedId = null;

    /**
     * Open modal to update record
     */
    $body.on("click", ".symfonytpl-btn-update", function(){
        selectedId = $(this).parents("tr").attr("id");
        renderModal("/melis/symfonytpl/form/"+selectedId);
    });

    /**
     * Open modal to add new record
     */
    $body.on("click", "#symfonytpl_btn_new", function(){
        selectedId = null;
        renderModal("/melis/symfonytpl/form");
    });

    /**
     * Save
     */
    $body.on("click", "#btn-save-symfonytpl", function(){
        if(selectedId == null)
            save("/melis/symfonytpl/save");
        else
            save("/melis/symfonytpl/save/"+selectedId);
    });

    /**
     * Delete
     */
    $body.on("click", ".symfonytpl-btn-delete", function(){
        var _this = $(this);
        melisCoreTool.confirm(
            translations.tool_symfony_tpl_confirm_modal_yes,
            translations.tool_symfony_tpl_confirm_modal_no,
            translations.tool_symfony_tpl_confirm_modal_title,
            translations.tool_symfony_tpl_confirm_modal_message,
            function() {
                $.ajax({
                    url: "/melis/symfonytpl/delete",
                    data: {"id" : _this.parents("tr").attr("id")},
                    method: "POST",
                    beforeSend: function(){

                    },
                    success: function(data){
                        // update flash messenger values
                        melisCore.flashMessenger();
                        data = $.parseJSON(data);

                        if(data.success){
                            melisHelper.melisOkNotification(data.title, data.message);
                            //refresh table
                            $("#symfonyTplTable").DataTable().ajax.reload();
                        }else{
                            melisHelper.melisKoNotification(data.title, data.message);
                        }
                    }
                });
            }
        );
    });

    /**
     * Save
     * @param url
     */
    function save(url){
        var form_data = new FormData($("#symfonytpl_prop_form")[0]);

        /**
         * If modal has language tab, we get all
         * of it's data
         */
        $("form.symfonytpl_lang_form").each(function(){
            var langId = $(this).data("lang-id");
            $.each($(this).serializeArray(), function(){
                if (!$(this).prop('disabled')){
                    form_data.append('language['+langId+']['+this.name+']', this.value);
                }
            });

            var formFiles = $(this).find("[type='file']");
            $.each(formFiles, function(){
                if (!$(this).prop('disabled')){
                    if (typeof $(this)[0].files[0] !== "undefined"){
                        form_data.append('language['+langId+']['+$(this).attr("name")+']', $(this)[0].files[0]);
                    }
                }
            });
        });

        $.ajax({
            url: url,
            data: form_data,
            method: "POST",
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function(){
                //disable button
                $("#btn-save-calendar").attr("disabled", true);
                //disable all fields
                $("#symfonytpl_prop_form :input").prop("disabled", true);
            },
            success: function(data){
                // update flash messenger values
                melisCore.flashMessenger();

                data = $.parseJSON(data);
                if(data.success) {
                    $("#symfonyTplModal").modal("hide");
                    melisHelper.melisOkNotification(data.title, data.message);
                    //refresh table
                    $("#symfonyTplTable").DataTable().ajax.reload();
                    //assign null to selectedId id after saving/updating record
                    selectedId = null;
                }else{
                    melisHelper.melisKoNotification(data.title, data.message, data.errors);
                    highlightFormErrors(0, data.errors, "#symfonytpl_prop_form");
                    //LANGUAGE_FORM_ERRORS
                }
                //enable save button
                $("#btn-save-calendar").attr("disabled", false);
                //enable form fields
                $("#symfonytpl_prop_form :input").prop("disabled", false);
            }
        });
    }

    /**
     *
     * @param url
     */
    function renderModal(url)
    {
        var modal = $("#symfonyTplModal");
        modal.modal('show');
        $.ajax({
            url: url,
            method: "GET",
            beforeSend: function(){
                /**
                 * Lets show a loader while waiting for the ajax to get
                 * the content
                 */
                modal.find(".modal-content #loader").removeClass('hidden');
                modal.find(".modal-content .modal-body").addClass('hidden');
                /**
                 * we need to modify a little bit the modal
                 * like changing the text and icon of the header to
                 * determine whether we are going to update or
                 * create a record since we are using one
                 * modal for both update and create
                 */
                var title =  modal.find("li.active").find("a");
                title.removeClass("tag");
                if(selectedId == null)
                    title.removeClass("edit").addClass("plus");
                else
                    title.removeClass("plus").addClass("edit");

            },
            success: function(data){
                data = $.parseJSON(data);

                /**
                 * Hide the load and show the content
                 */
                modal.find(".modal-content #loader").addClass('hidden');
                modal.find(".modal-content .modal-body").removeClass('hidden');
                /**
                 * Replace the content of the modal
                 */

                $.each(data, function(key, content){
                    modal.find(".tab-content #"+key).html(content);
                });
            }
        });
    }

    function highlightFormErrors(success, errors, divContainer) {
        // if all form fields are error color them red
        if (success === 0 || success === false) {
            $(divContainer + " .form-group label").css("color", "#686868");
            $.each(errors, function (key, error) {
                $(divContainer).each(function(i, el){
                    $(this).find(".form-control[name='" + key + "']").parents(".form-group").children(":first").css("color", "red");
                });
            });
        }
        // remove red color for correctly inputted fields
        else {
            $(divContainer + " .form-group label").css("color", "#686868");
        }
    }
});