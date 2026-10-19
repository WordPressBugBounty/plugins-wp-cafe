<?php
/**
 * Reservation form placeholder, printed inside the mount box so the page does not
 * jump while the React form loads. React shows this same markup until the real
 * form is ready (assets/src/frontend/ServerSkeleton.tsx).
 *
 * @var array $layout From wpc_reservation_skeleton_layout().
 */
defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template scope; included inside wpc_reservation_form_skeleton(), not real globals.

$section_class = 'wpc-rfs-section' . ( 'style-2' === $layout['style'] ? ' wpc-rfs-style-2' : '' );
$layout_class  = 'wpc-rfs-layout' . ( $layout['calendar'] ? ' wpc-rfs-layout--calendar' : '' );
?>
<div class="wpc-rfs wpc-rfs-pulse">
	<span class="wpc-rfs-sr" role="status"><?php esc_html_e( 'Loading reservation form', 'wp-cafe' ); ?></span>
	<div class="<?php echo esc_attr( $layout_class ); ?>" aria-hidden="true">
		<?php if ( $layout['calendar'] ) : ?>
			<span class="wpc-rfs-bar wpc-rfs-calendar"></span>
		<?php endif; ?>
		<div class="wpc-rfs-card">
			<?php if ( $layout['wizard_steps'] > 1 ) : ?>
				<span class="wpc-rfs-bar wpc-rfs-steps"></span>
			<?php endif; ?>
			<?php foreach ( $layout['sections'] as $section ) : ?>
				<div class="<?php echo esc_attr( $section_class ); ?>">
					<?php if ( $section['label'] ) : ?>
						<span class="wpc-rfs-bar wpc-rfs-heading"></span>
					<?php endif; ?>
					<?php foreach ( $section['fields'] as $type ) : ?>
						<div class="wpc-rfs-field wpc-rfs-field--<?php echo esc_attr( sanitize_html_class( $type ) ); ?>">
							<span class="wpc-rfs-bar wpc-rfs-label"></span>
							<span class="wpc-rfs-bar wpc-rfs-input"></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
			<?php if ( $layout['payments'] ) : ?>
				<span class="wpc-rfs-bar wpc-rfs-payments"></span>
			<?php endif; ?>
			<div class="wpc-rfs-actions">
				<span class="wpc-rfs-bar"></span>
				<span class="wpc-rfs-bar"></span>
			</div>
		</div>
	</div>
</div>
