import './editor.scss';

const { __ } = wp.i18n;
const {
	TextControl,
	SelectControl,
	Toolbar,
	Button,
	Tooltip,
	PanelBody,
	PanelRow,
	FormToggle,
	ToggleControl,
	ToolbarGroup,
	Disabled,
	RadioControl,
	RangeControl,
	FontSizePicker,
} = wp.components;

const { serverSideRender: ServerSideRender } = wp;


const { InspectorControls } = wp.blockEditor;

const productCategories = [
	{ label: __('Select one or more categories', 'wpcafe'), value: null }
];
let emptyProducts = true;
wp.apiFetch({path: "wc/store/products/categories"}).then(categories => {
	if(categories.length < 1 ){
		productCategories.push({ label: __('No Categories Found', 'wpcafe'), value: null });
	}
	emptyProducts = false;
	jQuery.each( categories, function( key, category ) {
		productCategories.push({label: category.name, value: category.id});
	});

}).catch();


export default function Edit(props) {
	const { setAttributes, attributes, className } = props;
	const inspectorControls = (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Settings', 'wpcafe' )}>
				<PanelRow>
					<SelectControl
						label = {__('Menu Order', 'wpcafe')}
						value = {attributes.food_menu_style}
						options={ [
							{ label: __('style 1', 'wpcafe'), value: 'style-1' },
							{ label: __('Style 2', 'wpcafe'), value: 'style-2' },
						] }
						onChange={ ( food_menu_style ) => {
								setAttributes( { food_menu_style } )
							}
						}
					/>
				</PanelRow>

				<PanelRow>
					<ToggleControl
						label = {__('Show Thumbnail', 'wpcafe')}
						checked={attributes.show_thumbnail == "yes" ? true : false}
						onChange={(show_thumbnail) => {
							setAttributes({
								show_thumbnail: show_thumbnail ? 'yes': 'no' ,
							})
						}}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						multiple
						className="multiple_select_control"
						label = {__('Select Categories', 'wpcafe')}
						value = {attributes.wpc_menu_cat}
						options = {productCategories}
						onChange={ ( cats ) => {
							setAttributes( { wpc_menu_cat: cats } )
						}
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label = {__('Show Description', 'wpcafe')}
						checked={attributes.wpc_show_desc == "yes" ? true : false}
						onChange={(show_desc) => setAttributes({
							wpc_show_desc: show_desc ? 'yes': 'no'
						})}
					/>
				</PanelRow>
				{
					attributes.wpc_show_desc == "yes" &&

					<PanelRow>
					<RangeControl
						label = {__('Description Limit', 'wpcafe')}
						value={ attributes.wpc_desc_limit }
						onChange={ ( wpc_desc_limit ) => {
							setAttributes( { wpc_desc_limit } )
						}
						}
						min={ 1 }
						max={ 50 }
					/>
				</PanelRow>
				}
				
				<PanelRow>
					<ToggleControl
						label = {__('Show Cart Button', 'wpcafe')}
						checked={attributes.wpc_cart_button_show == "yes" ? true : false}
						onChange={(cart_button) => setAttributes({
							wpc_cart_button_show: cart_button ? 'yes': 'no' 
						})}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label = {__('Show Title Link', 'wpcafe')}
						checked={attributes.title_link_show == "yes" ? true : false}
						onChange={(title_link) => setAttributes({
							title_link_show: title_link ? 'yes': 'no'
						})}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label = {__('Show Item Status', 'wpcafe')}
						checked={attributes.show_item_status == "yes" ? true : false}
						onChange={(item_status) => setAttributes({
							show_item_status: item_status ? 'yes': 'no'
						})}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label = {__('Show Price', 'wpcafe')}
						checked={attributes.wpc_price_show == "yes" ? true : false}
						onChange={(show_price) => setAttributes({
							wpc_price_show: show_price ? 'yes': 'no' 
						})}
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label = {__('Menu Count', 'wpcafe')}
						value={ attributes.wpc_menu_count }
						onChange={ ( wpc_menu_count ) => {
							setAttributes( { wpc_menu_count } )
						}
						}
						min={ 1 }
						max={ 50 }
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label = {__('Menu Order', 'wpcafe')}
						value = {attributes.wpc_menu_order}
						options={ [
							{ label: __('ASC', 'wpcafe'), value: 'ASC' },
							{ label: __('DESC', 'wpcafe'), value: 'DESC' },
						] }
						onChange={ ( wpc_menu_order ) => {
							setAttributes( { wpc_menu_order } )
						}
						}
					/>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	);

	return [
		<div className={ props.className }>

			<ServerSideRender
				block="wpc/food-menu-list"

				attributes = {{ 
					food_menu_style : attributes.food_menu_style,
					show_thumbnail: attributes.show_thumbnail,
					wpc_menu_cat: attributes.wpc_menu_cat,
					wpc_desc_limit: attributes.wpc_desc_limit,
					wpc_show_desc: attributes.wpc_show_desc,
					wpc_cart_button_show: attributes.wpc_cart_button_show,
					title_link_show: attributes.title_link_show,
					show_item_status: attributes.show_item_status,
					wpc_price_show: attributes.wpc_price_show,
					wpc_menu_count: attributes.wpc_menu_count,
					wpc_menu_order: attributes.wpc_menu_order
				}}
			/>

			{ inspectorControls }

			{ emptyProducts &&
			<p>No Category Found.</p>
			}

		</div>
	];
}
