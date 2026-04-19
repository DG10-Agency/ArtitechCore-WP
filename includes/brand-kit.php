<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Brand Kit Settings and Management
 *
 * Stores brand identity: colors, fonts, voice, style, logo, etc.
 * Used by AI Website Builder to generate brand-consistent pages.
 */

// Register Brand Kit settings
function artitechcore_register_brand_kit_settings() {
    register_setting(
        'artitechcore_brand_kit_group',
        'artitechcore_brand_kit',
        'artitechcore_sanitize_brand_kit'
    );
}
add_action('admin_init', 'artitechcore_register_brand_kit_settings');

/**
 * Sanitize brand kit data
 */
function artitechcore_sanitize_brand_kit($input) {
    if (!is_array($input)) {
        return [
            'brand_name' => '',
            'tagline' => '',
            'description' => '',
            'primary_color' => '#4A90E2',
            'secondary_color' => '#6C63FF',
            'accent_color' => '#FF6B6B',
            'background_style' => 'light',
            'heading_font' => 'Inter',
            'body_font' => 'Inter',
            'brand_voice' => 'professional',
            'design_aesthetic' => 'modern',
            'image_style' => 'photorealistic',
            'logo_attachment_id' => 0,
            'favicon_attachment_id' => 0,
        ];
    }

    $sanitized = [];

    $sanitized['brand_name'] = sanitize_text_field($input['brand_name'] ?? '');
    $sanitized['tagline'] = sanitize_text_field($input['tagline'] ?? '');
    $sanitized['description'] = sanitize_textarea_field($input['description'] ?? '');

    // Validate hex colors
    $primary = sanitize_hex_color($input['primary_color'] ?? '#4A90E2');
    $sanitized['primary_color'] = $primary ?: '#4A90E2';

    $secondary = sanitize_hex_color($input['secondary_color'] ?? '#6C63FF');
    $sanitized['secondary_color'] = $secondary ?: '#6C63FF';

    $accent = sanitize_hex_color($input['accent_color'] ?? '#FF6B6B');
    $sanitized['accent_color'] = $accent ?: '#FF6B6B';

    $sanitized['background_style'] = in_array($input['background_style'] ?? 'light', ['light', 'dark', 'custom']) ? $input['background_style'] : 'light';
    $sanitized['heading_font'] = sanitize_text_field($input['heading_font'] ?? 'Inter');
    $sanitized['body_font'] = sanitize_text_field($input['body_font'] ?? 'Inter');
    $sanitized['brand_voice'] = in_array($input['brand_voice'] ?? 'professional', ['professional', 'casual', 'innovative', 'trustworthy', 'friendly', 'luxury']) ? $input['brand_voice'] : 'professional';
    $sanitized['design_aesthetic'] = in_array($input['design_aesthetic'] ?? 'modern', ['minimal', 'bold', 'corporate', 'playful', 'luxury', 'modern']) ? $input['design_aesthetic'] : 'modern';
    $sanitized['image_style'] = in_array($input['image_style'] ?? 'photorealistic', ['photorealistic', 'illustrated', 'mixed']) ? $input['image_style'] : 'photorealistic';
    $sanitized['logo_attachment_id'] = absint($input['logo_attachment_id'] ?? 0);
    $sanitized['favicon_attachment_id'] = absint($input['favicon_attachment_id'] ?? 0);

    return $sanitized;
}

/**
 * Get brand kit with defaults
 */
function artitechcore_get_brand_kit() {
    $defaults = [
        'brand_name'            => '',
        'tagline'               => '',
        'description'           => '',
        'primary_color'         => '#4A90E2',
        'secondary_color'       => '#6C63FF',
        'accent_color'          => '#FF6B6B',
        'background_style'      => 'light',
        'heading_font'          => 'Inter',
        'body_font'             => 'Inter',
        'brand_voice'           => 'professional',
        'design_aesthetic'      => 'modern',
        'image_style'           => 'photorealistic',
        'logo_attachment_id'    => 0,
        'favicon_attachment_id' => 0,
    ];

    $saved  = get_option( 'artitechcore_brand_kit', [] );
    $brand  = wp_parse_args( $saved, $defaults );

    // Always ensure brand_name and description have a meaningful fallback
    // so the Website Builder doesn't block on a fresh install.
    if ( empty( $brand['brand_name'] ) ) {
        $brand['brand_name'] = get_bloginfo( 'name' );
    }
    if ( empty( $brand['description'] ) ) {
        $brand['description'] = get_bloginfo( 'description' );
    }
    if ( empty( $brand['tagline'] ) && ! empty( $brand['description'] ) && strlen( $brand['description'] ) < 160 ) {
        $brand['tagline'] = $brand['description'];
    }

    return $brand;
}

/**
 * Auto-detect brand kit from existing site data
 * Extends the existing business info detection
 * 
 * @param bool $force Determine if we should bypass the 'Init Shield' flag.
 */
function artitechcore_auto_detect_brand_kit($force = false) {
    // Init Shield: Prevent heavy rescans on every page load unless forced
    if (!$force && get_option('artitechcore_initial_scan_completed')) {
        return get_option('artitechcore_brand_kit', []);
    }
    $brand_kit = [
        'brand_name' => '',
        'tagline' => '',
        'description' => '',
        'primary_color' => '#4A90E2',
        'secondary_color' => '#6C63FF',
        'accent_color' => '#FF6B6B',
        'background_style' => 'light',
        'heading_font' => 'Inter',
        'body_font' => 'Inter',
        'brand_voice' => 'professional',
        'design_aesthetic' => 'modern',
        'image_style' => 'photorealistic',
        'logo_attachment_id' => 0,
        'favicon_attachment_id' => 0,
    ];

    // 1. Get existing business info (reuse detection)
    $business_info = artitechcore_auto_detect_business_info();

    if (!empty($business_info['name'])) {
        $brand_kit['brand_name'] = $business_info['name'];
    } else {
        $brand_kit['brand_name'] = get_bloginfo('name');
    }

    if (!empty($business_info['description'])) {
        $brand_kit['description'] = $business_info['description'];
    } else {
        $brand_kit['description'] = get_bloginfo('description');
    }

    // 2. Try to infer brand voice from business type
    $business_type = strtolower($business_info['name'] ?? '');
    if (strpos($business_type, 'law') !== false || strpos($business_type, 'legal') !== false) {
        $brand_kit['brand_voice'] = 'trustworthy';
        $brand_kit['design_aesthetic'] = 'corporate';
    } elseif (strpos($business_type, 'restaurant') !== false || strpos($business_type, 'cafe') !== false || strpos($business_type, 'food') !== false) {
        $brand_kit['brand_voice'] = 'friendly';
        $brand_kit['design_aesthetic'] = 'playful';
    } elseif (strpos($business_type, 'tech') !== false || strpos($business_type, 'software') !== false || strpos($business_type, 'digital') !== false) {
        $brand_kit['brand_voice'] = 'innovative';
        $brand_kit['design_aesthetic'] = 'minimal';
    } elseif (strpos($business_type, 'health') !== false || strpos($business_type, 'medical') !== false || strpos($business_type, 'dental') !== false) {
        $brand_kit['brand_voice'] = 'professional';
        $brand_kit['design_aesthetic'] = 'clean';
    }

    // 3. Use existing brand color if set, else try theme mods
    $existing_color = get_option('artitechcore_brand_color');
    if (!$existing_color || !sanitize_hex_color($existing_color)) {
        // Try common theme mods for colors
        $theme_primary = get_theme_mod('primary_color') ?: get_theme_mod('accent_color');
        if ($theme_primary && sanitize_hex_color($theme_primary)) {
            $brand_kit['primary_color'] = $theme_primary;
        } else {
            $brand_kit['primary_color'] = '#4A90E2'; // Default
        }
    } else {
        $brand_kit['primary_color'] = $existing_color;
    }

    // 4. Try to find logo in media library (heuristic: site icon or custom logo)
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $brand_kit['logo_attachment_id'] = $custom_logo_id;
    }

    // 5. Set tagline from site description if short
    if (strlen($brand_kit['description']) < 100) {
        $brand_kit['tagline'] = $brand_kit['description'];
    }

    // Mark initial scan as completed to trigger the Init Shield
    update_option('artitechcore_initial_scan_completed', true);

    return $brand_kit;
}

/**
 * AJAX handler for auto-detecting brand kit
 */
function artitechcore_ajax_auto_detect_brand_kit() {
    check_ajax_referer('artitechcore_ajax_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
        return;
    }

    $detected_brand = artitechcore_auto_detect_brand_kit(true); // Pass true to bypass shield

    // Save detected values
    update_option('artitechcore_brand_kit', $detected_brand);

    wp_send_json_success([
        'message' => 'Brand information detected and saved!',
        'data' => $detected_brand
    ]);
}
add_action('wp_ajax_artitechcore_auto_detect_brand_kit', 'artitechcore_ajax_auto_detect_brand_kit');

/**
 * Get brand colors as CSS variables
 */
function artitechcore_get_brand_css_variables() {
    $brand = artitechcore_get_brand_kit();

    $r = hexdec(substr($brand['primary_color'], 1, 2));
    $g = hexdec(substr($brand['primary_color'], 3, 2));
    $b = hexdec(substr($brand['primary_color'], 5, 2));

    $r2 = hexdec(substr($brand['secondary_color'], 1, 2));
    $g2 = hexdec(substr($brand['secondary_color'], 3, 2));
    $b2 = hexdec(substr($brand['secondary_color'], 5, 2));

    $r3 = hexdec(substr($brand['accent_color'], 1, 2));
    $g3 = hexdec(substr($brand['accent_color'], 3, 2));
    $b3 = hexdec(substr($brand['accent_color'], 5, 2));

    return ":root {
        --brand-primary: {$brand['primary_color']};
        --brand-primary-rgb: $r, $g, $b;
        --brand-secondary: {$brand['secondary_color']};
        --brand-secondary-rgb: $r2, $g2, $b2;
        --brand-accent: {$brand['accent_color']};
        --brand-accent-rgb: $r3, $g3, $b3;
        --heading-font: '{$brand['heading_font']}', sans-serif;
        --body-font: '{$brand['body_font']}', sans-serif;
        --bg-style: '{$brand['background_style']}';
    }";
}

/**
 * Get brand kit as prompt context for AI
 */
function artitechcore_brand_kit_to_prompt() {
    $brand = artitechcore_get_brand_kit();

    $voice_map = [
        'professional' => 'Professional, authoritative, polished',
        'casual' => 'Casual, friendly, conversational',
        'innovative' => 'Innovative, forward-thinking, cutting-edge',
        'trustworthy' => 'Trustworthy, reliable, reassuring',
        'friendly' => 'Friendly, warm, approachable',
        'luxury' => 'Luxury, exclusive, premium',
    ];

    $aesthetic_map = [
        'minimal' => 'Minimalist design with lots of white space, clean lines, simple color palette',
        'bold' => 'Bold, high-contrast design with strong visual impact',
        'corporate' => 'Corporate, traditional, conservative aesthetic',
        'playful' => 'Playful, colorful, fun, energetic design',
        'luxury' => 'Luxury, elegant, sophisticated with premium feel',
        'modern' => 'Modern, contemporary, clean aesthetic',
    ];

    $voice = $voice_map[$brand['brand_voice']] ?? 'Professional';
    $aesthetic = $aesthetic_map[$brand['design_aesthetic']] ?? 'Modern';

    return "BRAND IDENTITY:
Name: {$brand['brand_name']}
Tagline: {$brand['tagline']}
Description: {$brand['description']}
Voice/Tone: {$voice}
Design Style: {$aesthetic}
Colors: Primary {$brand['primary_color']}, Secondary {$brand['secondary_color']}, Accent {$brand['accent_color']}
Typography: Headings {$brand['heading_font']}, Body {$brand['body_font']}
Image Style: {$brand['image_style']}";
}
