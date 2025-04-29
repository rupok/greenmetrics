# GreenMetrics - Website Carbon Footprint, Sustainability & Performance Metrics

GreenMetrics helps you build a more sustainable internet by tracking your website's carbon footprint, energy consumption, and resource usage, offering insights and recommendations for eco-friendly performance.

Website: [getgreenmetrics.com](https://getgreenmetrics.com)

## Features

- **Comprehensive Dashboard**: Real-time metrics showing Carbon Footprint (g CO2), Energy Consumption (kWh), Data Transfer, HTTP Requests, Page Views, and Performance Score
- **Environmental Impact Context**: Understanding the real-world impact of your website through relatable metrics
- **Optimization Suggestions**: Actionable advice with status indicators for Page Size, HTTP Requests, Performance Score, and Green Hosting
- **Gutenberg Block & Shortcode**: Easily display your environmental stats on any page
- **Customizable Badge**: Control the position, theme, size, colors, icon and text of your eco-metrics badge
- **Enhanced Metrics Display**: Fully customizable metrics list with hover effects, fonts, and styling options
- **Real-time Tracking**: Continuous monitoring of your site's performance and environmental impact metrics
- **Per-Page and Total Website Metrics**: View both aggregated stats and per-page averages
- **Carbon Intensity Settings**: Configure your energy consumption calculations based on your hosting location
- **Global Badge Display**: Option to automatically display badge site-wide without manual placement
- **Popover Customization**: Complete control over popover appearance including colors, fonts, metrics display and hover effects

## Installation

1. Upload the plugin files to the `/wp-content/plugins/greenmetrics` directory, or install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to your WordPress dashboard to view the metrics
4. Use the GreenMetrics block or `[greenmetrics_badge]` shortcode to display your site's stats

## Usage

### Admin Dashboard

1. Go to WordPress admin > GreenMetrics
2. Enable/disable tracking and badge display
3. View your website's performance metrics and environmental impact

### Display Settings

The Display Settings page provides comprehensive customization for your badge and metrics display:

- **Badge Configuration**: Control visibility, position, size, and text
- **Icon Settings**: Choose between different icon styles or upload a custom icon
- **Color Options**: Customize badge background, text, and icon colors
- **Popover Settings**: Configure title, metrics to display, and custom content
- **Styling Options**: Customize fonts, font sizes, background colors, and hover effects

### Display Badge

You can display the eco-friendly badge in three ways:

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

3. **Global Badge**
   - Enable the global badge option in Display Settings to show the badge site-wide
   - Customize all aspects of the badge and popover through the admin interface

## Who is it for?

GreenMetrics is ideal for:
- Eco-conscious website owners who want to reduce their digital carbon footprint
- Developers looking to optimize website performance and efficiency
- Businesses striving to meet sustainability goals and showcase environmental responsibility
- Bloggers and content creators focusing on green initiatives

## Frequently Asked Questions

### How does GreenMetrics calculate my site's environmental impact?
We use a combination of observed data based on asset size, page views, and HTTP requests. Our calculations account for data transfer, energy consumption per byte, and regional carbon intensity to provide meaningful insights.

### Will it slow down my website?
No. GreenMetrics is lightweight and designed for performance. It uses efficient tracking methods and does not load external scripts on the frontend.

### How accurate are the carbon and energy calculations?
Our calculations are based on industry research regarding energy consumption per byte of data transferred and carbon intensity of electrical grids. While they provide meaningful approximations, they may not account for all variables in the hosting infrastructure.

### Can I track individual page performance?
Yes, GreenMetrics tracks metrics on a per-page basis, allowing you to identify which pages have the highest environmental impact and optimize them accordingly.

### Can I customize how the metrics are displayed?
Yes, the Display Settings page offers extensive customization options for the badge and popover, including colors, fonts, metrics selection, and hover effects.

## Development

### Requirements

- WordPress 5.5+
- PHP 7.2+
- MySQL 5.6+

### Directory Structure

```
greenmetrics/
├── admin/                # Admin-specific functionality
│   ├── css/              # Admin styles
│   ├── js/               # Admin scripts
│   └── partials/         # Admin templates
├── build/                # Compiled assets
├── includes/             # Core plugin functionality
│   ├── admin/            # Admin class files
│   ├── class-*.php       # Core classes
├── languages/            # Translation files
├── public/               # Public-facing functionality
│   ├── css/              # Public styles
│   ├── js/               # Public scripts
│   │   └── blocks/       # Gutenberg blocks
│   └── partials/         # Public templates
├── vendor/               # Composer dependencies
├── .editorconfig         # Editor configuration
├── .gitignore            # Git ignore rules
├── composer.json         # Composer dependencies
├── LICENSE               # License file
├── phpcs.xml             # PHP Code Sniffer config
├── README.md             # This file
├── readme.txt            # WordPress.org readme
├── uninstall.php         # Uninstall logic
└── greenmetrics.php      # Main plugin file
```

### Development Setup

1. Clone the repository
   ```bash
   git clone https://github.com/yourusername/greenmetrics.git
   cd greenmetrics
   ```

2. Install dependencies
   ```bash
   composer install
   ```

3. Run code formatting
   ```bash
   ./format-code.sh
   ```

4. Fix coding standard issues
   ```bash
   ./lint-fix.sh
   ```

### Coding Standards

GreenMetrics follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/). We use PHP_CodeSniffer to ensure code quality:

- Run `composer run lint` to check for violations
- Run `composer run fix` to automatically fix violations where possible

### Testing

Run the PHPUnit tests:

```bash
composer run test
```

### Building Assets

The JavaScript and CSS assets need to be built for production use:

1. Install Node.js dependencies:
   ```bash
   npm install
   ```

2. Build for development (with source maps):
   ```bash
   npm run dev
   ```

3. Build for production (minified):
   ```bash
   npm run build
   ```

### Environment Configuration

GreenMetrics supports different environments:

- **Development**: Enable debugging and developer tools by adding `define('GREENMETRICS_DEBUG', true);` to your wp-config.php
- **Testing**: Special test mode for running tests
- **Production**: Optimized performance with caching enabled

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please make sure your code follows our coding standards and includes appropriate tests.

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details. 