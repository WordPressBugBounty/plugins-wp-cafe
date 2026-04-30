<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals -- template scope; locally-extracted variables, hook names include backward-compat filters.

// check condition for calender view
if ( isset($atts['form_style']) ) {
    switch ( $atts['form_style'] ) {
        case "1":
            $view = "yes";
            break;
        case "2":
            $view = "no";

            break;
        default:
            $view = "yes";
            break;
    }
}
$multi_schedule        = !empty($settings['reser_multi_schedule']) ? $settings['reser_multi_schedule'] : "off";
$multi_sch_class       = $multi_schedule == "on" ? "wpc-multi-reservation-msg" : "";

?>

	<div class='wpc-reservation-form <?php echo esc_attr($cancellation_option) ?>' data-reservation_status='<?php echo esc_attr( wp_json_encode( $booking_status ) ); ?>'>
			<div class='late_booking' data-late_booking="<?php echo esc_html($late_one.$late_two.$late_three.$late_four.$late_five);?>"></div>
			<div class='wpc_cancell_log_message'></div>
			<div class='wpc_error_message' data-time_compare="<?php echo esc_html__('Booking end time must be after start time','wp-cafe')?>"></div>
			<div class='wpc_success_message <?php echo esc_attr($multi_sch_class)?>' data-start="<?php echo esc_html__("Start time",'wp-cafe');?>" data-end="<?php echo esc_html__("End time",'wp-cafe');?>" data-schedule="<?php echo esc_html__("Schedule",'wp-cafe');?>" data-late_booking = "<?php echo ( $wpc_late_bookings !=="" ) 
			?  sprintf( /* translators: %s: number of minutes */ esc_html__("You can book up until %s minutes before closing time",'wp-cafe') , esc_html( $wpc_late_bookings ) ) : "" ?>"></div>
			<div class='wpc_calender_view' data-view="<?php echo esc_html($view);?>"></div>
			<div class='date_missing' data-date_missing="<?php echo esc_html__("Please select a date first",'wp-cafe');?>"></div>
			<div class="form_style" data-form_style="free-<?php echo esc_attr( $style )?>" data-form_type="free"></div>
			<div class='wpc_reservation_form_two' style='display:none;'>
				<div class='wpc_reservation_form_two'>
					<form method='post' class=' wpc_reservation_table'>
						<div class='wpc-reservation-form'>
							<div class='wpc-row'>
								<?php if ($view === "yes") { ?>
										<div class='wpc-col-lg-6 wpc_bg_image' style="background-image: url(<?php echo esc_url($wpc_image_url) ?>);"></div>
								<?php } ?>
								<div class='<?php echo esc_attr($column_lg); ?>'>
									<div class='wpc_reservation_form wpc_reservation_user_info'>
										<!-- Reservation booking detials -->
										<?php
												if ( file_exists( wpcafe()->plugin_directory . "core/shortcodes/views/reservation/reservation-detials.php" ) ) {
														include_once wpcafe()->plugin_directory . "core/shortcodes/views/reservation/reservation-detials.php";
												}
										?>
										<button class='confirm_booking_btn wpc-btn' data-id='reservation_form_second_step'><?php echo esc_html( $booking_button_text ); ?></button>
										<button class='edit_booking_btn wpc-btn' data-id='edit_booking_btn'><?php echo esc_html__('Edit Booking', 'wp-cafe'); ?></button>
										<button class="wpc-another-reservation-free action-button wpc-btn" name="another_reservation_free"><i class="dashicons dashicons-image-rotate"></i><?php echo esc_html__('Book Again', 'wp-cafe'); ?></button>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
	
			<form method='post' class='wpc_reservation_table'>
				<input type='hidden' name='wpc_action' value='wpc_reservation' />
				<div class='wpc_reservation_form_one'>
					<div class="wpc-row">
						<?php if ("yes" === $view ) { ?>
							<div class='wpc-col-lg-6 wpc-align-self-center'>
								<div class='wpc-reservation-field date wpc-reservation-calender-field'>
									<h3 class="wpc-choose-date"><?php echo esc_html__('Choose a Date', 'wp-cafe'); ?></h3>
									<input type='text' name='wpc_booking_date' class='wpc-form-control' id='wpc_booking_date' value='' required aria-required='true' />
								</div>
							</div>
						<?php } ?>
						<div class='<?php echo esc_attr($column_lg); ?>'>
								<div class="wpc_reservation_form">
										<?php
										if ( isset($show_branches) && "yes" == $show_branches ) {
												?>
												<div class="wpc-row">
														<div class="wpc-col-lg-12 wpc-align-self-center">
																<div class='wpc-reservation-field branch'>
																		<label for='wpc-branch'><?php echo esc_html__('Which branch of our restaurant', 'wp-cafe'); echo ( $require_branch == "required" ) ? "<small class='wpc_required'>*</small>" : "" ?></label>
																		<select name='wpc_branch' id='wpc-branch' class='wpc-form-control' <?php echo esc_attr($require_branch == "required" ? "required" : ""); ?>>
																				<?php foreach( $wpc_location_arr as $key=>$branch ) {?>
																						<option value="<?php echo esc_attr( $key ); ?>" <?php echo count($wpc_location_arr) <= 2? "selected='selected'" : "" ?> ><?php echo esc_html( $branch ); ?></option>
																				<?php } ?>
																		</select>
																</div>
														</div>
												</div>
										<?php } ?>
										<div class="wpc-row">
												<div class='wpc-col-md-6'>
														<div class='wpc-reservation-field name'>
																<label for='wpc-name'><?php echo esc_html__('Your Name', 'wp-cafe'); ?><small class='wpc_required'>*</small></label>
																<input type='text' name='wpc_name' placeholder='<?php echo esc_html__('Name here', 'wp-cafe'); ?>' id='wpc-name' class='wpc-form-control' value='' required aria-required='true'>
																<div class="wpc-name wpc_danger_text"></div>
														</div>
												</div>
												<div class='wpc-col-md-6'>
														<div class='wpc-reservation-field email'>
																<label for='wpc-email'><?php echo esc_html__('Your Email', 'wp-cafe'); ?><small class='wpc_required'>*</small></label>
																<input type='email' name='wpc_email' placeholder='<?php echo esc_html__('Email here', 'wp-cafe'); ?>' class='wpc-form-control' id='wpc-email' value='' required aria-required='true'>
																<div class="wpc-email wpc_danger_text"></div>
														</div>
												</div>
										</div>
										<div class='wpc-row'>
												<div class='<?php echo esc_attr($column_md) ?>'>
														<div class='wpc-reservation-field phone'>
																<label for='wpc-phone'><?php echo esc_html__('How can we contact you?', 'wp-cafe');
																		echo ( isset($phone_required) && $phone_required == "required" ) ? "<small class='wpc_required'>*</small>" : "" ?>
																</label>
																<input type='tel' placeholder='<?php echo esc_html__('Phone Number here', 'wp-cafe'); ?>' <?php echo ( isset($phone_required) && $phone_required == "required" ) ? esc_attr("required") : ""; ?> name='wpc_phone' class='wpc-form-control' id='wpc-phone' value=''>
																<div class="wpc-phone wpc_danger_text"></div>
														</div>
												</div>
												<?php if ($view === "no") { ?>
														<div class='wpc-col-lg-6'>
																<div class='wpc-reservation-field'>
																		<label for='wpc_booking_date'><?php echo esc_html__('Date', 'wp-cafe'); ?><small class='wpc_required'>*</small></label>
																		<input type='text' placeholder='<?php echo esc_html__('Booking date here', 'wp-cafe'); ?>' name='wpc_booking_date' class='wpc-form-control' id='wpc_booking_date' value='' required aria-required='true' />
																</div>
														</div>
												<?php } ?>
										</div>
										<div class='wpc-row'>
												<div class='<?php echo esc_attr( $from_to_column ); ?>'>
														<?php if( $show_form_field == 'on'): ?>
																<div class='wpc-reservation-field time'>
																		<label for='wpc_from_time'><?php echo esc_html( $from_field_label); ?>
																				<?php if ( $required_from_field == 'on') : ?>
																						<small class='wpc_required'>*</small>
																				<?php endif; ?>
																		</label>
																		<input type='text' name='wpc_from_time' placeholder='<?php echo esc_html__('Start time here', 'wp-cafe'); ?>' class='wpc-form-control' id='wpc_from_time' value='' <?php echo ( $required_from_field == 'on' ) ? 'required aria-required="true"' : '' ?>  >
																		<span class="dashicons dashicons-clock"></span>

																</div>
														<?php endif;?>
												</div>
												<div class='<?php echo esc_attr( $from_to_column ); ?>'>
														<?php if( $show_to_field == 'on' ): ?>
																<div class='wpc-reservation-field time'>
																		<label for='wpc_to_time'><?php echo esc_html( $to_field_label); ?>
																				<?php if ( $required_to_field == 'on') : ?>
																						<small class='wpc_required'>*</small>
																				<?php endif; ?>
																		</label>
																		<input type='text' name='wpc_to_time' placeholder='<?php echo esc_html__('End time here', 'wp-cafe'); ?>' class='wpc-form-control' id='wpc_to_time' value='' <?php echo ( $required_to_field == 'on' ) ? 'required aria-required="true"' : '' ?> >
																		<span class="dashicons dashicons-clock"></span>
																</div>
														<?php endif;?>
												</div>
										</div>
										<?php
											$gest_limit = WpCafe\Utils\Wpc_Utilities::get_seat_count_limit();
										?>
										<div class='wpc-select party wpc-reservation-field'>
												<label for='wpc-party'><?php echo esc_html__('Total Guests ', 'wp-cafe'); ?><small class='wpc_required'>*</small></label>
												<select name='wpc_guest_count' id='wpc-party' class='wpc-form-control' required aria-required='true'>
														<option value=""><?php echo esc_html__("Select number of guests",'wp-cafe')?></option>
														<?php foreach ($gest_limit as $i) {
																$selected = ($wpc_default_gest_no == $i) ? "selected" : ""; ?>
																<option value='<?php echo esc_attr( $i ); ?>' <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $i ); ?></option>
														<?php } ?>
												</select>
										</div>

										<div class='wpc-reservation-fieldarea message wpc-reservation-field'>
												<label for='wpc-message'><?php echo esc_html__('Additional Information', 'wp-cafe'); ?></label>
												<textarea name='wpc_message' placeholder='<?php echo esc_html__('Enter Your Message here', 'wp-cafe'); ?>' id='wpc-message' class='wpc-form-control'></textarea>
										</div>
										<?php if(function_exists('wpcafe_pro')): ?>
											<div class='wpc-reservation-field wpc-webhook'>
													<input type='hidden' placeholder='<?php echo esc_html__('Webhook Url', 'wp-cafe'); ?>' name='wpc_webhook' class='wpc-form-control wpc_webhook' id='wpc-webhook' value='<?php echo esc_html($fluent_crm_webhook); ?>'>
											</div>
										<?php endif; ?>
										<?php
										// render extra field
										do_action('reservation_extra_fields_markup');
										?>
										
										<input type='hidden' value='reservation_form_first_step' class='reservation_form_first_step' />
										<button type='submit' class='reservation_form_submit wpc-btn'><?php echo esc_html( $first_booking_button ); ?></button>
										<span id='wpc_cancel_request'><?php echo esc_html($cancel_button_text); ?></span>
								</div>
						</div>
					</div>
				</div>
			</form>


	</div>

