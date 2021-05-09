<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Language class
*/
class Language {
	private $default = 'en-gb/default';
	private $directory;
	public $data = array();
	
	/**
	 * Constructor
	 *
	 * @param	string	$file
	 *
 	*/
	public function __construct($directory = '') {
		$this->directory = $directory;
	}
	
	/**
     * 
     *
     * @param	string	$key
	 * 
	 * @return	string
     */
	public function get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : $key);
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	/**
     * 
     *
	 * @return	array
     */	
	public function all() {
		return $this->data;
	}
	
	/**
     * 
     *
     * @param	string	$filename
	 * @param	string	$key
     * @param   string $store_lang_directory
	 * 
	 * @return	array
     */	
	public function load($filename, $key = '', $store_lang_directory = '') {

		if (!$key) {
			$_ = array();
	
			$file = DIR_LANGUAGE . $this->default . '/' . $filename . '.php';
	
			if (is_file($file)) {
				require($file);
			}
	
			$file = DIR_LANGUAGE . $this->directory . '/' . $filename . '.php';
			
			if (is_file($file)) {
				require($file);
			}
	
			$this->data = array_merge($this->data, $_);

			/** TSG start changes - changes for multi sites
             *
             */
            $file = DIR_LANGUAGE .  $this->directory . '/'. $store_lang_directory . '/' . $filename . '.php';

            if (is_file($file)) {
                require($file);
            }

            $this->data = array_merge($this->data, $_);

            /** END changes ***/

        } else {
			// Put the language into a sub key
			$this->data[$key] = new Language($this->directory);
			$this->data[$key]->load($filename);
		}
		
		return $this->data;
	}
}