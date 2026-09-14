# SocialBUMP Bricks Tweaks

Notes for whoever picks this up next, most likely a new chat with no memory of
how any of it came about. Read this first.

**Keep it current.** Change how something works, add a feature, or learn
something painful, and write it here in the same session. A note that is wrong is
worse than no note, so fix anything you find that has gone stale.

That means all of it, not just the overview. Add a module and it gets its own
entry under the detailed list, describing what it does, how it does it, and what
it can be set to. Change how a setting behaves and the entry for that setting
changes with it. Add a setting to an existing module and add it to that entry.
The detail is the point: an overview that says a module exists helps nobody who
has to change it.

## What it does

Bricks specific work: custom elements, element conditions, and tweaks to how
Bricks behaves. It refuses to run without the Bricks theme and says so. Anything
useful on a site without Bricks belongs in Site Kit instead.

## How it is organised

Everything is a module and every module can be switched off. A module that is off
is never loaded.

| Group | What is in it |
| --- | --- |
| Conditions | ACF Relationship, ACF Repeater, Bricks Content, Post Type, WooCommerce Archive Display |
| Extras | ACF Gallery Loop, ACF Loop Sorting, Default To WP Editor, Fix ACF CPT SVG Icons |
| Elements | Image Carousel |

## Conditions

Bricks lets an element be shown or hidden by a condition. SBBT_Conditions is the
shared engine behind all of ours: a module supplies a condition.php describing the
condition and how to answer it, and registers it with SBBT_Conditions::register().

- A condition about one post is answered against that post.
- A condition about the page rather than a post sets needs_post to false. That is
  how WooCommerce Archive Display works on a shop archive, where there is no post
  to ask about.

SBBT_Conditions stands down, with a notice, if the old WP CodeBox Bricks Toolkit
snippet is still defining the same helper. One or the other, never both.

## What each module does

Default says whether a fresh install has it on.

| Module | Needs | Default | What it does |
| --- | --- | --- | --- |
| ACF Relationship | ACF | off | Show or hide an element depending on whether a relationship or post object field has anything in it |
| ACF Repeater | ACF | off | Show or hide depending on whether a repeater has rows. The repeater is picked from a list |
| Bricks Content | | off | Show or hide depending on whether the post was built with Bricks. Useful for falling back to normal WordPress content |
| Post Type | | off | Show or hide depending on the post type, so one template can serve Pages, Posts and custom types |
| WooCommerce Archive Display | WooCommerce | off | Show or hide depending on whether the shop or category archive lists products, categories, or both |
| ACF Gallery Loop | ACF | off | Adds each ACF gallery field to the query loop Type list, so you can loop its images and build the markup yourself. Any return format, with ordering options |
| ACF Loop Sorting | ACF | off | An Order setting on ACF query loops: reversed, sorted or random. What ACF has saved never changes |
| Default To WP Editor | | off | Opens the WordPress editor rather than the Bricks tab on posts with no Bricks content. The Bricks tab is still one click away |
| Fix ACF CPT SVG Icons | ACF | off | Makes SVG menu icons on ACF post types behave like the rest: same size, and they change colour on hover and when active. Other plugins icons are left alone |
| Image Carousel | | on | An SEO friendly carousel: real img tags with alt text, drag ordering, ACF gallery support, breakpoint controls, lightbox and continuous scroll |

The WooCommerce Archive Display condition is the one to read first if you are
writing a new condition: it is the only one that answers a question about the page
rather than a post, so it shows what needs_post false is for.

## Every module in detail

What it does, how it does it, and what it can be set to. The hook named is the
one to look at first when something misbehaves.

### Conditions

All five are built the same way: a condition.php returning key, label, compare,
value and check, registered through SBBT_Conditions. Bricks shows them in the
element Conditions panel alongside its own. check is the callback that answers
true or false for the element being drawn.

**ACF Relationship.** Answers whether a relationship or post object field has
anything in it. The usual use is hiding a Related section when nothing has been
linked, rather than showing an empty heading. Needs ACF.

**ACF Repeater.** Answers whether a repeater has rows, with the repeater picked
from a list rather than typed. Same idea: no rows, no section. Needs ACF.

**Bricks Content.** Answers whether the post was built with Bricks. Useful in a
shared template that has to cope with both: show the Bricks content when there is
some, fall back to the WordPress editor content when there is not.

**Post Type.** Answers on the post type of what is being shown, so one template
can serve Pages, Posts and custom types and vary a header or a meta block.

**WooCommerce Archive Display.** Answers whether the shop or category archive is
set to show products, categories, or both. This is the one that proves the
needs_post false path: on a shop archive there is no post to ask about, so the
condition is about the page. Read this one first if you are writing a condition
that is not about a single post. Needs WooCommerce.

### Extras

**ACF Gallery Loop.** Adds every ACF gallery field to the Bricks query loop Type
list, so a gallery can be looped and the markup built by hand instead of using a
gallery element. It hooks bricks/setup/control_options to offer the fields, then
bricks/query/run to return the images, and bricks/query/loop_object_id and
loop_object_type so dynamic data inside the loop resolves against the image. It
also filters post_thumbnail_id, so a featured image element inside the loop shows
the looped image. Works with any ACF return format, ID, array or URL. Needs ACF.

**ACF Loop Sorting.** Adds an Order setting to ACF query loops through
bricks/query/result: as saved, reversed, sorted or random. It reorders what comes
back rather than what ACF stores, so the order saved in ACF never changes.
Settings: repeater and relationship, each a switch, so you can have it on one
kind of loop and not the other. Needs ACF.

**Default To WP Editor.** On a post with no Bricks content of its own, opens the
WordPress editor rather than the Bricks tab, through admin_enqueue_scripts. The
Bricks tab is still one click away. Handy on a site where most posts are written
normally and only a few are built.

**Fix ACF CPT SVG Icons.** A post type created in ACF with an SVG menu icon gets
an icon that ignores the admin menu colours: wrong size, and it does not change on
hover or when active. This prints CSS on admin_head to make them behave like the
rest. It only touches icons on ACF created post types, so icons from other plugins
are left alone. Setting: colour. Needs ACF.

### Elements

**Image Carousel.** A real Bricks element, not a wrapper around a library
shortcode, and the only module here that ships its own CSS and JavaScript. It was
written because most carousels output background images or lazy placeholders that
search engines and AI crawlers cannot read. This one outputs real img tags with
alt text. It takes images from a manual selection or an ACF gallery, has drag
ordering, per breakpoint controls for how many show at once, an optional lightbox
and continuous scroll. On by default, since it is the reason the plugin exists for
most sites.

Bricks caches element registration. After changing an element, regenerate the
Bricks CSS files and reload the builder before deciding something is broken.

## The files, and what each one is for

| File | What it is |
| --- | --- |
| socialbump-bricks-tweaks.php | constants, updater, hub check, sbbt_log_change(), refuses to run without Bricks |
| includes/class-sbbt-settings.php | the Features page, banner, menu, admin bar |
| includes/class-sbbt-modules.php | finds every module and works out what can run |
| includes/class-sbbt-conditions.php | the shared engine behind every element condition |
| includes/class-sbbt-acf-source.php | decides whether ACF is really present, or only the Advanced Themer copy |
| includes/class-sbbt-release.php | publishing, hub only |
| includes/class-sbbt-updates.php | the Updates page |
| includes/class-sbbt-transfer.php | settings export and import |
| includes/class-sbbt-docs.php | these notes and the Publishing panel |

The modules:

| Module | Files |
| --- | --- |
| condition-acf-relationship, -acf-repeater, -bricks-content, -post-type, -woo-archive-display | condition.php and module.php each |
| acf-sorting | class-sbbt-repeater-ordering.php and class-sbbt-relationship-ordering.php |
| gallery-loop | class-sbbt-gallery-loop.php |
| default-wp-editor | class-sbbt-default-wp-editor.php |
| fix-acf-cpt-svg-icons | class-sbbt-acf-svg-icons.php |
| image-carousel | class-element-image-carousel.php plus assets, a real Bricks element |

A condition module is only a condition.php and a module.php. An element module
registers a Bricks element class and ships its own CSS and JavaScript.

## Writing a module

A folder under includes/modules/<slug>/ with a module.php returning an array:
id, title, description, section, default, requires, optional settings and
features, and a boot callback.

- requires names what it needs: acf, bricks, woocommerce.
- ACF supplied only by the copy bundled with Advanced Themer does not count as
  ACF. The check is deliberately stricter than class_exists.
- Write the class file before the module.php that loads it.
- A fatal inside one module is caught, so it cannot take the site down.

## What it stores

| Name | Holds |
| --- | --- |
| sbbt_modules | which modules are on |
| sbbt_module_settings | each module settings |
| sbbt_github_token | encrypted, hub only |
| sbbt_pending_changes | notes for the next release |

## Where to be careful

- Bricks caches element registration. After adding an element, regenerate the
  Bricks CSS files and reload the builder before deciding it does not work.
- A condition that returns the wrong shape silently hides everything it touches.
  Test both states in the builder, not just the one you expect.
- This plugin is the natural home for anything that reads the Bricks element
  tree. SEO for AI has its own Bricks module for reading content into the export;
  that one belongs there, not here, because it is about the export.

<!-- shared:start -->

## House rules, shared by all three SocialBUMP plugins

This block is identical in the docs of all three plugins. Change it in one and
copy it to the other two in the same session. They all live on the hub, so that
is a two minute job, and the Publishing page warns you when they have drifted.

### The three plugins

| Plugin | Folder | Prefix | Menu |
| --- | --- | --- | --- |
| SocialBUMP Bricks Tweaks | socialbump-bricks-tweaks | SBBT_ / sbbt_ | SB Bricks Tweaks |
| SocialBUMP Site Kit | socialbump-site-kit | SBSK_ / sbsk_ | SB Site Kit |
| SocialBUMP SEO for AI | socialbump-ai-knowledge-exporter | SBAIKE_ / sbaike_ | SB SEO for AI |

SEO for AI was called AI Knowledge Exporter until September 2026. Its folder,
text domain, option names and GitHub repo still say so, deliberately: renaming
them would break the update checker and the saved settings on every site.

Which plugin does a job belong in? Needs the Bricks theme, Bricks Tweaks.
Useful on any site, Site Kit. About what AI crawlers read, SEO for AI.

### Files that are identical in each plugin

- includes/class-socialbump-admin-bar.php
- assets/js/save-state.js

Change one, change all three, then check the md5s match. Both are written so
that whichever plugin loads first wins and the others stand aside, so a site
running mixed versions still works.

### The shared admin bar item

SocialBUMP_Admin_Bar::register() takes id, label, href and items, and optionally
actions, attention, attention_title and current. Everything is drawn once, at
admin_bar_menu priority 200.

- One plugin active: that plugin sits on the bar on its own.
- Two or more: a single SocialBUMP item, each plugin a row inside it, its pages
  on a flyout from that row.
- Each row has a dot: green when there is nothing to do, amber when there is.
  Any amber row makes the SocialBUMP dot amber, so the top of the bar is the
  only thing that needs watching.
- attention means an update is waiting. SEO for AI also counts stale posts.
- The current page is white and bold, never the admin colour scheme accent:
  some accents are unreadable on the dark bar.
- An action row marked sb-bar-action is-idle looks inactive and ignores hover.

Two signals, and they mean different things. Keep them apart:

- The dot is about this site: content waiting to be rebuilt, an update ready to
  install. It is what someone looking after the site cares about.
- Amber wording, and a small count beside it, is about the hub: changes noted but
  not yet released. Publishing carries it, through attention and count on that
  item. Never fold this into the dot, and never colour the dot for it: on a client
  site there is nothing to publish and the distinction is the whole point.

### Getting between the pages

The banner carries a row of links to every page in the plugin, with the one you
are on marked. The admin menu lists them too, but on a long menu the plugin can
be a scroll away and its pages only show while you are already on one of them.

- Updates shows the new version number when one is waiting.
- Publishing shows how many changes are queued, and only exists on the hub, so a
  client site gets a shorter row and no badges.
- render_nav() builds it from bar_items(), the same list the admin bar uses, so
  a new page appears in the menu, the admin bar and the banner at once.
- It hides itself when a plugin has fewer than two pages.
### The SocialBUMP Hub page

class-socialbump-overview.php, identical in each plugin, same arrangement as the
admin bar: first to load defines the class, the others register with it.

- A top level SocialBUMP Hub menu, but only on the hub and only when more than
  one plugin is active. It therefore disappears by itself on every site built
  from the blueprint, which is the point: there is nothing to publish there.
- A card per plugin: version, whether an update is waiting, how many changes are
  queued for the next release, and links to its pages. The count is an amber pill
  that jumps down to that plugin publishing panel.
- Below that, each plugin publishing panel in turn, with the plugin name slid in
  as the heading inside the panel, so all of them go out from one screen.
- The item in the admin bar opens this page when it exists, and the first
  plugin otherwise.

register() takes id, name, version, file and pages, and optionally notes, css,
css_time, logo, accent_var, hub, and release, a callback that draws that plugin
publishing panel.

Two things about the page are easy to get wrong, and both have been:

- It belongs to no plugin in particular, so it loads every registered stylesheet,
  and each one is versioned by when the file changed rather than by the plugin
  version. Version it by the plugin and a browser serves yesterday CSS after every
  edit, which is exactly what happened.
- Each plugin styles itself from its own CSS variable, and nothing sets those on a
  page that belongs to none of them, so the page works out the accent itself and
  sets every registered variable. Without that the panels fall back to the
  WordPress blue and look nothing like the rest.

### After an update

Updating a plugin swaps its files out mid request. If you were on one of its own
pages, the page you land on afterwards can still be running the old code, so its
menus never register and the plugin appears to have vanished until you navigate
somewhere else. Each plugin now clears the compiled copies of its own files on
upgrader_process_complete, which settles it.

### What a client site must not carry

The hub is the blueprint new sites are built from, so whatever is in its database
travels with every copy. On any site that is not the hub, each plugin deletes its
GitHub token, its queued release notes and its release cache when an admin page
loads. A token has no business on a client site.

If you add anything else that only the hub should know, delete it there too.
### Unsaved changes, and the save button

Any form marked data-sb-dirty is watched. The save button sits disabled reading
Nothing to save until something changes, then wakes up with its own wording and
an amber reminder appears top right and follows you down the page. Put the change
back the way it was and both go quiet. Leaving with something unsaved warns you.

Attributes a button can carry:

- data-sb-save: treat as a save button even though it is not a submit.
- data-sb-label-dirty: the wording to use when there is something to save, for a
  button whose resting label says there is nothing.
- data-sb-always-on: never disable this one. Used for buttons that do work
  rather than save, such as Full Rebuild.
- data-sb-idle=1: nothing to run right now, so sit inactive until there is.

Styling: .sb-save--clean is a grey outline on transparent, .sb-save--dirty is
pale yellow with an amber border, matching the reminder. Both selectors lead with
.wp-core-ui and .button, because WordPress styles disabled and primary buttons
with important and would otherwise win.

### The look

- One stylesheet per plugin at assets/css/admin.css, every class prefixed.
- Dark banner: SocialBUMP logo, plugin name, page name in a span in the accent
  colour, then a version badge linking to Updates that turns amber when a
  release is waiting. The heading reads plugin name then page name, including on
  a landing page: Site Kit Modules, Bricks Tweaks Features, SEO for AI Content.
- Panels: prefix-section, with __head for the heading and description and __body
  for the content.
- Cards: prefix-card, is-on for a live one, is-unavailable for one waiting on
  something missing. The left edge carries the accent when live.
- Pills: prefix-status__pill, is-good green, is-stale amber. An amber one that
  can be acted on is a link, and clicking it does the thing it describes.
- Menu icon: the SocialBUMP exclamation, shared by all four items through
  SocialBUMP_Overview::brand_icon(). Each plugin positions its menu next to the
  others rather than at a fixed spot.
- WordPress does not recolour an SVG menu icon. It only recolours Dashicons,
  which are a font. An SVG given as a menu icon becomes a background image and
  keeps whatever colour is baked into it, so ours is white and the dimming when
  idle, and the brightening on hover, are done in CSS to match the icons around
  it. Build the SVG by concatenation with chr( 34 ): a quote mangled in the
  middle of it produces markup that silently draws nothing.
- Publishing lays out as two columns: the token and the zip stacked on the left,
  publishing beside them. The cards are placed with CSS grid rather than
  reordered, so the markup and the reading order stay as they are.
- The accent comes from the admin colour scheme, chosen by saturation so a
  washed out swatch is never picked, and exposed as --prefix-accent.

### Releasing

Everything is developed and released on the hub, bricks.socialbump.com.au. Each
plugin decides it is on the hub by host name, and only then loads its release
code and shows a Publishing page.

- Publishing pushes the code to GitHub, builds a zip, creates a release and
  attaches the zip. Sites update through the plugin update checker.
- One fine grained GitHub token per plugin, stored encrypted, scoped to that one
  repo with Contents read and write. A token cannot create repositories, so a new
  repo is made by hand first.
- The notes box fills from prefix_log_change() calls made since the last release,
  and the list empties once a release goes out. Call it after any change worth
  telling someone about, in their words rather than yours.
- Publishing retries on a 5xx, checks the zip actually attached, and checks again
  before undoing anything, because GitHub has published a release and then failed
  the response.
- The first release may carry the version already in the files. Every release
  after that has to be higher than the last.
- Everything in the plugin folder is published except .git, .github, node_modules
  and .DS_Store. These docs ship with the plugin, so they reach every site, and
  the repos are public: nothing private goes in them.

### How work actually gets done here

There is no local checkout and no git client. Everything happens on the live hub
through its Novamira MCP connector, by running PHP on the site. That shapes how
to work:

- Read a file with file_get_contents, write it with file_put_contents.
- Lint before you write. Put the new contents in a temporary file, run php -l on
  it, and only write the real file when it passes. A fatal in a plugin file takes
  the site down, and you are editing the site you would need to fix it.
- JavaScript has no linter here. Walk the brackets, minding strings, comments
  and regular expressions, before writing.
- Call opcache_invalidate() on a file after writing it.
- A class already loaded in the current request is still the old one. Check your
  work in a fresh call, not the one that wrote the file.
- Anchor edits on a unique string and check it matches exactly once. If it
  matches twice, widen it until it does not.
- Keep a copy before a risky edit. copy( $file, sys_get_temp_dir() . ... ) costs
  nothing and has saved a rewrite more than once.
- Verify after every write. A write that silently did nothing, because the anchor
  never matched or the function returned early, has cost more time here than any
  actual bug.

Watch out for quoting when building PHP through a JSON tool call. A backslash in
a regular expression, or a quote in a string, has to survive JSON, then PHP, then
whatever it is written into. Building strings with chr( 34 ) and concatenation is
uglier to read but far less likely to arrive mangled.

### Where things live

The hub is bricks.socialbump.com.au, and the plugins are in the usual place:
wp-content/plugins/<folder>/. Client sites each have their own connector and the
same folder structure.

Every plugin has the same shape:

| File | What it is |
| --- | --- |
| <plugin>.php | constants, updater, hub check, log_change(), loads everything |
| includes/class-<pre>-settings.php or -admin.php | menu, pages, banner, admin bar registration |
| includes/class-<pre>-modules.php | finds and boots the modules |
| includes/class-<pre>-release.php | publishing to GitHub, hub only |
| includes/class-<pre>-updates.php | the Updates page and the update check |
| includes/class-<pre>-transfer.php | settings export and import |
| includes/class-<pre>-docs.php | these notes, and the panel on Publishing |
| includes/class-socialbump-admin-bar.php | shared, identical in all three |
| assets/css/admin.css | everything the admin pages look like |
| assets/js/save-state.js | shared, identical in all three |
| vendor/plugin-update-checker | the updater library, left alone |

### Working on a plugin from a client site

Work on the hub by default. Build on a client site only when it has something
the hub has not, which in practice means WooCommerce: the WooCommerce modules in
Site Kit were built on drivingevents.com.au for that reason.

When you have, bring it home carefully. Assume nothing at any step:

1. List both folders and compare every file by md5 and by modified time. Not just
   the files you think you touched: a file you did not expect to differ is
   exactly the one worth knowing about.
2. For each file that differs, work out which side is newer and why before you
   move anything. The hub may have moved on while you were working elsewhere, and
   the client copy may be an older release rather than your new work.
3. Read any file the hub has changed, in full, before overwriting it. Two people
   editing the same file from different directions is how work disappears.
4. Copy back only the files that genuinely differ, one at a time.
5. Compare the md5s again afterwards and confirm each one matches.
6. Update these docs on the hub, never on the client site.
7. Call prefix_log_change() on the hub, so the work appears in the next release.

If the two sides have both changed the same file, stop and say so rather than
picking one. Merging by hand with both versions in front of you takes minutes.
Guessing wrong costs whatever was on the losing side, and nobody finds out until
later.

Nothing may live only on a client site. The next update overwrites the plugin
folder, and anything not carried back to the hub is gone.

### Habits that have paid off

- Lint every PHP file before writing it, and bracket check any JavaScript.
  Write to a temporary file, lint that, and only then put it in place.
- Write a class file before the loader that requires it, so a failure never
  leaves a plugin pointing at a file that is not there.
- Keep each edit small and check it took. A write that silently did nothing has
  cost more time here than any bug.
- Anchor edits on unique strings. If an anchor matches twice, stop and widen it.
- After editing a file, the class already loaded in that same request is still
  the old one. Verify in a fresh request, not the one that wrote the file.

### Things learned the hard way

- PHP declares top level classes and functions while compiling the file, before
  a line of it runs. A class_exists() guard inside the file that declares the
  class always sees its own class and returns, and the file never finishes. This
  broke SEO for AI once. Guard by other means.
- WordPress styles disabled and primary buttons with important. Beat it with
  specificity, not with another important on its own.
- admin_head has already been sent by the time the admin bar is built, so a
  style hooked only there never appears. Hook the footer as well.
- A settings page that submits only part of the settings must merge rather than
  replace, or saving one page wipes the others. Site Kit and SEO for AI have both
  had this bug. Both now post a marker of which sections were on the page.
- An element with no link is rendered by the admin bar as an empty item, not an
  anchor, so style both.
- Nested admin bar flyouts need position relative on the row, or they fly off
  to the right of the whole menu.
- The admin menu can be renamed by an admin menu plugin. Admin and Site
  Enhancements holds its own titles and wins over whatever the plugin registers.

<!-- shared:end -->
