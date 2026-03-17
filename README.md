# MCP Abilities - Rank Math

Rank Math SEO abilities for WordPress via MCP.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/mcp-abilities-rankmath)](https://github.com/bjornfix/mcp-abilities-rankmath/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

**Tested up to:** 6.9
**Stable tag:** 1.1.3
**Requires PHP:** 8.0
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## What It Does

This add-on plugin exposes Rank Math SEO functionality through MCP (Model Context Protocol). Your AI assistant can manage metadata, redirections, and 404 logs.

**Part of the [MCP Expose Abilities](https://github.com/bjornfix/mcp-expose-abilities) ecosystem.**

This is one piece of a bigger open WordPress automation stack that lets AI agents do real SEO operations inside WordPress instead of just making recommendations.

## Why This Is Cool

SEO cleanup usually dies in the gap between “we should fix this” and “who wants to do it by hand?”

This add-on closes that gap. You can ask the agent to inspect metadata, review redirects, look at 404 logs, check schema state, and make the actual change instead of leaving the work stuck in a spreadsheet or chat thread.

## Documentation

- [Core Plugin: MCP Expose Abilities](https://github.com/bjornfix/mcp-expose-abilities)
- [MCP Wiki Home](https://github.com/bjornfix/mcp-expose-abilities/wiki)
- [Why Teams Use It](https://github.com/bjornfix/mcp-expose-abilities/wiki/Why-Teams-Use-It)
- [Use Cases](https://github.com/bjornfix/mcp-expose-abilities/wiki/Use-Cases)
- [Rank Math Add-On Guide](https://github.com/bjornfix/mcp-expose-abilities/wiki/Addon-Rank-Math)
- [Getting Started](https://github.com/bjornfix/mcp-expose-abilities/wiki/Getting-Started)

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

## Abilities (23)

| Ability | Description |
|---------|-------------|
| `rankmath/list-options` | List Rank Math option names stored in wp_options |
| `rankmath/get-options` | Get Rank Math option values by name |
| `rankmath/update-options` | Update Rank Math option values by name |
| `rankmath/get-schema-status` | Return effective global publisher/schema settings |
| `rankmath/list-modules` | List Rank Math modules with active/disabled status |
| `rankmath/update-modules` | Enable or disable Rank Math modules by slug |
| `rankmath/get-rewrite-status` | Inspect stored rewrite rules for Rank Math endpoints |
| `rankmath/get-llms-status` | Return llms.txt module state, settings, rewrite status, and live preview |
| `rankmath/preview-llms` | Fetch the live llms.txt output for inspection |
| `rankmath/update-publisher-profile` | Safely update global publisher/entity fields |
| `rankmath/get-social-profiles` | Return social profile fields feeding `sameAs` |
| `rankmath/update-social-profiles` | Update social profile fields feeding `sameAs` |
| `rankmath/get-sitemap-status` | Return sitemap module state, enabled object types, and live sitemap check |
| `rankmath/refresh-llms-route` | Verify the Rank Math `llms.txt` rewrite rule and flush rewrites when needed |
| `rankmath/get-meta` | Get SEO metadata for a single post or page |
| `rankmath/update-meta` | Update SEO metadata (title, description, focus keyword, robots, canonical, flags, plus common aliases) |
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
    "description": "Updated meta description with focus keyword included."
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

### 1.1.3
- Improved: `rankmath/update-meta` now accepts the convenience aliases `title`, `description`, and `keyword` alongside the canonical SEO field names

### 1.1.2
- Fixed: moved organization contact restoration to Rank Math's final validated schema filter so public JSON-LD now keeps the configured contact fields

### 1.1.1
- Added: explicit `email` and `telephone` restoration for public `Organization` schema nodes when those fields are configured in the publisher profile
- Fixed: Rank Math contact details stored in MCP publisher settings now survive the final schema sanitization step
### 1.1.0
- Added: schema status, module management, rewrite inspection, llms status/preview, publisher profile, social profiles, and sitemap status abilities
- Improved: publisher/schema and llms debugging can now be done without raw option spelunking

### 1.0.11
- Added: llms.txt-only title override so Rank Math can output `Log In: Everywhere!` without polluting global entity/schema settings

### 1.0.9
- Added: `rankmath/refresh-llms-route` to inspect and refresh the Rank Math `llms.txt` rewrite rule after module changes

### 1.0.8
- Fixed: `rankmath/list-options` now uses a Plugin Check-compliant prepared SQL query while still exposing both `rank_math_*` and `rank-math-*` option names

### 1.0.7
- Fixed: Rank Math global settings stored as `rank-math-*` options are now visible and writable through the MCP option abilities

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

## Free and Open

Like the rest of the ecosystem, this add-on is free for everyone, fully open, and built to be genuinely useful in production.

## Star and Share

If this add-on helps, please star the repo, share the ecosystem, and point people to the main wiki:

- https://github.com/bjornfix/mcp-expose-abilities
- https://github.com/bjornfix/mcp-expose-abilities/wiki

## Links

- [Core Plugin (MCP Expose Abilities)](https://github.com/bjornfix/mcp-expose-abilities)
- [Main Wiki](https://github.com/bjornfix/mcp-expose-abilities/wiki)
- [Rank Math Add-On Guide](https://github.com/bjornfix/mcp-expose-abilities/wiki/Addon-Rank-Math)
