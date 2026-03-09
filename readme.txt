=== MCP Abilities - Rank Math ===
Contributors: devenia
Tags: seo, rank math, mcp, api, automation
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.1.1
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

**rankmath/get-schema-status** - Return effective global publisher/schema settings including publisher type, logo, and social profiles.

**rankmath/list-modules** - List Rank Math modules with active and disabled status.

**rankmath/update-modules** - Enable or disable Rank Math modules by slug.

**rankmath/get-rewrite-status** - Inspect stored rewrite rules for llms.txt, sitemap_index.xml, or a custom regex.

**rankmath/get-llms-status** - Return llms.txt module state, settings, rewrite status, and a live preview.

**rankmath/preview-llms** - Fetch the live llms.txt output and return the first lines for inspection.

**rankmath/update-publisher-profile** - Safely update the global publisher/entity fields used by Rank Math schema and local SEO.

**rankmath/get-social-profiles** - Return the global social profile fields that feed Rank Math sameAs output.

**rankmath/update-social-profiles** - Update the global social profile fields that feed Rank Math sameAs output.

**rankmath/get-sitemap-status** - Return sitemap module state, enabled object types, rewrite status, and a live sitemap index check.

**rankmath/refresh-llms-route** - Verify the Rank Math llms.txt rewrite rule and flush rewrite rules when needed.

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

= 1.1.1 =
* Added: explicit email and telephone restoration for public Organization schema nodes when those fields are configured in the publisher profile
* Fixed: Rank Math contact details stored in MCP publisher settings now survive the final schema sanitization step

= 1.1.0 =
* Added: schema status, module management, rewrite inspection, llms status/preview, publisher profile, social profiles, and sitemap status abilities
* Improved: publisher/schema and llms debugging can now be done without raw option spelunking

= 1.0.11 =
* Added: llms.txt-only title override so Rank Math can output `Log In: Everywhere!` without changing global entity/schema settings

= 1.0.9 =
* Added: `rankmath/refresh-llms-route` to inspect and refresh the Rank Math `llms.txt` rewrite rule after module changes

= 1.0.8 =
* Fixed: `rankmath/list-options` now uses a Plugin Check-compliant prepared SQL query while still exposing both `rank_math_*` and `rank-math-*` option names

= 1.0.7 =
* Fixed: Rank Math global settings stored in `rank-math-*` options are now visible and writable through the MCP option abilities

= 1.0.6 =
* Fixed: Removed hard plugin header dependency on abilities-api to avoid slug-mismatch activation blocking


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
