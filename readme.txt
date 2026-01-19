=== MCP Abilities - Rank Math ===
Contributors: devenia
Tags: seo, rank math, mcp, api, automation
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Rank Math SEO abilities for MCP. Get and update meta descriptions, titles, focus keywords, and other SEO settings via the Abilities API.

== Description ==

This add-on plugin extends [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/) with Rank Math SEO functionality. It enables AI agents and automation tools to manage SEO metadata, redirections, and 404 logs.

= Requirements =

* [MCP Expose Abilities](https://github.com/bjornfix/mcp-expose-abilities) (core plugin)
* [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/) plugin

= Abilities Included =

**rankmath/list-options** - List Rank Math option names stored in wp_options.

**rankmath/get-options** - Get Rank Math option values by name.

**rankmath/update-options** - Update Rank Math option values by name.

**rankmath/get-meta** - Get SEO metadata for a single post or page including title, description, focus keyword, robots directives, canonical URL, and content flags.

**rankmath/update-meta** - Update SEO metadata for a post or page. Supports title, description, focus keyword, robots, canonical URL, pillar content, and cornerstone content flags.

**rankmath/bulk-get-meta** - Retrieve SEO metadata for multiple posts with filtering options. Filter by post type, search by title, or find posts missing meta descriptions.

**rankmath/list-404-logs** - List recent Rank Math 404 log entries.

**rankmath/delete-404-logs** - Delete 404 log entries by ID.

**rankmath/clear-404-logs** - Clear all Rank Math 404 logs (requires confirm flag).

**rankmath/list-redirections** - List Rank Math redirections.

**rankmath/create-redirection** - Create Rank Math redirections with one or more sources.

**rankmath/delete-redirections** - Delete Rank Math redirections by ID.

= Use Cases =

* Audit SEO metadata across your content
* Find posts missing meta descriptions
* Bulk update SEO settings via automation
* Enable AI agents to optimize SEO metadata
* Inspect and clear 404 logs
* Manage Rank Math redirections

== Installation ==

1. Install and activate [MCP Expose Abilities](https://github.com/bjornfix/mcp-expose-abilities)
2. Install and activate [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/)
3. Upload `mcp-abilities-rankmath` to `/wp-content/plugins/`
4. Activate through the 'Plugins' menu
5. The abilities are now available via the MCP endpoint

== Changelog ==

= 1.0.5 =
* Safety: Require confirmation for clearing 404 logs

= 1.0.4 =
* Added Rank Math redirection and 404 log management abilities

= 1.0.3 =
* Simplify post access validation and reuse helper

= 1.0.2 =
* Security: Added per-post capability checks for meta access and bulk listings

= 1.0.1 =
* Fixed: Abilities now register correctly with 'site' category

= 1.0.0 =
* Initial release
* Added rankmath/get-meta ability
* Added rankmath/update-meta ability
* Added rankmath/bulk-get-meta ability
