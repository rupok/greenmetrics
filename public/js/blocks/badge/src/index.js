/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import Edit from './edit';

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
            default: __('Eco-Friendly Site', 'greenmetrics'),
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
            default: 'leaf',
        },
        iconSize: {
            type: 'number',
            default: 20,
        },
        customIconUrl: {
            type: 'string',
            default: '',
        },
        customIconId: {
            type: 'number',
            default: 0,
        },
        useCustomIcon: {
            type: 'boolean',
            default: false,
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
            default: __('Environmental Impact', 'greenmetrics'),
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
        badgeFontFamily: {
            type: 'string',
            default: 'inherit',
        },
        popoverContentFontFamily: {
            type: 'string',
            default: 'inherit',
        },
        metricsListFontFamily: {
            type: 'string',
            default: 'inherit',
        },
        metricsListFontSize: {
            type: 'number',
            default: 14,
        },
        metricsValueFontFamily: {
            type: 'string',
            default: 'inherit',
        },
        metricsValueFontSize: {
            type: 'number',
            default: 14,
        },
        metricsListBgColor: {
            type: 'string',
            default: '#f8f9fa',
        },
        metricsListHoverBgColor: {
            type: 'string',
            default: '#f3f4f6',
        },
        metricsValueBgColor: {
            type: 'string',
            default: 'rgba(0, 0, 0, 0.04)',
        },
        metricsValueColor: {
            type: 'string',
            default: '#333333',
        },
        position: {
            type: 'string', 
            default: '',
        },
        theme: {
            type: 'string',
            default: 'default', 
        },
        size: {
            type: 'string',
            default: 'medium',
        }
    },
    edit: Edit,
    // No save function as we are using a PHP render callback
}); 