import './editor.scss';
const { __ } = wp.i18n;

const { 
	PanelBody, 

} = wp.components;

const { serverSideRender: ServerSideRender } = wp;

const {
	InspectorControls
} = wp.blockEditor;
const {
	Fragment
} = wp.element;

 


export default function Edit(props) {
	const { setAttributes, attributes, className } = props;

	const inspectorControls = (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Settings' , 'wpcafe' )}>
				 
			</PanelBody>
		</InspectorControls>
	);

	return [
		<div className={ props.className }>

			<ServerSideRender
				block="wpc/pickup-delivery"
				attributes={{  }}
			/>

			{ inspectorControls }
			
			 
		</div>
	];
}
