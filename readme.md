# moodle-filter_metadata

The metadata filter plugin has been created as a companion plugin to the metadata local plugin
(see https://github.com/PoetOS/moodle-local_metadata). It allows metadata values to be extracted and displayed in place in
Moodle text outputs.

To specify a field value to be displayed in a Moodle page, use the following format:

    {{metadata::_context_=_instanceid_::_fieldshortname_}}

- _context_ is a valid context subplugin name. e.g. "course", "module".
- The "=_instanceid_" is optional. If specified, _instanceid_ is the Moodle instance id of a context (e.g. course id). If not
specified, the filter will attempt to determine if there is a valid instance id from the context of the page being viewed.
For example, a course id on a course page, or a module id on an activity page.
- The _fieldshortname_ is the shorname of a metadata field.
