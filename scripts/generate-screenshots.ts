/**
 * Generate WordPress plugin screenshots for wp.org submission.
 * Uses Playwright to render HTML mockups that look like real wp-admin.
 */
import { chromium } from "playwright";
import { writeFileSync } from "fs";

// Screenshots are NOT shipped inside the plugin zip. They live under
// wp-org-assets/ and are uploaded separately to the WordPress.org SVN
// /assets/ directory after the plugin is approved. See:
// https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
const OUTPUT_DIR = "/Users/a/Projects/measureboard-wordpress/wp-org-assets";

// WordPress admin styles (approximation)
const WP_STYLES = `
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; background: #f0f0f1; color: #1d2327; font-size: 13px; }
  .wrap { max-width: 800px; margin: 20px auto; padding: 0 20px; }
  h1 { font-size: 23px; font-weight: 400; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
  h1 .icon { font-size: 28px; color: #f97316; }
  .subtitle { color: #666; font-size: 14px; margin-bottom: 16px; }
  .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px 24px; margin: 16px 0; }
  .card h2 { font-size: 16px; font-weight: 600; margin: 0 0 12px; padding: 0; border: none; }
  .status { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 12px; }
  .status-green { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
  .status-red { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
  .status-yellow { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
  .score-row { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
  .score-circle { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; color: #fff; flex-shrink: 0; }
  .score-green { background: #22c55e; }
  .score-yellow { background: #eab308; }
  .score-red { background: #ef4444; }
  .checks-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .checks-table tr { border-bottom: 1px solid #f3f4f6; }
  .checks-table td { padding: 8px 6px; }
  .check-icon { width: 24px; text-align: center; font-weight: 700; }
  .pass .check-icon { color: #22c55e; }
  .fail .check-icon { color: #ef4444; }
  .check-status { text-align: right; font-weight: 600; font-size: 12px; }
  .pass .check-status { color: #22c55e; }
  .fail .check-status { color: #ef4444; }
  .btn { display: inline-block; padding: 6px 16px; font-size: 13px; border-radius: 4px; cursor: pointer; border: 1px solid #2271b1; text-decoration: none; }
  .btn-primary { background: #2271b1; color: #fff; border-color: #2271b1; }
  .btn-secondary { background: #f6f7f7; color: #2271b1; }
  .code-preview { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 16px; margin: 12px 0; }
  .code-preview h4 { margin: 0 0 8px; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
  .code-preview pre { margin: 0; font-size: 12px; line-height: 1.6; white-space: pre-wrap; font-family: Menlo, Monaco, Consolas, monospace; }
  .subtext { color: #888; font-size: 12px; }
  table.recs { width: 100%; border-collapse: collapse; font-size: 13px; }
  table.recs th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; padding: 8px 6px; border-bottom: 2px solid #e2e8f0; }
  table.recs td { padding: 10px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  table.recs code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
  .wp-dashboard { background: #f0f0f1; padding: 20px; }
  .dashboard-widget { background: #fff; border: 1px solid #c3c4c7; border-radius: 0; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
  .dashboard-widget h2 { background: #fff; border-bottom: 1px solid #c3c4c7; padding: 8px 12px; font-size: 14px; margin: 0; }
  .dashboard-widget .inside { padding: 12px; }
  .dashboard-checks { list-style: none; padding: 0; margin: 0 0 12px; font-size: 12px; }
  .dashboard-checks li { padding: 3px 0; }
  .dashboard-checks .pass { color: #22c55e; }
  .dashboard-checks .fail { color: #ef4444; }
</style>
`;

const screenshots = [
  {
    name: "screenshot-1",
    title: "Agent Readiness Score in WordPress Dashboard",
    html: `${WP_STYLES}
<div class="wp-dashboard">
  <div class="dashboard-widget" style="max-width: 500px;">
    <h2>📊 MeasureBoard - AI Agent Readiness</h2>
    <div class="inside">
      <div class="score-row">
        <div class="score-circle score-yellow">63</div>
        <div><strong>Agent Readiness Score</strong><br><span class="subtext">5/8 checks passed</span></div>
      </div>
      <ul class="dashboard-checks">
        <li class="pass">✓ robots.txt</li>
        <li class="pass">✓ Sitemap</li>
        <li class="pass">✓ JSON-LD Schema</li>
        <li class="fail">✗ AI Bot Rules</li>
        <li class="fail">✗ llms.txt</li>
        <li class="fail">✗ LLMS Directive</li>
        <li class="pass">✓ Link Headers</li>
        <li class="pass">✓ Markdown Negotiation</li>
      </ul>
      <a href="#" class="btn btn-secondary">View Full Report</a>
      <a href="#" class="btn btn-primary" style="margin-left: 8px;">Open MeasureBoard</a>
    </div>
  </div>
</div>`,
  },
  {
    name: "screenshot-2",
    title: "llms.txt Generator",
    html: `${WP_STYLES}
<div class="wrap">
  <h1><span class="icon">📊</span> MeasureBoard</h1>
  <p class="subtitle">AI-powered SEO analytics, GEO optimization, and agent readiness.</p>
  <div class="card">
    <h2>llms.txt Generator</h2>
    <p style="margin-bottom: 12px; color: #555;">An llms.txt file helps AI models understand your site structure. <a href="#">Learn more</a></p>
    <div class="status status-yellow">
      <span>✏️</span> Draft generated on April 18, 2026. Review below and publish when ready.
    </div>
    <p>
      <a href="#" class="btn btn-secondary">Regenerate llms.txt</a>
      <a href="#" class="btn btn-primary" style="margin-left: 8px;">Publish</a>
    </p>
    <div class="code-preview">
      <h4>Preview</h4>
      <pre># Acme Widgets Co

> Premium widgets for modern businesses. Quality craftsmanship since 2015.

## Main Pages

- [Home](https://acmewidgets.com/): Welcome to Acme Widgets - premium widgets...
- [About Us](https://acmewidgets.com/about/): Our story, mission, and the team behind...
- [Contact](https://acmewidgets.com/contact/): Get in touch with our sales and support...
- [Shop](https://acmewidgets.com/shop/): Browse our full collection of premium...

## Recent Posts

- [5 Ways Widgets Improve Productivity](https://acmewidgets.com/blog/widgets-productivity/): Research shows that...
- [Widget Maintenance Guide](https://acmewidgets.com/blog/maintenance/): Keep your widgets running...
- [2026 Widget Trends](https://acmewidgets.com/blog/2026-trends/): What's new in the widget...

## Products

- [Pro Widget X100](https://acmewidgets.com/product/x100/): Our flagship premium widget...
- [Widget Starter Kit](https://acmewidgets.com/product/starter/): Everything you need to get...
- [Enterprise Widget Suite](https://acmewidgets.com/product/enterprise/): For teams of 10+...

## Contact

- [Website](https://acmewidgets.com)</pre>
    </div>
  </div>
</div>`,
  },
  {
    name: "screenshot-3",
    title: "JSON-LD Schema Recommendations",
    html: `${WP_STYLES}
<div class="wrap">
  <h1><span class="icon">📊</span> MeasureBoard</h1>
  <div class="card">
    <h2>JSON-LD Schema Recommendations</h2>
    <p style="margin-bottom: 12px; color: #555;">Structured data helps AI models understand your content with confidence.</p>
    <table class="recs">
      <thead><tr><th>Schema Type</th><th>Recommendation</th><th>Where to Add</th></tr></thead>
      <tbody>
        <tr><td><code>Organization</code></td><td>Add Organization schema to your homepage for brand recognition in AI search.</td><td class="subtext">Homepage &lt;head&gt; or via your SEO plugin</td></tr>
        <tr><td><code>WebSite</code></td><td>Add WebSite schema with search action for sitelinks search box.</td><td class="subtext">Homepage &lt;head&gt;</td></tr>
        <tr><td><code>BreadcrumbList</code></td><td>Add breadcrumb schema to improve navigation signals.</td><td class="subtext">Enable in Yoast SEO &gt; Search Appearance &gt; Breadcrumbs</td></tr>
        <tr><td><code>Article</code></td><td>Add Article schema to your 47 blog posts. Most SEO plugins do this automatically.</td><td class="subtext">Handled by Yoast SEO when post type schema is set</td></tr>
        <tr><td><code>Product</code></td><td>WooCommerce adds Product schema automatically for your 23 products. Verify it includes price and availability.</td><td class="subtext">WooCommerce handles this. Check with Rich Results Test.</td></tr>
      </tbody>
    </table>
  </div>
</div>`,
  },
  {
    name: "screenshot-4",
    title: "robots.txt AI Bot Recommendations",
    html: `${WP_STYLES}
<div class="wrap">
  <h1><span class="icon">📊</span> MeasureBoard</h1>
  <div class="card">
    <h2>robots.txt Recommendations</h2>
    <p style="margin-bottom: 12px; color: #555;">Add the following to your robots.txt to improve AI agent discovery:</p>
    <div class="code-preview">
      <pre>
# AI Crawlers - Allow access for AI search visibility
User-agent: GPTBot
Allow: /

User-agent: ChatGPT-User
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: anthropic-ai
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: Applebot-Extended
Allow: /

Sitemap: https://acmewidgets.com/sitemap.xml
LLMS: https://acmewidgets.com/llms.txt</pre>
    </div>
    <p class="subtext">Edit via <strong>Settings &gt; Reading</strong> (if using a virtual robots.txt) or edit the robots.txt file in your site root.</p>
  </div>
</div>`,
  },
  {
    name: "screenshot-5",
    title: "MeasureBoard Connection Settings",
    html: `${WP_STYLES}
<div class="wrap">
  <h1><span class="icon">📊</span> MeasureBoard</h1>
  <p class="subtitle">AI-powered SEO analytics, GEO optimization, and agent readiness.</p>
  <div class="card">
    <h2>Connection</h2>
    <div class="status status-green">
      <span>✅</span> Connected to property <code>2I4AiZXM</code> <span class="subtext">since April 18, 2026</span>
    </div>
    <p>
      <a href="#" class="btn btn-primary">Open MeasureBoard Dashboard</a>
      <a href="#" class="btn btn-secondary" style="margin-left: 8px;">Disconnect</a>
    </p>
  </div>
  <div class="card">
    <h2>AI Agent Readiness Score</h2>
    <div class="score-row">
      <div class="score-circle score-yellow">63</div>
      <div><strong>5/8 checks passed</strong><br><span class="subtext">How well your site supports AI agents and crawlers.</span></div>
    </div>
    <table class="checks-table">
      <tr class="pass"><td class="check-icon">✓</td><td>robots.txt</td><td class="check-status">Pass</td></tr>
      <tr class="pass"><td class="check-icon">✓</td><td>Sitemap</td><td class="check-status">Pass</td></tr>
      <tr class="pass"><td class="check-icon">✓</td><td>JSON-LD Schema</td><td class="check-status">Pass</td></tr>
      <tr class="fail"><td class="check-icon">✗</td><td>AI Bot Rules</td><td class="check-status">Fail</td></tr>
      <tr class="fail"><td class="check-icon">✗</td><td>llms.txt</td><td class="check-status">Fail</td></tr>
      <tr class="fail"><td class="check-icon">✗</td><td>LLMS Directive</td><td class="check-status">Fail</td></tr>
      <tr class="pass"><td class="check-icon">✓</td><td>Link Headers</td><td class="check-status">Pass</td></tr>
      <tr class="pass"><td class="check-icon">✓</td><td>Markdown Negotiation</td><td class="check-status">Pass</td></tr>
    </table>
  </div>
</div>`,
  },
];

async function main() {
  const browser = await chromium.launch({ headless: true });

  for (const shot of screenshots) {
    console.log(`Capturing: ${shot.name} - ${shot.title}`);
    const page = await browser.newPage({
      viewport: { width: 900, height: 700 },
      deviceScaleFactor: 2,
    });
    await page.setContent(shot.html, { waitUntil: "load" });
    await page.waitForTimeout(500);

    // Auto-fit height
    const bodyHeight = await page.evaluate(() => document.body.scrollHeight);
    await page.setViewportSize({ width: 900, height: Math.min(bodyHeight + 40, 1200) });

    await page.screenshot({
      path: `${OUTPUT_DIR}/${shot.name}.png`,
      type: "png",
    });
    console.log(`  ✓ Saved ${shot.name}.png`);
    await page.close();
  }

  await browser.close();
  console.log(`\nDone! Screenshots saved to ${OUTPUT_DIR}`);
}

main().catch(console.error);
