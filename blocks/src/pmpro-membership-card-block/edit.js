import {__} from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from '@wordpress/block-editor';
import {PanelBody, TextareaControl, SelectControl, ToggleControl} from '@wordpress/components';

export default function Edit({attributes, setAttributes}) {
	const blockProps = useBlockProps();
	const {elements, print_size, qr_code, qr_data, show_avatar} = attributes;
	
	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Membership Card Settings', 'pmpro-membership-card')}>
					<ToggleControl
						label={__('Show Avatar', 'pmpro-membership-card')}
						checked={show_avatar}
						onChange={(value) => setAttributes({show_avatar: value})}
					/>
					<ToggleControl
						label={__('Display QR Code', 'pmpro-membership-card')}
						help={__('Optionally display a QR code on the card.', 'pmpro-membership-card')}
						checked={qr_code}
						onChange={(value) => setAttributes({qr_code: value})}
					/>
					{qr_code && (
						<SelectControl
							label={__('QR Code Data', 'pmpro-membership-card')}
							help={__('Specify what data the scanned QR code should return. If set to “other”, you must leverage the pmpro_membership_card_qr_data_other filter hook to set the value.', 'pmpro-membership-card')}
							value={qr_data}
							options={[
								{label: __('ID', 'pmpro-membership-card'), value: 'ID'},
								{label: __('Email', 'pmpro-membership-card'), value: 'email'},
								{label: __('Level', 'pmpro-membership-card'), value: 'level'},
								{label: __('Other', 'pmpro-membership-card'), value: 'other'},
							]}
							onChange={(value) => setAttributes({qr_data: value})}
						/>
					)}
					<SelectControl
						label={__('Print Size', 'pmpro-membership-card')}
						help={__('Specify what sizes to include in the print view.', 'pmpro-membership-card')}
						value={print_size}
						options={[
							{label: __('All', 'pmpro-membership-card'), value: 'all'},
							{label: __('Small', 'pmpro-membership-card'), value: 'small'},
							{label: __('Medium', 'pmpro-membership-card'), value: 'medium'},
							{label: __('Large', 'pmpro-membership-card'), value: 'large'},
						]}
						onChange={(value) => setAttributes({print_size: value})}
					/>
					<TextareaControl
						label={__('Elements (optional)', 'pmpro-membership-card')}
						help={<>{__('Enter one element per line using the format: label,field. If left blank, the default membership card layout will be used.', 'pmpro-membership-card')}<br/><a href="https://www.paidmembershipspro.com/add-ons/pmpro-membership-card/?utm_source=pmpro-membership-card&utm_medium=plugin&utm_content=block-settings" target="_blank" rel="noopener noreferrer">{ __( 'View documentation', 'pmpro-membership-card' ) }</a></>}
						value={elements}
						onChange={(value) => setAttributes({elements: value})}
					/>
				</PanelBody>
			</InspectorControls>
			<span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'pmpro-membership-card' ) }</span>
			<span className="pmpro-block-subtitle">{ __( 'Membership Card', 'pmpro-membership-card' ) }</span>
	</div>
	);
}
