=== SocialBUMP Bricks Tweaks ===
Contributors: socialbump
Tags: bricks, acf
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
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

Updates are published from the hub site through GitHub Releases, so they appear in WordPress and MainWP like any other plugin update.

== Changelog ==

= 1.2.0 =
* New: ACF Gallery Loop, loop over a gallery and control the markup yourself
* New: ACF Repeater Ordering for query loops
* New: element conditions split into three separate toggles
* New: Post Type condition now accepts multiple post types
* New: Fix ACF CPT SVG Icons, with an icon colour setting
* New: Default To WP Editor on posts with no Bricks content
* New: own admin menu, grouped settings and per module settings
* Modules that need ACF grey out when it is not active
* Internal names moved to the sbbt prefix
* Added readme.txt so View version details works

= 1.1.2 =
* Current release.
