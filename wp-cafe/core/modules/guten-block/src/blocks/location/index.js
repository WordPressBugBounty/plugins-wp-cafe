import Edit from './edit';
import Save from './save';
const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

registerBlockType( 'wpc/location', {
	title: __( 'WPC Location', 'wpcafe' ),
	icon: 'editor-table',
	category: 'wp-cafe-blocks',
	apiVersion: 1,
	keywords: [
		__('Location', 'wpcafe'),
		__( 'WP Cafe', 'wpcafe' ),
		__( 'Food Tab', 'wpcafe' ),
	],
	parent: ['woocommerce/checkout-fields-block'],
	//wp-block-woocommerce-checkout-fields-block
	//wp-block-woocommerce-checkout-contact-information-block
	//woocommerce/checkout-contact-information-block
	attributes: {
		lock: { 'type' : 'object', 'default': { 'remove': true, 'move': true } },		 
	},
	edit: Edit,

	save: Save
} );
