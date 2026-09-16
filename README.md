# MCP Abilities - Rank Math

Turn an SEO review into a set of changes you can check in WordPress. MCP Abilities - Rank Math lets an authenticated agent find missing metadata, inspect internal links, repair approved redirects, and read back the result through 32 WordPress abilities.

[![Release 1.1.21](https://img.shields.io/badge/release-1.1.21-blue.svg)](https://downloads.devenia.com/mcp-abilities-rankmath.zip)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://www.php.net/)

**Tested up to:** 7.1

**Stable tag:** 1.1.21

**License:** GPLv2 or later

**Tags:** seo, rank math, mcp, api, automation

## What It Does

The plugin connects an agent to Rank Math's stored SEO fields, settings, modules, schema records, sitemap output, 404 logs, and redirections. The agent can inspect a problem, make the requested change, and check the stored result without copying values between a chat and the WordPress editor.

## What You Can Build

### A focused metadata repair queue

Ask the agent to review service pages for empty SEO titles and descriptions. It can return the affected pages, prepare accurate copy from each page's subject, save approved text, and read it back. An empty custom field is a review signal: Rank Math may already supply a useful default template.

### A redirect review after a site restructure

A service has moved to a new URL, but visitors still request its old address. The agent can inspect 404 logs and existing redirect rules, verify the intended replacement, and create a specific redirect. Check the resulting HTTP route before clearing logs; deleting a log does not repair a URL.

### A publishing check that includes discovery

Ask which pages lack internal inbound links and whether expected URLs appear in the sitemap. Combine that evidence with a content review to decide where a useful link belongs. The add-on reports links found in stored content and navigation menus; it does not edit page copy or crawl every dynamically rendered link.

### A consistent publisher profile

An organisation changes its public contact information. The agent can inspect the current publisher and social settings, update verified details, and check the resulting structured data. Sitemap and llms.txt inspection can support the same maintenance routine when their Rank Math modules are enabled.

## The Real Workflow

1. Define the pages or settings in scope and the desired result.
2. Ask the agent to read current metadata, relevant links, and existing rules.
3. Review proposed wording or redirect destinations before applying them.
4. Save the selected changes through the relevant ability.
5. Read back metadata and inspect rendered pages or HTTP redirects.

## Why This Feels Different

The same task can move from finding an empty field to saving its replacement and showing the stored result. WordPress remains the content system, Rank Math remains the SEO engine, and the agent uses explicit operations that can be inspected individually.

## Before vs After

| Task | Manual workflow | With the add-on |
| --- | --- | --- |
| Review descriptions | Open each editor and collect fields | Retrieve stored fields and filter the review |
| Repair an old URL | Compare logs and rules in separate screens | Inspect both, then create the chosen rule |
| Check publisher details | Search through site settings | Read and update the supported profile fields |

## Who It Is For

Agencies, editors, and site owners who already use Rank Math and want repeatable maintenance through an authenticated agent. It is useful when a task covers several pages or needs evidence from more than one Rank Math screen.

## Requirements

- WordPress 6.9 or later, with the native Abilities API available.
- PHP 8.0 or later.
- [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/), with the modules needed for your task enabled.
- An MCP connection, such as [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/), configured for the WordPress user carrying out the work.

## Documentation

Read the [plugin page](https://devenia.com/plugins/mcp-abilities-rankmath/) and the [MCP Expose Abilities setup](https://devenia.com/plugins/mcp-expose-abilities/).

## Start Here

1. Install and activate Rank Math SEO.
2. Configure and verify your authenticated MCP connection.
3. Install and activate this add-on.
4. Discover the `rankmath/` abilities and start with a read operation on one page.

## Abilities (32)

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
| `rankmath/audit-faq-links` | Find Rank Math FAQ blocks with links, invalid question data, or empty items |
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

## Permissions and Limits

Content reads and writes require `edit_posts`; individual metadata operations also check the affected post. Inbound-link and FAQ reports also restrict post data to content the caller can edit. Administrative settings, modules, routes, logs, and redirect management require `manage_options`.

Deleting schema records requires `confirm_delete: true`. Clearing all 404 logs requires `confirm: true`. These confirmations apply to those operations; they are not a general approval system for every write. Global option writes can replace a complete option value, so read and preserve fields you intend to keep.

SEO scores are stored Rank Math values, not fresh measurements or search rankings. An audit of stored custom fields does not establish the final rendered title, schema, indexability, or search-engine treatment. Review those outputs separately. The plugin does not supply keyword research, traffic data, an AI model, or a guarantee of indexing, rankings, or AI citations.

The optional Devenia Workflow integration coordinates SEO metadata and FAQ handling when that plugin is present. Stable page sitemap ordering and image-attribute compatibility operate with Rank Math independently.

## Usage Examples

### Read a page before editing

```json
{"ability_name":"rankmath/get-meta","parameters":{"id":123}}
```

### Save a description based on the page's actual offer

```json
{"ability_name":"rankmath/update-meta","parameters":{"id":123,"description":"Compare fitted kitchen layouts, materials and installation options. See completed projects and request a design consultation."}}
```

### Restore the site's default robots behaviour

```json
{"ability_name":"rankmath/update-meta","parameters":{"id":123,"clear_robots":true}}
```

Do not include `robots` in the same request as `clear_robots`.

### Redirect an old service address

```json
{"ability_name":"rankmath/create-redirection","parameters":{"sources":[{"pattern":"old-service","comparison":"exact"}],"destination":"https://example.com/services/new-service/","header_code":301}}
```

Verify the destination and any existing matching rules before creating a redirect.

## Installation


For update notifications in WordPress, install [Devenia MCP Updater](https://downloads.devenia.com/devenia-mcp-updater.zip). The updater is optional. You choose which plugins update automatically through WordPress.

[Download MCP Abilities - Rank Math](https://downloads.devenia.com/mcp-abilities-rankmath.zip), upload the ZIP through WordPress **Plugins → Add New → Upload Plugin**, and activate it. Confirm that your MCP client can discover and execute an authorised read operation.

## Changelog


### 1.1.21

Add one dismissible Plugins-screen reminder when Devenia MCP Updater is missing or inactive, with persistent install or activate links. Automatic updates remain your choice in WordPress.

### 1.1.20
- Restrict FAQ and inbound-link reports to posts the caller can edit.

- Reject conflicting robots settings and invalid schema changes before saving any metadata.
- Preserve Rank Math variables and literal backslashes in SEO text and nested schema values while removing HTML from text fields.
- Add an explicit way to restore the default robots behaviour.
- Keep stable page sitemap ordering available without Devenia Workflow and skip FAQ counting for content without FAQ blocks.

### 1.1.18
- Preserves HTML5 characters in existing image attributes when Rank Math adds missing alternative text or titles.

### 1.1.17
- Filters page sitemap eligibility after bounded native queries while preserving deterministic pagination.

### 1.1.16
- Uses the native WordPress query interface for deterministic Workflow page sitemap slices.

### 1.1.15
- Makes Devenia Workflow page sitemap pagination deterministic when many published pages share one modified timestamp.

### 1.1.14
- Fixed the optional Workflow adapter strings to use this plugin's translation domain.

### 1.1.13
- Adds the optional Devenia Workflow adapter for Rank Math metadata, redirects, sitemap refreshes, and FAQ integrity checks.

### 1.1.12
- Fixed: llms.txt now preserves the current WordPress site's own Rank Math title and description instead of applying plugin-owned branding

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

Contributions should preserve native WordPress permissions and Rank Math behaviour. Include a focused example showing the changed public operation and its expected result.

## License

GPLv2 or later. See the [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html).

## Author

[basicus](https://profiles.wordpress.org/basicus/)

## Links

- [Plugin page](https://devenia.com/plugins/mcp-abilities-rankmath/)
- [Download](https://downloads.devenia.com/mcp-abilities-rankmath.zip)
- [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/)
