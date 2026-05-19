# ArtitechCore WP Plugin — Remaining Errors & Fix Instructions

## Overview

**Total errors fixed automatically:** 1,055 / 1,220 (86.5%)
**Remaining:** 165 errors
**Plugin location:** `~/Desktop/projects/artitechcore/`

---

## How to Run the Check

```bash
cd ~/Desktop/wp-plugin-testing-lab/
docker compose exec -T wordpress wp plugin check artitechcore --allow-root 2>&1 | grep "ERROR"
```

Or using the Makefile:
```bash
cd ~/Desktop/wp-plugin-testing-lab/
make test plugin=artitechcore
```

---

## ⚠️ INSTRUCTIONS FOR WP EXPERTS

For each error below, follow these rules:

1. **Read the FULL file and surrounding code** before making any change
2. **Understand the data flow** — is the output HTML? Attribute? URL? Plain text?
3. **Use the RIGHT escape function:**
   - `esc_html()` — for plain text
   - `esc_attr()` — for HTML attributes
   - `esc_url()` — for URLs
   - `wp_kses_post()` — for HTML content (allows safe tags)
   - `esc_js()` — for JavaScript strings
4. **Do NOT break functionality** — test after every change
5. **Commit after each file** so changes can be rolled back
6. **For SQL: use `$wpdb->prepare()`** with `%d`, `%s` placeholders

---

## REMAINING ERROR CATEGORIES

### 1. OutputNotEscaped — 106 errors ⚠️ CRITICAL

These are mostly in:
- **`includes/menu-generator.php`** — heredoc strings with `{$var}` interpolation (lines 426-449, 1993-2103)
  - Fix: Replace `echo "<<<HTML"` with proper concatenation + `esc_html()`/`esc_attr()` per variable
  - Example: `echo "<h4>Category: {$category}</h4>"` → `echo '<h4>Category: ' . esc_html($category) . '</h4>';`
- **`includes/custom-post-type-manager.php`** — `$required_attr`, `$aria_describedby` variables echoed without escaping
  - These are HTML attribute strings being built — use `esc_attr()` on each variable
  - Example: `echo 'required="' . $required_attr . '"'` → `echo 'required="' . esc_attr($required_attr) . '"'`
- **`includes/content-enhancer.php`** — complex HTML templates
- **`includes/schema-generator.php`** — admin URL and action links

### 2. ExceptionNotEscaped — 33 errors

- In `includes/ai-generator.php`, `includes/website-generator.php`
- Patterns: `throw new Exception(sprintf(__('OpenAI API error: %s'...`
- Fix: Change message construction for exceptions or suppress for trusted messages
- Safe approach: `throw new Exception(esc_html(sprintf(__('...', 'artitechcore'), $var)));`

### 3. UnsafePrintingFunction — 6 errors

- Direct `echo` of variables without escaping
- In `includes/custom-post-type-manager.php`, `includes/menu-generator.php`
- Fix: Wrap with `esc_html()` or `esc_attr()` based on context

### 4. fclose/fopen — 8 errors

- In `includes/keyword-analyzer.php` (lines 1268, 1380, 1411)
- **Structural change needed:** Replace with `WP_Filesystem` methods:
  ```php
  global $wp_filesystem;
  require_once ABSPATH . 'wp-admin/includes/file.php';
  WP_Filesystem();
  $contents = $wp_filesystem->get_contents($file);
  ```

### 5. SQL/Database — 8 errors

- **UnescapedDBParameter (6):** In `artitechcore-for-wordpress.php` — `$keys_in` array used directly in SQL
  - Fix: Sanitize the array before use:
    ```php
    $keys_in = array_map('sanitize_key', $keys_in);
    $placeholders = array_fill(0, count($keys_in), '%s');
    $query = $wpdb->prepare("WHERE meta_key IN (" . implode(',', $placeholders) . ")", $keys_in);
    ```
- **LikeWildcardsInQuery (2):** In `uninstall.php` — LIKE patterns with `%%` not as parameter
- **NotPrepared (1):** In `uninstall.php` — direct variable interpolation in SQL

### 6. UnorderedPlaceholdersText — 1 error

- In `includes/website-generator.php` line 681 — `__('Estimated cost: $%s (Content: $%s, Images: $%s)')`
- **DO NOT CHANGE** — this is used with JS `.replace('%s', ...).replace('%s', ...).replace('%s', ...)` which relies on sequential `%s` matching. The translators comment explains this.

---

## COMMON FIX PATTERNS

### Pattern A: Simple echo escaping
```php
// BEFORE
echo $some_variable;
echo "<div>$some_var</div>";

// AFTER
echo esc_html($some_variable);
echo '<div>' . esc_html($some_var) . '</div>';
```

### Pattern B: HTML attribute escaping
```php
// BEFORE
<input value="<?php echo $value; ?>">

// AFTER  
<input value="<?php echo esc_attr($value); ?>">
```

### Pattern C: URL escaping
```php
// BEFORE
<a href="<?php echo get_permalink($id); ?>">
<a href="<?php echo admin_url('admin.php?page=x'); ?>">

// AFTER
<a href="<?php echo esc_url(get_permalink($id)); ?>">
<a href="<?php echo esc_url(admin_url('admin.php?page=x')); ?>">
```

### Pattern D: Nonce in attributes
```php
// BEFORE
<a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=x'), 'nonce_name'); ?>">

// AFTER
<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=x'), 'nonce_name')); ?>">
```

### Pattern E: Exception messages
```php
// BEFORE
throw new Exception(sprintf(__('Error: %s', 'artitechcore'), $msg));

// AFTER
throw new Exception(esc_html(sprintf(__('Error: %s', 'artitechcore'), $msg)));
```

---

## VERIFICATION

After fixing, always run:
```bash
make test plugin=artitechcore
```

Check both:
1. **Plugin activation** — no PHP fatal errors
2. **Plugin Check errors** — should decrease

---

## TIPS

- The WP Plugin Check uses **PHP_CodeSniffer with WordPress rules**
- Some warnings (like `NonceVerification`) are acceptable on public-facing pages
- The **Objective score** should be ≥ 90
- **Zero errors =** ready for WP.org submission

---

*Generated: May 2026*
