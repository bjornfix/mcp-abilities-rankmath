# MCP Abilities - Rank Math

Rank Math SEO abilities for MCP. Get and update meta descriptions, titles, focus keywords, and other SEO settings.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/mcp-abilities-rankmath)](https://github.com/bjornfix/mcp-abilities-rankmath/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net)

**Tested up to:** 7.0
**Stable tag:** 1.1.9
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## What It Does

Rank Math SEO abilities for MCP. Get and update meta descriptions, titles, focus keywords, and other SEO settings.

This plugin is part of the MCP abilities ecosystem. It gives an MCP-capable agent a focused, authenticated way to work with Rank Math work inside WordPress through MCP.

**Example:** "Handle this WordPress maintenance task directly." - The agent can inspect the site, call the relevant ability, and return the result without making the human click through wp-admin for every step.

## The Real Workflow

In practice, the human should not have to memorize every ability name.

The normal pattern is:

1. install the base MCP stack
2. install only the add-ons the site actually needs
3. let the agent discover the available abilities
4. give the agent a clear task with boundaries
5. verify the result in WordPress

The human's job is mostly to describe the goal.
The agent's job is to figure out the mechanics.

## Why This Feels Different

Most WordPress automation still leaves the repetitive part to the human.

This plugin is different because the agent can act inside the site through a narrow, authenticated ability surface:

- inspect current site state before changing anything
- run the specific action needed for the task
- return structured results that are easy to verify
- keep the workflow inside WordPress instead of a separate checklist

That changes the experience from:

- `Here is what you should do in wp-admin`

to:

- `Tell the agent what needs doing, and let it carry out the work`

## Before vs After

### Before

- ask the AI what to do
- copy the answer into WordPress by hand
- click through wp-admin for the repetitive bits
- postpone maintenance because the task is tedious

### After

- tell the agent what needs doing
- let it inspect the relevant WordPress state
- let it run the targeted ability
- verify the result and move on

## Who It Is For

This is a good fit for:

- agencies managing WordPress sites with AI-assisted maintenance
- operators who want agents to do real WordPress work instead of producing instructions
- teams already using MCP Expose Abilities
- sites where this WordPress area is updated often enough to deserve automation

It is especially useful when the manual version is repetitive enough that important maintenance gets delayed.

## Documentation

Start with the main plugin page and base stack documentation:

- [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/)
- [Plugin Page](https://devenia.com/plugins/mcp-expose-abilities/#add-ons)
- [Getting Started](https://github.com/bjornfix/mcp-expose-abilities/wiki/Getting-Started)
- [Install Order and Dependencies](https://github.com/bjornfix/mcp-expose-abilities/wiki/Install-Order-and-Dependencies)

If you are using an AI agent, the simplest instruction is often just:

- `Read https://github.com/bjornfix/mcp-expose-abilities and figure out the stack before making changes.`

## Start Here

If you are new to the stack, use this order:

1. Install **Abilities API**.
2. Install **MCP Adapter**.
3. Install **MCP Expose Abilities**.
4. Install **MCP Abilities - Rank Math**.
5. Confirm the new abilities appear in discovery.
6. Give the agent a clear task that uses this add-on.

If you skip base-stack verification and start with add-ons immediately, troubleshooting gets harder than it needs to be.

## Abilities (31)

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
| `rankmath/get-meta` | Get SEO metadata and stored SEO score for a single post or page |
| `rankmath/update-meta` | Update SEO metadata (title, description, focus keyword, robots, canonical, flags, plus common aliases) |
| `rankmath/bulk-get-meta` | Retrieve SEO metadata and stored SEO scores for multiple posts with filtering |
| `rankmath/get-inbound-links` | Report internal inbound links to one target or list linked internal targets |
| `rankmath/audit-content-seo` | Find content with missing SEO fields, noindex, low scores, missing schema, or weak inbound links |
| `rankmath/get-post-schema` | Read Rank Math schema-related post meta |
| `rankmath/update-post-schema` | Update or delete Rank Math `rank_math_schema_*` post meta |
| `rankmath/get-primary-term` | Read the Rank Math primary term for a post taxonomy |
| `rankmath/update-primary-term` | Set or clear the Rank Math primary term for a post taxonomy |
| `rankmath/list-sitemap-urls` | Fetch sitemap index entries and child sitemap URLs |
| `rankmath/find-redirection` | Find redirection rules matching one URL or path |
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

### 1.1.11
- Preserves documented Rank Math percent variables such as `%date%` when sanitizing nested schema values.

### 1.1.8
- Refactored ability registration into focused include modules for options,
  routes, site settings, content SEO, and logs/redirections. No MCP ability
  names changed.

### 1.1.7
- Added: content SEO audit, post schema, primary term, sitemap URL, and
  redirection-match abilities for broader Rank Math automation

### 1.1.6
- Added: `rankmath/get-inbound-links` builds an internal inbound-link report
  from WordPress content and navigation menus

### 1.1.5
- Fixed: `rankmath/create-redirection` now rejects exact sources that normalize
  to the same path as the destination

### 1.1.4
- Added: `rankmath/get-meta` and `rankmath/bulk-get-meta` now include the stored Rank Math SEO score as `seo_score`

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

## Contributing

PRs welcome. Keep changes focused on the plugin's WordPress ability surface and preserve authenticated, explicit workflows.

## License

GPL-2.0+

## Author

[basicus](https://profiles.wordpress.org/basicus/)

## Links

- [Plugin Page](https://devenia.com/plugins/mcp-expose-abilities/#add-ons)
- [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/)
- [GitHub Releases](https://github.com/bjornfix/mcp-abilities-rankmath/releases)

## Star and Share

If this plugin saves you time or makes WordPress maintenance easier to verify, please:

- star the repo
- share it with people running WordPress sites
- point them to the main plugin page so they can see what the ecosystem can actually do

Why do it?

Because agent-friendly open WordPress tooling helps more of the boring but important work get done.
