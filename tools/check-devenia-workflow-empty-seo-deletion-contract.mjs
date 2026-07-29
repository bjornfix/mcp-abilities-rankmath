import assert from "node:assert/strict";
import fs from "node:fs";

const adapter = fs.readFileSync(new URL("../includes/devenia-workflow-adapter.php", import.meta.url), "utf8");
const start = adapter.indexOf("public static function sync_seo_meta");
const end = adapter.indexOf("public static function seo_meta_state", start);

assert.ok(start >= 0 && end > start, "Rank Math SEO sync owner seam must exist");
const syncSeoMeta = adapter.slice(start, end);

assert.match(
  syncSeoMeta,
  /array\( 'title' => 'rank_math_title', 'description' => 'rank_math_description', 'focus_keyword' => 'rank_math_focus_keyword' \)/,
  "deletion must remain bounded to the three controlled Rank Math SEO keys",
);
assert.match(
  syncSeoMeta,
  /in_array\( \$operation, array\( 'set', 'delete', 'preserve' \), true \)/,
  "the Adapter must require an explicit canonical operation for every controlled field",
);
assert.match(syncSeoMeta, /if \( 'preserve' === \$operation \)/, "preserve must remain distinct from deletion");
assert.match(
  syncSeoMeta,
  /if \( 'delete' === \$operation \)[\s\S]*metadata_exists\( 'post', \$post_id, \$meta_key \)[\s\S]*delete_post_meta\( \$post_id, \$meta_key \)[\s\S]*\$updated\[\] = \$meta_key/,
  "an approved empty value must delete an existing controlled key and report only a successful deletion",
);
assert.match(
  syncSeoMeta,
  /seo_sync_signature_from_values\([\s\S]*get_post_meta\( \$post_id, 'rank_math_focus_keyword', true \)/,
  "the signature must bind the actual final stored state after preserve or delete",
);
assert.doesNotMatch(syncSeoMeta, /delete_post_meta_by_key|delete_metadata\(/, "SEO sync must not perform broad metadata deletion");

console.log("Rank Math approved-empty SEO deletion contract passed.");
