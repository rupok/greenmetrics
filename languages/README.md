# GreenMetrics Plugin Translation Files

This directory contains translation files for the GreenMetrics plugin.

## Structure

- `.pot` files: Translation templates
- `.po` files: Editable translation files for specific languages
- `.mo` files: Compiled translation files used by WordPress

## Generating Translation Files

To generate or update the translation template:

1. Install WP-CLI: https://wp-cli.org/
2. Run the following command in the plugin root directory:

```bash
wp i18n make-pot . languages/greenmetrics.pot --domain=greenmetrics
```

## Adding Translations

To create translations for a specific language:

1. Copy the `.pot` file to `greenmetrics-LOCALE.po` (replace LOCALE with the language code, e.g., `de_DE`)
2. Edit the `.po` file with a translation tool like Poedit
3. Generate the `.mo` file using your translation tool or with WP-CLI:

```bash
wp i18n make-mo languages/
```

## JavaScript Translations

The plugin's JavaScript files are configured to use WordPress's JavaScript internationalization functions. Translation files for JavaScript are generated automatically by WordPress when the plugin is installed.

For more information on translating WordPress plugins, refer to the [WordPress Internationalization documentation](https://developer.wordpress.org/plugins/internationalization/). 