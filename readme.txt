=== MeasureBoard – AI SEO & Analytics ===
Contributors: measureboard
Tags: ai seo, geo optimization, ai rank tracker, llms.txt, woocommerce analytics
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free AI SEO analytics with GEO optimization, AI agent readiness audit, llms.txt generator, AI rank tracking, and WooCommerce sales attribution.

== Description ==

MeasureBoard is a free, all-in-one SEO and AI analytics platform. This plugin connects your WordPress site to MeasureBoard for AI-powered insights, and includes built-in tools that work without an account.

= Built-in Tools (No Account Needed) =

* **AI Agent Readiness Audit** - Tests 8 standards that determine how well AI agents can discover, read, and interact with your site. See your score right in your WordPress dashboard.
* **llms.txt Generator** - Generate, preview, and publish an llms.txt file that helps AI models like ChatGPT, Gemini, and Claude understand your site structure.
* **JSON-LD Schema Recommendations** - See which schema types your site is missing and get copy-paste code to add them.
* **robots.txt AI Bot Recommendations** - Check if your robots.txt addresses AI crawlers (GPTBot, ClaudeBot, PerplexityBot, etc.) and get the directives to add.

= With a Free MeasureBoard Account =

* **AI-Powered Traffic Reports** - Automated weekly analytics reports with AI executive summaries
* **AI Rank Tracker** - Monitor whether ChatGPT, Gemini, and Claude cite your website for the queries that matter
* **AI Traffic Intelligence** - Track referral traffic from AI platforms
* **GEO Optimization Suite** - Full AI search readiness analysis with schema, citations, sentiment, and prompt suggestions
* **Site Audit** - Crawl up to 2,000 pages for SEO issues
* **Backlink Analysis** - Referring domains, toxic links, and link building recommendations
* **Competitor Analysis** - Keyword gaps, backlink gaps, and content comparison
* **Keyword Research** - Search volume, CPC, difficulty, SERP analysis
* **Content Health** - Identify underperforming pages with AI pruning recommendations
* **Uptime Monitoring** - Checks as often as every 5 minutes with email alerts

= WooCommerce Integration =

When WooCommerce is active, the plugin automatically detects it and enables:

* **Sales Attribution** - Revenue broken down by channel, source, and campaign
* **Product Performance** - Top products by revenue with stock status
* **Revenue Trends** - 30/60/90-day revenue summaries
* **AI-Powered Sales Insights** - AI recommendations for improving conversion and revenue

= How It Works =

1. Install and activate the plugin
2. Use the built-in tools immediately (no account needed)
3. For full analytics, create a free MeasureBoard account at measureboard.com
4. Copy your Property ID and paste it into the plugin settings
5. Your WordPress content, WooCommerce data, and site health sync automatically

= Privacy & Security =

* **Read-only access** - The plugin never modifies your posts, pages, or database
* **llms.txt uses WordPress options** - Stored as a WordPress option, served via rewrite rule. You control when to publish or unpublish
* **API key authentication** - Communication with MeasureBoard uses a unique key pair generated on activation
* **No tracking** - The plugin does not add any tracking scripts to your frontend
* **Data stays yours** - Disconnect at any time to stop syncing. Delete your MeasureBoard account to remove all data

== External services ==

This plugin connects to the MeasureBoard.com API (https://www.measureboard.com/api) to provide AI-powered SEO and analytics features. Connecting is **optional** — the built-in tools (Agent Readiness Audit, llms.txt generator, JSON-LD recommendations, robots.txt recommendations) work entirely on your site without any external request. The plugin only contacts MeasureBoard.com after you enter a Property ID and click Connect on the settings screen.

What the service is and what it is used for:

MeasureBoard is an AI SEO and analytics platform that ingests your published content and site metadata to generate AI-powered traffic reports, AI rank tracking, content health analysis, and (when WooCommerce is active) sales attribution.

What data is sent and when:

* On Connect (one-time, when the user clicks Connect on the settings page): site URL, home URL, site name, the Property ID you entered, a plugin-generated site key + secret used to authenticate later requests, the WordPress version, the plugin version, and whether WooCommerce is active. Endpoint: `POST https://www.measureboard.com/api/wordpress/connect`.
* On Connect and once per day thereafter (site health sync): WordPress version, PHP version, site URL, home URL, site name, site description, active theme name and version, the slug + name + version of each active plugin, whether WooCommerce is active, whether the site is multisite, permalink structure, timezone, locale, published post and page counts, and whether the site uses SSL. Endpoint: `POST https://www.measureboard.com/api/wordpress/site-health`.
* On Connect and once per day thereafter (content sync): for every published post and page — title, slug, URL, excerpt, word count, publish date, modified date, author name, featured image URL, and any meta title / meta description / focus keyword that Yoast SEO, Rank Math, or All in One SEO has stored for that post. Endpoint: `POST https://www.measureboard.com/api/wordpress/content`.
* On Connect and once per day thereafter, **only if WooCommerce is active** (sales attribution): order totals, order dates, order statuses, the referring source/medium/campaign saved with each order, and product titles + revenue + stock status for the top products. No customer names, addresses, emails, or payment details are sent. Endpoint: `POST https://www.measureboard.com/api/wordpress/woocommerce`.
* On Disconnect (when the user clicks Disconnect): a single request that tells MeasureBoard to stop accepting data from this site. Endpoint: `DELETE https://www.measureboard.com/api/wordpress/disconnect`.

All requests include the site key + secret generated on plugin activation so MeasureBoard can authenticate the site they came from. No data is sent to any other domain.

This service is provided by MeasureBoard.com:

* Terms of Service: https://www.measureboard.com/terms
* Privacy Policy: https://www.measureboard.com/privacy

== Installation ==

1. Upload the `measureboard` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to the MeasureBoard settings page to see your Agent Readiness Score
4. (Optional) Create a free account at measureboard.com and enter your Property ID to enable full analytics

== Frequently Asked Questions ==

= Do I need a MeasureBoard account? =

No. The built-in tools (agent readiness audit, llms.txt generator, JSON-LD and robots.txt recommendations) work without an account. A free account unlocks AI-powered traffic reports, rank tracking, and the full analytics suite.

= Does this work with WooCommerce? =

Yes. When WooCommerce is active, the plugin automatically detects it and enables sales attribution, product performance tracking, and revenue analysis.

= Does this work with Yoast SEO / Rank Math? =

Yes. The plugin reads meta titles, descriptions, and focus keywords from Yoast SEO, Rank Math, and All in One SEO. It does not conflict with any SEO plugin.

= What is an llms.txt file? =

An llms.txt file is a structured text file (similar to robots.txt) that helps AI models understand your website. It lists your key pages, content, and site description in a format optimized for AI crawlers. Learn more at measureboard.com/blog/llms-txt-guide

= What is GEO (Generative Engine Optimization)? =

GEO is the practice of optimizing your website to be cited by AI search tools like ChatGPT, Perplexity, and Gemini. It builds on traditional SEO with AI-specific standards. Learn more at measureboard.com/blog/what-is-geo

= Is this plugin free? =

Yes. The plugin and MeasureBoard Starter plan are both free. Paid plans ($12/month Business, $30/month Pro) unlock deeper analysis and more frequent reports.

== Screenshots ==

1. Agent Readiness Score in your WordPress dashboard
2. llms.txt generator with preview and publish controls
3. JSON-LD schema recommendations
4. robots.txt AI bot recommendations
5. MeasureBoard connection settings

== Changelog ==

= 1.0.1 =
* Move admin settings page JavaScript out of an inline `<script>` block into a separate file enqueued via wp_enqueue_script
* Document the MeasureBoard.com external service in the readme (what data is sent, when, and links to Terms / Privacy)
* Remove WordPress.org directory screenshots from the plugin zip (they belong in the SVN /assets/ directory, not the plugin code)

= 1.0.0 =
* Initial release
* AI Agent Readiness Audit (8 checks)
* llms.txt generator with draft/publish workflow
* JSON-LD schema recommendations
* robots.txt AI crawler recommendations
* WooCommerce detection and sales data
* MeasureBoard API connection
* WordPress dashboard widget
* Daily data sync

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install to check your AI agent readiness score and generate an llms.txt file.
