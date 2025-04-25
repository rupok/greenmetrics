import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
    RichText,
    InspectorControls,
    PanelColorSettings,
    BlockControls,
    AlignmentToolbar,
    FontSizePicker,
} from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    ToggleControl,
    RangeControl,
} from '@wordpress/components';

registerBlockType('greenmetrics/badge', {
    title: __('GreenMetrics Badge', 'greenmetrics'),
    description: __('Display a GreenMetrics badge with customizable styles.', 'greenmetrics'),
    icon: 'chart-bar',
    category: 'widgets',
    attributes: {
        text: {
            type: 'string',
            default: __('Powered by GreenMetrics', 'greenmetrics'),
        },
        alignment: {
            type: 'string',
            default: 'left',
        },
        backgroundColor: {
            type: 'string',
            default: '#4CAF50',
        },
        textColor: {
            type: 'string',
            default: '#ffffff',
        },
        fontSize: {
            type: 'number',
            default: 14,
        },
        showIcon: {
            type: 'boolean',
            default: true,
        },
        iconSize: {
            type: 'number',
            default: 16,
        },
        borderRadius: {
            type: 'number',
            default: 4,
        },
        padding: {
            type: 'number',
            default: 8,
        },
    },

    edit: ({ attributes, setAttributes }) => {
        const {
            text,
            alignment,
            backgroundColor,
            textColor,
            fontSize,
            showIcon,
            iconSize,
            borderRadius,
            padding,
        } = attributes;

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Badge Settings', 'greenmetrics')}>
                        <ToggleControl
                            label={__('Show Icon', 'greenmetrics')}
                            checked={showIcon}
                            onChange={(value) => setAttributes({ showIcon: value })}
                        />
                        <RangeControl
                            label={__('Icon Size', 'greenmetrics')}
                            value={iconSize}
                            onChange={(value) => setAttributes({ iconSize: value })}
                            min={12}
                            max={32}
                        />
                        <RangeControl
                            label={__('Border Radius', 'greenmetrics')}
                            value={borderRadius}
                            onChange={(value) => setAttributes({ borderRadius: value })}
                            min={0}
                            max={20}
                        />
                        <RangeControl
                            label={__('Padding', 'greenmetrics')}
                            value={padding}
                            onChange={(value) => setAttributes({ padding: value })}
                            min={4}
                            max={20}
                        />
                        <RangeControl
                            label={__('Font Size', 'greenmetrics')}
                            value={fontSize}
                            onChange={(value) => setAttributes({ fontSize: value })}
                            min={12}
                            max={24}
                        />
                    </PanelBody>
                    <PanelColorSettings
                        title={__('Color Settings', 'greenmetrics')}
                        colorSettings={[
                            {
                                value: backgroundColor,
                                onChange: (value) => setAttributes({ backgroundColor: value }),
                                label: __('Background Color', 'greenmetrics'),
                            },
                            {
                                value: textColor,
                                onChange: (value) => setAttributes({ textColor: value }),
                                label: __('Text Color', 'greenmetrics'),
                            },
                        ]}
                    />
                </InspectorControls>
                <BlockControls>
                    <AlignmentToolbar
                        value={alignment}
                        onChange={(value) => setAttributes({ alignment: value })}
                    />
                </BlockControls>
                <div
                    style={{
                        backgroundColor,
                        color: textColor,
                        padding: `${padding}px`,
                        borderRadius: `${borderRadius}px`,
                        fontSize: `${fontSize}px`,
                        textAlign: alignment,
                    }}
                >
                    {showIcon && (
                        <span
                            className="dashicons dashicons-chart-bar"
                            style={{
                                fontSize: `${iconSize}px`,
                                marginRight: '8px',
                                verticalAlign: 'middle',
                            }}
                        />
                    )}
                    <RichText
                        tagName="span"
                        value={text}
                        onChange={(value) => setAttributes({ text: value })}
                        style={{ color: textColor }}
                    />
                </div>
            </>
        );
    },

    save: ({ attributes }) => {
        const {
            text,
            alignment,
            backgroundColor,
            textColor,
            fontSize,
            showIcon,
            iconSize,
            borderRadius,
            padding,
        } = attributes;

        return (
            <div
                style={{
                    backgroundColor,
                    color: textColor,
                    padding: `${padding}px`,
                    borderRadius: `${borderRadius}px`,
                    fontSize: `${fontSize}px`,
                    textAlign: alignment,
                }}
            >
                {showIcon && (
                    <span
                        className="dashicons dashicons-chart-bar"
                        style={{
                            fontSize: `${iconSize}px`,
                            marginRight: '8px',
                            verticalAlign: 'middle',
                        }}
                    />
                )}
                <span>{text}</span>
            </div>
        );
    },
}); 