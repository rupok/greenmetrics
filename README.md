# GreenMetrics - Website Carbon Footprint, Sustainability & Performance Metrics

GreenMetrics helps you build a more sustainable internet by tracking your website's carbon footprint, energy consumption, and resource usage, offering insights and recommendations for eco-friendly performance.

Website: [getgreenmetrics.com](https://getgreenmetrics.com)

## Features

### Core Features
- **Comprehensive Dashboard**: Real-time metrics showing Carbon Footprint (g CO2), Energy Consumption (kWh), Data Transfer, HTTP Requests, Page Views, and Performance Score
- **Environmental Impact Context**: Understanding the real-world impact of your website through relatable metrics
- **Optimization Suggestions**: Actionable advice with status indicators for Page Size, HTTP Requests, Performance Score, and Green Hosting
- **Real-time Tracking**: Continuous monitoring of your site's performance and environmental impact metrics
- **Per-Page and Total Website Metrics**: View both aggregated stats and per-page averages
- **Carbon Intensity Settings**: Configure your energy consumption calculations based on your hosting location

### Display & Customization
- **Gutenberg Block & Shortcode**: Easily display your environmental stats on any page
- **Customizable Badge**: Control the position, theme, size, colors, icon and text of your eco-metrics badge
- **Enhanced Metrics Display**: Fully customizable metrics list with hover effects, fonts, and styling options
- **Global Badge Display**: Option to automatically display badge site-wide without manual placement
- **Popover Customization**: Complete control over popover appearance including colors, fonts, metrics display and hover effects

### Data Management & Reporting
- **Data Aggregation**: Automatically aggregate detailed metrics into daily summaries for optimal database performance
- **Data Pruning**: Scheduled cleanup of old data to maintain database efficiency
- **Advanced Reporting**: Detailed metrics with customizable time periods and visualization options
- **Email Reporting**: Scheduled email reports with customizable frequency (daily, weekly, monthly)
- **Email Templates**: Fully customizable email templates with color schemes, placeholders, and content options
- **Email History**: Track and view all sent email reports with detailed history and status information
- **Visual Charts**: Beautiful trend charts showing metrics over time in email reports
- **Data Export**: Export your environmental metrics data for external analysis

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

### Data Management

The Data Management page helps you maintain optimal database performance:

- **Data Aggregation**: Automatically aggregate detailed metrics into daily summaries
- **Data Pruning**: Remove old data to keep your database lean and efficient
- **Manual Controls**: Run aggregation and pruning operations on demand
- **Schedule Settings**: Configure automatic maintenance schedules
- **Data Export/Import**: Export data in CSV, JSON, or PDF formats, and import previously exported data with options to skip, replace, or merge duplicates

### Advanced Reporting

The Advanced Reporting page provides detailed insights into your website's environmental impact:

- **Time Period Selection**: View metrics for custom date ranges
- **Visualization Options**: Toggle between different chart types
- **Metric Filtering**: Focus on specific metrics of interest
- **Data Export**: Download your metrics data in CSV, JSON, or PDF formats for external analysis

### Email Reporting

The Email Reporting page allows you to set up automated email reports:

- **Schedule Configuration**: Set daily, weekly, or monthly reporting frequency
- **Recipient Management**: Add multiple email recipients
- **Content Customization**: Choose which metrics to include in reports
- **Template Selection**: Pick from different email templates
- **Custom Styling**: Personalize colors, fonts, and layout
- **Visual Charts**: Include trend charts showing metrics over time

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

### How do the email reports work?
Email reports can be scheduled to send daily, weekly, or monthly summaries of your website's environmental metrics. You can customize the recipients, content, and appearance of these reports through the Email Reporting settings page. The system tracks all sent emails and maintains a complete history for your reference.

### What information is included in email reports?
Email reports include key metrics such as carbon footprint, energy consumption, and data transfer, along with visual charts showing trends over the selected time period. You can customize which metrics to include and how they're displayed. Dynamic placeholders allow you to personalize the content with site-specific information.

### How does the email history feature work?
The Email History tab maintains a complete record of all sent email reports, including date/time, recipients, subject, report type, and delivery status. You can view the full content of any previously sent report and track which reports were successfully delivered.

### How does the data management system work?
The data management system automatically aggregates detailed metrics into daily summaries and prunes old data to maintain optimal database performance. You can configure the aggregation and pruning schedules, or run these operations manually when needed.

### Will data aggregation affect the accuracy of my metrics?
No, data aggregation preserves the accuracy of your metrics while reducing database size. The system maintains daily summaries that provide the same insights as detailed data for historical analysis.

### Can I export my environmental impact data?
Yes, you can export your metrics data in CSV, JSON, or PDF formats from the Data Management page. This allows you to analyze your data externally, create custom reports, or back up your metrics.

### Can I import previously exported data?
Yes, the Data Management page includes an import feature that allows you to import previously exported data in CSV or JSON formats. You can choose how to handle duplicate records (skip, replace, or merge) during the import process.

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
│   │   ├── css/          # Admin CSS files
│   │   ├── js/           # Admin JavaScript files
│   │   ├── img/          # Admin images
│   │   └── fonts/        # Admin fonts
│   ├── class-*.php       # Core classes
│   ├── class-greenmetrics-data-manager.php    # Data management functionality
│   ├── class-greenmetrics-advanced-reports.php # Advanced reporting functionality
│   ├── class-greenmetrics-email-reporter.php  # Email reporting functionality
│   ├── class-greenmetrics-chart-generator.php # Chart generation for reports
├── languages/            # Translation files
├── public/               # Public-facing functionality
│   ├── css/              # Public styles
│   ├── js/               # Public scripts
│   │   └── blocks/       # Gutenberg blocks
│   └── partials/         # Public templates
├── templates/            # Email and report templates
│   ├── emails/           # Email templates
│   └── reports/          # Report templates
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