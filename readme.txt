=== ArtitechCore ===
Contributors: dg10agency
Tags: pages, schema markup, bulk creation, ai content, seo generator, menu generator, openai, gemini, hierarchy, structured data, json-ld, page builder, content enhancer
Requires at least: 5.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ArtitechCore is the ultimate page management and SEO infrastructure plugin, combining DG10 Agency design with powerful AI-driven content and schema generation.

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

1. **Branded Dashboard** - The central hub for all business management.
2. **AI Logic Engine** - Creating structured site maps from simple descriptions.
3. **Advanced Schema UI** - The full dashboard for structured data management.
4. **JSON Modal Editor** - Live code editing with validation tools.
5. **Import/Export System** - Managing large datasets with CSV tools.

== Changelog ==

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
