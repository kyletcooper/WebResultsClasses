jQuery(function($){

    function shadeAutoUpdate(input){
        let $wrapper = $(input).closest(".wrd-color_shades");
        let $shades = $wrapper.find("[data-shade]").not("[data-shade=500]");

        if($(input).is(":checked")){
            $shades.hide();
        }
        else{
            $shades.show();
        }
    }

    $(".wrd-color-shades__automatic__input").on("input", function(e){
        shadeAutoUpdate(this);
    });

    $(".wrd-color-shades__automatic__input").each(function(e){
        shadeAutoUpdate(this);
    });

});