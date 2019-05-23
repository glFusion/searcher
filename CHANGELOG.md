# Changelog - Searcher plugin for glFusion

## Version 1.0.0
Release 2019-05-23
- Add `type_item` key for more efficient reindexing
- Implement more flexible method to install plugin configurations
- Add table check to prevent callback functions from causing errors during plugin removal
- Accept either `q` or `query` as search parameter.
- Add missing `DB_query()` call to `Indexer::RemoveComments()`.
- Fix directory name when locating stemmer classes for configuration.
- Add admin option to update title/author/content weights directly.

## Version 0.1.1
Release 2018-04-23
- Fix button to clear counters in admin.
- Provide the ability to add custom stopwords via custom language file.
- Queue database writes during batch indexing to improve performance
- Handle multiple child IDs in `plugin_itemdeleted` function
- Do not index disabled or draft documents
- Add Soundex and Metaphone stemmer classes

## Version 0.1.0
Initial Public Release 2017-10-06
