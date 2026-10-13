<?php

namespace PlayaMatrixImporter\Converters;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://packettide.com
 * @since		Version 2.10.2
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Options Cell Converter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://packettide.com
 */

class OptionsCellConverter {

	/**
	 * Given an array of field settings an options-type field, returns an EE-compatible equivalent
	 *
	 * @param	array	Decoded and unserialized field_settings for an options field
	 * @param	bool	Whether native option fields support value/label pairs (EE3+)
	 * @return	array	Equivalent native field settings fit for any options field
	 */
	public static function convertSettings($settings, $value_label_pairs = FALSE)
	{
		$defaults = array(
			'field_pre_populate'	=> 'n',
			'field_fmt'				=> 'none',
			'field_list_items'		=> array(),
		);

		$options = self::getOptions($settings);

		// Keep the stored values when they differ from their labels so
		// existing data and template conditionals carry on working
		if ($value_label_pairs && self::hasDistinctValues($options))
		{
			$defaults['field_pre_populate'] = 'v';
			$defaults['field_list_items'] = '';
			$defaults['value_label_pairs'] = $options;
		}
		else if ( ! empty($options))
		{
			$defaults['field_list_items'] = implode("\n", $options);
		}

		return $defaults;
	}

	/**
	 * Given cell data from an options-type cell, returns data in the format
	 * native option fields expect
	 *
	 * @param	string	Matrix cell data
	 * @param	array	Decoded and unserialized settings for the cell's column
	 * @param	bool	Whether native option fields support value/label pairs (EE3+)
	 * @return	string	Converted cell data
	 */
	public static function convertData($data, $settings, $value_label_pairs = FALSE)
	{
		// Empty field? Do nothing
		if (empty($data))
		{
			return '';
		}

		$options = self::getOptions($settings);
		$keep_values = ($value_label_pairs && self::hasDistinctValues($options));

		// Some celltypes allow for key => value options to be stored and they
		// subsequently store the keys in the data table; without value/label
		// pairs EE only works with values so we need to map those keys to
		// their respective values and store those instead
		if (isset($settings['options']))
		{
			// Matrix data is stored separated by linebreak
			$data = explode("\n", $data);

			$new_data = array();

			// For each option, find its corresponding value
			foreach ($data as $option)
			{
				// Stored values are kept as they are, even ones no longer in
				// the options list, so templates see the same data as before
				if ($keep_values && $option !== '')
				{
					$new_data[] = $option;
				}
				elseif (isset($options[$option]))
				{
					$new_data[] = $options[$option];
				}
			}

			// EE data is stored pipe-delimited
			return implode('|', $new_data);
		}

		// For Switch celltypes
		if ($keep_values)
		{
			return $data;
		}

		if (isset($options[$data]))
		{
			return $options[$data];
		}

		// Hopefully should never get here in practice
		return '';
	}

	/**
	 * Gets a flat array of stored values to labels from an options-type
	 * cell's settings
	 *
	 * @param	array	Decoded and unserialized settings for the cell's column
	 * @return	array	Stored values => labels
	 */
	private static function getOptions($settings)
	{
		$options = array();

		if (isset($settings['options']) && is_array($settings['options']))
		{
			// Options may be separated in to groups, we need to flatten the array
			$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($settings['options']));
			foreach ($iterator as $value => $label)
			{
				$options[$value] = $label;
			}
		}
		// Switch celltype
		else if (isset($settings['off_label']) && isset($settings['on_label']))
		{
			$options[isset($settings['off_val']) ? $settings['off_val'] : $settings['off_label']] = $settings['off_label'];
			$options[isset($settings['on_val']) ? $settings['on_val'] : $settings['on_label']] = $settings['on_label'];
		}

		return $options;
	}

	/**
	 * Whether any option's stored value differs from its label
	 *
	 * @param	array	Stored values => labels
	 * @return	bool
	 */
	private static function hasDistinctValues($options)
	{
		foreach ($options as $value => $label)
		{
			if ((string) $value !== (string) $label)
			{
				return TRUE;
			}
		}

		return FALSE;
	}
}
