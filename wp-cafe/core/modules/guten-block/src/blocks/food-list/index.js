import Edit from './edit';
import Save from './save';

const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;



registerBlockType( 'wpc/food-menu-list', {
	title: __( 'WPC Food List', 'wpcafe' ),
	icon: 'list-view',
	category: 'wp-cafe-blocks',
	apiVersion: 1,
	keywords: [
		__('WPC Food List', 'wpcafe'),
		__( 'WP Cafe' , 'wpcafe'),
		__( 'Food Tab' , 'wpcafe'),
	],
	attributes:{ 
		food_menu_style: {
			type: 'string',
			default: 'style-1'
		},
		show_thumbnail: {
			type: 'string',
			default: 'yes'
		},
		wpc_menu_cat: {
			type: "array",
			default: []
		},
		wpc_desc_limit: {
			type: "integer",
			default: 20
		},
		wpc_show_desc: {
			type: 'string',
			default: 'yes'
		},
		wpc_cart_button_show : {
			type: 'string',
			default: 'yes'
		},
		title_link_show : {
			type: 'string',
			default: 'yes'
		},
		show_item_status : {
			type: 'string',
			default: 'yes'
		},
		wpc_price_show : {
			type: 'string',
			default: 'yes'
		},
		wpc_menu_count: {
			type: "integer",
			default: 20
		},
		wpc_menu_order: {
			type: 'string',
			default: 'DESC'
		},
	},
	edit: Edit,

	save: Save
} );
