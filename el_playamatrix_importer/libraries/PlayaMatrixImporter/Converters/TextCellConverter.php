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
 * ExpressionEngine Text Cell Converter Class
 *
 * @package		ExpressionEngine
 * @subpackage	Playa & Matrix Importer
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://packettide.com
 */

class TextCellConverter {

	/**
	 * Given an array of Text field settings, returns an EE-compatible equivalent
	 *
	 * @param	array	Decoded and unserialized field_settings for a Text field
	 * @return	array	Equivalent native text field settings
	 */
	public static function convertSettings($settings)
	{
		$defaults = array(
			'field_maxl'				=> 256,
			'field_content_type'		=> 'all',
			'field_text_direction'		=> 'ltr',
			'field_fmt'					=> 'none'
		);

		if (isset($settings['multiline']) && $settings['multiline'] == 'y')
		{
			throw new \Exception('Cannot convert a multi-line text field into a single-line text field. Use TextareaCellConverter instead.');
		}

		foreach (array(
			'maxl' => 'field_maxl',
			'fmt' => 'field_fmt',
			'dir' => 'field_text_direction') as $cell_key => $field_key)
		{
			if (isset($settings[$cell_key]))
			{
				$defaults[$field_key] = $settings[$cell_key];
			}
		}

		// Probably a Number cell, convert to a Text cell with a numeric content type
		if (isset($settings['max_value']))
		{
			$min = isset($settings['min_value']) ? $settings['min_value'] : '';
			$max = $settings['max_value'];

			// But only when both bounds are set and fit inside a signed integer
			// database column
			if (is_numeric($min) && is_numeric($max) &&
				$min >= -2147483648 && $max <= 2147483647)
			{
				$defaults['field_content_type'] = ( ! empty($settings['decimals']) && $settings['decimals'] > 0)
					? 'decimal' : 'integer';
			}
		}

		return $defaults;
	}
}
// EOF