import {__} from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import './editor.scss';
import qrCodeSample from '../../images/qr-code-sample.png';

function useFeaturedImage() {
	return useSelect((select) => {
		const editor = select('core/editor');
		const core = select('core');

		const featuredMediaId = editor.getEditedPostAttribute('featured_media');

		if (!featuredMediaId) {
			return { featuredMediaId: 0, featuredMedia: null, isLoading: false };
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
	const {printSize, qrcodeEnabled, qrcodeData, qrcodeDataCustom} = attributes;
	const currentUser = useSelect( select => select( 'core' ).getCurrentUser(), [] );
	let { featuredMedia } = useFeaturedImage();

	let url =
		featuredMedia?.media_details?.sizes?.thumbnail?.source_url ||
		featuredMedia?.source_url;

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title="Membership Card Settings">
                    <p>Tip: Display an avatar on your membership card by adding a featured image.</p>
                    <hr/>
					<SelectControl
						label={__('Print Size', textDomain)}
						help={__('Specify what sizes to include in the print view.', textDomain)}
						value={printSize}
						options={[
							{label: __('All', textDomain), value: 'all'},
							{label: __('Small', textDomain), value: 'small'},
							{label: __('Medium', textDomain), value: 'medium'},
							{label: __('Large', textDomain), value: 'large'},
						]}
						onChange={(value) => setAttributes({printSize: value})}
					/>
					<ToggleControl
						label={__('Display QR Code', textDomain)}
						help={__('Optionally display a QR code on the card.', textDomain)}
						checked={qrcodeEnabled}
						onChange={(value) => setAttributes({qrcodeEnabled: value})}
					/>
					{qrcodeEnabled && (
						<SelectControl
							label={__('QR Code Data', textDomain)}
							help={__('Specify what data the scanned QR code should return.', textDomain)}
							value={qrcodeData}
							options={[
								{label: __('ID', textDomain), value: 'ID'},
								{label: __('Email', textDomain), value: 'email'},
								{label: __('Level', textDomain), value: 'level'},
								{label: __('Other', textDomain), value: 'other'},
							]}
							onChange={(value) => setAttributes({qrcodeData: value})}
						/>
					)}
					{qrcodeData === 'other' && (
						<TextControl
							label={__('Custom QR Code Value', textDomain)}
							help={__('Pass a custom value for what the scanned QR code should return.', textDomain)}
							value={qrcodeDataCustom}
							onChange={(value) => setAttributes({qrcodeDataCustom: value})}
						/>
					)}
				</PanelBody>
			</InspectorControls>

			<div className="wp-block-pmpro-membership-card-block-inner">
				<div className="pmpro_membership_card-left">
					<div className="pmpro_membership_card_field pmpro_membership_card_field-qr_code">
						{ qrcodeEnabled && qrcodeData && (
							<img src={qrCodeSample} alt=""/>
						) }
					</div>
				</div>
				<div className="pmpro_membership_card-right">
					<div className="pmpro_membership_card_field pmpro_membership_card_field-display_name">
						<h2 className="pmpro_font-x-large">{
							currentUser ? currentUser.name : 'Member Name'
						}</h2>
					</div>
					<div className="pmpro_membership_card_field pmpro_membership_card_field-featured_image">
						<span className="pmpro_membership_card_field_data">
							{ featuredMedia && (
								<img src={url} className="pmpro_membership_card_image" alt=""/>
							) }
						</span>
					</div>
					<div className="pmpro_membership_card_field pmpro_membership_card_field-membership_startdate">
						<span className="pmpro_membership_card_field_label">Member Since</span>&nbsp;
						<span className="pmpro_membership_card_field_data">April 29, 2025</span>
					</div>
					<div className="pmpro_membership_card_field pmpro_membership_card_field-membership_name">
						<span className="pmpro_membership_card_field_label">Level</span>&nbsp;
						<span className="pmpro_membership_card_field_data"><span>Beginner</span>
						</span>
					</div>
				</div>
			</div>

			<p className="pmpro_membership_card_sample">Please note that the sample data above is for visual reference only.</p>
		</div>

	);
}
