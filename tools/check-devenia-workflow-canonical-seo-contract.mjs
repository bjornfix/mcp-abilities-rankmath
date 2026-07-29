#!/usr/bin/env node

import { readFileSync } from "node:fs";

const adapter = readFileSync(new URL("../includes/devenia-workflow-adapter.php", import.meta.url), "utf8");
const rankMathPlugin = readFileSync(new URL("../mcp-abilities-rankmath.php", import.meta.url), "utf8");

const checks = [
  [adapter.includes("final class MCP_RankMath_Devenia_Workflow_Adapter"), "The optional Workflow integration must be owned by the Rank Math plugin."],
  [adapter.includes("! self::is_active() || ! class_exists( 'Devenia_Workflow' )"), "The optional Adapter must remain inert unless both plugins are active."],
  [adapter.includes("devenia_workflow_translation_canonical_seo_surface"), "The Adapter must supply stored Rank Math values through Workflow's generic SEO seam."],
  [adapter.includes("devenia_workflow_translation_sync_seo_meta"), "The Adapter must consume Workflow's generic SEO mutation seam."],
  [adapter.includes("in_array( $operation, array( 'set', 'delete', 'preserve' ), true )"), "The Rank Math Adapter must consume explicit field operations."],
  [adapter.includes("if ( 'preserve' === $operation )") && adapter.includes("if ( 'delete' === $operation )"), "The Adapter must distinguish preservation from controlled deletion."],
  [adapter.includes("(string) get_post_meta( $post_id, 'rank_math_focus_keyword', true )"), "The sync signature must use actual final stored state after operations."],
  [!adapter.includes("array_key_exists( $field, $fields )"), "Rank Math field presence must not pretend to carry semantics erased upstream."],
  [rankMathPlugin.includes("ai_translation_workflow_gutenberg_guardrails") && rankMathPlugin.includes("ai_translation_workflow_semantic_link_count_content"), "Moving the Devenia Adapter must not remove the existing optional translation-workflow compatibility hooks."],
  [!rankMathPlugin.includes("../devenia-workflow"), "The public Rank Math plugin must not require a sibling Workflow source tree."],
];

for (const [passed, message] of checks) {
  if (!passed) {
    throw new Error(message);
  }
}

console.log("Rank Math Workflow Adapter contract passed.");
