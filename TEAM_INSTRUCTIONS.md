# 🎯 WP PLUGIN SUBMISSION — FIX REMAINING 165 ERRORS

**Plugin:** ArtitechCore  
**Repo:** `~/Desktop/projects/artitechcore/`  
**Errors to Fix:** 165 remaining (from original 1220)  
**Target:** Zero errors, score ≥ 90

---

## 🛑 BEFORE YOU START — READ THIS

**You must read the FULL surrounding code before touching ANY line.**  
These errors exist because each case is context-dependent. Guessing a fix will break the plugin.

**Your process for EVERY error:**
1. Open the file at that line  
2. Read 20 lines above and 10 lines below  
3. Understand: Is this HTML? Attribute? URL? JavaScript? SQL?  
4. Pick the RIGHT escape function  
5. Apply fix  
6. Verify with `php -l` and `wp plugin check`  
7. Commit

---

## 📋 ERROR BREAKDOWN BY FILE

### File 1: `includes/menu-generator.php` — 🔴 56 errors (OutputNotEscaped)

**Pattern:** Heredoc strings with `{$var}` interpolation  
**Lines:** 426-449, 1993-2103 (nearly identical patterns repeated)

```php
// ❌ BROKEN
echo "<h5>Category: {$category} (";
echo "<li><strong>{$service->post_title}</strong> - <a href='{$info['url']}'>";

// ✅ FIX
echo '<h5>Category: ' . esc_html($category) . ' (';
echo '<li><strong>' . esc_html($service->post_title) . '</strong> - <a href="' . esc_url($info['url']) . '">';
```

**Rule for each variable in heredocs:**
- If it's displayed text → `esc_html($var)`  
- If it's a URL → `esc_url($var)`  
- If it's inside HTML attribute → `esc_attr($var)`  
- If it's a function return like `get_permalink()` → `esc_url(get_permalink())`

---

### File 2: `includes/custom-post-type-manager.php` — 🔴 38 errors (mixed)

#### Group A: `$required_attr` and `$aria_describedby` (~20 occurrences, lines 1539-1632)
```php
// ❌ BROKEN  
echo '<input ' . $required_attr . ' ' . $aria_describedby . '>';

// ✅ FIX — these are HTML attributes being concatenated
$escaped_required = $required_attr ? 'required' : '';
$escaped_describedby = $aria_describedby ? 'aria-describedby="' . esc_attr($field_id) . '"' : '';
echo '<input ' . $escaped_required . ' ' . $escaped_describedby . '>';
```

**Context:** These are dynamic HTML attributes for form fields.  
**Decision:** `$required_attr` is either empty string or `'required'` (safe keyword). `$aria_describedby` needs `esc_attr()` on the field ID value.

#### Group B: Admin notices with `$class`, `$message_parts`, `$errors` (~10, lines 3058-3092)
```php
// ❌ BROKEN
echo '<div class="notice ' . $class . '"><p>' . implode(' ', $message_parts) . '</p></div>';
if (!empty($errors)) echo '<p>' . implode('<br>', $errors) . '</p>';

// ✅ FIX
$notice_class = esc_attr($class);
$message_text = esc_html(implode(' ', $message_parts));
echo '<div class="notice ' . $notice_class . '"><p>' . $message_text . '</p></div>';

if (!empty($errors)) {
    $error_text = implode('<br>', array_map('esc_html', $errors));
    echo '<p>' . wp_kses_post($error_text) . '</p>';
}
```

#### Group C: Simple echo `__('text')` (~8, lines 247-2996)
```php
// ❌ BROKEN
echo __('Some text', 'artitechcore');

// ✅ FIX  
echo esc_html__('Some text', 'artitechcore');
```

#### Group D: `wp_create_nonce()` in URLs (~4, lines 3362+)  
Already partially fixed — check if any remain.

---

### File 3: `includes/ai-generator.php` — 🟡 26 errors (ExceptionNotEscaped + OutputNotEscaped)

**Pattern:** `throw new Exception(sprintf(__('...', 'artitechcore'), $var))`  
**Lines:** 594, 762, 936, 1287, 1382 + admin notices at 297, 304, 1737, 1744

```php
// ❌ BROKEN
throw new Exception(sprintf(__('OpenAI API error: %s', 'artitechcore'), $error_message));

// ✅ FIX — wrap message in esc_html
throw new Exception(esc_html(sprintf(__('OpenAI API error: %s', 'artitechcore'), $error_message)));
```

**For admin notices:**
```php
// ❌ BROKEN
echo '<div class="notice notice-error"><p>' . sprintf(__('Please enter your %s API key...', 'artitechcore'), $provider) . '</p></div>';

// ✅ FIX
echo '<div class="notice notice-error"><p>' . esc_html(sprintf(__('Please enter your %s API key...', 'artitechcore'), $provider)) . '</p></div>';
```

**Special case — lines 1009-1077:** These build `$errors[]` arrays. The errors array is output elsewhere, so each entry needs escaping at OUTPUT time, not definition time. Check where `$errors` is echoed — it might already be handled.

---

### File 4: `includes/website-generator.php` — 🟡 18 errors (ExceptionNotEscaped + OutputNotEscaped)

**Same pattern as File 3** — lines 1281, 1291, 1304, 1410, 1420, 1433:
```php
throw new Exception(esc_html(sprintf(__('...', 'artitechcore'), $var)));
```

**Also:** JS-embedded `esc_js(__('text with %s', 'artitechcore'))` — these are already correct. The `esc_js()` provides appropriate escaping for JavaScript context.

---

### File 5: `includes/schema-generator.php` — 🟡 ~12 errors (OutputNotEscaped + date())

**Pattern:** `admin_url()` and `paginate_links()` in admin screens  
**Lines:** ~2276+ (action links)

```php
// ❌ BROKEN
$actions['remove_schema'] = '<a href="' . wp_nonce_url(admin_url('admin.php?page=x...'), 'nonce') . '">Remove Schema</a>';

// ✅ FIX
$actions['remove_schema'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=x...'), 'nonce')) . '">Remove Schema</a>';
```

---

### File 6: `includes/content-enhancer.php` — 🟡 ~6 errors

Check for remaining `paginate_links()` without wrapping. Most already fixed.

---

### File 7: `includes/keyword-analyzer.php` — 🔴 STRUCTURAL FIX

**`fclose` / `fopen` / `fwrite` at lines 1366-1411**

```php
// ❌ BROKEN — direct PHP file I/O
$handle = fopen($file, 'w');
fwrite($handle, $content);
fclose($handle);

// ✅ FIX — use WP_Filesystem
global $wp_filesystem;
require_once ABSPATH . 'wp-admin/includes/file.php';
WP_Filesystem();
$wp_filesystem->put_contents($file, $content);
```

**Complete refactor needed.** The function reads/writes CSV analysis files. Convert completely to `$wp_filesystem`.

---

### File 8: `artitechcore-for-wordpress.php` — 🔴 SQL SECURITY (lines 357-452)

**Pattern:** `$keys_in` array used directly in SQL `IN ($keys_in)` clause  
~6 occurrences of `UnescapedDBParameter`

```php
// ❌ BROKEN — SQL injection risk
$keys_in = get_option('artitechcore_keys', array());
$results = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ($keys_in)");

// ✅ FIX — sanitize array + use placeholders
$keys_in = get_option('artitechcore_keys', array());
if (!empty($keys_in)) {
    $keys_in = array_map('sanitize_key', $keys_in);
    $placeholders = array_fill(0, count($keys_in), '%s');
    $query = $wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key IN (" . implode(',', $placeholders) . ")",
        $keys_in
    );
    $results = $wpdb->get_col($query);
}
```

**Apply this pattern to ALL 6 `$keys_in` usages** (get_col, get_results, query at lines 360, 368, 399, 413, 420, 449).

---

### File 9: `uninstall.php` — 🔴 LIKE WILDCARDS + SQL (lines 136, 139)

```php
// ❌ BROKEN
$query = "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'artitechcore_%%' AND meta_key NOT IN ($placeholders)";
$wpdb->query($query);

// ✅ FIX
$search_pattern = $wpdb->esc_like('artitechcore_') . '%';
$query = $wpdb->prepare(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_key NOT IN ($placeholders)",
    $search_pattern
);
// Also need to fix $placeholders — it should be built with %s placeholders
```

---

## 🚫 DO NOT TOUCH

| File | Line | Reason |
|------|------|--------|
| `website-generator.php` | 681 | `__('Estimated cost: $%s...')` — keeps `%s` unordered for JS `.replace()` chaining |
| `settings-page.php` | 63 | `wp_get_sidebars_widgets()` — legitimate WP function, false positive |
| Any `// phpcs:ignore` line | — | Already handled |
| `.distignore` | — | Already configured |

---

## ✅ VERIFICATION PROTOCOL

**After fixing ONE file:**
```bash
# 1. Check PHP syntax
docker compose exec -T wordpress php -l /var/www/html/wp-content/plugins/artitechcore/path/to/file.php

# 2. Activate plugin
docker compose exec -T wordpress wp plugin activate artitechcore --allow-root

# 3. Run Plugin Check
docker compose exec -T wordpress wp plugin check artitechcore --allow-root 2>&1 | grep "ERROR" | wc -l

# 4. Commit
cd ~/Desktop/projects/artitechcore
git add -A && git commit -m "fix: escaped output in menu-generator.php"
```

**Final validation (all files fixed):**
```bash
make test plugin=artitechcore    # Should show 0 errors
make audit plugin=artitechcore   # Generate final HTML report
```

---

## 🎯 DEADLINE & DELIVERABLE

**Final deliverable:**
1. ✅ Zero ERRORs in `wp plugin check`
2. ✅ Zero WARNINGs in readme/header checks
3. ✅ HTML audit report in `reports/artitechcore-final.html`
4. ✅ All commits pushed to Git
5. ✅ Plugin ZIP ready for WP.org submission

---

*Crafted by Hermes Agent — DG10 Agency*
