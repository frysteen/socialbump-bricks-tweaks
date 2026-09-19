=== SocialBUMP Bricks Tweaks ===
Contributors: socialbump
Tags: bricks, acf
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Custom Bricks elements, element conditions and admin tweaks for SocialBUMP sites.

== Description ==

A modular toolkit for Bricks sites. Every feature is a module you switch on or off under SB Bricks Tweaks, and anything switched off is not loaded at all.

Bricks elements:

* Image Carousel: an SEO friendly carousel built on Splide, with real img tags, alt text, ACF gallery support, breakpoint controls, lightbox and continuous scroll.

Conditional logic, added to the Bricks Conditions panel under a SocialBUMP group:

* ACF Repeater: show or hide an element depending on whether a repeater has rows.
* Bricks Content: show or hide depending on whether the post was built with Bricks.
* Post Type: show or hide on one or more post types.

Extras:

* ACF Gallery Loop: loop over an ACF gallery's images in a query loop, with ordering.
* ACF Repeater Ordering: reorder repeater rows in a query loop without changing the saved data.
* Fix ACF CPT SVG Icons: make SVG menu icons on ACF post types behave like the other admin icons.
* Default To WP Editor: open the WordPress editor on posts with no Bricks content.

== Changelog ==
= 1.0.6 =
* Fixed settings not saving properly since the features were split into their own pages. Saving one page wiped what was set on the others, so saving Extras would switch the Conditional Logic features back off and the other way round. Each page now saves only what is on it.
* Fix ACF CPT SVG Icons has moved to SocialBUMP Site Kit, under Admin Settings, because it has nothing to do with Bricks. If you were using it, switch it on there and it behaves exactly as before.

= 1.0.5 =
* HOT FIX: on a site running an older SocialBUMP Site Kit, this plugin could take the site down with a fatal error, because both carry the same shared file and this one loaded it first. It now loads last, so the order no longer matters and an older Site Kit is left alone.

= 1.0.4 =
* New Modules page, the same one Site Kit has. Each group of features sits on a card you can switch on or off, collapse to its title, and drag into the order you want.
* Each group now has a page of its own: Bricks Elements, Conditional Logic and Extras. The menu, the tabs across the top and the admin bar all follow the order you set, and a group that is switched off drops out of all three.
* Saving on a group page now leaves you on that page instead of returning you to the list.

= 1.0.3 =
* The Update now button on the Updates page now runs the update the same way the WordPress dashboard does, under maintenance mode, instead of deactivating and reactivating the plugin. The old way could leave the plugin switched off after an update.

= 1.0.2 =
* The banner now lists every page in the plugin, so you can move between them without going back to the admin menu. Updates shows a waiting version and Publishing shows how many changes are queued.

= 1.0.1 =
* A SocialBUMP overview page collects every plugin on the site, and lets all of them be published from one screen.
* Updating no longer leaves the plugin missing from the menus until you navigate away.
* A site that is not the publishing hub now clears the GitHub token and release notes it has no use for.
* A SocialBUMP Hub page gathers every plugin on the site, with one place to publish them all from. It only appears on the publishing hub.
* Menus now carry the SocialBUMP mark, and publishing lays out in two columns instead of three stretched cards.

= 1.0.0 =
* Admin bar item is now shared: with more than one SocialBUMP plugin active they sit together under a single SocialBUMP menu, each with its own pages.
* Save buttons stay greyed out until something is actually changed, with a reminder that follows you down the page while changes are unsaved.
* Unsaved changes now also warn before you leave the page with something unsaved.
* Save buttons look the same in every SocialBUMP plugin: a plain grey outline when there is nothing to save, amber when there is.
* Page headings now read the plugin name followed by the page you are on.
* The plugin now carries its own notes at docs/context.md, and they can be read and edited on the Publishing page.
* The plugin notes now describe every module and condition in detail, including how it works and what it can be set to.

= 0.1.7 =
* Exported settings file name reads properly for the site it came from.

= 0.1.6 =
* Updates page can now export the settings to a JSON file and import them on another site.

= 0.1.5 =
* Publishing now retries GitHub when it fails, checks the zip attached, and no longer undoes a release that actually went out.
* Publish page fills in the notes box from changes logged since the last release.
* Added an SB Bricks Tweaks shortcut to the admin bar, with a dropdown to each of its pages.
* Admin bar shortcut highlights the plugin and the page you are on.
* New WooCommerce Archive Display condition, so a shop template can switch between a product loop and a category loop without a snippet.
* Modules can now require WooCommerce, and a condition can be checked on an archive where there is no single post.

= 0.1.4 =
* Maintenance release.

= 0.1.2 =
* Fix: the header logo markup was broken by the last release, so the logo did not show.

= 0.1.1 =
* The logo in the header now takes you back to the Features page.

= 0.1.0 =
* First numbered build while the plugin is still being put together.
* Bricks elements: SEO friendly image carousel.
* Conditions: ACF Relationship, ACF Repeater, Bricks Content, Post Type.
* Extras: ACF Gallery Loop, ACF Loop Sorting, Default To WP Editor, Fix ACF CPT SVG Icons.
* ACF features switch themselves off when ACF is only present as the copy bundled with Advanced Themer.

