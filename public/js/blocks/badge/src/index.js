/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
    RichText,
    InspectorControls,
    PanelColorSettings,
    BlockControls,
    AlignmentToolbar,
    useBlockProps,
} from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    RangeControl,
    SelectControl,
    TextControl,
    CheckboxControl,
    ColorPicker,
} from '@wordpress/components';
import { icons } from './icons';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';

// Available icons for the badge
const ICON_OPTIONS = [
    { value: 'chart-bar', label: __('Chart Bar', 'greenmetrics') },
    { value: 'chart-line', label: __('Chart Line', 'greenmetrics') },
    { value: 'chart-pie', label: __('Chart Pie', 'greenmetrics') },
    { value: 'leaf', label: __('Leaf', 'greenmetrics') },
    { value: 'recycle', label: __('Recycle', 'greenmetrics') },
    { value: 'energy', label: __('Energy', 'greenmetrics') },
    { value: 'water', label: __('Water', 'greenmetrics') },
    { value: 'eco', label: __('Eco', 'greenmetrics') },
    { value: 'nature', label: __('Nature', 'greenmetrics') },
    { value: 'sustainability', label: __('Sustainability', 'greenmetrics') }
];

// Available metrics to display
const METRIC_OPTIONS = [
    { value: 'carbon_footprint', label: __('Carbon Footprint', 'greenmetrics') },
    { value: 'energy_consumption', label: __('Energy Consumption', 'greenmetrics') },
    { value: 'data_transfer', label: __('Data Transfer', 'greenmetrics') },
    { value: 'views', label: __('Page Views', 'greenmetrics') },
    { value: 'http_requests', label: __('HTTP Requests', 'greenmetrics') },
    { value: 'performance_score', label: __('Performance Score', 'greenmetrics') },
];

// Helper function to format metric values
const getMetricValue = (metric, data = null) => {
    if (!data) {
        switch (metric) {
            case 'carbon_footprint':
                return '{{carbon_footprint}}';
            case 'energy_consumption':
                return '{{energy_consumption}}';
            case 'data_transfer':
                return '{{data_transfer}}';
            case 'views':
                return '{{views}}';
            case 'http_requests':
                return '{{http_requests}}';
            case 'performance_score':
                return '{{performance_score}}';
            default:
                return '{{0}}';
        }
    }
    
    switch (metric) {
        case 'carbon_footprint':
            // Use carbon_footprint consistently throughout the codebase
            return data.carbon_footprint ? `${data.carbon_footprint}g CO2` : '0g CO2';
        case 'energy_consumption':
            return data.energy_consumption ? `${data.energy_consumption} kWh` : '0 kWh';
        case 'data_transfer':
            return data.data_transfer ? `${(data.data_transfer / 1024).toFixed(2)} KB` : '0 KB';
        case 'views':
            return data.total_views ? data.total_views.toString() : '0';
        case 'http_requests':
            return data.requests ? data.requests.toString() : '0';
        case 'performance_score':
            return data.performance_score ? `${data.performance_score}%` : '0%';
        default:
            return '0';
    }
};

/**
 * Block registration
 */
registerBlockType('greenmetrics/badge', {
    apiVersion: 2,
    title: __('GreenMetrics Badge', 'greenmetrics'),
    description: __('Display a GreenMetrics badge with customizable styles.', 'greenmetrics'),
    icon: 'chart-bar',
    category: 'widgets',
    attributes: {
        text: {
            type: 'string',
            default: 'Eco-Friendly Site',
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
        iconColor: {
            type: 'string',
            default: '#ffffff',
        },
        showIcon: {
            type: 'boolean',
            default: true,
        },
        iconName: {
            type: 'string',
            default: 'chart-bar',
        },
        iconSize: {
            type: 'number',
            default: 20,
        },
        borderRadius: {
            type: 'number',
            default: 4,
        },
        padding: {
            type: 'number',
            default: 8,
        },
        showContent: {
            type: 'boolean',
            default: true,
        },
        contentTitle: {
            type: 'string',
            default: 'Environmental Impact',
        },
        selectedMetrics: {
            type: 'array',
            default: ['carbon_footprint', 'energy_consumption'],
        },
        customContent: {
            type: 'string',
            default: '',
        },
        contentBackgroundColor: {
            type: 'string',
            default: '#ffffff',
        },
        contentTextColor: {
            type: 'string',
            default: '#333333',
        },
        contentPadding: {
            type: 'number',
            default: 15,
        },
        animationDuration: {
            type: 'number',
            default: 300,
        },
        showText: {
            type: 'boolean',
            default: true,
        },
        textFontSize: {
            type: 'number',
            default: 14,
        },
    },

    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();
        const [metricsData, setMetricsData] = useState(null);
        const [isLoading, setIsLoading] = useState(true);

        useEffect(() => {
            // Get the current post ID
            const postId = wp.data.select('core/editor')?.getCurrentPostId();
            
            if (!postId) {
                console.error('No post ID found');
                setIsLoading(false);
                return;
            }

            // Fetch metrics data
            wp.apiFetch({
                path: `/greenmetrics/v1/metrics?page_id=${postId}`,
                method: 'GET'
            }).then(data => {
                setMetricsData(data);
                setIsLoading(false);

                // Send metrics to server for storage
                const metricsData = {
                    data_transfer: data.data_transfer || 0,
                    load_time: data.load_time || 0,
                    requests: data.requests || 0
                };

                console.log('Sending metrics data:', {
                    url: greenmetricsTracking.ajax_url,
                    action: greenmetricsTracking.action,
                    nonce: greenmetricsTracking.nonce,
                    metrics: metricsData
                });

                jQuery.ajax({
                    url: greenmetricsTracking.ajax_url,
                    type: 'POST',
                    data: {
                        action: greenmetricsTracking.action,
                        nonce: greenmetricsTracking.nonce,
                        metrics: JSON.stringify(metricsData)
                    },
                    success: function(response) {
                        console.log('Metrics saved successfully:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error sending metrics to server:', error);
                    }
                });
            }).catch(error => {
                console.error('Error fetching metrics:', error);
                setIsLoading(false);
            });
        }, []);

        const {
            text,
            alignment,
            backgroundColor,
            textColor,
            iconColor,
            showIcon,
            iconName,
            iconSize,
            borderRadius,
            padding,
            showContent,
            contentTitle,
            selectedMetrics,
            customContent,
            contentBackgroundColor,
            contentTextColor,
            contentPadding,
            animationDuration,
            showText,
            textFontSize,
        } = attributes;

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Badge Settings', 'greenmetrics')}>
                        <ToggleControl
                            label={__('Show Icon', 'greenmetrics')}
                            checked={showIcon}
                            onChange={(value) => setAttributes({ showIcon: value })}
                            __nextHasNoMarginBottom
                        />
                        {showIcon && (
                            <>
                                <div className="icon-selector">
                                    <p>{__('Select Icon', 'greenmetrics')}</p>
                                    <div className="icon-grid">
                                        {icons.map((icon) => {
                                            const isSelected = icon.id === iconName;
                                            return (
                                                <button
                                                    key={icon.id}
                                                    className={`icon-button ${isSelected ? 'selected' : ''}`}
                                                    onClick={() => {
                                                        console.log('Setting icon to:', icon.id);
                                                        setAttributes({ iconName: icon.id });
                                                    }}
                                                    dangerouslySetInnerHTML={{ __html: icon.svg }}
                                                    style={{ color: iconColor }}
                                                />
                                            );
                                        })}
                                    </div>
                                </div>
                                <RangeControl
                                    label={__('Icon Size', 'greenmetrics')}
                                    value={iconSize}
                                    onChange={(value) => setAttributes({ iconSize: value })}
                                    min={16}
                                    max={48}
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                />
                            </>
                        )}
                        <hr />
                        <ToggleControl
                            label={__('Show Text', 'greenmetrics')}
                            checked={showText}
                            onChange={(value) => setAttributes({ showText: value })}
                            __nextHasNoMarginBottom
                        />
                        {showText && (
                            <>
                                <TextControl
                                    label={__('Badge Text', 'greenmetrics')}
                                    value={text}
                                    onChange={(value) => setAttributes({ text: value })}
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                />
                                <RangeControl
                                    label={__('Text Size', 'greenmetrics')}
                                    value={textFontSize}
                                    onChange={(value) => setAttributes({ textFontSize: value })}
                                    min={12}
                                    max={24}
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                />
                            </>
                        )}
                        <hr />
                        <RangeControl
                            label={__('Border Radius', 'greenmetrics')}
                            value={borderRadius}
                            onChange={(value) => setAttributes({ borderRadius: value })}
                            min={0}
                            max={20}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <RangeControl
                            label={__('Badge Padding', 'greenmetrics')}
                            value={padding}
                            onChange={(value) => setAttributes({ padding: value })}
                            min={4}
                            max={20}
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </PanelBody>
                    <PanelBody title={__('Content Settings', 'greenmetrics')}>
                        <ToggleControl
                            label={__('Show Content', 'greenmetrics')}
                            checked={showContent}
                            onChange={(value) => setAttributes({ showContent: value })}
                            __nextHasNoMarginBottom
                        />
                        {showContent && (
                            <>
                                <TextControl
                                    label={__('Content Title', 'greenmetrics')}
                                    value={contentTitle}
                                    onChange={(value) => setAttributes({ contentTitle: value })}
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                />
                                <RangeControl
                                    label={__('Content Padding', 'greenmetrics')}
                                    value={contentPadding}
                                    onChange={(value) => setAttributes({ contentPadding: value })}
                                    min={10}
                                    max={30}
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                />
                                <div className="components-base-control">
                                    <label className="components-base-control__label">
                                        {__('Select Metrics', 'greenmetrics')}
                                    </label>
                                    {METRIC_OPTIONS.map((metric) => (
                                        <CheckboxControl
                                            key={metric.value}
                                            label={metric.label}
                                            checked={selectedMetrics.includes(metric.value)}
                                            onChange={(checked) => {
                                                const newMetrics = checked
                                                    ? [...selectedMetrics, metric.value]
                                                    : selectedMetrics.filter((m) => m !== metric.value);
                                                setAttributes({ selectedMetrics: newMetrics });
                                            }}
                                            __nextHasNoMarginBottom
                                        />
                                    ))}
                                </div>
                                <TextControl
                                    label={__('Custom Content', 'greenmetrics')}
                                    value={customContent}
                                    onChange={(value) => setAttributes({ customContent: value })}
                                    help={__('Add any additional content to display below the metrics', 'greenmetrics')}
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                />
                                <RangeControl
                                    label={__('Animation Duration (ms)', 'greenmetrics')}
                                    value={animationDuration}
                                    onChange={(value) => setAttributes({ animationDuration: value })}
                                    min={100}
                                    max={1000}
                                    step={100}
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                />
                            </>
                        )}
                    </PanelBody>
                    <PanelColorSettings
                        title={__('Color Settings', 'greenmetrics')}
                        colorSettings={[
                            {
                                value: backgroundColor,
                                onChange: (value) => setAttributes({ backgroundColor: value }),
                                label: __('Badge Background Color', 'greenmetrics'),
                            },
                            showIcon && {
                                value: iconColor,
                                onChange: (value) => setAttributes({ iconColor: value }),
                                label: __('Icon Color', 'greenmetrics'),
                            },
                            showText && {
                                value: textColor,
                                onChange: (value) => setAttributes({ textColor: value }),
                                label: __('Text Color', 'greenmetrics'),
                            },
                            {
                                value: contentBackgroundColor,
                                onChange: (value) => setAttributes({ contentBackgroundColor: value }),
                                label: __('Content Background Color', 'greenmetrics'),
                            },
                            {
                                value: contentTextColor,
                                onChange: (value) => setAttributes({ contentTextColor: value }),
                                label: __('Content Text Color', 'greenmetrics'),
                            },
                        ].filter(Boolean)}
                    />
                </InspectorControls>
                <BlockControls>
                    <AlignmentToolbar
                        value={alignment}
                        onChange={(value) => setAttributes({ alignment: value })}
                    />
                </BlockControls>
                <div className="wp-block-greenmetrics-badge-wrapper">
                    <div
                        className="wp-block-greenmetrics-badge"
                        style={{
                            backgroundColor,
                            color: textColor,
                            padding: `${padding}px`,
                            borderRadius: `${borderRadius}px`,
                            textAlign: alignment,
                            cursor: showContent ? 'pointer' : 'default',
                        }}
                    >
                        {showIcon && (
                            <div 
                                className="wp-block-greenmetrics-badge__icon" 
                                style={{ 
                                    width: `${iconSize}px`, 
                                    height: `${iconSize}px`, 
                                    color: iconColor 
                                }}
                                dangerouslySetInnerHTML={{ __html: icons.find(icon => icon.id === iconName)?.svg || icons[0].svg }}
                            />
                        )}
                        {showText && (
                            <span style={{ 
                                color: textColor,
                                fontSize: `${textFontSize}px`
                            }}>
                                {text}
                            </span>
                        )}
                    </div>
                    {showContent && (
                        <div
                            className="wp-block-greenmetrics-content"
                            style={{
                                backgroundColor: contentBackgroundColor,
                                color: contentTextColor,
                                padding: `${contentPadding}px`,
                                transition: `all ${animationDuration}ms ease-in-out`,
                            }}
                        >
                            <h3>{contentTitle}</h3>
                            <div className="wp-block-greenmetrics-metrics">
                                {selectedMetrics.map((metric) => (
                                    <div key={metric} className="wp-block-greenmetrics-metric">
                                        <div className="metric-label">
                                            <span>{METRIC_OPTIONS.find((m) => m.value === metric)?.label}</span>
                                            <span className="metric-value">
                                                {getMetricValue(metric, metricsData)}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            {customContent && (
                                <div className="wp-block-greenmetrics-custom-content">
                                    {customContent}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        );
    },

    save: ({ attributes }) => {
        const blockProps = useBlockProps.save();
        const {
            text,
            alignment,
            backgroundColor,
            textColor,
            iconColor,
            showIcon,
            iconName,
            iconSize,
            borderRadius,
            padding,
            showContent,
            contentTitle,
            selectedMetrics,
            customContent,
            contentBackgroundColor,
            contentTextColor,
            contentPadding,
            animationDuration,
            showText,
            textFontSize,
        } = attributes;

        const selectedIcon = icons.find(icon => icon.id === iconName);
        if (!selectedIcon) {
            console.error('Icon not found:', iconName);
        }
        const iconSvg = selectedIcon ? selectedIcon.svg : icons[0].svg;

        return (
            <div {...blockProps}>
                <div className="wp-block-greenmetrics-badge-wrapper">
                    <div 
                        className="wp-block-greenmetrics-badge"
                        style={{
                            backgroundColor,
                            color: textColor,
                            padding: `${padding}px`,
                            borderRadius: `${borderRadius}px`,
                            textAlign: alignment,
                            cursor: showContent ? 'pointer' : 'default'
                        }}
                    >
                        {showIcon && (
                            <div 
                                className="wp-block-greenmetrics-badge__icon" 
                                style={{ 
                                    width: `${iconSize}px`, 
                                    height: `${iconSize}px`, 
                                    color: iconColor 
                                }}
                                dangerouslySetInnerHTML={{ __html: iconSvg }}
                            />
                        )}
                        {showText && (
                            <span style={{ 
                                color: textColor,
                                fontSize: `${textFontSize}px`
                            }}>
                                {text}
                            </span>
                        )}
                    </div>
                    {showContent && (
                        <div 
                            className="wp-block-greenmetrics-content"
                            style={{
                                backgroundColor: contentBackgroundColor,
                                color: contentTextColor,
                                padding: `${contentPadding}px`,
                                transition: `all ${animationDuration}ms ease-in-out`
                            }}
                        >
                            <h3>{contentTitle}</h3>
                            <div className="wp-block-greenmetrics-metrics">
                                {selectedMetrics.map((metric) => (
                                    <div key={metric} className="wp-block-greenmetrics-metric">
                                        <div className="metric-label">
                                            <span>{METRIC_OPTIONS.find(m => m.value === metric)?.label}</span>
                                            <span className="metric-value">
                                                {getMetricValue(metric)}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            {customContent && (
                                <div className="wp-block-greenmetrics-custom-content"
                                    dangerouslySetInnerHTML={{ __html: customContent }}
                                />
                            )}
                        </div>
                    )}
                </div>
            </div>
        );
    },
}); 