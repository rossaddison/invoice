<?php

declare(strict_types=1);

Namespace App\Invoice\Libraries;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Log\Logger;

class Lang
{

	/**
	 * List of translations
	 *
	 * @var	array
	 */
	public $_language = [];

	/**
	 * List of loaded language files
	 *
	 * @var	array
	 */
	public $_is_loaded = [];
        
        public Logger $_logger;

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
	    $this->_logger = new Logger();
            $this->_logger->info('Language Class Initialized');            
	}

	// --------------------------------------------------------------------

	/**
	 * Load a language file
	 *
	 * @param array|string $langfile	Language file name
	 * @param string $idiom		Language name (english, etc.)
	 * @param bool $return		Whether to return the loaded array of translations
	 * @param bool $add_suffix	Whether to add suffix to $langfile
	 * @param string $alt_path	Alternative path to look for the language file
	 *
	 * @return array|null|true Array containing translations, if $return is set to true
	 *
	 * @psalm-return array<empty, empty>|null|true
	 */
	public function load(array|string $langfile, $idiom = '', $return = FALSE, $add_suffix = true, $alt_path = '')
	{
		if (is_array($langfile))
		{
			/** @var string $value */
                        foreach ($langfile as $value)
			{
			    // Recursive	
                            $this->load($value, $idiom, $return, $add_suffix, $alt_path);
			}
			return;
		}

		$langfile = str_replace('.php', '', $langfile);

		if ($add_suffix === true)
		{
			$langfile = preg_replace('/_lang$/', '', $langfile).'_lang';
		}

		$langfile .= '.php';

		if (empty($idiom) OR ! preg_match('/^[a-z_-]+$/i', $idiom))
		{
			$idiom = 'English';
		}

		// Load the base file, so any others found can override it
                $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                        '@language' => dirname(__DIR__).DIRECTORY_SEPARATOR.'Language']);
                $path = $aliases->get('@language');
                $basepath = $path.'/'.$idiom.'/'.$langfile;
                $lang = [];
		if (($found = file_exists($basepath)) === true)
		{
		    // $lang is a full array in $basepath	
                    include($basepath);
		}

		if ($found !== true)
		{
			$this->_logger->info('Unable to load the requested language file: language/'.$idiom.'/'.$langfile);
                        $lang = '';
		}
                
                // $lang is declared in basepath 
                if (!is_array($lang))
		{
			if ($return === true)
			{
			     return array();
			}
			return;
		}

		$this->_is_loaded[$langfile] = $idiom;
		$this->_language = array_merge($this->_language, $lang);

		$this->_logger->info('Language file loaded: language/'.$idiom.'/'.$langfile);
		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Language line
	 *
	 * Fetches a single line of text from the language array
	 *
	 * @param	string	$line		Language line key
	 * @param	bool	$log_errors	Whether to log an error message if the line is not found
	 * @return	string|false|mixed	Translation
	 */
	public function line($line, $log_errors = true)
	{
		/** @var string|false|mixed $value */
                $value = isset($this->_language[$line]) ? $this->_language[$line] : FALSE;

		// Because killer robots like unicorns!
		if ($value === FALSE && $log_errors === true)
		{
			$this->_logger->info('Could not find the language line "'.$line.'"');
		}

		return $value;
	}

}
