<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://postapanduri.ro
 * @since      1.0.0
 *
 * @package    PostaPanduri
 * @subpackage PostaPanduri/public/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div id="pp-selected-dp">
    <div id="pp-selected-dp-text"></div>
    <button type="button"
            id="pp-selected-dp-map"><?php echo __('Modifica punctul de ridicare', 'postapanduri'); ?></button>
</div>
<div id="harta-pp" class="pp-container">
    <div class="pp-panel">
        <div class="pp-panel__header">
            <div class="pp-col">
                <div class="pp-form-group">
                    <select id="judete" class="pp-form-control pp-chosen-select">
                        <option value="0" selected
                                disabled><?php echo __('Selectati un judet', 'postapanduri'); ?></option>
						<?php
						foreach ($delivery_points_states as $dp) {
							echo '<option value="' . $dp->judet . '" ' . (WC()->session->get('judet') == $dp->judet ? 'selected' : '') . '>' . $dp->judet . '</option>';
						}
						?>
                    </select>
                </div>
            </div>
            <div class="pp-col">
                <div class="pp-form-group" style="display: none;">
                    <!-- <label class="pp-control-label">Selecteaza Locatie</label> -->
                    <select id="orase" class="pp-form-control pp-chosen-select">
                    </select>
                </div>
            </div>
            <div class="pp-col">
                <div class="pp-form-group" style="display: none;">
                    <!-- <label class="pp-control-label">Selecteaza Pachetomat</label> -->
                    <select id="pachetomate" class="pp-form-control pp-chosen-select">
                    </select>
                </div>
            </div>
        </div>

        <div class="pp-panel__body">
            <div class="pp-map-wrap">
                <div class="pp-map">
                    <div class="pp-map__item">
                        <div class="pp-map__item" id="pp-map-canvas"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pp-panel__footer">
            <button type="button" id="pp-close"><?php echo __('Inchide fereastra', 'postapanduri'); ?></button>
        </div>

    </div>

</div>

<input type='hidden' name='lo_delivery_points_select' id='lo_delivery_points_select'
       value="<?php if (isset($lo_delivery_points_select)) {
	       echo $lo_delivery_points_select;
       } ?>">

<div style="display: none">
    <script type="text/javascript">
        var ppLocationsArray = <?php echo $delivery_points?>;
        var last_dp_id = "<?php echo WC()->session->get('dp_id') ?: ''?>";
        var last_dp_name = "<?php echo WC()->session->get('dp_name') ?: ''?>";
        var icon = "<?php echo plugin_dir_url(__FILE__) . '../../../img/location-pin.png';?>";
    </script>
</div>
