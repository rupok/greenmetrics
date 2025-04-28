/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
    InspectorControls,
    PanelColorSettings,
    BlockControls,
    AlignmentToolbar,
    useBlockProps,
    MediaUpload,
    MediaUploadCheck,
} from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    RangeControl,
    SelectControl,
    TextControl,
    CheckboxControl,
    ColorPicker,
    Button,
    Spinner,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { icons } from './icons';
import { METRIC_OPTIONS, FONT_FAMILY_OPTIONS } from './constants';
import { getMetricValue } from './utils';

/**
 * Badge edit component
 */
const Edit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    const [metricsData, setMetricsData] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    
    // Add dynamic hover effect style to show the hover color in the editor
    useEffect(() => {
        const styleId = 'greenmetrics-hover-style';
        let styleTag = document.getElementById(styleId);
        
        // Create the style element if it doesn't exist
        if (!styleTag) {
            styleTag = document.createElement('style');
            styleTag.id = styleId;
            document.head.appendChild(styleTag);
        }
        
        // Set the hover style using the attribute
        styleTag.innerHTML = `
            .editor-styles-wrapper .wp-block-greenmetrics-metric:hover {
                background-color: ${attributes.metricsListHoverBgColor || '#f3f4f6'} !important;
            }
        `;
        
        // Clean up when component unmounts
        return () => {
            styleTag = document.getElementById(styleId);
            if (styleTag) {
                document.head.removeChild(styleTag);
            }
        };
    }, [attributes.metricsListHoverBgColor]);
    
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
        customIconUrl,
        customIconId,
        useCustomIcon,
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
        badgeFontFamily,
        popoverContentFontFamily,
        metricsListFontFamily,
        metricsListFontSize,
        metricsValueFontFamily,
        metricsValueFontSize,
        metricsListBgColor,
        metricsListHoverBgColor,
        metricsValueBgColor,
        metricsValueColor,
    } = attributes;

    // Handler for selecting a custom icon
    const onSelectCustomIcon = (media) => {
        if (!media || !media.url) {
            return;
        }
        
        setAttributes({
            customIconUrl: media.url,
            customIconId: media.id,
            useCustomIcon: true,
            iconName: 'custom' // Set to custom so we know to use the custom icon
        });
    };

    // Handler for removing the custom icon
    const removeCustomIcon = () => {
        setAttributes({
            customIconUrl: '',
            customIconId: 0,
            useCustomIcon: false,
            iconName: 'leaf' // Reset to default icon
        });
    };

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
                                        const isSelected = !useCustomIcon && icon.id === iconName;
                                        return (
                                            <button
                                                key={icon.id}
                                                className={`icon-button ${isSelected ? 'selected' : ''}`}
                                                onClick={() => {
                                                    console.log('Setting icon to:', icon.id);
                                                    setAttributes({ 
                                                        iconName: icon.id,
                                                        useCustomIcon: false
                                                    });
                                                }}
                                                dangerouslySetInnerHTML={{ __html: icon.svg }}
                                                style={{ color: iconColor }}
                                            />
                                        );
                                    })}
                                </div>
                            </div>
                            <div className="custom-icon-upload">
                                <p>{__('Or Upload Custom Icon', 'greenmetrics')}</p>
                                <div className="custom-icon-controls">
                                    {useCustomIcon && customIconUrl ? (
                                        <div className="custom-icon-preview">
                                            <img 
                                                src={customIconUrl}
                                                alt={__('Custom Icon', 'greenmetrics')}
                                                style={{ 
                                                    maxWidth: '100%',
                                                    height: 'auto',
                                                    maxHeight: '60px'
                                                }}
                                            />
                                            <Button 
                                                isDestructive 
                                                onClick={removeCustomIcon}
                                            >
                                                {__('Remove', 'greenmetrics')}
                                            </Button>
                                        </div>
                                    ) : (
                                        <MediaUploadCheck>
                                            <MediaUpload
                                                onSelect={onSelectCustomIcon}
                                                allowedTypes={['image']}
                                                value={customIconId}
                                                render={({ open }) => (
                                                    <Button
                                                        onClick={open}
                                                        variant="secondary"
                                                    >
                                                        {__('Upload Custom Icon', 'greenmetrics')}
                                                    </Button>
                                                )}
                                            />
                                        </MediaUploadCheck>
                                    )}
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
                            <SelectControl
                                label={__('Font Family', 'greenmetrics')}
                                value={badgeFontFamily}
                                options={FONT_FAMILY_OPTIONS}
                                onChange={(value) => setAttributes({ badgeFontFamily: value })}
                                __nextHasNoMarginBottom
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
                            <SelectControl
                                label={__('Content Font Family', 'greenmetrics')}
                                value={popoverContentFontFamily}
                                options={FONT_FAMILY_OPTIONS}
                                onChange={(value) => setAttributes({ popoverContentFontFamily: value })}
                                __nextHasNoMarginBottom
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
                            
                            <SelectControl
                                label={__('Metrics List Font Family', 'greenmetrics')}
                                value={metricsListFontFamily}
                                options={FONT_FAMILY_OPTIONS}
                                onChange={(value) => setAttributes({ metricsListFontFamily: value })}
                                __nextHasNoMarginBottom
                            />
                            
                            <RangeControl
                                label={__('Metrics List Font Size', 'greenmetrics')}
                                value={metricsListFontSize}
                                onChange={(value) => setAttributes({ metricsListFontSize: value })}
                                min={10}
                                max={24}
                                __nextHasNoMarginBottom
                            />
                            
                            <SelectControl
                                label={__('Metrics Value Font Family', 'greenmetrics')}
                                value={metricsValueFontFamily}
                                options={FONT_FAMILY_OPTIONS}
                                onChange={(value) => setAttributes({ metricsValueFontFamily: value })}
                                __nextHasNoMarginBottom
                            />
                            
                            <RangeControl
                                label={__('Metrics Value Font Size', 'greenmetrics')}
                                value={metricsValueFontSize}
                                onChange={(value) => setAttributes({ metricsValueFontSize: value })}
                                min={10}
                                max={24}
                                __nextHasNoMarginBottom
                            />
                            
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
                            onChange: (value) => setAttributes({ backgroundColor: value || '#4CAF50' }),
                            label: __('Badge Background Color', 'greenmetrics'),
                        },
                        showIcon && {
                            value: iconColor,
                            onChange: (value) => setAttributes({ iconColor: value || '#ffffff' }),
                            label: __('Icon Color', 'greenmetrics'),
                        },
                        showText && {
                            value: textColor,
                            onChange: (value) => setAttributes({ textColor: value || '#ffffff' }),
                            label: __('Text Color', 'greenmetrics'),
                        },
                        {
                            value: contentBackgroundColor,
                            onChange: (value) => setAttributes({ contentBackgroundColor: value || '#ffffff' }),
                            label: __('Content Background Color', 'greenmetrics'),
                        },
                        {
                            value: contentTextColor,
                            onChange: (value) => setAttributes({ contentTextColor: value || '#333333' }),
                            label: __('Content Text Color', 'greenmetrics'),
                        },
                        showContent && {
                            value: metricsListBgColor || '#f8f9fa',
                            onChange: (value) => setAttributes({ metricsListBgColor: value || '#f8f9fa' }),
                            label: __('Metrics List Background Color', 'greenmetrics'),
                        },
                        showContent && {
                            value: metricsListHoverBgColor || '#f3f4f6',
                            onChange: (value) => setAttributes({ metricsListHoverBgColor: value || '#f3f4f6' }),
                            label: __('Metrics List Hover Background Color', 'greenmetrics'),
                        },
                        showContent && {
                            value: metricsValueBgColor || 'rgba(0, 0, 0, 0.04)',
                            onChange: (value) => setAttributes({ metricsValueBgColor: value || 'rgba(0, 0, 0, 0.04)' }),
                            label: __('Metrics Value Background Color', 'greenmetrics'),
                        },
                        showContent && {
                            value: metricsValueColor || '#333333',
                            onChange: (value) => setAttributes({ metricsValueColor: value || '#333333' }),
                            label: __('Metrics Value Text Color', 'greenmetrics'),
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
                        fontFamily: badgeFontFamily,
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
                        >
                            {useCustomIcon && customIconUrl ? (
                                <img 
                                    src={customIconUrl} 
                                    alt="Custom Icon"
                                    style={{
                                        width: '100%',
                                        height: '100%',
                                        objectFit: 'contain',
                                    }}
                                />
                            ) : (
                                <div dangerouslySetInnerHTML={{ 
                                    __html: icons.find(icon => icon.id === iconName)?.svg || icons[0].svg 
                                }} />
                            )}
                        </div>
                    )}
                    {showText && (
                        <span style={{ 
                            color: textColor,
                            fontSize: `${textFontSize}px`,
                            fontFamily: badgeFontFamily,
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
                            fontFamily: popoverContentFontFamily,
                        }}
                    >
                        <h3 style={{ fontFamily: popoverContentFontFamily }}>{contentTitle}</h3>
                        <div className="wp-block-greenmetrics-metrics">
                            {selectedMetrics.map((metric) => (
                                <div 
                                    key={metric} 
                                    className="wp-block-greenmetrics-metric" 
                                    style={{ 
                                        backgroundColor: metricsListBgColor || '#f8f9fa',
                                    }}
                                >
                                    <div 
                                        className="metric-label"
                                        style={{ 
                                            fontFamily: metricsListFontFamily,
                                            fontSize: `${metricsListFontSize}px`,
                                        }}
                                    >
                                        <span>{METRIC_OPTIONS.find((m) => m.value === metric)?.label}</span>
                                        <span 
                                            className="metric-value"
                                            style={{ 
                                                fontFamily: metricsValueFontFamily,
                                                fontSize: `${metricsValueFontSize}px`,
                                                background: metricsValueBgColor || "rgba(0, 0, 0, 0.04)",
                                                color: metricsValueColor || "#333333",
                                                padding: "4px 8px",
                                                borderRadius: "4px",
                                            }}
                                        >
                                            {getMetricValue(metric, metricsData)}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                        {customContent && (
                            <div 
                                className="wp-block-greenmetrics-custom-content"
                                style={{ fontFamily: popoverContentFontFamily }}
                                dangerouslySetInnerHTML={{ __html: customContent }}
                            />
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};

export default Edit; 