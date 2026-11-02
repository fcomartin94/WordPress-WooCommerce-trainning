# Course Store - Custom WooCommerce Child Theme

A Storefront child theme for an online course store. Each course is a
WooCommerce product with custom fields (ACF), quantity-based discounts
implemented in PHP without plugins, and a customer dashboard that consumes
the WP REST API.

## Stack
PHP 8.2 - WordPress - WooCommerce - ACF - WP REST API - Vanilla JavaScript - MySQL - WP-CLI

## Features
- "Course" product type with duration, level, instructor, and date (ACF)
- "Syllabus" tab added to the product page via a WooCommerce filter
- Tiered quantity discounts (10% from 3 items, 20% from 5 items) implemented in PHP, without plugins
- Dynamic cart notice encouraging customers to reach the next discount tier
- Custom REST endpoint `/cursos/v1/mis-pedidos` protected by the user session
- "My Courses" tab in My Account consuming the endpoint with fetch

## Installation
1. WordPress with the Storefront theme installed
2. Plugins: WooCommerce and Advanced Custom Fields enabled
3. Copy `storefront-child/` to `wp-content/themes/` and activate it
4. Create the ACF field group "Course Details" (see screenshots)
5. Settings -> Permalinks -> Save Changes (flushes the rewrite rules)

