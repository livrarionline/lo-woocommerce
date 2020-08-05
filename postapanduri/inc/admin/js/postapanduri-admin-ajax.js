(function ($) {
    'use strict';
    var $formAWB = $('#awb');
    var $rezultat = $('#rezultat');
    var $rezultatAWB = $('#rezultat-awb');
    var $holderAwbGenerat = $('#holder-awb-generat');
    var $getAwb = $('#get-awb');
    var $cancelAwb = $('#cancel-awb');
    var $trackingAwb = $('#tracking-awb');


    $getAwb.on("click", function (e) {
        e.preventDefault();
        /* Inlocuieste butonul Genereaza AWB cu un mesaj */
        $(this).hide().after('<span class="processing">Se proceseaza, va rugam asteptati...</span>');
        $rezultat.html('');
        $('p.raspuns-colet').remove();
        $('b.awb-anulat').remove();

        $.ajax({
            type: "POST",
            url: ppaadmin.ajaxurl,
            data: {
                'action': 'genereaza_awb',
                'data': $formAWB.find('input,select').serialize()
            },
            dataType: 'json',
            error: function (tXMLHttpRequest, textStatus, errorThrown) {
                $('span.processing').remove();
                $holderAwbGenerat.html('<div class="error inline"><p>Din pacate nu s-a putut genera AWB-ul. Motiv: <b>' + errorThrown + '</b></p></div>');
                $getAwb.show();
            },
            success: function (mesaj) {
                if (mesaj.status != "error") {
                    $('span.processing').remove();
                    $holderAwbGenerat.html('<div class="updated settings-error notice"><p>' + mesaj.message + '</p></div>');
                    $formAWB.hide();
                } else {
                    $('span.processing').remove();
                    $holderAwbGenerat.html('<div class="error inline"><p>Din pacate nu s-a putut genera AWB-ul. Motiv: <b>' + mesaj.message + '</b></p></div>');
                    $getAwb.show();
                }
                ;
            }
        });
    });

    $('#form_generare_awb').on('click', '#tracking-awb', function (e) {
        e.preventDefault();
        $rezultat.hide();

        $('p.raspuns-colet').after('<span class="processing" style="display:block;margin:5px;">Se proceseaza, va rugam asteptati...</span>');
        $.ajax({
            type: "POST",
            url: ppaadmin.ajaxurl,
            data: {
                'action': 'tracking_awb',
                'data': $('#form-tracking-awb').find('input').serialize()
            },
            dataType: 'json',
            error: function (tXMLHttpRequest, textStatus, errorThrown) {
                $('span.processing').remove();
                $holderAwbGenerat.html('<div class="error inline"><p>Din pacate nu s-a putut urmari AWB-ul. Motiv: <b>' + errorThrown + '</b></p></div>');
                $getAwb.show();
            },
            success: function (mesaj) {
                if (mesaj.status != "error") {
                    $('span.processing').html(mesaj.message);
                } else {
                    $('span.processing').remove();
                    $holderAwbGenerat.html('<div class="error inline"><p>Din pacate nu s-a putut urmari AWB-ul. Motiv: <b>' + mesaj.message + '</b></p></div>');
                    $getAwb.show();
                }
                ;
            }
        });
    });

    $('#form_generare_awb').on('click', '#cancel-awb', function (e) {
        e.preventDefault();
        $('#form-tracking-awb').hide();
        $('#print-awb').hide();
        $(this).hide().after('<span class="processing">Se proceseaza, va rugam asteptati...</span>');

        $.ajax({
            type: "POST",
            url: ppaadmin.ajaxurl,
            data: {
                'action': 'cancel_awb',
                'data': $('#form-cancel-awb').find('input').serialize()
            },
            dataType: 'json',
            error: function (tXMLHttpRequest, textStatus, errorThrown) {
                $('span.processing').remove();
                $holderAwbGenerat.html('<div class="error inline"><p>Din pacate nu s-a putut anula AWB-ul. Motiv: <b>' + errorThrown + '</b></p></div>');
                $getAwb.show();
            },
            success: function (mesaj) {
                if (mesaj.status != "error") {
                    $('span.processing').remove();
                    $holderAwbGenerat.html('<div class="updated settings-error notice"><p>AWB-ul a fost anulat cu succes</p><a id="reload" href="javascript:void(0)">Click aici pentru a reincarca pagina</a></div>');
                    $formAWB.hide();
                } else {
                    $('span.processing').remove();
                    $holderAwbGenerat.html('<div class="error inline"><p>Din pacate nu s-a putut anula AWB-ul. Motiv: <b>' + mesaj.message + '</b></p></div>');
                    $getAwb.show();
                }
                ;
            }
        });
    });
    $('#adauga-pachet').on('click', function () {
        var clonedRow = $('tbody>tr:last', '#colete').clone().find(':input').val(0).end();
        clonedRow.find('select').val(1).end();
        $('tbody>tr:last', '#colete').after(clonedRow);
        var $nrcolete = $('#nrcolete');
        $nrcolete.val(parseInt($nrcolete.val()) + 1);
    });
    $('#form_generare_awb').on('click', '#reload', function () {
        location.reload();
    })
})(jQuery);
