(function ($) {
    'use strict';
    var $servicii = $("#servicii");
    $("span.add_serviciu").on("click", function () {
        var regex = /\d/;
        var $last = $('input.activ_serviciu:last');
        var last_name = $last.attr('name') || "";
        var m = last_name.match(regex) || [];
        var max = parseInt(m[0]);
        var $cloneTable = $("table.adauga_serviciu_table:last");
        var $clone = $cloneTable.clone();
        $clone.find("input,select,textarea").each(function () {
            this.value = '';
            this.checked = false;
            var name = this.name || "";
            var match = name.match(regex) || [];
            name = name.replace(match[0], max + 1);
            this.name = name;
        });
        $servicii.append($clone);
    });
    $servicii.on("click", ".sterge_serviciu", function () {
        $(this).closest(".adauga_serviciu_table").remove();
    });
})(jQuery);
