=== SocialBUMP Bricks Tweaks ===
Contributors: socialbump
Tags: bricks, acf
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
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
= 1.3.0 =
* New: ACF Relationship Ordering, for sorting relationship and post object query loops

= 1.2.2 =
* Change: the Bricks Content options read as Built with Bricks and Not built with Bricks

= 1.2.1 =
* Fix: the Bricks Content value was an empty dropdown, so it had to be typed by hand
* Change: the value now reads Set or Not set

= 1.2.0 =
* Change: the Bricks Content condition now reads as Is / Is not with a value, matching the built in conditions. Conditions saved in the old format keep working.

= 1.1.0 =
* New: ACF Relationship condition, for relationship and post object fields
* New: Updates and Publishing now have their own sub pages
* New: the version in the header links to Updates and turns red when one is available
* Plugin now declares its WordPress and PHP requirements, and ships an icon
* Module card accent follows the admin colour scheme
* A module that fails to load is skipped instead of breaking the site

= 1.0.0 =
* First full release.
* Bricks element: SEO friendly Image Carousel built on Splide.
* Element conditions: ACF Repeater, Bricks Content and Post Type, each its own toggle.
* ACF Gallery Loop: loop over a gallery in a query loop, with ordering.
* ACF Repeater Ordering: reorder repeater rows in a loop without changing the saved data.
* Fix ACF CPT SVG Icons, with an icon colour setting.
* Default To WP Editor on posts with no Bricks content.
* Own admin menu, grouped settings, per module settings and dependency checks.
* Updates delivered from the hub through GitHub Releases.

