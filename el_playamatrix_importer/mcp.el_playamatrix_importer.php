<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(PATH_THIRD.'el_playamatrix_importer/libraries/autoload.php');

/**
 * ExpressionEngine - by Packet Tide
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2013, Packet Tide, LLC
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://packettide.com
 * @since		Version 2.10.2
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Playa to Relationships Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		Packet Tide
 * @link		http://packettide.com
 */

class El_playamatrix_importer_mcp {

	public $return_data;

	private $_base_url;	
	private $site_id = 1;
	private $_form_base_url;

	private $EE3 = FALSE;
	private $EE4 = FALSE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if (defined('APP_VER') && version_compare(APP_VER, '3.0.0', '>='))
		{
			$this->EE3 = TRUE;
		}

		// Field groups shared between channels and the current publish
		// layout format arrived in EE4
		if (defined('APP_VER') && version_compare(APP_VER, '4.0.0', '>='))
		{
			$this->EE4 = TRUE;
		}

		$this->site_id = ee()->config->item('site_id');
		
		$this->_form_base_url = ($this->EE3) ?  ee('CP/URL', 'addons/settings/el_playamatrix_importer') : 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=el_playamatrix_importer';
		$this->_base_url = ($this->EE3) ? $this->_form_base_url : BASE.AMP.$this->_form_base_url;

		if ($this->EE3)
		{
			ee()->cp->set_right_nav(array('module_home' => $this->_base_url));
			ee()->view->cp_page_title = lang('el_playamatrix_importer_module_name');
		}
	}

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		ee()->load->model('addons_model');

		if ( ! ee()->addons_model->fieldtype_installed('grid') OR ! ee()->addons_model->fieldtype_installed('relationship'))
		{
			return '<p class="notice">'.lang('grid_relationships_not_installed').'</p>';
		}
	
		$fields = array();

		$channel_fields = ee()->db
			->select('field_id, field_name, field_label, field_type')
			->where_in('field_type', array('matrix', 'playa'))
			->where_in('site_id', array(0, $this->site_id))
			->order_by('field_id')
			->get('channel_fields')
			->result_array();

		// Existing Grid and Relationship fields, used to flag fields that have already been imported
		$imported_fields = array();

		$existing_fields = ee()->db
			->select('field_name, field_type')
			->where_in('field_type', array('grid', 'relationship'))
			->where_in('site_id', array(0, $this->site_id))
			->get('channel_fields')
			->result_array();

		foreach ($existing_fields as $existing_field)
		{
			$imported_fields[$existing_field['field_name']] = $existing_field['field_type'];
		}

		foreach ($channel_fields as $channel_field)
		{
			$label = htmlspecialchars('['.$channel_field['field_id'].'] '.$channel_field['field_label'].': '.$channel_field['field_name'].' - ['.$channel_field['field_type'].']', ENT_QUOTES, 'UTF-8');

			if ($channel_field['field_type'] == 'matrix')
			{
				$new_name = $channel_field['field_name'].'_grid';
				$new_type = 'grid';
			}
			else
			{
				$new_name = $channel_field['field_name'].'_relate';
				$new_type = 'relationship';
			}

			// Fields renamed on import hand their original name to the new field
			if (preg_match('/^__(.+)_(matrix|playa)$/', $channel_field['field_name'], $matches) &&
				isset($imported_fields[$matches[1]]) && $imported_fields[$matches[1]] == $new_type)
			{
				$new_name = $matches[1];
			}

			if (isset($imported_fields[$new_name]) && $imported_fields[$new_name] == $new_type)
			{
				$label .= ' <i>('.sprintf(lang('already_imported'), htmlspecialchars($new_name, ENT_QUOTES, 'UTF-8'), $new_type).')</i>';
			}

			$fields[$channel_field['field_id']] = $label;
		}
		
		$text_fields = ee()->db
			->select('field_id, field_name, field_label, field_type')
			->where_in('field_type', array('text', 'textarea'))
			->where_in('site_id', array(0, $this->site_id))
			->order_by('field_id')
			->get('channel_fields')
			->result_array();

		foreach ($text_fields as $text_field)
		{
			$target_field[$text_field['field_id']] = '['.$text_field['field_id'].'] '.$text_field['field_label'].': '.$text_field['field_name'].' - ['.$text_field['field_type'].']';
		}
		
		
		if ($this->EE3)
		{
		
			// Form definition array
			$vars['sections'] = array(
			  array(
					  
				array(
				  'title' => 'el_playamatrix_fields',
				  'desc' => 'el_playamatrix_importer_module_long_description',
				  'fields' => array(
					'fields' => array(
					  'type' => 'checkbox',
					  'choices' => $fields,
					  //'value' => $checked_values
					)
				  )
				)

			  )
			);

			// Optional steps to swap the new fields in for the old ones
			if ($this->EE4)
			{
				foreach (array('rename_fields', 'remove_old_fields', 'replace_in_layouts') as $option)
				{
					$vars['sections']['cutover_options'][] = array(
						'title' => $option,
						'desc' => $option.'_desc',
						'fields' => array(
							$option => array(
								'type' => 'yes_no',
								'value' => 'n'
							)
						)
					);
				}
			}

			// Final view variables we need to render the form
			$vars += array(
			  'base_url' => $this->_base_url.AMP.'method=do_import',
			  'cp_page_title' => lang('el_playamatrix_importer_module_description'),
			  'save_btn_text' => 'btn_import',
			  'save_btn_text_working' => 'btn_import'
			);

			return ee('View')->make('ee:_shared/form')->render($vars);
		
		}
		else
		{
			ee()->load->library('table');
			ee()->load->helper('form');
			
			ee()->view->cp_page_title = ee()->lang->line('el_playamatrix_importer_module_name');
		
			$message = '';

			if (ee()->session->flashdata('message_success'))
			{
				$message = '<p class="notice success"><b>'.ee()->session->flashdata('message_success').'</b></p>';
			}
			elseif (ee()->session->flashdata('message_error'))
			{
				$message = '<p class="notice failure"><b>'.ee()->session->flashdata('message_error').'</b></p>';
			}
		
			$vars['message'] = $message;
			$vars['fields'] = $fields;
			$vars['form_action'] = $this->_form_base_url.AMP.'method=do_import';

			return ee()->load->view('index', $vars, TRUE);

		}
		

	}

	/**
	 * Does the import
	 *
	 * @return 	void
	 */
	public function do_import()
	{
		// Attempt to get more time
		@set_time_limit(0);
		
		$fields = ee()->input->post('fields');
		
		if (is_array($fields) && !empty($fields))
		{

			// The install I was testing this on had an ff_settings field which
			// was set to NOT NULL, causing us not to be able to add new fields;
			// not sure how that happened, so putting this here just in case
			if (ee()->db->field_exists('ff_settings', 'channel_fields'))
			{
				ee()->load->library('smartforge');
				ee()->smartforge->modify_column(
					'channel_fields',
					array(
						'ff_settings'	=> array(
							'name'		=> 'ff_settings',
							'type'		=> 'mediumtext',
							'null'		=> TRUE
						)
					)
				);
			}

			ee()->load->library(array('playa_importer', 'matrix_importer'));

			ee()->load->model('addons_model');

			$new_relationship_ids = array();
			$new_grid_ids = array();

			// Do the import
			if (ee()->addons_model->fieldtype_installed('playa'))
			{
				$new_relationship_ids = ee()->playa_importer->do_import($fields);
			}

			if (ee()->addons_model->fieldtype_installed('matrix'))
			{
				$new_grid_ids = ee()->matrix_importer->do_import($fields);
			}
			
			// Update field groups
			$this->import_field_groups($new_relationship_ids+$new_grid_ids);

			$cutover_messages = array();
			if ($this->EE4)
			{
				$cutover_messages = $this->cutover_fields($new_relationship_ids+$new_grid_ids, array(
					'rename_fields'			=> (ee()->input->post('rename_fields') == 'y'),
					'remove_old_fields'		=> (ee()->input->post('remove_old_fields') == 'y'),
					'replace_in_layouts'	=> (ee()->input->post('replace_in_layouts') == 'y')
				));
			}

			if ($this->EE3)
			{
				$alert = ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('import_success'))
					->addToBody(sprintf(lang('import_completed'), count($new_relationship_ids), count($new_grid_ids)));

				if ( ! empty($cutover_messages))
				{
					$alert->addToBody($cutover_messages);
				}

				$alert->defer();
			}
			else
			{
				ee()->session->set_flashdata('success', sprintf(lang('import_completed'), count($new_relationship_ids), count($new_grid_ids)));
			}
		}
		else
		{
			if ($this->EE3) 
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('import_fail'))
					->addToBody(lang('import_no_fields'))
					->defer();
			}
			else
			{
				ee()->session->set_flashdata('success', 'import_no_fields');
			}
			
		}

		ee()->functions->redirect($this->_base_url);
	}
	
	/**
	 * Copies over field groups from Matrix fields into their new Grid fields'
	 *
	 * @param	array	Associative array of Matrix field IDs to their corresponding Grid field IDs
	 * @return	void
	 */
	private function import_field_groups($imported_fields)
	{
		foreach ($imported_fields as $old_field_id => $new_field_id) 
		{
			if (ee()->db->table_exists('channel_field_groups_fields'))
			{
				$rows = ee()->db->where('field_id', $old_field_id)
					->get('channel_field_groups_fields')
					->result_array();
			
				foreach ($rows as $row) {
					$data = array(
						'field_id' => $new_field_id,
						'group_id' => $row['group_id']
					);
					ee()->db->insert('channel_field_groups_fields', $data);
				}
			}
			
			if (ee()->db->table_exists('channels_channel_fields'))
			{
				$rows = ee()->db->where('field_id', $old_field_id)
					->get('channels_channel_fields')
					->result_array();
			
				foreach ($rows as $row) {
					$data = array(
						'field_id' => $new_field_id,
						'channel_id' => $row['channel_id']
					);
					ee()->db->insert('channels_channel_fields', $data);
				}
			}
		}
	}

	/**
	 * Optionally swaps newly imported fields in for the fields they were
	 * imported from: the new field takes the original name, the old field
	 * leaves its channels and field groups, and publish layouts show the
	 * new field in the old field's place
	 *
	 * @param	array	Associative array of old field IDs to new field IDs
	 * @param	array	Which steps to run, keyed by option name
	 * @return	array	Messages describing what was done
	 */
	private function cutover_fields($imported_fields, $options)
	{
		$messages = array();

		foreach ($imported_fields as $old_field_id => $new_field_id)
		{
			$old_field = ee()->db->where('field_id', $old_field_id)
				->get('channel_fields')
				->row_array();

			if (empty($old_field))
			{
				continue;
			}

			$name = $old_field['field_name'];
			$old_name = $name;

			if ($options['rename_fields'])
			{
				$suffix = ($old_field['field_type'] == 'playa') ? 'playa' : 'matrix';
				$old_name = $this->get_free_field_name('__', $name, '_'.$suffix, $old_field['site_id']);

				// Free up the original name first, field names are unique
				ee()->db->where('field_id', $old_field_id)
					->update('channel_fields', array(
						'field_name'	=> $old_name,
						'field_label'	=> substr('__'.$old_field['field_label'].' '.ucfirst($suffix), 0, 50)
					));

				ee()->db->where('field_id', $new_field_id)
					->update('channel_fields', array(
						'field_name'	=> $name,
						'field_label'	=> $old_field['field_label']
					));

				$messages[] = sprintf(lang('cutover_renamed'), $name, $old_name);
			}

			if ($options['remove_old_fields'])
			{
				ee()->db->where('field_id', $old_field_id)->delete('channel_field_groups_fields');
				ee()->db->where('field_id', $old_field_id)->delete('channels_channel_fields');

				$messages[] = sprintf(lang('cutover_removed'), $old_name);
			}

			if ($options['replace_in_layouts'])
			{
				$messages[] = sprintf(
					lang('cutover_layouts'),
					$name,
					$this->replace_in_layouts($old_field_id, $new_field_id)
				);
			}
		}

		return $messages;
	}

	/**
	 * Builds a field name from a prefix, name and suffix that isn't in use,
	 * shortening the name to keep within the 32 character limit
	 *
	 * @param	string	Prefix, e.g. '__'
	 * @param	string	Original field name
	 * @param	string	Suffix, e.g. '_matrix'
	 * @param	int		Site ID
	 * @return	string	Unused field name
	 */
	private function get_free_field_name($prefix, $name, $suffix, $site_id)
	{
		$number = '';

		while (TRUE)
		{
			$candidate = $prefix.substr($name, 0, 32 - strlen($prefix.$suffix.$number)).$suffix.$number;

			$taken = ee()->db->where('field_name', $candidate)
				->where_in('site_id', array(0, $site_id))
				->count_all_results('channel_fields');

			if ( ! $taken)
			{
				return $candidate;
			}

			$number = ($number === '') ? 2 : $number + 1;
		}
	}

	/**
	 * Puts a new field where an old field was in every publish layout,
	 * keeping its visible and collapsed state; EE adds new fields to the
	 * end of a layout's first tab, so that copy is removed
	 *
	 * @param	int		Old field ID
	 * @param	int		New field ID
	 * @return	int		Number of layouts changed
	 */
	private function replace_in_layouts($old_field_id, $new_field_id)
	{
		$old = 'field_id_'.$old_field_id;
		$new = 'field_id_'.$new_field_id;
		$count = 0;

		// Quoted so field_id_1 doesn't match field_id_10
		$layouts = ee()->db->select('layout_id, field_layout')
			->like('field_layout', '"'.$old.'"')
			->get('layout_publish')
			->result_array();

		foreach ($layouts as $layout)
		{
			$tabs = @unserialize($layout['field_layout']);

			if ( ! is_array($tabs))
			{
				continue;
			}

			$replaced = FALSE;

			foreach ($tabs as $t => $tab)
			{
				if (empty($tab['fields']))
				{
					continue;
				}

				foreach ($tab['fields'] as $f => $field)
				{
					if ($field['field'] == $new)
					{
						unset($tabs[$t]['fields'][$f]);
					}
					elseif ($field['field'] == $old)
					{
						$tabs[$t]['fields'][$f]['field'] = $new;
						$replaced = TRUE;
					}
				}

				$tabs[$t]['fields'] = array_values($tabs[$t]['fields']);
			}

			if ($replaced)
			{
				ee()->db->where('layout_id', $layout['layout_id'])
					->update('layout_publish', array('field_layout' => serialize($tabs)));

				$count++;
			}
		}

		return $count;
	}

}
// EOF