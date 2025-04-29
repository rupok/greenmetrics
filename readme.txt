=== GreenMetrics – Website Carbon Footprint, Sustainability & Performance Metrics ===
Contributors: re_enter_rupok
Tags: sustainability, carbon footprint, website performance, energy usage, eco-friendly, co2, green web, optimization, website efficiency, carbon emissions, environmental impact
Requires at least: 5.5
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Measure and reduce your website's environmental impact. Track CO2, energy, and performance stats directly in your WordPress dashboard.

== Description ==

**GreenMetrics helps you build a more sustainable internet.**  
This plugin tracks your website's **carbon footprint**, **energy consumption**, and **resource usage**, offering insights and recommendations for eco-friendly performance improvements.

GreenMetrics is perfect for eco-conscious website owners who want to reduce their digital carbon footprint and showcase their commitment to sustainability.

For more information, visit [getgreenmetrics.com](https://getgreenmetrics.com).

### 🌱 Free Features

* **Comprehensive Dashboard**: Real-time metrics showing Carbon Footprint (g CO2), Energy Consumption (kWh), Data Transfer, HTTP Requests, Page Views, and Performance Score.
* **Environmental Impact Context**: Understanding the real-world impact of your website through relatable metrics.
* **Optimization Suggestions**: Actionable advice with status indicators for Page Size, HTTP Requests, Performance Score, and Green Hosting.
* **Gutenberg Block & Shortcode**: Easily display your environmental stats on any page using the built-in block editor or `[greenmetrics_badge]` shortcode.
* **Customizable Badge**: Control the position, theme, size, colors, icon and text of your eco-metrics badge with numerous configuration options.
* **Enhanced Metrics Display**: Fully customizable metrics list with hover effects, fonts, and styling options.
* **Real-time Tracking**: Continuous monitoring of your site's performance and environmental impact metrics.
* **Per-Page and Total Website Metrics**: View both aggregated stats and per-page averages to identify optimization opportunities.
* **Carbon Intensity Settings**: Configure your energy consumption calculations based on your hosting location.
* **Global Badge Display**: Option to automatically display badge site-wide without manual placement.
* **Popover Customization**: Complete control over popover appearance including colors, fonts, metrics display and hover effects.

### 🌍 Who Is This For?

GreenMetrics is ideal for:

* **Eco-conscious Website Owners** who want to reduce their digital carbon footprint
* **Developers** looking to optimize website performance and efficiency
* **Businesses** striving to meet sustainability goals and showcase environmental responsibility
* **Bloggers and Content Creators** focusing on green initiatives

### 📊 Track What Matters

GreenMetrics gives you comprehensive insights into your website's environmental impact:

* **Carbon Footprint** (grams of CO2)
* **Energy Consumption** (kilowatt-hours)
* **Data Transfer** (megabytes)
* **HTTP Requests** (count)
* **Page Views** (count)
* **Performance Score** (percentage)

### 🎯 Actionable Insights

Beyond raw metrics, GreenMetrics provides:

* **Status Indicators** showing where you need improvement
* **Optimization Recommendations** specific to your site
* **Real-world Comparisons** to help understand environmental impact
* **Historical Trends** to track your progress over time

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/greenmetrics` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to your WordPress dashboard to view the metrics.
4. Use the GreenMetrics block or `[greenmetrics_badge]` shortcode to display your site's stats.

== Usage ==

= Admin Dashboard =
1. Go to WordPress admin > GreenMetrics
2. Enable/disable tracking and badge display
3. View your website's performance metrics and environmental impact

= Display Settings =
The Display Settings page provides comprehensive customization for your badge and metrics display:

* **Badge Configuration**: Control visibility, position, size, and text
* **Icon Settings**: Choose between different icon styles or upload a custom icon
* **Color Options**: Customize badge background, text, and icon colors
* **Popover Settings**: Configure title, metrics to display, and custom content
* **Styling Options**: Customize fonts, font sizes, background colors, and hover effects

= Display Badge =
You can display the eco-friendly badge in three ways:

1. **Gutenberg Block**
   * Add the "GreenMetrics Badge" block to your page/post
   * Customize the appearance in the block settings

2. **Shortcode**
   * Use `[greenmetrics_badge theme="light" size="medium" position="bottom-right"]`
   * Parameters: theme (light/dark), size (small/medium/large), position (bottom-right/bottom-left/top-right/top-left)

3. **Global Badge**
   * Enable the global badge option in Display Settings to show the badge site-wide
   * Customize all aspects of the badge and popover through the admin interface

== Screenshots ==

1. Environmental impact metrics on the WordPress dashboard.
2. Add a GreenMetrics block to display stats on your site.
3. Example of the eco-awareness badge in the footer.
4. Customizable badge display settings.
5. Metrics popover with hover effects.

== Frequently Asked Questions ==

= How does GreenMetrics calculate my site's environmental impact? =

We use a combination of observed data based on asset size, page views, and HTTP requests. Our calculations account for data transfer, energy consumption per byte, and regional carbon intensity to provide meaningful insights.

= Will it slow down my website? =

No. GreenMetrics is lightweight and designed for performance. It uses efficient tracking methods and does not load external scripts on the frontend.

= Can I customize how the metrics are displayed? =

Yes, the Display Settings page offers extensive customization options for the badge and popover, including colors, fonts, metrics selection, and hover effects. Additionally, the shortcode and block offer multiple customization options.

= How accurate are the carbon and energy calculations? =

Our calculations are based on industry research regarding energy consumption per byte of data transferred and carbon intensity of electrical grids. While they provide meaningful approximations, they may not account for all variables in the hosting infrastructure.

= Can I track individual page performance? =

Yes, GreenMetrics tracks metrics on a per-page basis, allowing you to identify which pages have the highest environmental impact and optimize them accordingly.

= Does the plugin work with caching plugins? =

Yes, GreenMetrics is compatible with most popular caching plugins. It tracks actual user visits regardless of whether they're served cached content.

= Is the data stored locally or on external servers? =

All data is stored locally in your WordPress database. GreenMetrics does not send your website metrics to any external servers.

= Can I export my environmental impact data? =

Currently, data export features are not available, but they're on our roadmap for future versions.

= How often are the statistics updated? =

Statistics are updated in real-time as users visit your site. The dashboard displays are refreshed daily or can be manually refreshed at any time.

== Changelog ==

= 1.0.0 =
* Initial release with core environmental metrics, dashboard integration, and shortcode/block support
* Comprehensive metrics dashboard with total and per-page averages
* Environmental impact context visualizations
* Optimization suggestions with status indicators
* Customizable metrics badge via shortcode and block
* Global badge display option
* Enhanced display settings with complete customization of colors, fonts, and hover effects
* Popover customization with metrics selection

== Upgrade Notice ==

= 1.0.0 =
Initial public release of GreenMetrics – track your website's carbon footprint today!

== License ==

This plugin is licensed under the GPLv2 or later.

== Credits ==

* Icons provided by various open-source icon libraries
* Built with love for a greener, more sustainable web 