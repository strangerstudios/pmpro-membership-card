/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import {__} from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */

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
    const {showAvatar, printSize, qrcodeEnabled, qrCodeData, qrcodeDataCustom} = attributes;
    const currentUser = useSelect( select => select( 'core' ).getCurrentUser(), [] );
    const { featuredMedia } = useFeaturedImage();

    const url =
        featuredMedia?.media_details?.sizes?.thumbnail?.source_url ||
        featuredMedia?.source_url;

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title="Membership Card Settings">
                    <ToggleControl
                        label={__('Show Avatar', textDomain)}
                        help={__('Set from your featured image.', textDomain)}
                        checked={showAvatar}
                        onChange={(value) => setAttributes({showAvatar: value})}
                    />
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
                            value={qrCodeData}
                            options={[
                                {label: __('ID', textDomain), value: 'ID'},
                                {label: __('Email', textDomain), value: 'email'},
                                {label: __('Level', textDomain), value: 'level'},
                                {label: __('Other', textDomain), value: 'other'},
                            ]}
                            onChange={(value) => setAttributes({qrCodeData: value})}
                        />
                    )}
                    {qrCodeData === 'other' && (
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
                        { qrcodeEnabled && qrCodeData && (
                            <div>QR Code Placeholder</div>
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
                            { showAvatar && useFeaturedImage && (
                                <img src={url} className="pmpro_membership_card_image"/>
                            ) }
                        </span>
                    </div>
                    <div className="pmpro_membership_card_field pmpro_membership_card_field-membership_startdate">
                        <span className="pmpro_membership_card_field_label">Member Since</span>
                        <span className="pmpro_membership_card_field_data">April 29, 2025</span>
                    </div>
                    <div className="pmpro_membership_card_field pmpro_membership_card_field-membership_name">
                        <span className="pmpro_membership_card_field_label">Level</span>
                        <span className="pmpro_membership_card_field_data"><span>Beginner</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}
