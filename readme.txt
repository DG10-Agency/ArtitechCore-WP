=== ArtitechCore ===
Contributors: dg10agency
Tags: pages, schema markup, bulk creation, ai content, seo generator
Requires at least: 5.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ArtitechCore is the AI website builder and automatic schema.org JSON-LD plugin for WordPress: generate full sites from industry blueprints, auto-generate valid structured data (FAQ, Service, MedicalBusiness, Article, LocalBusiness) for rich results, and enhance content for SEO with OpenAI, Gemini, or DeepSeek.

== Description ==

**ArtitechCore ()** is a comprehensive solution designed to eliminate the manual labor of building WordPress websites. Built for agencies and power-users, it integrates leading AI providers (OpenAI, Google Gemini, DeepSeek) into a professional interface that manages everything from content hierarchy to structured data (Schema.org).

With ArtitechCore, you don't just "write pages"—you architect entire business ecosystems. The plugin understands your business goals and suggests the ideal Custom Post Types, categories, and page structures required for your industry.

= 🚀 Main Features in Detail =

*   **🤖 AI-Powered Content Architecture** - Go beyond text. ArtitechCore builds your site structure. It generates intelligent page hierarchies based on your business model.
*   **🏗️ Advanced CPT & Taxonomy Engine** - Create business-specific Custom Post Types (e.g., Doctors, Products, etc.) and link them to AI-suggested taxonomies. Business-critical fields (Price, Duration, Location) are automatically implemented.
*   **📊 Pro Schema Management Suite** - A dedicated dashboard to monitor your SEO coverage. Generate, edit, and bulk-manage JSON-LD schema (FAQ, Product, LocalBusiness, etc.) with a live code editor and export your entire schema set to CSV.
*   **📂 Intelligent CSV Bulk Import** - Deploy hundreds of SEO-optimized pages in seconds. Supports robust validation, parent-child relationships, and metadata mapping.
*   **🍔 Smart Menu Generator** - Instantly build navigation, service, and footer menus based on your site's hierarchy. Automatically organizes your content for best UX.
*   **✨ AI Content Enhancer & Conversion Booster** - Transform standard posts into high-converting articles. Automatically generate Key Takeaways (TL;DR), Smart Conclusions, and intelligent Call-to-Actions (CTAs) that adapt to your brand color.
*   **🎨 Premium DG10 Agency Design** - A glassmorphic, modern admin interface built for usability. High contrast, mobile-responsive, and visually stunning.
*   **⚡ High Performance Infrastructure** - Built with efficiency in mind. Sequential batch processing for bulk actions and optimized SQL counts to keep your dashboard lightning fast.
*   **♿ Full Accessibility** - 100% WCAG 2.1 AA compliant. Proper ARIA labels, focus management, and keyboard-first navigation are standard.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the **ArtitechCore** dashboard in your admin sidebar.
4. Go to **Settings** to add your OpenAI, Gemini, or DeepSeek API key to unlock the AI features.

== Frequently Asked Questions ==

= Does it support bulk schema generation? =
Yes! In the Schema Generator dashboard, you can filter your pages and apply "Generate" or "Remove" actions to all filtered results at once. It processes items in batches to prevent server timeouts.

= Can I export my schema data for auditing? =
Absolutely. There is a built-in CSV export button that captures all structured data stored for your Posts, Pages, and Taxonomies into a single portable file.

= Which AI provider do you recommend? =
For most content and structural generation, we strictly recommend **OpenAI**. For high-speed large-scale suggestions, Google Gemini is an excellent alternative. DeepSeek is also supported as a cost-effective option.

= Is the schema markup invisible to users? =
Yes. All schema is generated as JSON-LD and inserted into the `<head>` of your website. It is designed for search engines like Google and Bing and will not affect your frontend layout.

= Is my API Key secure? =
Yes, your API keys are stored securely in your WordPress database and are only used for direct server-to-server communication with the AI provider. Keys are never exposed to the frontend or third parties.

= How does the AI Content Enhancer improve SEO? =
By generating **Key Takeaways** at the top of the post, you capture search intent faster and improve "Dwell Time." The **Smart Conclusion** ensures a clean semantic structure, following SEO best practices for article endings.

= Can I keep my AI enhancements if I deactivate the plugin? =
Yes. In the Content Enhancer settings, you can enable "Persistence." When the plugin is deactivated or uninstalled, a lightweight "bridge" is created in your `mu-plugins` folder to ensure your Key Takeaways, Conclusions, and CTAs continue to display perfectly.

= What happens to my data if I uninstall the plugin? =
You can choose to keep SEO schemas and/or AI enhancement data after uninstallation. The Persistence Bridge feature maintains frontend output even without the active plugin. All data remains in the database unless you choose to delete it during uninstall.

== Requirements ==
* WordPress 5.6 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher (or MariaDB 10.0+)
* Valid API key from one of the supported AI providers (OpenAI, Google Gemini, or DeepSeek) for AI features
* Minimum 128MB PHP memory limit recommended for bulk processing

== Screenshots ==

1. **Manual Page Creation** - Build page hierarchies by hand with custom parent-child structure.
2. **Schema Generator Dashboard** - Coverage stats, per-type distribution, bulk generate/remove, CSV export.
3. **AI Ecosystem Architect** - Describe the business, get a full page ecosystem with AI.
4. **Settings: Providers & Brand Kit** - OpenAI/Gemini/DeepSeek keys, rate limits, brand identity.
5. **Website Builder Blueprints** - Dental, legal, restaurant, e-commerce, services, portfolio, corporate.
6. **Content Enhancer** - Bulk Key Takeaways (TL;DR), Smart Conclusions, adaptive CTAs.
7. **CSV Bulk Import** - Hundreds of validated SEO pages with parent-child mapping in seconds.
8. **Menu Generator** - Nav, service, and footer menus generated from your hierarchy.
9. **Page Hierarchy** - Visual sitemap to audit site structure at a glance.
10. **Keyword Analysis** - Density and on-page SEO signals per post.
11. **Custom Post Types** - Industry post types (Doctors, Products) with taxonomies.
12. **Post Templates** - Dynamic templates per post type for consistent output.

🎬 Video tour (26 sec, all features working live): https://github.com/DG10-Agency/ArtitechCore-WP/blob/main/.github/readme/artitechcore-tour.mp4

== Changelog ==

= 1.0.0 =
* **NEW**: Multiple schema rows per post/term (one per schema type) with merged @graph output — FAQ + Service + MedicalBusiness rows now coexist instead of overwriting each other. Includes automatic v2 database migration (dedupes legacy rows, keeps newest).
* **NEW**: Global Organization + WebSite schema fallback for non-singular pages (blog index, archives, search, 404) — these pages are never schema-less now.
* **FIX**: Singular output uses get_queried_object_id() with get_the_ID() fallback for reliable rendering in wp_head.
* **FIX**: Homepage fallback schema is now persisted to the database on first render instead of being rebuilt on every view.
* **FIX**: `artitechcore_skip_schema_output` filter now genuinely suppresses output (previously it printed an "additive" note and kept rendering).
* **IMPROVED**: Schema @type emits the single most specific type (e.g. Dentist) instead of the redundant ancestry chain.
* **IMPROVED**: medicalSpecialty now only carries valid schema.org MedicalSpecialty enum values (e.g. Dental); free-text procedures move to knowsAbout.

= 1.1.0 =
* **NEW**: AI Content Enhancer (Conversion Booster).
* **NEW**: Key Takeaways (TL;DR) auto-generation.
* **NEW**: Smart Conclusion generator.
* **NEW**: Native CTA System for high-conversion lead generation.
* **NEW**: Unified Persistence Bridge (mu-plugins) for deactivation safety.
* Refined brand color integration across the entire UI.

= 1.0 =
* Initial release.
* Full AI content and schema generation suite.
* DG10 Agency design system implementation.

== Upgrade Notice ==

= 1.0 =
Updates Coming soon...

== Privacy Policy ==

ArtitechCore does not store or collect personal user data on our servers.

**Third-Party AI Services:**
When you use AI features, your content and business context are sent to:
- OpenAI: https://openai.com/privacy/
- Google Gemini: https://policies.google.com/privacy
- DeepSeek: https://deepseek.com/privacy

Only the site administrator can configure which provider is used. No data is shared with any other third parties. All API keys are stored securely in the WordPress database and are never exposed to the frontend.

You can disable AI features at any time from the plugin settings. All schema data and AI-generated content are stored locally in your WordPress database.
