<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Schema types constants
if (!defined('ArtitechCore_SCHEMA_FAQ')) define('ArtitechCore_SCHEMA_FAQ', 'faq');
if (!defined('ArtitechCore_SCHEMA_BLOG')) define('ArtitechCore_SCHEMA_BLOG', 'blog');
if (!defined('ArtitechCore_SCHEMA_ARTICLE')) define('ArtitechCore_SCHEMA_ARTICLE', 'article');
if (!defined('ArtitechCore_SCHEMA_SERVICE')) define('ArtitechCore_SCHEMA_SERVICE', 'service');
if (!defined('ArtitechCore_SCHEMA_PRODUCT')) define('ArtitechCore_SCHEMA_PRODUCT', 'product');
if (!defined('ArtitechCore_SCHEMA_ORGANIZATION')) define('ArtitechCore_SCHEMA_ORGANIZATION', 'organization');
if (!defined('ArtitechCore_SCHEMA_LOCAL_BUSINESS')) define('ArtitechCore_SCHEMA_LOCAL_BUSINESS', 'local_business');
if (!defined('ArtitechCore_SCHEMA_WEBPAGE')) define('ArtitechCore_SCHEMA_WEBPAGE', 'webpage');
if (!defined('ArtitechCore_SCHEMA_HOWTO')) define('ArtitechCore_SCHEMA_HOWTO', 'howto');
if (!defined('ArtitechCore_SCHEMA_REVIEW')) define('ArtitechCore_SCHEMA_REVIEW', 'review');
if (!defined('ArtitechCore_SCHEMA_EVENT')) define('ArtitechCore_SCHEMA_EVENT', 'event');

/**
 * Get schema data from custom table
 * @param int $object_id The post or term ID.
 * @param string $object_type The object type ('post' or 'term').
 * @return array|false The row data as an associative array or false if not found.
 */
function artitechcore_get_schema_data($object_id, $object_type = 'post') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'artitechcore_schema_data';
    
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE object_id = %d AND object_type = %s",
        $object_id,
        $object_type
    ), ARRAY_A);
    
    return $row ? $row : false;
}

/**
 * Save schema data to custom table
 */
function artitechcore_save_schema_data($object_id, $data, $schema_type, $object_type = 'post', $origin = 'generated', $is_locked = 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'artitechcore_schema_data';
    
    // Ensure data is properly encoded if it's an array
    if (is_array($data)) {
        $data = wp_json_encode($data);
    }

    return $wpdb->replace($table_name, [
        'object_id' => $object_id,
        'object_type' => $object_type,
        'schema_type' => $schema_type,
        'schema_data' => $data,
        'origin' => $origin,
        'is_locked' => $is_locked
    ]);
}

/**
 * Delete schema data from custom table
 */
function artitechcore_delete_schema_data($object_id, $object_type = 'post') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'artitechcore_schema_data';
    
    return $wpdb->delete($table_name, [
        'object_id' => $object_id,
        'object_type' => $object_type
    ]);
}

/**
 * AI-enriched global entity profile (cached).
 *
 * Purpose:
 * - Increase schema accuracy across ALL industries by inferring the best schema subtypes
 *   (e.g. MedicalBusiness / Dentist / LegalService / Restaurant) and key entities
 *   (e.g. a primary professional Person like Physician/Attorney/etc.).
 * - Avoid hard-coding niche logic while still producing entity-specific schema.
 *
 * Notes:
 * - We do NOT hallucinate address/phone/email; we only infer types + specialties + relationships.
 * - Cached via transient to control AI spend.
 *
 * @return array|false
 */
if (!function_exists('artitechcore_get_ai_entity_profile')) {
function artitechcore_get_ai_entity_profile() {
    $enabled = get_option('artitechcore_ai_schema_enrichment', 1);
    if (empty($enabled)) {
        return false;
    }

    $provider = get_option('artitechcore_ai_provider', 'openai');
    $api_key = get_option('artitechcore_' . $provider . '_api_key');
    if (empty($api_key)) {
        return false;
    }

    $cache_key = 'artitechcore_ai_entity_profile_' . md5(home_url() . '|' . get_option('artitechcore_business_description', '') . '|' . get_bloginfo('name'));
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return is_array($cached) ? $cached : false;
    }

    $business_name = get_option('artitechcore_business_name', get_bloginfo('name'));
    $business_description = get_option('artitechcore_business_description', get_bloginfo('description'));
    $business_address = get_option('artitechcore_business_address', '');
    $business_phone = get_option('artitechcore_business_phone', '');
    $business_email = get_option('artitechcore_business_email', '');

    // Light site sample to help inference across themes/setups.
    $sample_posts = get_posts([
        'post_type' => ['page', 'post'],
        'post_status' => 'publish',
        'numberposts' => 8,
        'orderby' => 'modified',
        'order' => 'DESC',
    ]);

    $samples = [];
    foreach ($sample_posts as $p) {
        $samples[] = 'TITLE: ' . $p->post_title . "\n" . 'CONTENT: ' . wp_strip_all_tags(wp_trim_words($p->post_content, 120, '...'));
        if (strlen(implode("\n\n---\n\n", $samples)) > 6500) {
            break;
        }
    }

    $prompt =
        "You are a schema.org structured data expert.\n\n"
        . "Task: infer the most accurate entity types + relationships for THIS website (any industry).\n"
        . "Return ONLY valid JSON. No markdown.\n\n"
        . "Website URL: " . home_url() . "\n"
        . "Business Name: " . $business_name . "\n"
        . "Business Description: " . $business_description . "\n"
        . "Known Address (may be blank): " . $business_address . "\n"
        . "Known Phone (may be blank): " . $business_phone . "\n"
        . "Known Email (may be blank): " . $business_email . "\n\n"
        . "Recent Content Samples:\n" . implode("\n\n---\n\n", $samples) . "\n\n"
        . "Return JSON with this exact shape:\n"
        . "{\n"
        . "  \"organization\": {\n"
        . "    \"types\": [\"Organization\"],\n"
        . "    \"specialties\": []\n"
        . "  },\n"
        . "  \"primaryPerson\": {\n"
        . "    \"name\": null,\n"
        . "    \"types\": [\"Person\"],\n"
        . "    \"jobTitle\": null,\n"
        . "    \"specialties\": []\n"
        . "  },\n"
        . "  \"relationship\": {\n"
        . "    \"personToOrganization\": \"none\"\n"
        . "  }\n"
        . "}\n\n"
        . "Rules:\n"
        . "- organization.types MUST include \"Organization\".\n"
        . "- Add more specific Organization/LocalBusiness subtypes when clearly applicable (examples: MedicalBusiness, Dentist, LegalService, Restaurant, Store).\n"
        . "- primaryPerson.types MUST include \"Person\". Add more specific types only when clearly supported by content (examples: Physician, Dentist, Attorney).\n"
        . "- relationship.personToOrganization must be one of: none, founder, employee, member, worksFor, owner.\n"
        . "- specialties should be short strings.\n"
        . "- Do NOT invent facts like address/phone/email.\n";

    $result = null;
    switch ($provider) {
        case 'openai':
            $result = artitechcore_ai_call_openai_json($prompt, $api_key);
            break;
        case 'gemini':
            $result = artitechcore_ai_call_gemini_json($prompt, $api_key);
            break;
        case 'deepseek':
            $result = artitechcore_ai_call_deepseek_json($prompt, $api_key);
            break;
    }

    if (!is_array($result) || empty($result['organization']['types'])) {
        set_transient($cache_key, 0, 24 * HOUR_IN_SECONDS);
        return false;
    }

    // Normalize / validate.
    $org_types = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array)($result['organization']['types'] ?? [])))));
    if (!in_array('Organization', $org_types, true)) {
        array_unshift($org_types, 'Organization');
    }
    $result['organization']['types'] = $org_types;
    $result['organization']['specialties'] = array_values(array_filter(array_map('sanitize_text_field', (array)($result['organization']['specialties'] ?? []))));

    $person_types = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array)($result['primaryPerson']['types'] ?? ['Person'])))));
    if (!in_array('Person', $person_types, true)) {
        array_unshift($person_types, 'Person');
    }
    $result['primaryPerson']['types'] = $person_types;
    $result['primaryPerson']['name'] = !empty($result['primaryPerson']['name']) ? sanitize_text_field($result['primaryPerson']['name']) : null;
    $result['primaryPerson']['jobTitle'] = !empty($result['primaryPerson']['jobTitle']) ? sanitize_text_field($result['primaryPerson']['jobTitle']) : null;
    $result['primaryPerson']['specialties'] = array_values(array_filter(array_map('sanitize_text_field', (array)($result['primaryPerson']['specialties'] ?? []))));

    $allowed_rel = ['none', 'founder', 'employee', 'member', 'worksFor', 'owner'];
    $rel = sanitize_text_field($result['relationship']['personToOrganization'] ?? 'none');
    $result['relationship']['personToOrganization'] = in_array($rel, $allowed_rel, true) ? $rel : 'none';

    set_transient($cache_key, $result, 7 * DAY_IN_SECONDS);
    return $result;
}
}

if (!function_exists('artitechcore_schema_types_to_at_type')) {
function artitechcore_schema_types_to_at_type($types, $fallback) {
    $types = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array)$types))));
    if (empty($types)) {
        return $fallback;
    }
    return count($types) > 1 ? $types : $types[0];
}
}

if (!function_exists('artitechcore_build_primary_person_node')) {
function artitechcore_build_primary_person_node($profile) {
    if (!is_array($profile) || empty($profile['primaryPerson']['name'])) {
        return null;
    }
    $site_url = home_url();
    $types = (array)($profile['primaryPerson']['types'] ?? ['Person']);
    $name = sanitize_text_field($profile['primaryPerson']['name']);
    if ($name === '') {
        return null;
    }

    $node = [
        '@type' => artitechcore_schema_types_to_at_type($types, 'Person'),
        '@id' => $site_url . '/#primaryPerson',
        'name' => $name,
    ];

    if (!empty($profile['primaryPerson']['jobTitle'])) {
        $node['jobTitle'] = sanitize_text_field($profile['primaryPerson']['jobTitle']);
    }

    $specialties = (array)($profile['primaryPerson']['specialties'] ?? []);
    if (!empty($specialties)) {
        $types_str = strtolower(is_array($node['@type']) ? implode(' ', $node['@type']) : $node['@type']);
        if (strpos($types_str, 'physician') !== false || strpos($types_str, 'medical') !== false || strpos($types_str, 'dentist') !== false) {
            $node['medicalSpecialty'] = array_values($specialties);
        } else {
            $node['knowsAbout'] = array_values($specialties);
        }
    }

    return $node;
}
}

if (!function_exists('artitechcore_get_schema_source')) {
function artitechcore_get_schema_source($post_id) {
    $schema = artitechcore_get_schema_data($post_id, 'post');
    if ($schema) {
        return $schema['origin'];
    }
    return 'unknown';
}
}

/**
 * AI-powered schema data extraction
 * This function asks the AI to extract specific schema properties from the page content.
 * @param int $post_id The post ID.
 * @param string $schema_type The detected schema type.
 * @return array|false The extracted schema properties or false on failure.
 */
function artitechcore_ai_extract_schema_data($post_id, $schema_type) {
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }

    $provider = get_option('artitechcore_ai_provider', 'openai');
    $api_key = get_option('artitechcore_' . $provider . '_api_key');

    if (empty($api_key)) {
        return false;
    }

    $content = wp_strip_all_tags($post->post_content);
    $title = $post->post_title;
    $business_knowledge = get_option('artitechcore_business_description', '');
    
    // Limit content for API efficiency
    if (strlen($content) > 3000) {
        $content = substr($content, 0, 3000) . '...';
    }

    // Define expected properties per schema type
    // Define expected properties per schema type
    $property_prompts = [
        'faq' => 'Extract questions and answers as a JSON array: [{"question": "...", "answer": "..."}, ...]',
        'service' => 'Extract: {"serviceType": "...", "areaServed": "...", "features": ["..."], "offers": {"price": "...", "priceCurrency": "...", "description": "..."}, "audience": "...", "provider": "..."}',
        'product' => 'Extract: {"brand": "...", "sku": "...", "price": "...", "priceCurrency": "...", "availability": "InStock/OutOfStock", "features": ["..."], "color": "...", "material": "...", "condition": "NewCondition"}',
        'review' => 'Extract: {"ratingValue": 1-5, "itemReviewed": "...", "reviewSummary": "...", "author": "...", "pros": ["..."], "cons": ["..."]}',
        'howto' => 'Extract: {"steps": [{"name": "...", "text": "...", "image": "..."}], "totalTime": "PT...M", "estimatedCost": "...", "supply": ["..."], "tool": ["..."]}',
        'event' => 'Extract: {"startDate": "YYYY-MM-DD", "endDate": "YYYY-MM-DD", "location": "...", "organizer": "...", "ticketUrl": "...", "performer": "...", "eventStatus": "EventScheduled"}',
        'article' => 'Extract: {"wordCount": ..., "keywords": ["..."], "articleSection": "...", "alternativeHeadline": "...", "proficiencyLevel": "..."}',
        'blog' => 'Extract: {"keywords": ["..."], "articleSection": "...", "audience": "..."}',
    ];

    if (!isset($property_prompts[$schema_type])) {
        return false; // No AI extraction for this type
    }

    $post_type = $post->post_type;
    $prompt = "You are a schema.org expert. Analyze the following content and extract specific data for a '$schema_type' schema.

Business Context: $business_knowledge

Post Type: $post_type
Page Title: $title
Page Content: $content

" . $property_prompts[$schema_type] . "

IMPORTANT: Return ONLY a valid JSON object. Do not include any explanations, markdown formatting, or extra text. If a value is not found, use null.";

    $result = null;
    switch ($provider) {
        case 'openai':
            $result = artitechcore_ai_call_openai_json($prompt, $api_key);
            break;
        case 'gemini':
            $result = artitechcore_ai_call_gemini_json($prompt, $api_key);
            break;
        case 'deepseek':
            $result = artitechcore_ai_call_deepseek_json($prompt, $api_key);
            break;
    }

    return $result;
}

// OpenAI JSON extraction helper
function artitechcore_ai_call_openai_json($prompt, $api_key) {
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.2,
            'max_tokens' => 500,
        ]),
        'timeout' => 30,
    ]);
    if (is_wp_error($response)) return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['choices'][0]['message']['content'])) {
        $json_str = trim($body['choices'][0]['message']['content']);
        $data = json_decode($json_str, true);
        return is_array($data) ? $data : false;
    }
    return false;
}

// Gemini JSON extraction helper
function artitechcore_ai_call_gemini_json($prompt, $api_key) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode(['contents' => [['parts' => [['text' => $prompt]]]], 'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 500]]),
        'timeout' => 30,
    ]);
    if (is_wp_error($response)) return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
        $json_str = trim($body['candidates'][0]['content']['parts'][0]['text']);
        // Strip potential markdown fences
        $json_str = preg_replace('/^```json\s*|\s*```$/i', '', $json_str);
        $data = json_decode($json_str, true);
        return is_array($data) ? $data : false;
    }
    return false;
}

// DeepSeek JSON extraction helper
function artitechcore_ai_call_deepseek_json($prompt, $api_key) {
    $response = wp_remote_post('https://api.deepseek.com/v1/chat/completions', [
        'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key],
        'body' => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.2,
            'max_tokens' => 500,
        ]),
        'timeout' => 30,
    ]);
    if (is_wp_error($response)) return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['choices'][0]['message']['content'])) {
        $json_str = trim($body['choices'][0]['message']['content']);
        $data = json_decode($json_str, true);
        return is_array($data) ? $data : false;
    }
    return false;
}


// AI-powered content analysis for schema detection
function artitechcore_ai_analyze_content_for_schema($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }

    $provider = get_option('artitechcore_ai_provider', 'openai');
    $api_key = get_option('artitechcore_' . $provider . '_api_key');

    if (empty($api_key)) {
        return false;
    }

    $content = wp_strip_all_tags($post->post_content);
    $title = $post->post_title;
    $excerpt = $post->post_excerpt;
    
    // Limit content length for API efficiency
    if (strlen($content) > 2000) {
        $content = substr($content, 0, 2000) . '...';
    }

    $valid_schema_types = [
        'faq', 'blog', 'article', 'service', 'product', 
        'organization', 'local_business', 'howto', 'review', 'event', 'webpage'
    ];

    $post_type = $post->post_type;
    switch ($provider) {
        case 'openai':
            return artitechcore_ai_analyze_content_openai($title, $content, $excerpt, $post_type, $api_key, $valid_schema_types);
        case 'gemini':
            return artitechcore_ai_analyze_content_gemini($title, $content, $excerpt, $post_type, $api_key, $valid_schema_types);
        case 'deepseek':
            return artitechcore_ai_analyze_content_deepseek($title, $content, $excerpt, $post_type, $api_key, $valid_schema_types);
        default:
            return false;
    }
}

// OpenAI content analysis for schema
function artitechcore_ai_analyze_content_openai($title, $content, $excerpt, $post_type, $api_key, $valid_schema_types) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $post_type_hint = '';
    $pt_lower = strtolower((string)$post_type);
    if ($pt_lower === 'post') {
        $post_type_hint = "Hint: This is a blog post. Prefer blog or article when appropriate.";
    } elseif (strpos($pt_lower, 'product') !== false) {
        $post_type_hint = "Hint: This looks like a product post type. Prefer product when appropriate.";
    } elseif (strpos($pt_lower, 'service') !== false) {
        $post_type_hint = "Hint: This looks like a service post type. Prefer service when appropriate.";
    } elseif (strpos($pt_lower, 'event') !== false) {
        $post_type_hint = "Hint: This looks like an event post type. Prefer event when appropriate.";
    }

    $prompt = "Analyze the following webpage content and determine the most appropriate Schema.org type for SEO optimization.

Post Type: {$post_type}
{$post_type_hint}
Title: {$title}
Content: {$content}
Excerpt: {$excerpt}

Valid schema types: " . implode(', ', $valid_schema_types) . "

Consider the content structure, purpose, and user intent. Return ONLY the most appropriate schema type from the valid list above. Examples:
- FAQ pages with questions/answers: faq
- Step-by-step tutorials: howto  
- Product/service reviews: review
- Events, webinars, conferences: event
- Blog posts: blog
- Articles/guides: article
- Service pages: service
- Product pages: product
- Company info: organization
- Local business info: local_business
- General pages: webpage

Return only the schema type name, nothing else.";

    $body = json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.3,
        'max_tokens' => 50,
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $body,
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['choices'][0]['message']['content'])) {
        $schema_type = trim(strtolower($response_body['choices'][0]['message']['content']));
        if (in_array($schema_type, $valid_schema_types)) {
            return $schema_type;
        }
    }

    return false;
}

// Gemini content analysis for schema
function artitechcore_ai_analyze_content_gemini($title, $content, $excerpt, $post_type, $api_key, $valid_schema_types) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    
    $post_type_hint = '';
    $pt_lower = strtolower((string)$post_type);
    if ($pt_lower === 'post') {
        $post_type_hint = "Hint: This is a blog post. Prefer blog or article when appropriate.";
    } elseif (strpos($pt_lower, 'product') !== false) {
        $post_type_hint = "Hint: This looks like a product post type. Prefer product when appropriate.";
    } elseif (strpos($pt_lower, 'service') !== false) {
        $post_type_hint = "Hint: This looks like a service post type. Prefer service when appropriate.";
    } elseif (strpos($pt_lower, 'event') !== false) {
        $post_type_hint = "Hint: This looks like an event post type. Prefer event when appropriate.";
    }

    $prompt = "Analyze the following webpage content and determine the most appropriate Schema.org type for SEO optimization.

Post Type: {$post_type}
{$post_type_hint}
Title: {$title}
Content: {$content}
Excerpt: {$excerpt}

Valid schema types: " . implode(', ', $valid_schema_types) . "

Consider the content structure, purpose, and user intent. Return ONLY the most appropriate schema type from the valid list above. Examples:
- FAQ pages with questions/answers: faq
- Step-by-step tutorials: howto  
- Product/service reviews: review
- Events, webinars, conferences: event
- Blog posts: blog
- Articles/guides: article
- Service pages: service
- Product pages: product
- Company info: organization
- Local business info: local_business
- General pages: webpage

Return only the schema type name, nothing else.";

    $body = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 50,
        ]
    ]);

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
        $schema_type = trim(strtolower($response_body['candidates'][0]['content']['parts'][0]['text']));
        if (in_array($schema_type, $valid_schema_types)) {
            return $schema_type;
        }
    }

    return false;
}

// DeepSeek content analysis for schema
function artitechcore_ai_analyze_content_deepseek($title, $content, $excerpt, $post_type, $api_key, $valid_schema_types) {
    $url = 'https://api.deepseek.com/v1/chat/completions';
    
    $post_type_hint = '';
    $pt_lower = strtolower((string)$post_type);
    if ($pt_lower === 'post') {
        $post_type_hint = "Hint: This is a blog post. Prefer blog or article when appropriate.";
    } elseif (strpos($pt_lower, 'product') !== false) {
        $post_type_hint = "Hint: This looks like a product post type. Prefer product when appropriate.";
    } elseif (strpos($pt_lower, 'service') !== false) {
        $post_type_hint = "Hint: This looks like a service post type. Prefer service when appropriate.";
    } elseif (strpos($pt_lower, 'event') !== false) {
        $post_type_hint = "Hint: This looks like an event post type. Prefer event when appropriate.";
    }

    $prompt = "Analyze the following webpage content and determine the most appropriate Schema.org type for SEO optimization.

Post Type: {$post_type}
{$post_type_hint}
Title: {$title}
Content: {$content}
Excerpt: {$excerpt}

Valid schema types: " . implode(', ', $valid_schema_types) . "

Consider the content structure, purpose, and user intent. Return ONLY the most appropriate schema type from the valid list above. Examples:
- FAQ pages with questions/answers: faq
- Step-by-step tutorials: howto  
- Product/service reviews: review
- Events, webinars, conferences: event
- Blog posts: blog
- Articles/guides: article
- Service pages: service
- Product pages: product
- Company info: organization
- Local business info: local_business
- General pages: webpage

Return only the schema type name, nothing else.";

    $body = json_encode([
        'model' => 'deepseek-chat',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.3,
        'max_tokens' => 50,
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $body,
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['choices'][0]['message']['content'])) {
        $schema_type = trim(strtolower($response_body['choices'][0]['message']['content']));
        if (in_array($schema_type, $valid_schema_types)) {
            return $schema_type;
        }
    }

    return false;
}

// Detect schema type for a page (enhanced with AI analysis)
function artitechcore_detect_schema_type($post_id, $use_ai = true) {
    $post = get_post($post_id);
    if (!$post) {
        return ArtitechCore_SCHEMA_WEBPAGE;
    }

    // If AI is disabled, use strong post-type defaults first.
    if (!$use_ai) {
        if ($post->post_type === 'post') return ArtitechCore_SCHEMA_BLOG;
    }

    // Generic CPT inference (works for any public CPT naming)
    $pt = strtolower((string)$post->post_type);
    if (strpos($pt, 'product') !== false) return ArtitechCore_SCHEMA_PRODUCT;
    if (strpos($pt, 'service') !== false) return ArtitechCore_SCHEMA_SERVICE;
    if (strpos($pt, 'event') !== false) return ArtitechCore_SCHEMA_EVENT;
    if (strpos($pt, 'review') !== false) return ArtitechCore_SCHEMA_REVIEW;
    if (strpos($pt, 'faq') !== false) return ArtitechCore_SCHEMA_FAQ;
    if (strpos($pt, 'article') !== false) return ArtitechCore_SCHEMA_ARTICLE;

    // Check if handling a dynamic CPT
    if (function_exists('artitechcore_is_dynamic_cpt') && artitechcore_is_dynamic_cpt($post->post_type)) {
        // Try to infer schema from CPT slug
        $cpt_slug = strtolower($post->post_type);
        if (strpos($cpt_slug, 'product') !== false) return ArtitechCore_SCHEMA_PRODUCT;
        if (strpos($cpt_slug, 'service') !== false) return ArtitechCore_SCHEMA_SERVICE;
        if (strpos($cpt_slug, 'event') !== false) return ArtitechCore_SCHEMA_EVENT;
        if (strpos($cpt_slug, 'review') !== false) return ArtitechCore_SCHEMA_REVIEW;
        if (strpos($cpt_slug, 'faq') !== false) return ArtitechCore_SCHEMA_FAQ;
        if (strpos($cpt_slug, 'article') !== false) return ArtitechCore_SCHEMA_ARTICLE;
    }

    // Try AI analysis first (if enabled)
    if ($use_ai) {
        $ai_schema_type = artitechcore_ai_analyze_content_for_schema($post_id);
        if ($ai_schema_type) {
            return $ai_schema_type;
        }
    }

    // Fallback to keyword-based detection
    $content = $post->post_content;
    $title = $post->post_title;
    $excerpt = $post->post_excerpt;

    // Check for FAQ content patterns
    if (artitechcore_is_faq_page($content, $title)) {
        return ArtitechCore_SCHEMA_FAQ;
    }

    // Check for HowTo content
    if (artitechcore_is_howto_page($content, $title)) {
        return ArtitechCore_SCHEMA_HOWTO;
    }

    // Check for Review content
    if (artitechcore_is_review_page($content, $title)) {
        return ArtitechCore_SCHEMA_REVIEW;
    }

    // Check for Event content
    if (artitechcore_is_event_page($content, $title)) {
        return ArtitechCore_SCHEMA_EVENT;
    }

    // Check for blog post
    if (artitechcore_is_blog_post($post)) {
        return ArtitechCore_SCHEMA_BLOG;
    }

    // Check for article
    if (artitechcore_is_article($content, $title)) {
        return ArtitechCore_SCHEMA_ARTICLE;
    }

    // Check for service page
    if (artitechcore_is_service_page($title, $content)) {
        return ArtitechCore_SCHEMA_SERVICE;
    }

    // Check for product page
    if (artitechcore_is_product_page($title, $content)) {
        return ArtitechCore_SCHEMA_PRODUCT;
    }

    // Check for organization page
    if (artitechcore_is_organization_page($title)) {
        return ArtitechCore_SCHEMA_ORGANIZATION;
    }

    // Check for local business page
    if (artitechcore_is_local_business_page($title)) {
        return ArtitechCore_SCHEMA_LOCAL_BUSINESS;
    }

    // Default to webpage
    return ArtitechCore_SCHEMA_WEBPAGE;
}

// Check if content contains FAQ patterns
function artitechcore_is_faq_page($content, $title) {
    // Check title for FAQ indicators
    $faq_keywords = ['faq', 'frequently asked', 'questions', 'q&a', 'help center'];
    foreach ($faq_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for question-answer patterns
    $question_patterns = [
        '/<h[1-6][^>]*>.*\?.*<\/h[1-6]>/i',
        '/<strong>.*\?.*<\/strong>/i',
        '/<b>.*\?.*<\/b>/i',
        '/<p><strong>.*\?.*<\/strong><\/p>/i'
    ];

    foreach ($question_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Check if post is a blog post
function artitechcore_is_blog_post($post) {
    if ($post->post_type === 'post') {
        return true;
    }

    // Check if page has blog-like characteristics
    $blog_categories = ['blog', 'news', 'article', 'post'];
    $categories = wp_get_post_categories($post->ID, ['fields' => 'names']);
    
    foreach ($categories as $category) {
        if (in_array(strtolower($category), $blog_categories)) {
            return true;
        }
    }

    return false;
}

// Check if content is an article
function artitechcore_is_article($content, $title) {
    // Articles typically have longer content
    if (str_word_count(strip_tags($content)) > 500) {
        return true;
    }

    // Check for article indicators in title
    $article_keywords = ['guide', 'tutorial', 'how to', 'tips', 'review', 'analysis'];
    foreach ($article_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

// Check if page is a service page
function artitechcore_is_service_page($title, $content) {
    $service_keywords = ['service', 'solution', 'package', 'offer', 'consulting', 'support'];
    foreach ($service_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for service-related terms
    $service_content_keywords = ['pricing', 'features', 'benefits', 'what we offer'];
    foreach ($service_content_keywords as $keyword) {
        if (stripos($content, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

// Check if page is a product page
function artitechcore_is_product_page($title, $content) {
    $product_keywords = ['product', 'item', 'buy', 'purchase', 'order', 'shop'];
    foreach ($product_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check for price information
    if (preg_match('/\$\d+\.?\d*/', $content) || preg_match('/\d+\.?\d*\s*(USD|EUR|GBP|INR)/i', $content)) {
        return true;
    }

    return false;
}

// Check if page is an organization page
function artitechcore_is_organization_page($title) {
    $org_keywords = ['about', 'company', 'team', 'mission', 'vision', 'values', 'history'];
    foreach ($org_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// Check if page is a local business page
function artitechcore_is_local_business_page($title) {
    $business_keywords = ['location', 'store', 'office', 'contact', 'address', 'hours', 'map'];
    foreach ($business_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// Check if page is a HowTo/tutorial page
function artitechcore_is_howto_page($content, $title) {
    // Check title for HowTo indicators
    $howto_keywords = ['how to', 'how-to', 'tutorial', 'guide', 'step by step', 'instructions', 'walkthrough'];
    foreach ($howto_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for step patterns
    $step_patterns = [
        '/step\s+\d+/i',
        '/\d+\.\s*[A-Z]/',
        '/first\s*,?\s*second\s*,?\s*third/i',
        '/next\s*,?\s*then\s*,?\s*finally/i'
    ];

    foreach ($step_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Check if page is a review page
function artitechcore_is_review_page($content, $title) {
    // Check title for review indicators
    $review_keywords = ['review', 'rating', 'testimonial', 'feedback', 'opinion', 'analysis'];
    foreach ($review_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for review patterns
    $review_patterns = [
        '/\d+\/\d+\s*(stars?|rating)/i',
        '/pros?\s*and\s*cons?/i',
        '/recommend/i',
        '/overall\s*rating/i',
        '/my\s*experience/i'
    ];

    foreach ($review_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Check if page is an event page
function artitechcore_is_event_page($content, $title) {
    // Check title for event indicators
    $event_keywords = ['event', 'conference', 'webinar', 'workshop', 'seminar', 'meeting', 'training', 'course'];
    foreach ($event_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for event patterns
    $event_patterns = [
        '/\d{1,2}\/\d{1,2}\/\d{4}/', // Date patterns
        '/\d{1,2}:\d{2}\s*(am|pm)/i', // Time patterns
        '/register\s*(now|here)/i',
        '/ticket/i',
        '/venue/i',
        '/speaker/i'
    ];

    foreach ($event_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Generate schema markup for a page
if (!function_exists('artitechcore_generate_schema_markup')) {
    function artitechcore_generate_schema_markup($post_id, $use_ai = true) {
        // Production Practice: Validate post exists and is not a revision
        $post = get_post($post_id);
        if (!$post || wp_is_post_revision($post)) {
            return false;
        }
        $schema_type = artitechcore_detect_schema_type($post_id, $use_ai);
        $permalink = get_permalink($post_id);
        $site_url = home_url();
        
        // Initialize Graph
        $graph = [];

        // 1. Organization (Global Publisher)
        $org_schema = artitechcore_get_organization_schema();
        $graph[] = $org_schema;

        // 1b. Primary Person (if detected). This enables explicit linking like Physician ↔ Clinic.
        if ($use_ai) {
            $profile = artitechcore_get_ai_entity_profile();
            $person_node = artitechcore_build_primary_person_node($profile);
            if (is_array($person_node)) {
                $graph[] = $person_node;
            }
        }

        // 2. WebSite (Global)
        $website_schema = artitechcore_generate_website_schema();
        $graph[] = $website_schema;

        // 3. WebPage (Current Page)
        $webpage_schema = [
            '@type' => 'WebPage',
            '@id' => $permalink . '#webpage',
            'url' => $permalink,
            'name' => get_the_title($post_id),
            'description' => get_the_excerpt($post_id),
            'isPartOf' => ['@id' => $site_url . '/#website'],
            'publisher' => ['@id' => $site_url . '/#organization'],
            'inLanguage' => get_bloginfo('language'),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'breadcrumb' => ['@id' => $permalink . '#breadcrumb']
        ];
        
        // Add featured image to WebPage
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
        if ($thumbnail_url) {
            $webpage_schema['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                '@id' => $permalink . '#primaryimage',
                'url' => $thumbnail_url
            ];
            $webpage_schema['image'] = ['@id' => $permalink . '#primaryimage'];
        }

        // 4. BreadcrumbList
        $breadcrumb_schema = artitechcore_generate_breadcrumb_schema($post_id);
        $graph[] = $breadcrumb_schema;

        // 5. Main Entity
        $main_entity = [];
        switch ($schema_type) {
            case ArtitechCore_SCHEMA_FAQ:
                $main_entity = artitechcore_generate_faq_schema($post_id);
                break;
            case ArtitechCore_SCHEMA_BLOG:
                $main_entity = artitechcore_generate_blog_schema($post_id);
                break;
            case ArtitechCore_SCHEMA_ARTICLE:
                $main_entity = artitechcore_generate_article_schema($post_id);
                break;
            case ArtitechCore_SCHEMA_SERVICE:
                $main_entity = artitechcore_generate_service_schema($post_id);
                break;
            case ArtitechCore_SCHEMA_PRODUCT:
                $main_entity = artitechcore_generate_product_schema($post_id);
                break;
            case ArtitechCore_SCHEMA_ORGANIZATION:
                // If the page IS the organization page, merge distinct features or just use global org
                $main_entity = artitechcore_generate_organization_schema($post_id);
                // Force ID match if specific org page
                $main_entity['@id'] = $site_url . '/#organization'; 
                break;
            case ArtitechCore_SCHEMA_LOCAL_BUSINESS:
                $main_entity = artitechcore_generate_local_business_schema($post_id);
                break;
            case ArtitechCore_SCHEMA_HOWTO:
                $main_entity = artitechcore_generate_howto_schema($post_id);
                break;
            case ArtitechCore_SCHEMA_REVIEW:
                $main_entity = artitechcore_generate_review_schema($post_id);
                break;
            case ArtitechCore_SCHEMA_EVENT:
                $main_entity = artitechcore_generate_event_schema($post_id);
                break;
            default:
                // For default webpages, the WebPage IS the main entity, so we don't add a separate node
                $main_entity = null;
                break;
        }

        if ($main_entity) {
            // Clean up Main Entity
            unset($main_entity['@context']); // Remove context as it's in root
            
            // Assign ID if not present
            if (!isset($main_entity['@id'])) {
                $main_entity['@id'] = $permalink . '#article'; // Default suffix, could vary
            }
            
            // Link to WebPage
            $main_entity['isPartOf'] = ['@id' => $permalink . '#webpage'];
            $main_entity['mainEntityOfPage'] = ['@id' => $permalink . '#webpage'];
            
            // Link to Publisher/Organization
            $main_entity['publisher'] = ['@id' => $site_url . '/#organization'];

            $graph[] = $main_entity;
            
            // Link WebPage to Main Entity
            $webpage_schema['about'] = ['@id' => $main_entity['@id']];
        }

        $graph[] = $webpage_schema;

        $schema_data = [
            '@context' => 'https://schema.org',
            '@graph' => $graph
        ];

        // Store schema data in custom table
        artitechcore_save_schema_data($post_id, $schema_data, $schema_type, 'post', 'generated', 0);
        
        return $schema_data;
    }
}

/**
 * Output schema markup in wp_head (posts + taxonomy archives).
 *
 * - Singular: outputs schema data from custom table if present.
 * - Term archives: outputs schema data from custom table if present; if missing and auto-enabled, generates a baseline CollectionPage graph.
 */
/**
 * Check if a known SEO plugin that generates schema markup is active.
 * If so, ArtitechCore defers to avoid duplicate structured data.
 *
 * @return bool True if a conflicting schema plugin is active.
 */
function artitechcore_has_conflicting_schema_plugin() {
    // Allow developers/site owners to force-suppress schema output via filter
    $skip = apply_filters('artitechcore_skip_schema_output', false);
    if ($skip) {
        return true;
    }

    $conflicting_plugins = [
        'wordpress-seo/wp-seo.php',                          // Yoast SEO
        'seo-by-rank-math/rank-math.php',                    // RankMath
        'all-in-one-seo-pack/all_in_one_seo_pack.php',      // AIOSEO
        'wp-schema-pro/wp-schema-pro.php',                   // Schema Pro
        'schema/schema.php',                                 // Schema plugin
    ];

    $active_plugins = (array) get_option('active_plugins', []);

    // Also check network-activated plugins for multisite
    if (is_multisite()) {
        $network_plugins = array_keys((array) get_site_option('active_sitewide_plugins', []));
        $active_plugins = array_merge($active_plugins, $network_plugins);
    }

    foreach ($conflicting_plugins as $plugin) {
        if (in_array($plugin, $active_plugins, true)) {
            return true;
        }
    }

    return false;
}

function artitechcore_output_schema_markup() {
    // Conflict Detection: Skip if another major SEO plugin is handling schema
    if (artitechcore_has_conflicting_schema_plugin()) {
        echo "\n" . '<!-- ArtitechCore Schema: Deferred (conflicting SEO plugin detected) -->' . "\n";
        return;
    }
    // Term archives (category/tag/custom tax)
    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        if ($term && isset($term->term_id, $term->taxonomy)) {
            $schema_row = artitechcore_get_schema_data($term->term_id, 'term');
            $schema_data = !empty($schema_row['schema_data']) ? json_decode($schema_row['schema_data'], true) : null;

            if (empty($schema_data) && get_option('artitechcore_auto_schema_generation', true)) {
                $schema_data = artitechcore_generate_term_schema_markup($term->term_id, $term->taxonomy, true);
            }

            if (!empty($schema_data)) {
                echo "\n" . '<!-- ArtitechCore Schema -->' . "\n";
                echo '<script type="application/ld+json">' . "\n";
                echo wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                echo "\n" . '</script>' . "\n";
                echo '<!-- /ArtitechCore Schema -->' . "\n";
            }
        }
        return;
    }

    // Singular (posts, pages, CPT)
    if (!is_singular()) {
        return;
    }

    $schema_row = artitechcore_get_schema_data(get_the_ID(), 'post');
    $schema_data = !empty($schema_row['schema_data']) ? json_decode($schema_row['schema_data'], true) : null;
    if (!empty($schema_data)) {
        echo "\n" . '<!-- ArtitechCore Schema -->' . "\n";
        echo '<script type="application/ld+json">' . "\n";

        if (is_array($schema_data) || is_object($schema_data)) {
            echo wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            echo $schema_data;
        }

        echo "\n" . '</script>' . "\n";
        echo '<!-- /ArtitechCore Schema -->' . "\n";
    }
}
add_action('wp_head', 'artitechcore_output_schema_markup');

/**
 * Generate a baseline schema graph for a taxonomy term archive.
 * Stores it in the custom schema table.
 */
function artitechcore_generate_term_schema_markup($term_id, $taxonomy, $use_ai = true) {
    $term = get_term($term_id, $taxonomy);
    if (!$term || is_wp_error($term)) {
        return false;
    }

    $site_url = home_url();
    $term_link = get_term_link($term);
    if (is_wp_error($term_link)) {
        $term_link = $site_url;
    }

    $graph = [];
    $graph[] = artitechcore_get_organization_schema();
    $graph[] = artitechcore_generate_website_schema();

    $graph[] = [
        '@type' => 'CollectionPage',
        '@id' => esc_url_raw($term_link) . '#term',
        'url' => esc_url_raw($term_link),
        'name' => sanitize_text_field($term->name),
        'description' => sanitize_text_field(wp_strip_all_tags(term_description($term))),
        'isPartOf' => ['@id' => $site_url . '/#website'],
        'publisher' => ['@id' => $site_url . '/#organization'],
        'inLanguage' => get_bloginfo('language'),
    ];

    $schema_data = [
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ];

    // Save to custom database table
    artitechcore_save_schema_data($term_id, $schema_data, 'CollectionPage', 'term', 'generated', 0);

    return $schema_data;
}

function artitechcore_generate_term_schema_on_edit($term_id, $tt_id = 0, $taxonomy = '') {
    if (!current_user_can('manage_categories')) {
        return;
    }
    if (!get_option('artitechcore_auto_schema_generation', true)) {
        return;
    }
    if (empty($taxonomy)) {
        return;
    }
    artitechcore_generate_term_schema_markup($term_id, $taxonomy, true);
}
add_action('edited_term', 'artitechcore_generate_term_schema_on_edit', 10, 3);
add_action('created_term', 'artitechcore_generate_term_schema_on_edit', 10, 3);

// Generate WebSite Schema
function artitechcore_generate_website_schema() {
    $site_url = home_url();
    $site_name = get_bloginfo('name');
    
    return [
        '@type' => 'WebSite',
        '@id' => $site_url . '/#website',
        'url' => $site_url,
        'name' => $site_name,
        'description' => get_bloginfo('description'),
        'publisher' => ['@id' => $site_url . '/#organization'],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => $site_url . '/?s={search_term_string}',
            'query-input' => 'required name=search_term_string'
        ]
    ];
}


// Generate FAQ schema - Enhanced with AI extraction
function artitechcore_generate_faq_schema($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    $faq_items = [];

    // 1. Check for AI Content Enhancer FAQs (Highest Priority/Manual Edit)
    $ce_faq = get_post_meta($post_id, '_artitechcore_ce_faq', true);
    if (!empty($ce_faq) && is_array($ce_faq)) {
        foreach ($ce_faq as $item) {
            if (!empty($item['q']) && !empty($item['a'])) {
                $faq_items[] = [
                    '@type' => 'Question',
                    'name' => sanitize_text_field($item['q']),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => wp_strip_all_tags(wpautop(sanitize_textarea_field($item['a'])))
                    ]
                ];
            }
        }
    }

    // 2. Try Generic AI extraction if no CE FAQs
    if (empty($faq_items)) {
        $ai_data = artitechcore_ai_extract_schema_data($post_id, 'faq');
        if ($ai_data && is_array($ai_data)) {
            foreach ($ai_data as $item) {
                if (isset($item['question']) && isset($item['answer']) && !empty($item['question']) && !empty($item['answer'])) {
                    $faq_items[] = [
                        '@type' => 'Question',
                        'name' => sanitize_text_field($item['question']),
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => sanitize_textarea_field($item['answer'])
                        ]
                    ];
                }
            }
        }
    }

    // 3. Fallback: Regex extraction
    if (empty($faq_items)) {
        $faq_items = artitechcore_extract_faq_items($content);
    }
    
    if (empty($faq_items)) {
        return artitechcore_generate_webpage_schema($post_id);
    }

    return [
        '@type' => 'FAQPage',
        'mainEntity' => $faq_items
    ];
}


// Extract FAQ items from content
function artitechcore_extract_faq_items($content) {
    $faq_items = [];
    
    // Pattern to match question-answer pairs
    $patterns = [
        // Match headings with questions followed by paragraphs
        '/(<h[1-6][^>]*>.*\?.*<\/h[1-6]>)(.*?)(?=<h[1-6]|$)/is',
        // Match bold questions followed by text
        '/(<strong>.*\?.*<\/strong>)(.*?)(?=<strong>|$)/is',
        // Match paragraph with strong question
        '/(<p><strong>.*\?.*<\/strong><\/p>)(.*?)(?=<p><strong>|$)/is'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $question = sanitize_text_field(trim(strip_tags($match[1])));
                $answer = sanitize_textarea_field(trim(strip_tags($match[2])));
                
                if (!empty($question) && !empty($answer)) {
                    $faq_items[] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $answer
                        ]
                    ];
                }
            }
        }
    }

    return $faq_items;
}

// Generate Blog schema
function artitechcore_generate_blog_schema($post_id) {
    $post = get_post($post_id);
    $author_id = $post->post_author;
    $author = get_userdata($author_id);
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_date('c', $post_id),
        'author' => [
            '@type' => 'Person',
            'name' => sanitize_text_field($author->display_name)
        ],
        'publisher' => artitechcore_get_organization_schema(),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => esc_url_raw(get_permalink($post_id))
        ]
    ];

    // Add featured image if available
    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
    if ($thumbnail_url) {
        $schema['image'] = [
            '@type' => 'ImageObject',
            'url' => $thumbnail_url
        ];
    }

    return $schema;
}

// Generate Article schema
function artitechcore_generate_article_schema($post_id) {
    $post = get_post($post_id);
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_date('c', $post_id),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => esc_url_raw(get_permalink($post_id))
        ],
        'publisher' => artitechcore_get_organization_schema()
    ];

    // Add author if available
    $author_id = $post->post_author;
    if ($author_id) {
        $author = get_userdata($author_id);
        $schema['author'] = [
            '@type' => 'Person',
            'name' => $author->display_name
        ];
    }

    // Add featured image if available
    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
    if ($thumbnail_url) {
        $schema['image'] = [
            '@type' => 'ImageObject',
            'url' => $thumbnail_url
        ];
    }
    
    // Try AI extraction for richer data (Content Aware)
    $ai_data = artitechcore_ai_extract_schema_data($post_id, 'article');
    if ($ai_data && is_array($ai_data)) {
        if (!empty($ai_data['articleSection'])) {
            $schema['articleSection'] = sanitize_text_field($ai_data['articleSection']);
        }
        if (!empty($ai_data['alternativeHeadline'])) {
            $schema['alternativeHeadline'] = sanitize_text_field($ai_data['alternativeHeadline']);
        }
        if (!empty($ai_data['keywords'])) {
            $keywords = is_array($ai_data['keywords']) ? implode(', ', $ai_data['keywords']) : $ai_data['keywords'];
            $schema['keywords'] = sanitize_text_field($keywords);
        }
        if (!empty($ai_data['wordCount'])) {
            $schema['wordCount'] = absint($ai_data['wordCount']);
        }
        if (!empty($ai_data['proficiencyLevel'])) {
            $schema['proficiencyLevel'] = sanitize_text_field($ai_data['proficiencyLevel']);
        }
    }

    return $schema;
}

// Generate Service schema - Enhanced with AI extraction
function artitechcore_generate_service_schema($post_id) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'provider' => artitechcore_get_organization_schema(),
        'areaServed' => 'Worldwide',
        'serviceType' => sanitize_text_field(get_the_title($post_id))
    ];

    // Try AI extraction for richer data
    $ai_data = artitechcore_ai_extract_schema_data($post_id, 'service');
    if ($ai_data && is_array($ai_data)) {
        if (!empty($ai_data['serviceType'])) {
            $schema['serviceType'] = sanitize_text_field($ai_data['serviceType']);
        }
        if (!empty($ai_data['areaServed'])) {
            $schema['areaServed'] = sanitize_text_field($ai_data['areaServed']);
        }
        if (!empty($ai_data['features']) && is_array($ai_data['features'])) {
            $schema['hasOfferCatalog'] = [
                '@type' => 'OfferCatalog',
                'name' => 'Service Features',
                'itemListElement' => array_map(function($feature) {
                    return [
                        '@type' => 'ListItem',
                        'item' => [
                            '@type' => 'Thing',
                            'name' => sanitize_text_field($feature)
                        ]
                    ];
                }, $ai_data['features'])
            ];
        }
        if (!empty($ai_data['offers']) && is_array($ai_data['offers'])) {
            $offer = [
                '@type' => 'Offer',
                'price' => sanitize_text_field($ai_data['offers']['price'] ?? '0'),
                'priceCurrency' => sanitize_text_field($ai_data['offers']['priceCurrency'] ?? 'USD')
            ];
            if (!empty($ai_data['offers']['description'])) {
                $offer['description'] = sanitize_text_field($ai_data['offers']['description']);
            }
            $schema['offers'] = $offer;
        }
        if (!empty($ai_data['audience'])) {
            $schema['audience'] = [
                '@type' => 'Audience',
                'audienceType' => sanitize_text_field($ai_data['audience'])
            ];
        }
        if (!empty($ai_data['provider'])) {
            $schema['provider'] = [
                '@type' => 'Organization',
                'name' => sanitize_text_field($ai_data['provider'])
            ];
        }
    }

    return $schema;
}

// Generate Product schema - Enhanced with AI extraction
function artitechcore_generate_product_schema($post_id) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'sku' => 'PROD-' . absint($post_id)
    ];

    // Try AI extraction for richer data
    $ai_data = artitechcore_ai_extract_schema_data($post_id, 'product');
    if ($ai_data && is_array($ai_data)) {
        if (!empty($ai_data['brand'])) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => sanitize_text_field($ai_data['brand'])
            ];
        }
        if (!empty($ai_data['sku'])) {
            $schema['sku'] = sanitize_text_field($ai_data['sku']);
        }
        
        // Build offers
        $schema['offers'] = [
            '@type' => 'Offer',
            'priceCurrency' => sanitize_text_field($ai_data['priceCurrency'] ?? 'USD'),
            'price' => sanitize_text_field($ai_data['price'] ?? '0'),
            'availability' => 'https://schema.org/' . ($ai_data['availability'] === 'OutOfStock' ? 'OutOfStock' : 'InStock')
        ];
        
        // Add richer product details
        if (!empty($ai_data['color'])) {
            $schema['color'] = sanitize_text_field($ai_data['color']);
        }
        if (!empty($ai_data['material'])) {
            $schema['material'] = sanitize_text_field($ai_data['material']);
        }
        if (!empty($ai_data['condition'])) {
            $schema['itemCondition'] = 'https://schema.org/' . sanitize_text_field($ai_data['condition']);
        }
        if (!empty($ai_data['features']) && is_array($ai_data['features'])) {
            $schema['additionalProperty'] = [];
            foreach ($ai_data['features'] as $feature) {
                $schema['additionalProperty'][] = [
                    '@type' => 'PropertyValue',
                    'name' => 'Feature',
                    'value' => sanitize_text_field($feature)
                ];
            }
        }
    } else {
        // Fallback
        $schema['offers'] = [
            '@type' => 'Offer',
            'priceCurrency' => 'USD',
            'price' => '0.00',
            'availability' => 'https://schema.org/InStock'
        ];
    }

    // Add featured image if available
    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
    if ($thumbnail_url) {
        $schema['image'] = $thumbnail_url;
    }

    return $schema;
}

// Generate Organization schema
function artitechcore_generate_organization_schema($post_id) {
    return artitechcore_get_organization_schema();
}

// Get organization schema (reusable) - Enhanced with Business Settings
// Get organization schema (reusable) - Enhanced with Business Settings
function artitechcore_get_organization_schema() {
    // Use business settings if available, fallback to site info
    $business_name = get_option('artitechcore_business_name', get_bloginfo('name'));
    $business_description = get_option('artitechcore_business_description', get_bloginfo('description'));
    $site_url = home_url();
    
    $logo_url = get_site_icon_url();
    $schema = [
        '@type' => 'Organization',
        '@id' => $site_url . '/#organization',
        'name' => $business_name,
        'url' => $site_url
    ];

    if ($logo_url) {
        $schema['logo'] = [
            '@type' => 'ImageObject',
            'url' => esc_url_raw($logo_url)
        ];
    }

    // Add description if available
    if (!empty($business_description)) {
        $schema['description'] = sanitize_text_field($business_description);
    }

    // Add contact information
    $email = get_option('artitechcore_business_email', '');
    $phone = get_option('artitechcore_business_phone', '');
    
    if (!empty($email) || !empty($phone)) {
        $schema['contactPoint'] = [
            '@type' => 'ContactPoint',
            'contactType' => 'customer service'
        ];
        if (!empty($email)) {
            $schema['contactPoint']['email'] = sanitize_email($email);
        }
        if (!empty($phone)) {
            $schema['contactPoint']['telephone'] = sanitize_text_field($phone);
        }
    }

    // Add address if available
    $address = get_option('artitechcore_business_address', '');
    if (!empty($address)) {
        $schema['address'] = artitechcore_build_postal_address($address);
    }

    // Add social links if available
    $social_links = [];
    $facebook = get_option('artitechcore_business_social_facebook', '');
    $twitter = get_option('artitechcore_business_social_twitter', '');
    $linkedin = get_option('artitechcore_business_social_linkedin', '');
    
    if (!empty($facebook)) $social_links[] = esc_url_raw($facebook);
    if (!empty($twitter)) $social_links[] = esc_url_raw($twitter);
    if (!empty($linkedin)) $social_links[] = esc_url_raw($linkedin);
    
    if (!empty($social_links)) {
        $schema['sameAs'] = $social_links;
    }

    // AI-enrich specificity and add key person relationship if detected
    $profile = artitechcore_get_ai_entity_profile();
    if (is_array($profile) && !empty($profile['organization']['types'])) {
        $schema['@type'] = artitechcore_schema_types_to_at_type($profile['organization']['types'], 'Organization');

        if (!empty($profile['organization']['specialties'])) {
            $types_str = strtolower(is_array($schema['@type']) ? implode(' ', $schema['@type']) : $schema['@type']);
            if (strpos($types_str, 'medical') !== false || strpos($types_str, 'clinic') !== false || strpos($types_str, 'physician') !== false || strpos($types_str, 'dentist') !== false) {
                $schema['medicalSpecialty'] = array_values((array)$profile['organization']['specialties']);
            } else {
                $schema['knowsAbout'] = array_values((array)$profile['organization']['specialties']);
            }
        }

        $rel = $profile['relationship']['personToOrganization'] ?? 'none';
        if ($rel !== 'none' && !empty($profile['primaryPerson']['name'])) {
            $schema[$rel] = ['@id' => $site_url . '/#primaryPerson'];
        }
    }

    return $schema;
}

// Generate BreadcrumbList schema
function artitechcore_generate_breadcrumb_schema($post_id) {
    $breadcrumbs = [];
    $position = 1;
    $site_url = home_url();
    
    // Home
    $breadcrumbs[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => 'Home',
        'item' => $site_url
    ];

    $ancestors = get_post_ancestors($post_id);
    if ($ancestors) {
        $ancestors = array_reverse($ancestors);
        foreach ($ancestors as $ancestor_id) {
            $breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($ancestor_id),
                'item' => get_permalink($ancestor_id)
            ];
        }
    }

    // Current Page
    $breadcrumbs[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'name' => get_the_title($post_id),
        'item' => get_permalink($post_id)
    ];

    return [
        '@type' => 'BreadcrumbList',
        '@id' => get_permalink($post_id) . '#breadcrumb',
        'itemListElement' => $breadcrumbs
    ];
}


// Generate Local Business schema - Enhanced with Business Settings
function artitechcore_generate_local_business_schema($post_id) {
    $business_name = get_option('artitechcore_business_name', get_bloginfo('name'));
    $business_description = get_option('artitechcore_business_description', get_the_excerpt($post_id));
    $address = get_option('artitechcore_business_address', '');
    $phone = get_option('artitechcore_business_phone', '');
    $email = get_option('artitechcore_business_email', '');
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => sanitize_text_field($business_name),
        'description' => sanitize_text_field($business_description),
        'url' => esc_url_raw(home_url())
    ];

    // AI-enrich LocalBusiness subtype/specificity when applicable (generic across industries).
    $profile = artitechcore_get_ai_entity_profile();
    if (is_array($profile) && !empty($profile['organization']['types'])) {
        $types = (array)$profile['organization']['types'];
        // Ensure LocalBusiness is present when we are explicitly generating a local business node.
        if (!in_array('LocalBusiness', $types, true)) {
            array_unshift($types, 'LocalBusiness');
        }
        $schema['@type'] = artitechcore_schema_types_to_at_type($types, 'LocalBusiness');

        if (!empty($profile['organization']['specialties'])) {
            $types_str = strtolower(is_array($schema['@type']) ? implode(' ', $schema['@type']) : $schema['@type']);
            if (strpos($types_str, 'medical') !== false || strpos($types_str, 'clinic') !== false || strpos($types_str, 'physician') !== false || strpos($types_str, 'dentist') !== false) {
                $schema['medicalSpecialty'] = array_values((array)$profile['organization']['specialties']);
            } else {
                $schema['knowsAbout'] = array_values((array)$profile['organization']['specialties']);
            }
        }

        // Link to primary person if present (helps “flat hierarchy” issue)
        if (!empty($profile['primaryPerson']['name'])) {
            $schema['employee'] = ['@id' => home_url() . '/#primaryPerson'];
        }
    }

    // Add address if available
    if (!empty($address)) {
        $schema['address'] = artitechcore_build_postal_address($address);
    }

    // Add contact info
    if (!empty($phone)) {
        $schema['telephone'] = sanitize_text_field($phone);
    }
    if (!empty($email)) {
        $schema['email'] = sanitize_email($email);
    }

    // Add logo
    $logo_url = get_site_icon_url();
    if ($logo_url) {
        $schema['logo'] = esc_url_raw($logo_url);
    }

    return $schema;
}


// Generate WebPage schema (fallback)
function artitechcore_generate_webpage_schema($post_id) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'url' => esc_url_raw(get_permalink($post_id))
    ];

    // Add publisher information
    $schema['publisher'] = artitechcore_get_organization_schema();

    return $schema;
}

// Generate HowTo schema - Enhanced with AI extraction
function artitechcore_generate_howto_schema($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    
    // Try AI extraction first for better step identification
    $ai_data = artitechcore_ai_extract_schema_data($post_id, 'howto');
    
    if ($ai_data && is_array($ai_data) && !empty($ai_data['steps'])) {
        $steps = [];
        $step_counter = 1;
        foreach ($ai_data['steps'] as $step) {
            if (isset($step['text']) && !empty($step['text'])) {
                $steps[] = [
                    '@type' => 'HowToStep',
                    'name' => sanitize_text_field($step['name'] ?? "Step $step_counter"),
                    'text' => sanitize_textarea_field($step['text'])
                ];
                $step_counter++;
            }
        }
        
        if (!empty($steps)) {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'HowTo',
                'name' => sanitize_text_field(get_the_title($post_id)),
                'description' => sanitize_text_field(get_the_excerpt($post_id)),
                'step' => $steps
            ];
            
            if (!empty($ai_data['totalTime'])) {
                $schema['totalTime'] = sanitize_text_field($ai_data['totalTime']);
            }
            if (!empty($ai_data['estimatedCost'])) {
                $schema['estimatedCost'] = [
                    '@type' => 'MonetaryAmount',
                    'currency' => 'USD',
                    'value' => sanitize_text_field($ai_data['estimatedCost'])
                ];
            }
            return $schema;
        }
    }

    // Fallback: Extract steps from content using regex
    $steps = artitechcore_extract_howto_steps($content);
    
    if (empty($steps)) {
        return artitechcore_generate_webpage_schema($post_id);
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'step' => $steps
    ];

    $total_time = artitechcore_extract_total_time($content);
    if ($total_time) {
        $schema['totalTime'] = $total_time;
    }

    $estimated_cost = artitechcore_extract_estimated_cost($content);
    if ($estimated_cost) {
        $schema['estimatedCost'] = [
            '@type' => 'MonetaryAmount',
            'currency' => 'USD',
            'value' => $estimated_cost
        ];
    }

    return $schema;
}


// Extract HowTo steps from content
function artitechcore_extract_howto_steps($content) {
    $steps = [];
    
    // Pattern to match numbered steps
    $patterns = [
        // Match "Step 1:", "1.", etc.
        '/(?:step\s+)?(\d+)\.?\s*([^<]+?)(?=(?:step\s+)?\d+\.|$)/is',
        // Match ordered list items
        '/<li[^>]*>([^<]+)<\/li>/is'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $step_text = sanitize_text_field(trim(strip_tags($match[2] ?? $match[1])));
                if (!empty($step_text) && strlen($step_text) > 10) {
                    $steps[] = [
                        '@type' => 'HowToStep',
                        'name' => 'Step ' . count($steps) + 1,
                        'text' => $step_text
                    ];
                }
            }
        }
    }

    return $steps;
}

// Extract total time from content
function artitechcore_extract_total_time($content) {
    $time_patterns = [
        '/(\d+)\s*(?:minutes?|mins?)/i',
        '/(\d+)\s*(?:hours?|hrs?)/i',
        '/(\d+)\s*(?:days?)/i'
    ];

    foreach ($time_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $value = intval($matches[1]);
            if (stripos($matches[0], 'minute') !== false || stripos($matches[0], 'min') !== false) {
                return 'PT' . $value . 'M';
            } elseif (stripos($matches[0], 'hour') !== false || stripos($matches[0], 'hr') !== false) {
                return 'PT' . $value . 'H';
            } elseif (stripos($matches[0], 'day') !== false) {
                return 'P' . $value . 'D';
            }
        }
    }

    return null;
}

// Extract estimated cost from content
function artitechcore_extract_estimated_cost($content) {
    $cost_patterns = [
        '/\$(\d+(?:\.\d{2})?)/',
        '/(\d+(?:\.\d{2})?)\s*(?:dollars?|usd)/i'
    ];

    foreach ($cost_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return floatval($matches[1]);
        }
    }

    return null;
}

// Generate Review schema - Enhanced with AI extraction
function artitechcore_generate_review_schema($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    
    // Try AI extraction first
    $ai_data = artitechcore_ai_extract_schema_data($post_id, 'review');
    
    $rating = null;
    $reviewed_item = null;
    
    if ($ai_data && is_array($ai_data)) {
        $rating = isset($ai_data['ratingValue']) ? floatval($ai_data['ratingValue']) : null;
        $reviewed_item = !empty($ai_data['itemReviewed']) ? sanitize_text_field($ai_data['itemReviewed']) : null;
    }
    
    // Fallback to regex if AI failed
    if (!$rating) {
        $rating = artitechcore_extract_review_rating($content);
    }
    if (!$reviewed_item) {
        $reviewed_item = artitechcore_extract_reviewed_item($post->post_title, $content);
    }
    
    // Fix: Clamp rating to valid range (1-5)
    if ($rating !== null) {
        $rating = max(1, min(5, $rating));
    }
    
    $schema = [
        '@type' => 'Review',
        'headline' => sanitize_text_field(get_the_title($post_id)),
        'reviewBody' => sanitize_text_field(wp_strip_all_tags(wp_trim_words($content, 200))),
        'datePublished' => get_the_date('c', $post_id),
        'author' => [
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $post->post_author)
        ]
    ];

    if ($rating) {
        $schema['reviewRating'] = [
            '@type' => 'Rating',
            'ratingValue' => $rating,
            'bestRating' => 5,
            'worstRating' => 1
        ];
    }

    if ($reviewed_item) {
        // Fix: Use Product as a more valid type for itemReviewed than generic Thing
        $schema['itemReviewed'] = [
            '@type' => 'Product',
            'name' => $reviewed_item
        ];
    }

    return $schema;
}


// Extract review rating from content
function artitechcore_extract_review_rating($content) {
    $rating_patterns = [
        '/(\d+)\/(\d+)\s*(?:stars?|rating)/i',
        '/(\d+)\s*(?:out\s*of\s*)?(\d+)/i',
        '/rating[:\s]*(\d+)/i'
    ];

    foreach ($rating_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $rating = floatval($matches[1]);
            $max_rating = isset($matches[2]) ? floatval($matches[2]) : 5;
            
            // Normalize to 5-star scale if needed
            if ($max_rating != 5) {
                $rating = ($rating / $max_rating) * 5;
            }
            
            return round($rating, 1);
        }
    }

    return null;
}

// Extract reviewed item from title and content
function artitechcore_extract_reviewed_item($title, $content) {
    // Try to extract from title first
    $title_patterns = [
        '/review[:\s]*of\s*([^,]+)/i',
        '/([^,]+)\s*review/i'
    ];

    foreach ($title_patterns as $pattern) {
        if (preg_match($pattern, $title, $matches)) {
            return sanitize_text_field(trim($matches[1]));
        }
    }

    // Fallback: look for product names in content
    $content_patterns = [
        '/product[:\s]*([^,\.]+)/i',
        '/service[:\s]*([^,\.]+)/i'
    ];

    foreach ($content_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return sanitize_text_field(trim($matches[1]));
        }
    }

    return null;
}

// Generate Event schema
function artitechcore_generate_event_schema($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    
    // Extract event data
    $event_date = artitechcore_extract_event_date($content);
    $event_location = artitechcore_extract_event_location($content);
    $event_organizer = artitechcore_extract_event_organizer($content);
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id))
    ];

    if ($event_date) {
        $schema['startDate'] = $event_date;
    }

    if ($event_location) {
        $schema['location'] = [
            '@type' => 'Place',
            'name' => $event_location,
            'address' => artitechcore_build_postal_address(get_option('artitechcore_business_address', 'Worldwide'))
        ];
    }

    if ($event_organizer) {
        $schema['organizer'] = [
            '@type' => 'Organization',
            'name' => $event_organizer
        ];
    } else {
        $schema['organizer'] = artitechcore_get_organization_schema();
    }

    return $schema;
}

// Extract event date from content
function artitechcore_extract_event_date($content) {
    $date_patterns = [
        '/(\d{1,2}\/\d{1,2}\/\d{4})/',
        '/(\d{4}-\d{2}-\d{2})/',
        '/(\w+\s+\d{1,2},?\s+\d{4})/'
    ];

    foreach ($date_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $date = strtotime($matches[1]);
            if ($date !== false) {
                return date('c', $date);
            }
        }
    }

    return null;
}

// Extract event location from content
function artitechcore_extract_event_location($content) {
    $location_patterns = [
        '/location[:\s]*([^,\.]+)/i',
        '/venue[:\s]*([^,\.]+)/i',
        '/address[:\s]*([^,\.]+)/i'
    ];

    foreach ($location_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return sanitize_text_field(trim($matches[1]));
        }
    }

    return null;
}

// Extract event organizer from content
function artitechcore_extract_event_organizer($content) {
    $organizer_patterns = [
        '/organizer[:\s]*([^,\.]+)/i',
        '/hosted\s*by[:\s]*([^,\.]+)/i',
        '/presented\s*by[:\s]*([^,\.]+)/i'
    ];

    foreach ($organizer_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return sanitize_text_field(trim($matches[1]));
        }
    }

    return null;
}

// Generate schema when page is saved
function artitechcore_generate_schema_on_save($post_id) {
    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Check if schema generation is enabled
    $auto_generate = get_option('artitechcore_auto_schema_generation', true);
    if (!$auto_generate) {
        return;
    }

    // If the schema was manually edited/saved, don't overwrite on normal saves
    if (function_exists('artitechcore_should_skip_auto_schema_generation') && artitechcore_should_skip_auto_schema_generation($post_id)) {
        return;
    }

    // Generate schema markup
    artitechcore_generate_schema_markup($post_id);
}
add_action('save_post', 'artitechcore_generate_schema_on_save');
add_action('save_post_page', 'artitechcore_generate_schema_on_save');

// Add schema column to pages list
function artitechcore_add_schema_column($columns) {
    $columns['schema'] = 'Schema';
    return $columns;
}
add_filter('manage_page_posts_columns', 'artitechcore_add_schema_column');

// Display schema type in the schema column
function artitechcore_display_schema_column($column, $post_id) {
    if ($column === 'schema') {
        $schema_row = artitechcore_get_schema_data($post_id, 'post');
        $schema_type = !empty($schema_row['schema_type']) ? $schema_row['schema_type'] : '';
        if (!empty($schema_type)) {
            echo '<span class="artitechcore-schema-badge artitechcore-schema-' . esc_attr($schema_type) . '">' . esc_html(ucfirst($schema_type)) . '</span>';
        } else {
            echo '<span class="artitechcore-schema-badge artitechcore-schema-none">Not Generated</span>';
        }
    }
}
add_action('manage_page_posts_custom_column', 'artitechcore_display_schema_column', 10, 2);

// Make schema column sortable
function artitechcore_make_schema_column_sortable($columns) {
    $columns['schema'] = 'schema';
    return $columns;
}
add_filter('manage_edit-page_sortable_columns', 'artitechcore_make_schema_column_sortable');

// Register admin hooks for Dynamic CPTs
function artitechcore_register_cpt_schema_admin_hooks() {
    // Register schema column/actions for ALL public post types (posts, pages, CPTs)
    $post_types = get_post_types(['public' => true], 'names');
    unset($post_types['attachment'], $post_types['revision'], $post_types['nav_menu_item']);

    foreach ($post_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'artitechcore_add_schema_column');
        add_action("manage_{$post_type}_posts_custom_column", 'artitechcore_display_schema_column', 10, 2);
        add_filter("manage_edit-{$post_type}_sortable_columns", 'artitechcore_make_schema_column_sortable');
    }

    // Row actions for all post types
    add_filter('post_row_actions', 'artitechcore_add_schema_quick_actions', 10, 2);
}
add_action('admin_init', 'artitechcore_register_cpt_schema_admin_hooks');

// Handle schema column sorting
function artitechcore_handle_schema_column_sorting($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('orderby') === 'schema') {
        // Meta-based sorting is disabled in the new custom table architecture.
        // Dashboard sorting is handled via the dedicated Schema Management interface.
    }
}
add_action('pre_get_posts', 'artitechcore_handle_schema_column_sorting');

// Add quick actions for schema generation
function artitechcore_add_schema_quick_actions($actions, $post) {
    $schema_row = artitechcore_get_schema_data($post->ID, 'post');
    $schema_type = !empty($schema_row['schema_type']) ? $schema_row['schema_type'] : '';

    if (empty($schema_type)) {
        $actions['generate_schema'] = '<a href="' . wp_nonce_url(admin_url('admin.php?page=artitechcore-main&action=generate_schema&post=' . $post->ID), 'generate_schema_' . $post->ID) . '">Generate Schema</a>';
    } else {
        $actions['regenerate_schema'] = '<a href="' . wp_nonce_url(admin_url('admin.php?page=artitechcore-main&action=regenerate_schema&post=' . $post->ID), 'regenerate_schema_' . $post->ID) . '">Regenerate Schema</a>';
        $actions['remove_schema'] = '<a href="' . wp_nonce_url(admin_url('admin.php?page=artitechcore-main&action=remove_schema&post=' . $post->ID), 'remove_schema_' . $post->ID) . '" onclick="return confirm(\'Are you sure you want to remove schema from this page?\')">Remove Schema</a>';
    }

    return $actions;
}
add_filter('page_row_actions', 'artitechcore_add_schema_quick_actions', 10, 2);

// Handle schema generation actions
function artitechcore_handle_schema_generation_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'artitechcore-main') {
        return;
    }

    if (isset($_GET['action']) && isset($_GET['post']) && isset($_GET['_wpnonce'])) {
        $action = sanitize_text_field($_GET['action']);
        $post_id = intval($_GET['post']);

        if ($action === 'generate_schema') {
            if (wp_verify_nonce($_GET['_wpnonce'], 'generate_schema_' . $post_id)) {
                artitechcore_generate_schema_markup($post_id);
                wp_redirect(admin_url('admin.php?page=artitechcore-main&tab=schema&schema_generated=1'));
                exit;
            }
        } elseif ($action === 'regenerate_schema') {
            if (wp_verify_nonce($_GET['_wpnonce'], 'regenerate_schema_' . $post_id)) {
                artitechcore_generate_schema_markup($post_id);
                wp_redirect(admin_url('admin.php?page=artitechcore-main&tab=schema&schema_regenerated=1'));
                exit;
            }
        }
    }
}
add_action('admin_init', 'artitechcore_handle_schema_generation_actions');

// Add admin notices for schema generation
function artitechcore_schema_generation_notices() {
    if (isset($_GET['schema_generated']) && sanitize_key($_GET['schema_generated']) == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Schema generated successfully!', 'artitechcore') . '</p></div>';
    }
    if (isset($_GET['schema_regenerated']) && sanitize_key($_GET['schema_regenerated']) == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Schema regenerated successfully!', 'artitechcore') . '</p></div>';
    }
}
add_action('admin_notices', 'artitechcore_schema_generation_notices');

// Remove schema from a page
function artitechcore_remove_schema_from_page($post_id) {
    return artitechcore_delete_schema_data($post_id, 'post');
}

// Handle schema removal actions
function artitechcore_handle_schema_removal_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'artitechcore-main') {
        return;
    }

    if (isset($_GET['action']) && isset($_GET['post']) && isset($_GET['_wpnonce'])) {
        $action = sanitize_text_field($_GET['action']);
        $post_id = intval($_GET['post']);

        if ($action === 'remove_schema') {
            if (wp_verify_nonce($_GET['_wpnonce'], 'remove_schema_' . $post_id)) {
                artitechcore_remove_schema_from_page($post_id);
                wp_redirect(admin_url('admin.php?page=artitechcore-main&tab=schema&schema_removed=1'));
                exit;
            }
        }
    }
}
add_action('admin_init', 'artitechcore_handle_schema_removal_actions');

// Add admin notices for schema removal
function artitechcore_schema_removal_notices() {
    if (isset($_GET['schema_removed']) && sanitize_key($_GET['schema_removed']) == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Schema removed successfully!', 'artitechcore') . '</p></div>';
    }
}
add_action('admin_notices', 'artitechcore_schema_removal_notices');

// Schema generator tab content (enhanced with management dashboard)
function artitechcore_schema_generator_tab() {
    // Use the enhanced management dashboard
    artitechcore_schema_management_dashboard();
}

// Enhanced schema management dashboard
function artitechcore_schema_management_dashboard() {
    // Get all public post types (pages, posts, CPTs) for schema management
    $post_types = get_post_types(['public' => true], 'names');
    unset($post_types['attachment'], $post_types['revision'], $post_types['nav_menu_item']);
    $allowed_post_types = array_values($post_types);

    // Server-side filters + pagination (also used by bulk actions)
    $filter_post_type = isset($_GET['artitechcore_post_type']) ? sanitize_key($_GET['artitechcore_post_type']) : '';
    $filter_status = isset($_GET['artitechcore_status']) ? sanitize_key($_GET['artitechcore_status']) : '';
    $filter_search = isset($_GET['artitechcore_search']) ? sanitize_text_field(wp_unslash($_GET['artitechcore_search'])) : '';
    if ($filter_post_type && !in_array($filter_post_type, $allowed_post_types, true)) {
        $filter_post_type = '';
    }

    $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $per_page = 50;

    $query_args = [
        'post_type' => $filter_post_type ? [$filter_post_type] : $allowed_post_types,
        'post_status' => $filter_status ? [$filter_status] : 'any',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'orderby' => 'title',
        'order' => 'ASC',
        's' => $filter_search ? $filter_search : '',
    ];

    $get_filtered_ids = function () use ($query_args) {
        $ids = [];
        $page = 1;
        $batch = 200;

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ignore_user_abort(true);

        while (true) {
            $q = new WP_Query(array_merge($query_args, [
                'posts_per_page' => $batch,
                'paged' => $page,
                'fields' => 'ids',
                'no_found_rows' => true,
                'orderby' => 'ID',
                'order' => 'DESC',
            ]));

            if (empty($q->posts)) {
                break;
            }

            foreach ($q->posts as $pid) {
                $ids[] = (int)$pid;
            }

            if (count($q->posts) < $batch) {
                break;
            }
            $page++;
        }

        return $ids;
    };

    // Handle bulk actions
    if (isset($_POST['bulk_schema_action']) && check_admin_referer('artitechcore_bulk_schema_action')) {
        $action = sanitize_text_field($_POST['bulk_schema_action']);
        $apply_scope = isset($_POST['bulk_apply_scope']) ? sanitize_key($_POST['bulk_apply_scope']) : 'selected';
        $selected_pages = isset($_POST['selected_pages']) ? array_map('intval', (array)$_POST['selected_pages']) : [];

        // Rehydrate filters from POST so bulk acts on the same filtered set
        if (isset($_POST['artitechcore_post_type'])) {
            $filter_post_type = sanitize_key($_POST['artitechcore_post_type']);
            if ($filter_post_type && !in_array($filter_post_type, $allowed_post_types, true)) {
                $filter_post_type = '';
            }
            $query_args['post_type'] = $filter_post_type ? [$filter_post_type] : $allowed_post_types;
        }
        if (isset($_POST['artitechcore_status'])) {
            $filter_status = sanitize_key($_POST['artitechcore_status']);
            $query_args['post_status'] = $filter_status ? [$filter_status] : 'any';
        }
        if (isset($_POST['artitechcore_search'])) {
            $filter_search = sanitize_text_field(wp_unslash($_POST['artitechcore_search']));
            $query_args['s'] = $filter_search ? $filter_search : '';
        }

        $target_ids = [];
        if ($apply_scope === 'filtered') {
            $target_ids = $get_filtered_ids();
        } else {
            $target_ids = $selected_pages;
        }

        if (!empty($target_ids)) {
            $processed = 0;
            $failed = 0;

            // Increase resources for bulk processing - Production Grade check
            $time_limit = 600;
            if (function_exists('set_time_limit') && !in_array('set_time_limit', explode(',', ini_get('disable_functions')))) {
                @set_time_limit($time_limit);
            }
            @ini_set('memory_limit', '512M');

            // Prune logs table periodically (Senior practice: don't let logs grow forever)
            artitechcore_prune_generation_logs();

            foreach ($target_ids as $page_id) {
                if (!current_user_can('edit_post', $page_id)) {
                    continue;
                }
                if ($action === 'generate' || $action === 'regenerate') {
                    try {
                        // Re-increase timeout for each loop iteration to reset the clock if possible
                        if (function_exists('set_time_limit')) {
                            @set_time_limit(300);
                        }
                        artitechcore_generate_schema_markup($page_id);
                        $processed++;
                    } catch (Exception $e) {
                        error_log('ArtitechCore Bulk Schema Error (Post ' . $page_id . '): ' . $e->getMessage());
                        $failed++;
                    } catch (Throwable $t) {
                        error_log('ArtitechCore Bulk Schema Fatal (Post ' . $page_id . '): ' . $t->getMessage());
                        $failed++;
                    }
                } elseif ($action === 'remove') {
                    artitechcore_remove_schema_from_page($page_id);
                    $processed++;
                }
            }
            
            $message = sprintf(
                esc_html__('Processed %d pages successfully! (%d failed)', 'artitechcore'),
                $processed,
                $failed
            );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }

    $posts_q = new WP_Query($query_args);
    $pages = $posts_q->posts;

    // Lightweight stats using found_posts + direct SQL for schema counts.
    // Avoids the previous O(n) approach that loaded every post's meta.
    global $wpdb;
    $schema_stats = [
        'total' => (int) $posts_q->found_posts,
        'with_schema' => 0,
        'types' => []
    ];

    // Count posts that have schema in the custom table
    $table_name = $wpdb->prefix . 'artitechcore_schema_data';
    
    // Build the post_type IN clause for the same filter set
    $stat_post_types = $filter_post_type ? [$filter_post_type] : $allowed_post_types;
    $pt_placeholders = implode(',', array_fill(0, count($stat_post_types), '%s'));
    $stat_statuses = $filter_status ? [$filter_status] : ['publish', 'draft', 'pending', 'private', 'future', 'trash'];
    $st_placeholders = implode(',', array_fill(0, count($stat_statuses), '%s'));

    // Query schema type distribution from custom table
    $prepare_args = array_merge($stat_post_types, $stat_statuses);
    $type_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT schema_type, COUNT(*) AS cnt
         FROM $table_name
         WHERE object_type = 'post'
           AND object_id IN (
               SELECT ID FROM {$wpdb->posts}
               WHERE post_type IN ($pt_placeholders)
               AND post_status IN ($st_placeholders)
           )
         GROUP BY schema_type",
        ...$prepare_args
    ));

    if ($type_rows) {
        foreach ($type_rows as $row) {
            $schema_stats['types'][sanitize_text_field($row->schema_type)] = (int) $row->cnt;
            $schema_stats['with_schema'] += (int) $row->cnt;
        }
    }
    ?>
    <div class="wrap artitechcore-schema-dashboard">
        <p>Manage structured data (schema.org) markup for your pages to improve SEO and search visibility.</p>

        <!-- Export -->
        <div class="artitechcore-bulk-actions" style="margin-top: 10px;">
            <h2>Export</h2>
            <p class="description" style="margin-top:0;">
                Export a CSV backup of all schema stored in the custom high-performance database table.
            </p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=artitechcore_export_schema_csv'), 'artitechcore_export_schema_csv')); ?>">
                    Export Schema CSV
                </a>
            </p>
        </div>

        <!-- Schema Statistics -->
        <div class="artitechcore-schema-stats">
            <h2>Schema Statistics</h2>
            <div class="artitechcore-stats-grid">
                <div class="artitechcore-stat-card">
                    <h3><?php echo esc_html($schema_stats['total']); ?></h3>
                    <p>Total Items</p>
                </div>
                <div class="artitechcore-stat-card">
                    <h3><?php echo esc_html($schema_stats['with_schema']); ?></h3>
                    <p>Items with Schema</p>
                </div>
                <div class="artitechcore-stat-card">
                    <h3><?php echo esc_html($schema_stats['total'] - $schema_stats['with_schema']); ?></h3>
                    <p>Items without Schema</p>
                </div>
                <div class="artitechcore-stat-card">
                    <h3><?php echo esc_html($schema_stats['total'] > 0 ? round(($schema_stats['with_schema'] / $schema_stats['total']) * 100, 1) : 0); ?>%</h3>
                    <p>Schema Coverage</p>
                </div>
            </div>
            
            <?php if (!empty($schema_stats['types'])): ?>
            <h3>Schema Type Distribution</h3>
            <div class="artitechcore-schema-types">
                <?php foreach ($schema_stats['types'] as $type => $count): ?>
                <div class="artitechcore-schema-type">
                    <span class="artitechcore-schema-badge artitechcore-schema-<?php echo esc_attr($type); ?>">
                        <?php echo esc_html(ucfirst($type)); ?>
                    </span>
                    <span class="artitechcore-schema-count"><?php echo esc_html($count); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
               <!-- EXPORT BUTTON MOVED HERE -->
        <p>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&action=export_schema_csv'), 'export_schema_csv')); ?>" 
               class="button button-secondary">Export All Schema to CSV</a>
        </p>

        <!-- Main Branded Workspace -->
        <div class="dg10-brand">
            <!-- Filters + Bulk Actions -->
            <div class="artitechcore-schema-section" style="margin-bottom: 24px;">
                <form method="get" action="" style="margin-bottom: 20px;">
                    <input type="hidden" name="page" value="<?php echo esc_attr(sanitize_text_field($_GET['page'] ?? 'artitechcore-main')); ?>">
                    <input type="hidden" name="tab" value="schema">

                    <!-- Vertical Stack Filter Form -->
                    <div style="display: flex; flex-direction: column; gap: 15px; width: 300px; max-width: 100%;">
                        <label style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 13px;">
                            Post Type
                            <select name="artitechcore_post_type" id="artitechcore-filter-post-type" style="width: 100%; max-width: 100%;">
                                <option value="">All</option>
                                <?php foreach ($allowed_post_types as $pt): ?>
                                    <?php $obj = get_post_type_object($pt); ?>
                                    <option value="<?php echo esc_attr($pt); ?>" <?php selected($filter_post_type, $pt); ?>>
                                        <?php echo esc_html($obj ? $obj->labels->singular_name : $pt); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 13px;">
                            Status
                            <select name="artitechcore_status" id="artitechcore-filter-status" style="width: 100%; max-width: 100%;">
                                <option value="" <?php selected($filter_status, ''); ?>>All</option>
                                <option value="publish" <?php selected($filter_status, 'publish'); ?>>Publish</option>
                                <option value="draft" <?php selected($filter_status, 'draft'); ?>>Draft</option>
                                <option value="pending" <?php selected($filter_status, 'pending'); ?>>Pending</option>
                                <option value="private" <?php selected($filter_status, 'private'); ?>>Private</option>
                            </select>
                        </label>

                        <label style="display: flex; flex-direction: column; gap: 6px; font-weight: 600; font-size: 13px;">
                            Search
                            <input type="search" name="artitechcore_search" id="artitechcore-filter-search" value="<?php echo esc_attr($filter_search); ?>" placeholder="Search title..." style="width: 100%; max-width: 100%;" />
                        </label>

                        <div style="display: flex; gap: 10px; margin-top: 5px;">
                            <button type="submit" class="button" style="flex: 1;">Filter Results</button>
                            <?php if ($filter_post_type || $filter_status || $filter_search): ?>
                                <a class="button button-link" href="<?php echo esc_url(admin_url('admin.php?page=artitechcore-main&tab=schema')); ?>">Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <hr style="margin-top: 24px; margin-bottom: 24px; border: 0; border-top: 1px solid #e2e8f0;"/>

                <form method="post" action="">
                    <?php wp_nonce_field('artitechcore_bulk_schema_action'); ?>
                    <!-- Preserve filter context on submit -->
                    <input type="hidden" name="artitechcore_post_type" value="<?php echo esc_attr($filter_post_type); ?>">
                    <input type="hidden" name="artitechcore_status" value="<?php echo esc_attr($filter_status); ?>">
                    <input type="hidden" name="artitechcore_search" value="<?php echo esc_attr($filter_search); ?>">
                    
                    <!-- Vertical Stack Bulk Actions -->
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                        <h3 style="margin:0; font-size: 14px; font-weight: 600;">Bulk Apply Schema</h3>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <select name="bulk_schema_action" required style="min-width: 160px;">
                                <option value="">Select Action...</option>
                                <option value="generate">Generate Schema</option>
                                <option value="remove">Remove Schema</option>
                            </select>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="radio" name="bulk_apply_scope" value="selected" checked>
                                Selected (this page)
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="radio" name="bulk_apply_scope" value="filtered">
                                All filtered results
                            </label>
                            <button type="submit" class="button button-primary" style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); border: none;">Apply</button>
                        </div>
                    </div>
                    
                    <!-- Pages Table -->
                    <div class="artitechcore-schema-table-wrap">
                    <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="select-all-pages">
                            </td>
                            <th class="manage-column">Page Title</th>
                            <th class="manage-column">Status</th>
                            <th class="manage-column">Schema Type</th>
                            <th class="manage-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $pages = $posts_q->posts;
                        $post_ids = wp_list_pluck($pages, 'ID');
                        $schema_lookup = [];
                        if (!empty($post_ids)) {
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'artitechcore_schema_data';
                            $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
                            $results = $wpdb->get_results($wpdb->prepare(
                                "SELECT * FROM $table_name WHERE object_id IN ($placeholders) AND object_type = 'post'",
                                ...$post_ids
                            ));
                            foreach ($results as $res) {
                                $schema_lookup[$res->object_id] = $res;
                            }
                        }
                        ?>
                        <?php foreach ($pages as $page): ?>
                        <?php 
                        $schema_row = isset($schema_lookup[$page->ID]) ? $schema_lookup[$page->ID] : null;
                        $schema_type = $schema_row ? $schema_row->schema_type : '';
                        ?>
                        <tr data-post-type="<?php echo esc_attr($page->post_type); ?>" data-post-status="<?php echo esc_attr($page->post_status); ?>">
                            <th class="check-column">
                                <input type="checkbox" name="selected_pages[]" value="<?php echo esc_attr($page->ID); ?>">
                            </th>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url(get_edit_post_link($page->ID)); ?>">
                                        <?php echo esc_html($page->post_title); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo esc_url(get_permalink($page->ID)); ?>" target="_blank">View</a> |
                                    </span>
                                    <span class="edit">
                                        <a href="<?php echo esc_url(get_edit_post_link($page->ID)); ?>">Edit</a>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="artitechcore-page-status status-<?php echo esc_attr($page->post_status); ?>">
                                    <?php echo esc_html(ucfirst($page->post_status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($schema_type)): ?>
                                    <span class="artitechcore-schema-badge artitechcore-schema-<?php echo esc_attr($schema_type); ?>">
                                        <?php echo esc_html(ucfirst($schema_type)); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="artitechcore-schema-badge artitechcore-schema-none">No Schema</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="artitechcore-schema-actions">
                                    <?php if (empty($schema_type)): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&action=generate_schema&post=' . $page->ID), 'generate_schema_' . $page->ID)); ?>" 
                                           class="button button-small">Generate Schema</a>
                                    <?php else: ?>
                                        <button type="button" class="button button-small artitechcore-preview-schema" data-page-id="<?php echo esc_attr($page->ID); ?>">Preview</button>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&action=regenerate_schema&post=' . $page->ID), 'regenerate_schema_' . $page->ID)); ?>" 
                                           class="button button-small">Regenerate</a>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&action=remove_schema&post=' . $page->ID), 'remove_schema_' . $page->ID)); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('Are you sure you want to remove schema from this page?')">Remove</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </form>

            <?php
            // Pagination
            $total_pages = (int)$posts_q->max_num_pages;
            if ($total_pages > 1) {
                $base_url = remove_query_arg('paged');
                echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 16px 0;">';
                echo paginate_links([
                    'base' => esc_url_raw(add_query_arg('paged', '%#%', $base_url)),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged,
                ]);
                echo '</div></div>';
            }
            ?>
        </div>

        <!-- Taxonomy / Term Archives (lightweight; avoids heavy count queries) -->
        <div class="artitechcore-schema-section" style="margin-top: 24px;">
            <h2>Taxonomy Archives</h2>
            <p class="description" style="margin-top:0;">
                Manage schema for category/tag/custom taxonomy archives. (Showing up to 20 terms per filter to keep the dashboard fast.)
            </p>

            <?php
            $public_tax = get_taxonomies(['public' => true], 'objects');
            $tax_filter = isset($_GET['artitechcore_taxonomy']) ? sanitize_key($_GET['artitechcore_taxonomy']) : '';
            $tax_search = isset($_GET['artitechcore_term_search']) ? sanitize_text_field(wp_unslash($_GET['artitechcore_term_search'])) : '';
            if ($tax_filter && !isset($public_tax[$tax_filter])) {
                $tax_filter = '';
            }

            $terms = get_terms([
                'taxonomy' => $tax_filter ? [$tax_filter] : array_keys($public_tax),
                'hide_empty' => false,
                'number' => 20,
                'search' => $tax_search ?: '',
            ]);
            ?>

            <form method="get" action="" style="margin-bottom: 12px;">
                <input type="hidden" name="page" value="<?php echo esc_attr(sanitize_text_field($_GET['page'] ?? 'artitechcore-main')); ?>">
                <input type="hidden" name="tab" value="schema">

                <div class="artitechcore-bulk-controls" style="margin-bottom: 12px;">
                    <label>
                        <span style="display:inline-block; min-width:90px;">Taxonomy</span>
                        <select name="artitechcore_taxonomy">
                            <option value="">All</option>
                            <?php foreach ($public_tax as $tax_name => $tax_obj): ?>
                                <option value="<?php echo esc_attr($tax_name); ?>" <?php selected($tax_filter, $tax_name); ?>>
                                    <?php echo esc_html($tax_obj->labels->singular_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label style="flex: 1;">
                        <span style="display:inline-block; min-width:90px;">Search</span>
                        <input type="search" name="artitechcore_term_search" class="regular-text" value="<?php echo esc_attr($tax_search); ?>" placeholder="Search terms..." />
                    </label>
                    <button type="submit" class="button">Filter</button>
                    <?php if ($tax_filter || $tax_search): ?>
                        <a class="button button-link" href="<?php echo esc_url(admin_url('admin.php?page=artitechcore-main&tab=schema')); ?>">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="artitechcore-schema-table-wrap">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th class="manage-column">Term</th>
                    <th class="manage-column">Taxonomy</th>
                    <th class="manage-column">Schema</th>
                    <th class="manage-column">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (is_wp_error($terms) || empty($terms)): ?>
                    <tr><td colspan="4">No terms found.</td></tr>
                <?php else: ?>
                    <?php
                    $term_ids = wp_list_pluck($terms, 'term_id');
                    $term_schema_lookup = [];
                    if (!empty($term_ids)) {
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'artitechcore_schema_data';
                        $placeholders = implode(',', array_fill(0, count($term_ids), '%d'));
                        $results = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM $table_name WHERE object_id IN ($placeholders) AND object_type = 'term'",
                            ...$term_ids
                        ));
                        foreach ($results as $res) {
                            $term_schema_lookup[$res->object_id] = $res;
                        }
                    }
                    ?>
                    <?php foreach ($terms as $term): ?>
                        <?php
                        $t_row = isset($term_schema_lookup[$term->term_id]) ? $term_schema_lookup[$term->term_id] : null;
                        $has_schema = !empty($t_row);
                        $t_source = $t_row ? $t_row->origin : '';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($term->name); ?></strong></td>
                            <td><?php echo esc_html($term->taxonomy); ?></td>
                            <td>
                                <?php if ($has_schema): ?>
                                    <span class="artitechcore-schema-badge artitechcore-schema-webpage">Yes</span>
                                    <span class="description" style="margin-left:8px;"><?php echo esc_html($t_source ?: 'unknown'); ?></span>
                                <?php else: ?>
                                    <span class="artitechcore-schema-badge artitechcore-schema-none">No Schema</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="artitechcore-schema-actions">
                                    <?php if (!$has_schema): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&tab=schema&action=generate_term_schema&taxonomy=' . $term->taxonomy . '&term_id=' . $term->term_id), 'artitechcore_generate_term_schema_' . $term->term_id)); ?>" class="button button-small">Generate</a>
                                    <?php else: ?>
                                        <button type="button" class="button button-small artitechcore-preview-schema-term" data-term-id="<?php echo esc_attr($term->term_id); ?>" data-taxonomy="<?php echo esc_attr($term->taxonomy); ?>">Preview</button>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&tab=schema&action=regenerate_term_schema&taxonomy=' . $term->taxonomy . '&term_id=' . $term->term_id), 'artitechcore_regenerate_term_schema_' . $term->term_id)); ?>" class="button button-small">Regenerate</a>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=artitechcore-main&tab=schema&action=remove_term_schema&taxonomy=' . $term->taxonomy . '&term_id=' . $term->term_id), 'artitechcore_remove_term_schema_' . $term->term_id)); ?>" class="button button-small button-link-delete" onclick="return confirm('Remove schema for this term?')">Remove</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
        <!-- Schema Modal -->
        <div id="artitechcore-schema-modal" class="artitechcore-modal-overlay" style="display:none;">
            <div class="artitechcore-modal artitechcore-modal-large">
                <div class="artitechcore-modal-header">
                    <h2>Schema Preview</h2>
                    <span class="artitechcore-modal-close">&times;</span>
                </div>
                <div class="artitechcore-modal-body">
                    <p class="description" style="margin-top:0;" id="artitechcore-schema-preview-hint">
                        Preview the JSON-LD below. If you need to adjust it, click <strong>Edit Schema</strong>.
                    </p>
                    <pre style="margin-top:12px;"><code id="artitechcore-schema-preview-code"></code></pre>

                    <div id="artitechcore-schema-editor-wrap" style="display:none; margin-top: 12px;">
                        <p class="description" style="margin-top:0;">
                            You are now editing the schema. Click <strong>Save</strong> to store it for this post and use it on the frontend.
                        </p>
                        <textarea id="artitechcore-schema-editor" class="large-text code" rows="18" spellcheck="false"></textarea>
                    </div>
                    <input type="hidden" id="artitechcore-schema-page-id" value="">
                    <input type="hidden" id="artitechcore-schema-entity-type" value="post">
                </div>
                <div class="artitechcore-modal-footer">
                    <span id="artitechcore-schema-save-status" style="margin-right:auto;"></span>
                    <button type="button" class="button" id="artitechcore-schema-edit-toggle">Edit Schema</button>
                    <button type="button" class="button" id="artitechcore-schema-validate" style="display:none;">Validate JSON</button>
                    <button type="button" class="button button-primary" id="artitechcore-schema-save" style="display:none;">Save Schema</button>
                </div>
            </div>
        </div>

        <!-- Schema Information -->
        <div class="artitechcore-schema-info">
            <h2>About Schema Markup</h2>
            <div class="artitechcore-info-grid">
                <div class="artitechcore-info-card">
                    <h3>What is Schema Markup?</h3>
                    <p>Schema.org markup helps search engines understand your content better, which can lead to rich snippets in search results and improved click-through rates.</p>
                </div>
                <div class="artitechcore-info-card">
                    <h3>Where is Schema Inserted?</h3>
                    <p>Schema markup is automatically inserted in the <code>&lt;head&gt;</code> section of your pages as JSON-LD structured data. It's invisible to visitors but visible to search engines.</p>
                </div>
                <div class="artitechcore-info-card">
                    <h3>Manual Removal</h3>
                    <p>To manually remove schema from a page, use the <b>Remove</b> action in the Schema Management dashboard above.</p>
                </div>
                <div class="artitechcore-info-card">
                    <h3>AI-Powered Detection</h3>
                    <p>The plugin uses AI to analyze your content and automatically determine the most appropriate schema type for each page, with fallback to keyword-based detection.</p>
                </div>
            </div>
        </div>
    </div>


    <?php
}

// AJAX handler for schema preview
function artitechcore_ajax_get_schema_preview() {
    check_ajax_referer('artitechcore_schema_preview', 'nonce');

    $page_id = isset($_POST['page_id']) ? absint($_POST['page_id']) : 0;
    if (!$page_id) {
        wp_send_json_error(['message' => esc_html__('Invalid page ID', 'artitechcore')]);
    }

    if (!current_user_can('edit_post', $page_id)) {
        wp_send_json_error(['message' => esc_html__('Unauthorized', 'artitechcore')], 403);
    }

    $schema_row = artitechcore_get_schema_data($page_id, 'post');
    if (empty($schema_row)) {
        wp_send_json_error(['message' => esc_html__('No schema data found for this item. Generate schema first, then preview again.', 'artitechcore')]);
    }

    $schema_data = $schema_row['schema_data'];
    $schema_json = is_string($schema_data)
        ? trim($schema_data)
        : wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if ($schema_json === '' || $schema_json === false) {
        wp_send_json_error(['message' => esc_html__('Schema data exists but could not be encoded as JSON.', 'artitechcore')]);
    }

    wp_send_json_success([
        'post_id' => $page_id,
        'schema_json' => $schema_json,
        'schema_type' => $schema_row['schema_type'],
        'schema_source' => $schema_row['origin'],
        'locked' => !empty($schema_row['is_locked']),
    ]);
}
add_action('wp_ajax_artitechcore_get_schema_preview', 'artitechcore_ajax_get_schema_preview');

// AJAX handler for term schema preview
function artitechcore_ajax_get_term_schema_preview() {
    check_ajax_referer('artitechcore_schema_preview', 'nonce');

    if (!current_user_can('manage_categories')) {
        wp_send_json_error(['message' => esc_html__('Unauthorized', 'artitechcore')], 403);
    }

    $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_key($_POST['taxonomy']) : '';
    if (!$term_id || !$taxonomy) {
        wp_send_json_error(['message' => esc_html__('Invalid term.', 'artitechcore')]);
    }

    $term = get_term($term_id, $taxonomy);
    if (!$term || is_wp_error($term)) {
        wp_send_json_error(['message' => esc_html__('Term not found.', 'artitechcore')]);
    }

    $schema_row = artitechcore_get_schema_data($term_id, 'term');
    if (empty($schema_row)) {
        wp_send_json_error(['message' => esc_html__('No schema data found for this term. Generate schema first, then preview again.', 'artitechcore')]);
    }

    $schema_data = $schema_row['schema_data'];
    $schema_json = is_string($schema_data)
        ? trim($schema_data)
        : wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if ($schema_json === '' || $schema_json === false) {
        wp_send_json_error(['message' => esc_html__('Schema data exists but could not be encoded as JSON.', 'artitechcore')]);
    }

    wp_send_json_success([
        'term_id' => $term_id,
        'taxonomy' => $taxonomy,
        'schema_json' => $schema_json,
        'schema_source' => $schema_row['origin'],
        'locked' => !empty($schema_row['is_locked']),
    ]);
}
add_action('wp_ajax_artitechcore_get_term_schema_preview', 'artitechcore_ajax_get_term_schema_preview');

// AJAX handler to save edited schema JSON-LD to post meta
function artitechcore_ajax_save_schema_override() {
    check_ajax_referer('artitechcore_schema_preview', 'nonce');

    $page_id = isset($_POST['page_id']) ? absint($_POST['page_id']) : 0;
    if (!$page_id) {
        wp_send_json_error(['message' => esc_html__('Invalid page ID', 'artitechcore')]);
    }

    if (!current_user_can('edit_post', $page_id)) {
        wp_send_json_error(['message' => esc_html__('Unauthorized', 'artitechcore')], 403);
    }

    $raw = isset($_POST['schema_json']) ? wp_unslash($_POST['schema_json']) : '';
    $raw = trim($raw);
    if ($raw === '') {
        wp_send_json_error(['message' => esc_html__('Schema JSON is empty.', 'artitechcore')]);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        wp_send_json_error(['message' => esc_html__('Invalid JSON. Please fix and try again.', 'artitechcore')]);
    }

    // Basic sanity: schema should be object-like (associative array) or a list of nodes
    $encoded = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!$encoded) {
        wp_send_json_error(['message' => esc_html__('Could not encode schema JSON.', 'artitechcore')]);
    }

    // Get existing type to preserve it if not provided
    $current = artitechcore_get_schema_data($page_id, 'post');
    $type = $current ? $current['schema_type'] : 'webpage';

    artitechcore_save_schema_data($page_id, $decoded, $type, 'post', 'override', 1);

    wp_send_json_success([
        'message' => esc_html__('Schema saved.', 'artitechcore'),
        'data' => $decoded,
    ]);
}
add_action('wp_ajax_artitechcore_save_schema_override', 'artitechcore_ajax_save_schema_override');

function artitechcore_ajax_save_term_schema_override() {
    check_ajax_referer('artitechcore_schema_preview', 'nonce');

    if (!current_user_can('manage_categories')) {
        wp_send_json_error(['message' => esc_html__('Unauthorized', 'artitechcore')], 403);
    }

    $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_key($_POST['taxonomy']) : '';
    if (!$term_id || !$taxonomy) {
        wp_send_json_error(['message' => esc_html__('Invalid term.', 'artitechcore')]);
    }

    $raw = isset($_POST['schema_json']) ? wp_unslash($_POST['schema_json']) : '';
    $raw = trim($raw);
    if ($raw === '') {
        wp_send_json_error(['message' => esc_html__('Schema JSON is empty.', 'artitechcore')]);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        wp_send_json_error(['message' => esc_html__('Invalid JSON. Please fix and try again.', 'artitechcore')]);
    }

    $encoded = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!$encoded) {
        wp_send_json_error(['message' => esc_html__('Could not encode schema JSON.', 'artitechcore')]);
    }

    // Get existing type to preserve it if not provided
    $current = artitechcore_get_schema_data($term_id, 'term');
    $type = $current ? $current['schema_type'] : 'CollectionPage';

    artitechcore_save_schema_data($term_id, $decoded, $type, 'term', 'override', 1);

    wp_send_json_success([
        'message' => esc_html__('Schema saved.', 'artitechcore'),
        'data' => $decoded,
    ]);
}
add_action('wp_ajax_artitechcore_save_term_schema_override', 'artitechcore_ajax_save_term_schema_override');

function artitechcore_remove_schema_from_term($term_id) {
    return artitechcore_delete_schema_data($term_id, 'term');
}

// Handle term schema management actions
function artitechcore_handle_term_schema_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'artitechcore-main') {
        return;
    }
    if (!isset($_GET['tab']) || $_GET['tab'] !== 'schema') {
        return;
    }

    if (!isset($_GET['action'], $_GET['term_id'], $_GET['taxonomy'], $_GET['_wpnonce'])) {
        return;
    }

    if (!current_user_can('manage_categories')) {
        return;
    }

    $action = sanitize_key($_GET['action']);
    $term_id = absint($_GET['term_id']);
    $taxonomy = sanitize_key($_GET['taxonomy']);

    if (!$term_id || !$taxonomy) {
        return;
    }

    if ($action === 'generate_term_schema' && wp_verify_nonce($_GET['_wpnonce'], 'artitechcore_generate_term_schema_' . $term_id)) {
        artitechcore_generate_term_schema_markup($term_id, $taxonomy, true);
        wp_redirect(admin_url('admin.php?page=artitechcore-main&tab=schema&term_schema_generated=1'));
        exit;
    }

    if ($action === 'regenerate_term_schema' && wp_verify_nonce($_GET['_wpnonce'], 'artitechcore_regenerate_term_schema_' . $term_id)) {
        // Regenerate should overwrite lock
        delete_term_meta($term_id, '_artitechcore_schema_locked');
        artitechcore_generate_term_schema_markup($term_id, $taxonomy, true);
        wp_redirect(admin_url('admin.php?page=artitechcore-main&tab=schema&term_schema_regenerated=1'));
        exit;
    }

    if ($action === 'remove_term_schema' && wp_verify_nonce($_GET['_wpnonce'], 'artitechcore_remove_term_schema_' . $term_id)) {
        artitechcore_remove_schema_from_term($term_id);
        wp_redirect(admin_url('admin.php?page=artitechcore-main&tab=schema&term_schema_removed=1'));
        exit;
    }
}
add_action('admin_init', 'artitechcore_handle_term_schema_actions');

function artitechcore_term_schema_notices() {
    if (isset($_GET['term_schema_generated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Term schema generated successfully!', 'artitechcore') . '</p></div>';
    }
    if (isset($_GET['term_schema_regenerated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Term schema regenerated successfully!', 'artitechcore') . '</p></div>';
    }
    if (isset($_GET['term_schema_removed'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Term schema removed successfully!', 'artitechcore') . '</p></div>';
    }
}
add_action('admin_notices', 'artitechcore_term_schema_notices');

// Admin-post handler: export schema CSV backup
function artitechcore_admin_export_schema_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized', 'artitechcore'), 403);
    }
    check_admin_referer('artitechcore_export_schema_csv');

    // Query all objects that have schema stored in the custom table
    global $wpdb;
    $table_name = $wpdb->prefix . 'artitechcore_schema_data';
    $rows = $wpdb->get_results("SELECT * FROM $table_name");

    $filename = 'artitechcore-schema-export-' . gmdate('Y-m-d-His') . '.csv';
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    if (!$out) {
        wp_die(esc_html__('Could not open output stream.', 'artitechcore'));
    }

    // Header row
    fputcsv($out, [
        'entity_type',
        'id',
        'type_or_taxonomy',
        'status',
        'title_or_name',
        'permalink',
        'last_modified_gmt',
        'schema_type',
        'origin',
        'is_locked',
        'schema_data',
    ]);

    foreach ((array)$rows as $row) {
        $object_id = $row->object_id;
        $object_type = $row->object_type;
        $schema_json = $row->schema_data;
        $schema_type = $row->schema_type;
        $origin = $row->origin;
        $locked = $row->is_locked;

        if ($object_type === 'post') {
            $post = get_post($object_id);
            if (!$post) continue;
            
            fputcsv($out, [
                'post',
                (int)$object_id,
                $post->post_type,
                $post->post_status,
                $post->post_title,
                get_permalink($object_id),
                get_post_modified_time('c', true, $object_id),
                $schema_type,
                $origin,
                !empty($locked) ? '1' : '0',
                $schema_json,
            ]);
        } else {
            $term = get_term($object_id);
            if (!$term || is_wp_error($term)) continue;
            
            $link = get_term_link($term);
            fputcsv($out, [
                'term',
                (int)$object_id,
                $term->taxonomy,
                '',
                $term->name,
                !is_wp_error($link) ? $link : '',
                '',
                $schema_type,
                $origin,
                !empty($locked) ? '1' : '0',
                $schema_json,
            ]);
        }
    }

    fclose($out);
    exit;
}
add_action('admin_post_artitechcore_export_schema_csv', 'artitechcore_admin_export_schema_csv');

// Prevent auto-regeneration from overwriting a user override
function artitechcore_should_skip_auto_schema_generation($post_id) {
    $schema = artitechcore_get_schema_data($post_id, 'post');
    return $schema && !empty($schema['is_locked']);
}

/**
 * Senior Developer Practice: Prune production logs to prevent database bloat.
 * Removes logs older than 30 days.
 */
function artitechcore_prune_generation_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'artitechcore_generation_logs';
    
    // Simple check to see if table exists before pruning
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE generation_time < %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
    }
}
/**
 * Helper to build a PostalAddress object from a string
 * Tries to perform basic parsing for better rich result compliance
 */
function artitechcore_build_postal_address($address_str) {
    if (empty($address_str)) return null;

    $address = [
        '@type' => 'PostalAddress',
        'streetAddress' => sanitize_text_field($address_str)
    ];

    // Simple heuristic-based parsing for richer data
    $parts = explode(',', $address_str);
    if (count($parts) >= 3) {
        $address['streetAddress'] = sanitize_text_field(trim($parts[0]));
        $address['addressLocality'] = sanitize_text_field(trim($parts[1]));
        
        // Try to split State/Zip if third part exists
        $state_zip = trim($parts[2]);
        if (preg_match('/^([A-Z]{2})\s+(\d{5}(?:-\d{4})?)$/i', $state_zip, $matches)) {
            $address['addressRegion'] = sanitize_text_field($matches[1]);
            $address['postalCode'] = sanitize_text_field($matches[2]);
        } else {
            $address['addressRegion'] = sanitize_text_field($state_zip);
        }

        if (count($parts) >= 4) {
            $address['addressCountry'] = sanitize_text_field(trim($parts[3]));
        }
    }

    return $address;
}
