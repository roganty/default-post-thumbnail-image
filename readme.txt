=== Default Post Thumbnail Image ===
Plugin URI: http://devl.roganty.co.uk/dpti/
Contributors: roganty
Author URI: http://www.roganty.co.uk/
Tags: thumbnail, post, attachment
Requires at least: 3.5
Tested up to: 3.8.1
Stable tag: 0.7
License: GPLv2

Set a default thumbnail using an image from your gallery or use your gravatar for posts with no thumbnail set

== Description ==

Set a default thumbnail using either an image from your image gallery or use your gravatar for posts with no thumbnail set.

This plugin requires your theme to have support for post thumbnails enabled.
Although no extra code needs to be added to your theme, some alterations might be required for trouble free usage.


== Installation ==

1. Upload the file and extract it in the /wp-content/plugins/ directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to the Settings -> Media page and select an image.

== Frequently Asked Questions ==

= My selected post thumbnail does not show, why not? =

Although this plugin hooks into the `get_the_post_thumbnail()` function to show your selected image it does not alter your posts, so `has_post_thumbnail()` returns false.

To display the thumbnail without fail remove any if conditions to just leave the `get_the_post_thumbnail()` function

for example, change this
`<?php if ( has_post_thumbnail() ) : ?>
<?php echo get_the_post_thumbnail(); ?>
<?php else : ?>
Some other html
<?php endif; ?>`

to this
`<?php echo get_the_post_thumbnail(); ?>`


== Screenshots ==

Screenshots later

== Changelog ==

= Version 0.7 =

This version adds the option to choose a different users avatar to use instead of the site admin

= Version 0.6 =

Initial release

== Upgrade Notice ==

= 0.7 =

Adds support for choosing different users avatars