# MCP Abilities - Rank Math

Rank Math SEO abilities for [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/#add-ons). Get and update meta descriptions, titles, focus keywords, and other SEO settings via the Abilities API.

**Stable tag: 1.0.2**

## Requirements

- [MCP Expose Abilities](https://github.com/bjornfix/mcp-expose-abilities) (core plugin)
- [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/) plugin

## Abilities (3)

| Ability | Description |
|---------|-------------|
| `rankmath/get-meta` | Get SEO metadata for a single post or page |
| `rankmath/update-meta` | Update SEO metadata (title, description, focus keyword, robots, canonical, flags) |
| `rankmath/bulk-get-meta` | Retrieve SEO metadata for multiple posts with filtering |

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

## Changelog

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
