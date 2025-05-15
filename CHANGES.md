# CHANGES

## Warnings

- `xml_set_object` is deprecated with PHP 8.4. Check the `scorm` mod to find a solution in next Moodle release.

## 4.3.1

- Removed the activity description on the main view.

## 4.3

- Set plugin version to `2023100400` and requires `2023100400`.
- `pix/monologo.png` and `pix/monologo.svg` taken from Moodle 4.3 SCORM plugin.
- Removed dynamic properties (deprecated).
- Removed `classes/xapi` folder which is associated with TRAX Logs plugin.
- Changed `scormlite_get_file_info` PHP docs.
- Removed `FEATURE_GROUPMEMBERSONLY` feature support (deprecated).
- Replaced `print_error` by `throw new \moodle_exception` (deprecated).
