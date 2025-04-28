/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

// Available icons for the badge
export const ICON_OPTIONS = [
    { value: 'leaf', label: __('Leaf', 'greenmetrics') },
    { value: 'tree', label: __('Tree', 'greenmetrics') },
    { value: 'globe', label: __('Globe', 'greenmetrics') },
    { value: 'recycle', label: __('Recycle', 'greenmetrics') },
    { value: 'chart-bar', label: __('Chart Bar', 'greenmetrics') },
    { value: 'chart-line', label: __('Chart Line', 'greenmetrics') },
    { value: 'chart-pie', label: __('Chart Pie', 'greenmetrics') },
    { value: 'analytics', label: __('Analytics', 'greenmetrics') },
    { value: 'performance', label: __('Performance', 'greenmetrics') },
    { value: 'energy', label: __('Energy', 'greenmetrics') },
    { value: 'water', label: __('Water', 'greenmetrics') },
    { value: 'eco', label: __('Eco', 'greenmetrics') },
    { value: 'nature', label: __('Nature', 'greenmetrics') },
    { value: 'sustainability', label: __('Sustainability', 'greenmetrics') }
];

// Available metrics to display
export const METRIC_OPTIONS = [
    { value: 'carbon_footprint', label: __('Carbon Footprint', 'greenmetrics') },
    { value: 'energy_consumption', label: __('Energy Consumption', 'greenmetrics') },
    { value: 'data_transfer', label: __('Data Transfer', 'greenmetrics') },
    { value: 'views', label: __('Page Views', 'greenmetrics') },
    { value: 'http_requests', label: __('HTTP Requests', 'greenmetrics') },
    { value: 'performance_score', label: __('Performance Score', 'greenmetrics') },
];

// Font family options
export const FONT_FAMILY_OPTIONS = [
    { value: 'inherit', label: __('Default', 'greenmetrics') },
    { value: 'Arial, sans-serif', label: __('Arial', 'greenmetrics') },
    { value: 'Helvetica, Arial, sans-serif', label: __('Helvetica', 'greenmetrics') },
    { value: 'Georgia, serif', label: __('Georgia', 'greenmetrics') },
    { value: '"Times New Roman", Times, serif', label: __('Times New Roman', 'greenmetrics') },
    { value: 'Verdana, Geneva, sans-serif', label: __('Verdana', 'greenmetrics') },
    { value: 'Tahoma, Geneva, sans-serif', label: __('Tahoma', 'greenmetrics') },
    { value: '"Trebuchet MS", sans-serif', label: __('Trebuchet MS', 'greenmetrics') },
    { value: '"Courier New", monospace', label: __('Courier New', 'greenmetrics') },
    { value: 'system-ui, sans-serif', label: __('System UI', 'greenmetrics') },
    { value: 'sans-serif', label: __('Sans-serif', 'greenmetrics') },
    { value: 'serif', label: __('Serif', 'greenmetrics') },
    { value: 'monospace', label: __('Monospace', 'greenmetrics') },
]; 