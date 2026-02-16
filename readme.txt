=== AgentMark ===
Contributors: selvasudar
Tags: markdown, ai, llms, rag, api
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

AI-Ready Markdown Endpoints & llms.txt discovery for WordPress. Clean, machine-readable content for AI agents and RAG systems.

== Description ==

**The Problem:**
WordPress outputs HTML that's difficult for AI systems to parse accurately. Important content gets lost in markup, scripts, and styling.

**The Solution:**
AgentMark provides clean Markdown versions of your posts and pages through simple `.md` endpoints, plus an industry-standard `llms.txt` file that helps AI agents discover your best content.

= Use Cases =
* **AI Search Engines**: Help tools like Perplexity, Claude, and ChatGPT cite your content accurately
* **RAG Systems**: Enable companies to build knowledge bases from your public content
* **Documentation**: Provide developers with clean, parseable API docs
* **Content Syndication**: Share articles in a universal, lightweight format
* **Knowledge Graphs**: Help semantic web tools understand your site structure

= Key Features =
* **Zero Configuration**: Works out of the box with sensible defaults
* **Markdown Endpoints**: Access any post/page as Markdown by adding `.md` to the URL
* **llms.txt Standard**: Auto-generates discovery file for AI agents
* **YAML Front Matter**: Rich metadata for better AI understanding
* **Flexible**: Control which post types are exposed
* **Performance**: Built-in caching and rate limiting
* **Privacy-Focused**: Per-post control over AI access
* **Standards-Compliant**: Follows emerging llms.txt specification

= Business Value =
* **Future-Proof**: Position your content for the AI-driven web
* **Zero Cost**: No API fees or external dependencies
* **SEO Boost**: Alternate content formats can improve discoverability
* **Competitive Edge**: Early adoption of AI-friendly formats
* **Analytics**: Track which content AI agents prefer

== Installation ==

= Automatic Installation =
1. Log into your WordPress admin panel
2. Navigate to Plugins -> Add New
3. Search for "AgentMark"
4. Click "Install Now" and then "Activate"
5. Go to Settings -> AgentMark to configure

= Manual Installation =
1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/` and extract
3. Activate through the Plugins menu
4. Go to Settings -> Permalinks and click "Save Changes" (flush rewrite rules)
5. Configure at Settings -> AgentMark

= After Installation =
* Visit `yoursite.com/llms.txt` to see your AI-ready content index
* Add `.md` to any post URL to see the Markdown version
* Check Settings -> AgentMark to customize behavior

== Frequently Asked Questions ==

= How do I access the Markdown version of a post? =
Simply append `.md` to the end of any public post or page URL. 
Example: `yoursite.com/my-post/` becomes `yoursite.com/my-post.md`

= What is llms.txt? =
It's an emerging standard file format (similar to robots.txt) that helps AI agents discover high-quality, machine-readable content on your website. It's located at `yoursite.com/llms.txt`.

= Will this affect my SEO? =
No. Markdown endpoints are marked as alternate versions, not duplicate content. They can actually improve your site's discoverability.

= Can I exclude certain posts from AI agents? =
Yes! Each post has an AgentMark meta box where you can disable the .md endpoint or exclude it from llms.txt.

= Does this work with Gutenberg blocks? =
Yes. The plugin processes the final rendered HTML, so all block types are supported.

= Will this slow down my site? =
No. Markdown conversion is cached, and rate limiting prevents bot overload. Regular visitors see no impact.

= Can I customize the Markdown output? =
Currently, the conversion is automatic. Custom templates are planned for a future version.

= Does this share my private content? =
No. Only public, published posts of enabled post types are accessible. Draft and private posts are never exposed.

= How do I track AI agent usage? =
The built-in analytics show basic usage stats at Settings -> AgentMark Stats.

= Can I use this with custom post types? =
Yes! Go to Settings -> AgentMark and enable any public custom post type.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added Markdown endpoints with caching.
* Integrated League HTML-to-Markdown for superior conversion.
* Added llms.txt discovery standard.
* Added analytics and rate limiting.
* Added per-post controls.
