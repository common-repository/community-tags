=== Plugin Name ===
Contributors: Rob Sawyer, Matt Mullenweg
Donate link: http://fundabrotha.com/
Tags: tags, social, community
Requires at least: 2.7.1
Tested up to: 2.7.1
Stable tag: 0.2

This plugin was extended from Matt's Community Tags and it allows the community to add tags to your posts. The tags should be added with commas and once they are added they show up in your administration panel for moderation.


== Description ==

Community Tags is useful if you would like the community to be able to add tags to your posts. This is particularly useful for photos. Once a user adds a tag the tag is held in a proposed tags array and held for moderation.

== Installation ==


e.g.

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php $post_id = the_ID(); <div id="tagthis-$post_id" class="tagthis" style="display:none"></div> ?>` in your post loop.

== Frequently Asked Questions ==

= Can I moderate the tags? =

Yes, currently the plugin requires that you moderate the tags.

== Screenshots ==

1. Administration panel
2. Administration panel with no tags
