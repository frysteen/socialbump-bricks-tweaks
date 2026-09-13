=== SocialBUMP Bricks Tweaks ===
Contributors: socialbump
Tags: bricks, acf
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.2
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

