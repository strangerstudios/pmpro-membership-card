import {__} from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from '@wordpress/block-editor';
import {PanelBody, TextareaControl, SelectControl, ToggleControl} from '@wordpress/components';

export default function Edit({attributes, setAttributes, isSelected}) {
	const blockProps = useBlockProps();
	const textDomain = 'pmpro-membership-card';
	const {elements, print_size, qr_code, qr_data, show_avatar} = attributes;
	
	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Membership Card Settings', textDomain)}>
					<ToggleControl
						label={__('Show Avatar', textDomain)}
						checked={show_avatar}
						onChange={(value) => setAttributes({show_avatar: value})}
					/>
					<ToggleControl
						label={__('Display QR Code', textDomain)}
						help={__('Optionally display a QR code on the card.', textDomain)}
						checked={qr_code}
						onChange={(value) => setAttributes({qr_code: value})}
					/>
					{qr_code && (
						<SelectControl
							label={__('QR Code Data', textDomain)}
							help={__('Specify what data the scanned QR code should return. If set to “other”, you must leverage the pmpro_membership_card_qr_data_other filter hook to set the value.', textDomain)}
							value={qr_data}
							options={[
								{label: __('ID', textDomain), value: 'ID'},
								{label: __('Email', textDomain), value: 'email'},
								{label: __('Level', textDomain), value: 'level'},
								{label: __('Other', textDomain), value: 'other'},
							]}
							onChange={(value) => setAttributes({qr_data: value})}
						/>
					)}
					<SelectControl
						label={__('Print Size', textDomain)}
						help={__('Specify what sizes to include in the print view.', textDomain)}
						value={print_size}
						options={[
							{label: __('All', textDomain), value: 'all'},
							{label: __('Small', textDomain), value: 'small'},
							{label: __('Medium', textDomain), value: 'medium'},
							{label: __('Large', textDomain), value: 'large'},
						]}
						onChange={(value) => setAttributes({print_size: value})}
					/>
					<TextareaControl
						label={__('Elements (optional)', textDomain)}
						help={<>{__('Enter one element per line using the format: label,field. If left blank, the default membership card layout will be used.', textDomain)}<br/><a href="https://www.paidmembershipspro.com/add-ons/pmpro-membership-card/?utm_source=pmpro-membership-card&utm_medium=plugin&utm_content=block-settings" target="_blank">{ __( 'View documentation', textDomain ) }</a></>}
						value={elements}
						onChange={(value) => setAttributes({elements: value})}
					/>
				</PanelBody>
			</InspectorControls>
			<span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
			<span className="pmpro-block-subtitle">{ __( 'Membership Card', 'paid-memberships-pro' ) }</span>
	</div>
	);
}
