=== Sphinx Search ===
Contributors: jshreve, andy
Tags: search,gsoc,api,search-api,sphinx
Tested up to: 2.8.1
Stable tag: 1.0.0
Requires at least: 2.8
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6969492

Sphinx Search is a plugin that uses the Search API plugin and Sphinx as a backend for searching. It searches comments, posts, and pages and also allow filtering by type. The plugin also supports an extended query language (you can search just by title, etc.)

== Description ==

Sphinx Search is distributed as another example of the Search API project and uses Sphinx has a backend for searching.

The plugin requires some additional server side work (installing and configuring Sphinx, a cron job, etc.), so it should only be used by advanced users. Details on how to install the plugin can be found on the Installation page.

== Changelog ==

= 1.0.0 =

* Initial Version

== Installation ==

To install Sphinx you will need ssh access and the proper permissions for installing and compiling applications. The Sphinx Search website <a href="http://www.sphinxsearch.com/docs/current.html#installation">contains a tutorial on how to install Sphinx</a>. 

1. Make sure that the Search API plugin has been installed.
2. Upload the files in this directory in this archive to 'wp-content/plugins/search/'
3. Go to the Plugins page and activate "Sphinx Search". Make sure all other search plugins have been disabled.
4. Next <a href="http://www.sphinxsearch.com/docs/current.html#quick-tour">set up a configuration file</a>. There is a sphinx.conf within this archive that you should base your Sphinx configuration on. Make sure to edit the SQL username, password, and database paths as well as the table name in the configuration file. Follow the directions in the Sphinx documentation on how to use the Sphinx configuration file.
5. Finally, set up a cron job to reindex the data. This step depends on your system and if your host allows cron jobs. Some hosting control panels allow you to add this feature. The job should run the following command (paths may need to be adjusted).

indexer --all --config /path/to/sphinx.conf

== Issue Tracker ==

WordPress Search API has an <a href="http://code.google.com/p/wpsearchapi/issues/list">issue tracker</a>. Please submit any bug reports or feature suggestions here.