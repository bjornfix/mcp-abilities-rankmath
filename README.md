# MCP Abilities - Rank Math

Rank Math SEO abilities for [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/#add-ons). Manage SEO metadata, redirections, and 404 logs via the Abilities API.

**Stable tag: 1.0.5**

## Requirements

- [MCP Expose Abilities](https://github.com/bjornfix/mcp-expose-abilities) (core plugin)
- [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/) plugin

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

## Installation

1. Install and activate [MCP Expose Abilities](https://github.com/bjornfix/mcp-expose-abilities)
2. Install and activate [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/)
3. Download the latest release zip
4. Upload to WordPress via Plugins → Add New → Upload Plugin
5. Activate the plugin

## Usage Examples

### Get SEO Meta for a Page

```json
{
  "ability": "rankmath/get-meta",
  "parameters": {
    "id": 123
  }
}
```

Response:
```json
{
  "success": true,
  "id": 123,
  "title": "My Page Title",
  "url": "https://example.com/my-page/",
  "seo_title": "My Page | Example Site",
  "seo_description": "A compelling meta description for this page.",
  "focus_keyword": "my page, example",
  "robots": ["index", "follow"],
  "canonical_url": "",
  "is_pillar": false,
  "is_cornerstone": true
}
```

### Update Meta Description

```json
{
  "ability": "rankmath/update-meta",
  "parameters": {
    "id": 123,
    "seo_description": "Updated meta description with focus keyword included."
  }
}
```

### Find Posts Missing Meta Descriptions

```json
{
  "ability": "rankmath/bulk-get-meta",
  "parameters": {
    "missing_desc": true,
    "per_page": 50
  }
}
```

### Create a Redirection

```json
{
  "ability": "rankmath/create-redirection",
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
