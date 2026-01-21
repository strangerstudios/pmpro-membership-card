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
import {useBlockProps, InspectorControls} from '@wordpress/block-editor';
import {PanelBody, TextControl, SelectControl, ToggleControl} from '@wordpress/components';

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

export default function Edit({attributes, setAttributes}) {
    const blockProps = useBlockProps();
    const textDomain = 'pmpro-membership-card';
    const {showAvatar, printSize, qrcodeEnabled, qrCodeData, qrcodeDataCustom} = attributes;

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title="Membership Card Settings">
                    <ToggleControl
                        label={__('Show Avatar', textDomain)}
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
                    {qrCodeData === 'other' && (
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

            <div {...blockProps}>
                <h2>PMPro Team</h2>
                <p><span>Member since</span> April 29,2025</p>
                <p><span>Level</span> Beginner</p>
            </div>
        </div>
);
}
