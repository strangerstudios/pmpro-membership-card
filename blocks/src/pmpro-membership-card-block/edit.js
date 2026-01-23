import {__} from '@wordpress/i18n';
import {useBlockProps, InspectorControls} from '@wordpress/block-editor';
import {PanelBody, TextControl, SelectControl, ToggleControl} from '@wordpress/components';
import {useSelect} from '@wordpress/data';
import './editor.scss';
import qrCodeSample from './images/qr-code-sample.png';
import avatarSample from './images/avatar-sample.png';

function useFeaturedImage() {
	return useSelect((select) => {
		const editor = select('core/editor');
		const core = select('core');

		const featuredMediaId = editor.getEditedPostAttribute('featured_media');

		if (!featuredMediaId) {
			return {featuredMediaId: 0, featuredMedia: null, isLoading: false};
		}

		const media = core.getMedia(featuredMediaId);

		return {
			featuredMediaId,
			featuredMedia: media ?? null,
			isLoading: !media,
		};
	}, []);
}

export default function Edit({attributes, setAttributes}) {
	const blockProps = useBlockProps();
	const textDomain = 'pmpro-membership-card';
	const {elements, print_size, qr_code, qr_data, show_avatar} = attributes;
	const currentUser = useSelect(select => select('core').getCurrentUser(), []);
	let {featuredMedia} = useFeaturedImage();

	let url =
		featuredMedia?.media_details?.sizes?.thumbnail?.source_url ||
		featuredMedia?.source_url;

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Membership Card Settings', textDomain)}>
					<TextControl
						label={__('Elements (optional)', textDomain)}
						help={__('This attribute accepts a list of label names and values in the following format: label,field;label,field;...', textDomain)}
						value={elements}
						onChange={(value) => setAttributes({elements: value})}
						/>
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
					<ToggleControl
						label={__('Show Avatar', textDomain)}
						checked={show_avatar}
						onChange={(value) => setAttributes({show_avatar: value})}
					/>
				</PanelBody>
			</InspectorControls>

			<div className="wp-block-pmpro-membership-card-block-inner">
                <div className="pmpro_membership_card-left">
                    <div className="pmpro_membership_card_field pmpro_membership_card_field-avatar">
                        {show_avatar && (
                            <img
                            src={avatarSample} alt="Avatar sample"
                            className="avatar pmpro_membership_card_avatar" height="98" width="98"/>
                        )}
                    </div>
                    <div className="pmpro_membership_card_field pmpro_membership_card_field-qr_code">
                        {qr_code && qr_data && (
                            <img src={qrCodeSample} alt="QR code sample"/>
                        )}
                    </div>
                </div>
                <div className="pmpro_membership_card-right">
                    <div className="pmpro_membership_card_field pmpro_membership_card_field-display_name">
                        <h2 className="pmpro_font-x-large">{
                            currentUser ? currentUser.name : __('Member Name', textDomain)
                        }</h2>
                    </div>
                    <div className="pmpro_membership_card_field pmpro_membership_card_field-featured_image">
						<span className="pmpro_membership_card_field_data">
							{ featuredMedia && (
								<img src={url} className="pmpro_membership_card_image" alt="Featured image"/>
							)}
						</span>
					</div>
					<div className="pmpro_membership_card_field pmpro_membership_card_field-membership_startdate">
						<span className="pmpro_membership_card_field_label">{__('Member Since', textDomain)}</span>&nbsp;
						<span className="pmpro_membership_card_field_data">{__('April 29, 2025', textDomain)}</span>
					</div>
					<div className="pmpro_membership_card_field pmpro_membership_card_field-membership_name">
						<span className="pmpro_membership_card_field_label">{__('Level', textDomain)}</span>&nbsp;
						<span className="pmpro_membership_card_field_data"><span>{__('Beginner', textDomain)}</span>
						</span>
					</div>
				</div>
			</div>

			<p className="pmpro_membership_card_sample">
				{__('Please note that the sample data above is for visual reference only.', textDomain)}
			</p>
		</div>

	);
}
