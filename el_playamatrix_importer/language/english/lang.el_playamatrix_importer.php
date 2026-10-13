<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(
	'el_playamatrix_importer_module_name' =>
	'Playa & Matrix Importer',

	'el_playamatrix_importer_module_description' =>
	'Creates new Relationships and Grid fields from Playa and Matrix fields.',

	'el_playamatrix_importer_module_long_description' => "<p>This module will import existing Playa and Matrix fields into new Relationship and Grid fields. The import is <b>non-destructive</b>, meaning it doesn't convert or change the existing fields or data in any way. It creates <b>new</b> fields and copies the data over to them, so you have a chance to inspect and check that the import has done its job before you commit to the new fields.</p><p>Backup your database in case you want to rollback, then click Import to get started.</p>",

	'el_playamatrix_fields' => 'Playa & Matrix Fields',

	'module_home' => 'Playa & Matrix Importer Home',

	'grid_relationships_not_installed' => 'Please make sure both Grid and Relationships are installed before importing.',

	'btn_import' => 'Import',

	'import_success' => 'Import Successful',
	'import_fail' => 'Import Failed',
	'import_completed' => 'Success: Import has created %d new Relationship fields and %d new Grid fields',
	'import_no_fields' => 'Failed: No Matrix or Playa fields selected',
	'already_imported' => 'already imported as %s [%s]',

	'cutover_options' => 'After import',
	'rename_fields' => 'Give new fields the original names',
	'rename_fields_desc' => 'Renames each old field to <b>__name_matrix</b> (or <b>__name_playa</b>) with its label prefixed by __, then gives the new field the original name and label so templates and add-ons that use the name carry on working.',
	'remove_old_fields' => 'Remove old fields from channels',
	'remove_old_fields_desc' => 'Removes each old field from its field groups and channels. The field and its data are kept so they can be checked or restored.',
	'replace_in_layouts' => 'Replace old fields in publish layouts',
	'replace_in_layouts_desc' => 'Puts each new field in the old field\'s place in every publish layout, keeping whether it is visible or collapsed.',
	'cutover_renamed' => '%1$s renamed to %2$s; the new field is now %1$s',
	'cutover_removed' => '%s removed from its channels and field groups',
	'cutover_layouts' => '%s replaced in %d publish layout(s)'
);

// EOF