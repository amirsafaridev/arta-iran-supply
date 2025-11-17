# Arta Iran Supply - Contract Management Panel

A comprehensive WordPress plugin for managing contracts and orders for organizations. This plugin provides a complete contract management system with stage tracking, file uploads, and WooCommerce integration.

## Description

Arta Iran Supply is a powerful contract management plugin designed for organizations. It provides a dedicated frontend panel where organizations can manage their contracts, track contract stages, upload files, and handle order requests through WooCommerce integration.

## Features

### Core Features

- **Contract Management**: Custom post type for managing contracts with full CRUD operations
- **Contract Stages**: Track and manage multiple stages for each contract with status updates
- **Organization Panel**: Frontend panel accessible at `/contracts-panel` for organization users
- **User Roles**: Custom "organization" user role with specific capabilities
- **File Uploads**: Upload and manage files associated with contracts
- **WooCommerce Integration**: "Request Order" feature that modifies WooCommerce checkout behavior
- **Settings Page**: Comprehensive settings page for customizing panel appearance and behavior
- **Help Menu**: Built-in help documentation for users

### Request Order Feature

When enabled, this feature:
- Replaces standard "Add to Cart" buttons with "Request Order" buttons
- Creates orders with "on-hold" status (no payment required)
- Provides a custom payment gateway for order requests
- Redirects users to checkout after adding items via "Request Order"

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **WooCommerce**: Required for Request Order feature (optional for core contract management)

## Installation

1. Upload the plugin files to the `/wp-content/plugins/arta-iran-supply` directory, or install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to **Contracts > Settings** to configure the plugin
4. Create organization users with the "organization" role
5. Access the organization panel at `/contracts-panel`

## Configuration

### Settings Page

Navigate to **Contracts > Settings** in the WordPress admin to configure:

- **Panel Title**: Custom title for the organization panel
- **Panel Logo**: Upload a logo for the panel
- **Login Page Colors**: Customize background, primary, and secondary colors for the login page
- **Panel Colors**: Customize primary, secondary, and sidebar background colors
- **Request Order Feature**: Enable/disable the WooCommerce Request Order functionality

### User Roles

The plugin creates a custom user role called "organization" with the following capabilities:
- `read`: Basic reading capability
- `read_contracts`: Read contracts
- `edit_own_contracts`: Edit their own contracts
- `upload_files`: Upload files

### Contract Stages

Contract stages can be managed through the WordPress admin. Each contract can have multiple stages with:
- Stage name
- Status (pending, in-progress, completed, etc.)
- Notes
- File attachments
- Timestamps

## Usage

### For Administrators

1. **Create Contracts**: Go to **Contracts > Add New** to create new contracts
2. **Manage Stages**: Edit contracts to add and manage contract stages
3. **Assign Organizations**: Assign contracts to organization users
4. **Configure Settings**: Customize the panel appearance and features in **Contracts > Settings**

### For Organization Users

1. **Access Panel**: Log in and navigate to `/contracts-panel`
2. **View Contracts**: See all assigned contracts in the dashboard
3. **Manage Stages**: Update contract stages and upload required files
4. **Request Orders**: If enabled, use "Request Order" buttons on WooCommerce products

### Request Order Workflow

1. Organization user browses WooCommerce products
2. Clicks "Request Order" instead of "Add to Cart"
3. Product is added to cart and user is redirected to checkout
4. User completes checkout (no payment required)
5. Order is created with "on-hold" status
6. Admin can review and process the order

## File Structure

```
arta-iran-supply/
├── admin/
│   └── templates/
├── assets/
│   ├── css/
│   │   ├── help-page.css
│   │   ├── panel.css
│   │   └── settings-page.css
│   ├── font/
│   │   └── PeydaWeb-Regular.woff2
│   └── js/
│       ├── admin-contract-stages.js
│       ├── panel.js
│       └── settings-page.js
├── includes/
│   ├── class-ajax-handler.php
│   ├── class-contract-post-type.php
│   ├── class-contract-stages.php
│   ├── class-help-menu.php
│   ├── class-organization-panel.php
│   ├── class-request-order.php
│   ├── class-settings.php
│   └── class-user-roles.php
├── languages/
├── templates/
│   └── panel-template.php
├── arta-iran-supply.php
└── README.md
```

## Hooks and Filters

### Actions

- `arta_contract_stage_added`: Fired when a new contract stage is added
- `arta_contract_stage_updated`: Fired when a contract stage is updated
- `arta_contract_file_uploaded`: Fired when a file is uploaded to a contract

### Filters

- `arta_contract_stages`: Filter contract stages before display
- `arta_panel_settings`: Filter panel settings before saving
- `arta_request_order_enabled`: Filter to enable/disable Request Order feature

## Development

### Code Standards

- Follows WordPress Coding Standards
- Uses singleton pattern for main classes
- Properly namespaced with `Arta_Iran_Supply_` prefix
- All text strings are translatable

### Extending the Plugin

To extend the plugin functionality:

1. Use the provided hooks and filters
2. Create custom templates in your theme (if needed)
3. Override CSS by enqueuing your own stylesheet with higher priority

## Support

For support, feature requests, or bug reports, please contact the plugin author or visit the plugin support page.

## Changelog

### 1.0.0
- Initial release
- Contract management system
- Organization panel
- Contract stages management
- File upload functionality
- WooCommerce Request Order integration
- Settings page
- Help menu

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## Credits

Developed for Arta Iran Supply contract management system.

