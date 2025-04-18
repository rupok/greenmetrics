# GreenMetrics - WordPress Environmental Impact Plugin

GreenMetrics helps you track and reduce your website's environmental impact by monitoring carbon emissions and data transfer.

## Features

- Track page load metrics (data transfer, load time)
- Display an eco-friendly badge on your website
- Monitor your website's environmental impact
- Get optimization suggestions
- Easy-to-use admin dashboard
- Gutenberg block and shortcode support

## Installation

1. Download the plugin zip file
2. Go to WordPress admin > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Activate the plugin

## Usage

### Admin Dashboard

1. Go to WordPress admin > GreenMetrics
2. Enable/disable tracking and badge display
3. View your website's performance metrics

### Display Badge

You can display the eco-friendly badge in two ways:

1. **Gutenberg Block**
   - Add the "GreenMetrics Badge" block to your page/post
   - Customize the appearance in the block settings

2. **Shortcode**
   ```php
   [greenmetrics_badge theme="light" size="medium" position="bottom-right"]
   ```

   Parameters:
   - `theme`: light, dark (default: light)
   - `size`: small, medium, large (default: medium)
   - `position`: bottom-right, bottom-left, top-right, top-left (default: bottom-right)

## Development

### Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.6+

### Setup

1. Clone the repository
   ```bash
   git clone https://github.com/yourusername/greenmetrics.git
   ```

2. Install dependencies
   ```bash
   cd greenmetrics
   npm install
   ```

3. Build assets
   ```bash
   npm run build
   ```

### Directory Structure

```
greenmetrics/
├── admin/              # Admin-specific files
├── includes/           # Core plugin files
├── languages/          # Translation files
├── public/            # Public-facing files
└── greenmetrics.php   # Main plugin file
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details. 