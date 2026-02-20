# MCP Abilities - Rank Math

Rank Math SEO abilities for WordPress via MCP.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/mcp-abilities-rankmath)](https://github.com/bjornfix/mcp-abilities-rankmath/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

**Tested up to:** 6.9
**Stable tag:** 1.0.6
**Requires PHP:** 8.0
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## What It Does

This add-on plugin exposes Rank Math SEO functionality through MCP (Model Context Protocol). Your AI assistant can manage metadata, redirections, and 404 logs.

**Part of the [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/) ecosystem.**

## Requirements

- WordPress 6.9+
- PHP 8.0+
- [Abilities API](https://github.com/WordPress/abilities-api) plugin
- [MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin
- [MCP Expose Abilities](https://github.com/bjornfix/mcp-expose-abilities) core plugin
- [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/) plugin

## Installation

1. Install and activate MCP Expose Abilities
2. Install and activate Rank Math SEO
3. Download the latest release from [Releases](https://github.com/bjornfix/mcp-abilities-rankmath/releases)
4. Upload via WordPress Admin > Plugins > Add New > Upload Plugin
5. Activate the plugin

## Abilities (12)

| Ability | Description |
|---------|-------------|
| `rankmath/list-options` | List Rank Math option names stored in wp_options |
| `rankmath/get-options` | Get Rank Math option values by name |
| `rankmath/update-options` | Update Rank Math option values by name |
| `rankmath/get-meta` | Get SEO metadata for a single post or page |
| `rankmath/update-meta` | Update SEO metadata (title, description, focus keyword, robots, canonical, flags) |
| `rankmath/bulk-get-meta` | Retrieve SEO metadata for multiple posts with filtering |
| `rankmath/list-404-logs` | List recent Rank Math 404 log entries |
| `rankmath/delete-404-logs` | Delete 404 log entries by ID |
| `rankmath/clear-404-logs` | Clear all Rank Math 404 logs (requires confirm) |
| `rankmath/list-redirections` | List Rank Math redirections |
| `rankmath/create-redirection` | Create Rank Math redirections with one or more sources |
| `rankmath/delete-redirections` | Delete Rank Math redirections by ID |

## Usage Examples

### Get SEO meta for a page

```json
{
  "ability_name": "rankmath/get-meta",
  "parameters": {
    "id": 123
  }
}
```

### Update meta description

```json
{
  "ability_name": "rankmath/update-meta",
  "parameters": {
    "id": 123,
    "seo_description": "Updated meta description with focus keyword included."
  }
}
```

### Create a redirection

```json
{
  "ability_name": "rankmath/create-redirection",
  "parameters": {
    "sources": [
      {
        "pattern": "old-page",
        "comparison": "exact"
      }
    ],
    "destination": "https://example.com/new-page/",
    "header_code": 301
  }
}
```

## Changelog

### 1.0.6
- Fixed: Removed hard plugin header dependency on abilities-api to avoid slug-mismatch activation blocking
- Improved: README and ability docs synced with current release

### 1.0.5
- Safety: Require confirmation for clearing 404 logs

### 1.0.4
- Added Rank Math redirection and 404 log management abilities

### 1.0.3
- Simplify post access validation and reuse helper

### 1.0.2
- Security: Added per-post capability checks for meta access and bulk listings

### 1.0.1
- Fixed: Abilities now register correctly with 'site' category

### 1.0.0
- Initial release
- Added `rankmath/get-meta` ability
- Added `rankmath/update-meta` ability
- Added `rankmath/bulk-get-meta` ability

## License

GPL-2.0+

## Author

[Devenia](https://devenia.com) - We've been doing SEO and web development since 1993.

## Links

- [Plugin Page](https://devenia.com/plugins/mcp-expose-abilities/)
- [Core Plugin (MCP Expose Abilities)](https://github.com/bjornfix/mcp-expose-abilities)
- [All Add-on Plugins](https://devenia.com/plugins/mcp-expose-abilities/#add-ons)
