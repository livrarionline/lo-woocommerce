(function ($) {
    let map;
    const {__, _x, _n, _nx} = wp.i18n;
    'use strict';

    $('body').on('updated_checkout', function () {
        let sm = '';
        if ($('input.shipping_method').length == 1) {
            sm = $('input[type="hidden"].shipping_method', '#order_review').val();
        } else {
            sm = $('input.shipping_method:checked', '#order_review').val();
        }
        if (typeof sm !== 'undefined') {
            sm = sm.split('_')[0];
        }

        if (sm === 'pachetomat') {
            if (!last_dp_id) {
                $('#harta-pp').css({
                    'transform': 'translateY(0)',
                    'z-index': '999',
                    'display': 'block'
                });
                $('#pp-selected-dp-map').text(__('Alege punctul de ridicare', 'postapanduri'));
                $('body').addClass('pp-overlay');
                $('#judete', '#order_review').trigger('change');
                map = new GMaps({
                    div: '#pp-map-canvas',
                    lat: 46.203567,
                    lng: 25.003274,
                    zoom: 12,
                });
                plotMarkers(ppLocationsArray);
            } else {
                $('#pp-selected-dp-text').html(__('Coletul va fi livrat la', 'postapanduri') + ' <b>' + last_dp_name + '</b>');
                $('body').removeClass('pp-overlay');
                $('#pp-selected-dp-map').focus();
            }
            $('#pp-close').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $('#pp-selected-dp-text').html(__('Coletul va fi livrat la', 'postapanduri') + ' <b>' + $('#pachetomate option:selected').text() + '</b>');
                $('#harta-pp').hide();
                $('body').removeClass('pp-overlay');
                $('#pp-selected-dp-map').focus();
                if (last_dp_id) {
                    $('body').trigger('update_checkout');
                }
            });
            $('#pp-selected-dp-map').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $('#harta-pp').css({
                    'transform': 'translateY(0)',
                    'z-index': '999',
                    'display': 'block'
                });

                $('body').addClass('pp-overlay');
                $('#judete', '#order_review').trigger('change');

                map = new GMaps({
                    div: '#pp-map-canvas',
                    lat: 46.203567,
                    lng: 25.003274,
                    zoom: 12,
                });
                plotMarkers(ppLocationsArray);
            });

        }
    });


    $('#order_review').on('change', '#judete', function (event, data) {
        let js = $('option:selected', this).val();
        $('.pp-panel__body', '#order_review').show();
        $.ajax({
            type: "POST",
            dataType: 'json',
            url: ppa.ajaxurl,
            data: {
                'action': 'ajax_get_localitati',
                'judet': $('option:selected', this).val()
            },
            beforeSend: function () {
                $('#pp-close').text(__('Inchide fereastra', 'postapanduri'));
                $('#judete').closest('li').find('input.shipping_method').attr('checked', true);
                $('#orase').find('option').remove().end();
                $('#pachetomate').find('option').remove().end();
            },
            success: function (response) {
                let count = response.count;
                let orase = response.orase;
                let pachetomate = response.pachetomate;
                let oras_selectat;
                let pselected;
                let jselected;

                if (typeof data !== 'undefined' && typeof data.preselected_state !== 'undefined') {
                    oras_selectat = data.preselected_state;
                } else {
                    oras_selectat = response.selected;
                }
                if (typeof data !== 'undefined' && typeof data.preselected_dp_id !== 'undefined') {
                    pselected = data.preselected_dp_id;
                } else {
                    pselected = response.pselected;
                }
                if (typeof data !== 'undefined' && typeof data.preselected_county !== 'undefined') {
                    jselected = data.preselected_county;
                } else {
                    jselected = response.jselected;
                }

                $('#orase').append($('<option>', {
                    value: 0,
                    text: 'Selectati un oras',
                    disabled: true,
                    selected: true
                }));
                $('#pachetomate').append($('<option>', {
                    value: 0,
                    text: __('Selectati un punct de ridicare', 'postapanduri'),
                    disabled: true,
                    selected: true
                }));
                $.each(orase, function (index, value) {
                    $('#orase').append($('<option>', {
                        value: value.oras,
                        text: value.oras
                    }));
                });

                if (jselected == js && oras_selectat && pselected) {
                    $('#orase').val(oras_selectat);
                    $('#orase').trigger('change', {'preselected_state': oras_selectat, 'preselected_dp_id': pselected});
                } else if (count == 1) {
                    $('#orase option').eq(1).prop('selected', true);
                    $('#orase').trigger('change');
                    plotMarkers(pachetomate);
                } else {
                    plotMarkers(pachetomate);
                }

                $('#orase').closest('.pp-form-group').show();
            }
        });
    });

    $('#order_review').on('change', '#orase', function (event, data) {
        let os = $('option:selected', this).val();
        $.ajax({
            type: "POST",
            dataType: 'json',
            url: ppa.ajaxurl,
            data: {
                'action': 'ajax_get_pachetomate',
                'oras': $('option:selected', this).val()
            },
            beforeSend: function () {
                $('#pp-close').text(__('Inchide fereastra', 'postapanduri'));
                $('#pachetomate').find('option').remove().end()
            },
            success: function (response) {
                let pachetomate = response.pachetomate;
                let pachetomat_selectat;
                let oselected;
                if (typeof data !== 'undefined' && typeof data.preselected_state !== 'undefined') {
                    oselected = data.preselected_state;
                } else {
                    oselected = response.oselected;
                }

                if (typeof data !== 'undefined' && typeof data.preselected_dp_id !== 'undefined') {
                    pachetomat_selectat = data.preselected_dp_id;
                } else {
                    pachetomat_selectat = response.selected;
                }

                last_dp_id = pachetomat_selectat;
                last_dp_name = response.selected_name;
                $('#pachetomate').append($('<option>', {
                    value: 0,
                    text: __('Selectati un punct de ridicare', 'postapanduri'),
                    disabled: true,
                    selected: true
                }));
                $.each(pachetomate, function (index, value) {
                    let p = $('<option>', {
                        value: value.dp_id,
                        text: value.dp_denumire + (value.dp_active == 10 ? ' - ' + __('Pachetomat plin', 'postapanduri') : '')
                    });
                    if (value.dp_active == 10) {
                        p.prop('disabled', true);
                    }
                    $('#pachetomate').append(p);
                });

                if (pachetomate.length === 1 && !(os === oselected && pachetomat_selectat)) {
                    if (!$('#pachetomate option').eq(1).prop('disabled')) {
                        $('#pachetomate option').eq(1).prop('selected', true);
                        $('#pachetomate').trigger('change');
                    }
                } else if (os === oselected && pachetomat_selectat) {
                    $('#pachetomate').val(pachetomat_selectat);
                    $('#pachetomate').trigger('change', {'preselected_dp_id': pachetomat_selectat});
                }

                $('#pachetomate').closest('.pp-form-group').show();
            }
        });
    });

    $('#order_review').on('change', '#pachetomate', function () {
        let dp_id = $('option:selected', this).val();

        $.ajax({
            type: "POST",
            dataType: 'json',
            url: ppa.ajaxurl,
            data: {
                'action': 'ajax_get_pachetomat',
                'pachetomat': dp_id,
            },
            beforeSend: function () {
                $('#pp-close').text(__('Inchide fereastra', 'postapanduri'));
            },
            success: function (response) {
                let pachetomat_selectat = response.selected;
                last_dp_id = pachetomat_selectat;
                $('#pp-close').text(__('Confirma selectie Pachetomat','postapanduri'));
                showMarkerDetails(pachetomat_selectat);
            }
        });
    });

    function plotMarkers(m) {
        if (m) {
            map.removeMarkers();
            let markers_data = [];
            m.forEach(function (marker) {
                let temperatura = '';
                if (typeof marker.dp_temperatura !== 'undefined') {
                    temperatura = '<div>' + __('Temperatura', 'postapanduri') + ': <b>' + marker.dp_temperatura.split('.')[0] + '<sup>o</sup>C</b></div>';
                }
                markers_data.push({
                    id: marker.dp_id,
                    lat: parseFloat(marker.dp_gps_lat),
                    lng: parseFloat(marker.dp_gps_long),
                    title: marker.dp_denumire,
                    icon: {
                        size: new google.maps.Size(20, 30),
                        url: icon
                    },
                    run_ajax: true,
                    click: function (t) {
                        let id = t.id;
                        if (id > 0 && t.run_ajax && marker.dp_active > 0 && marker.dp_active != 10) {
                            $.ajax({
                                type: "POST",
                                dataType: 'json',
                                url: ppa.ajaxurl,
                                data: {
                                    'action': 'ajax_get_pachetomat',
                                    'pachetomat': id,
                                },
                                beforeSend: function () {
                                },
                                success: function (response) {
                                    last_dp_id = response.pachetomat.dp_id;
                                    $('#judete', '#order_review').val(response.pachetomat.dp_judet).trigger('change', {
                                        'preselected_county': response.pachetomat.dp_judet,
                                        'preselected_state': response.pachetomat.dp_oras,
                                        'preselected_dp_id': response.pachetomat.dp_id
                                    });
                                    showMarkerDetails(response.selected);
                                }
                            });
                        } else {
                            t.run_ajax = true;
                        }
                    },
                    infoWindow: {
                        content: '<div class="pp-map__infowindow infowindow">\
				  				<div class="infowindow-header">\
									<div class="infowindow-body">\
										<h3 class="infowindow-title">' + marker.dp_denumire + '</h3>\
										<div>' + marker.dp_adresa + ', ' + marker.dp_oras + ', ' + marker.dp_judet + ' (' + (marker.dp_indicatii ? marker.dp_indicatii : '') + ')</div>\
										<hr class="hr--dashed" />\
										' + temperatura + '\
										<div>' + marker.orar + '</div>\
				 '
                    }
                });
            });
            map.addMarkers(markers_data);
            map.fitZoom();
        }
    }

    function showMarkerDetails(id) {
        map.markers.forEach(function (value, index) {
            if (value.id == id) {
                let position = map.markers[index].getPosition();
                let lat = position.lat();
                let lng = position.lng();
                map.setCenter(lat, lng);
                map.setZoom(12);
                map.markers[index].run_ajax = false;
                google.maps.event.trigger(map.markers[index], 'click');
            }
        });
    }

})(jQuery);
