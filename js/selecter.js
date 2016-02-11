var Selecter = function(parent, object, shift_class, sel_class, unsel_class) {

    $(parent).click(function(){
        $(object)
            .removeClass(shift_class)
            .removeClass(sel_class)
            .addClass(unsel_class);
    });

    $(parent).on("click", object, function(event){
        event.stopPropagation();

        if ($(this).hasClass(unsel_class)) {
            var select = false;
        } else
        if ($(this).hasClass(sel_class)) {
            var select = true;
        }

        if (event.ctrlKey) {
            $(object).removeClass(shift_class);
            $(this).addClass(shift_class);

            if (!select) {
                $(this)
                    .removeClass(unsel_class)
                    .addClass(sel_class);
            } else {
                $(this)
                    .removeClass(sel_class)
                    .addClass(unsel_class);
            }
        }
        else if (event.shiftKey) {
            $(object)
                .removeClass(sel_class)
                .addClass(unsel_class);

            if ($(object).hasClass(shift_class)) {
                var listItem = $("." + shift_class);
                var start = ($(object).index(listItem));
            } else {
                var start = 0;
            }

            var end = $(object).index(this);

            if (start < end) {
                for ( var i = start; i < end; i++ ) {
                    $(object)
                        .eq(i)
                        .removeClass(unsel_class)
                        .addClass(sel_class);
                };
            } else {
                for ( var i = start; i > end; i-- ) {
                    $(object)
                        .eq(i)
                        .removeClass(unsel_class)
                        .addClass(sel_class);
                };
            }

            $(this)
                .removeClass(unsel_class)
                .addClass(sel_class);
        } else {
            $(object).removeClass(shift_class);
            $(this).addClass(shift_class);
            $(object)
                .removeClass(sel_class)
                .addClass(unsel_class);

            $(this)
                .removeClass(unsel_class)
                .addClass(sel_class);
        }
    });

};
