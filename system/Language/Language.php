<?php namespace CodeIgniter\Language;

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	CodeIgniter Dev Team
 * @copyright	Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 3.0.0
 * @filesource
 */

use Config\Services;

class Language
{
	/**
	 * Stores the retrieved language lines
	 * from files for faster retrieval on
	 * second use.
	 *
	 * @var array
	 */
	protected $language = [];

	/**
	 * The current language/locale to work with.
	 *
	 * @var string
	 */
	protected $locale;

	/**
	 * Boolean value whether the intl
	 * libraries exist on the system.
	 *
	 * @var bool
	 */
	protected $intlSupport = false;

	/**
	 * Stores filenames that have been
	 * loaded so that we don't load them again.
	 *
	 * @var array
	 */
	protected $loadedFiles = [];

	//--------------------------------------------------------------------

	public function __construct(string $locale)
	{
		$this->locale = $locale;

		if (class_exists('\MessageFormatter'))
		{
			$this->intlSupport = true;
		};
	}

	//--------------------------------------------------------------------

	/**
	 * Parses the language string for a file, loads the file, if necessary,
	 * getting
	 *
	 * @param string $line
	 * @param array  $args
	 *
	 * @return string
	 */
	public function getLine(string $line, array $args = []): string
	{
		// Parse out the file name and the actual alias.
		// Will load the language file and strings.
		list($file, $line) = $this->parseLine($line);

		$output = isset($this->language[$file][$line])
			? $this->language[$file][$line]
			: $line;

		// Do advanced message formatting here
		// if the 'intl' extension is available.
		if ($this->intlSupport && count($args))
		{
			$output = \MessageFormatter::formatMessage($this->locale, $output, $args);
		}

		return $output;
	}

	//--------------------------------------------------------------------

	/**
	 * Parses the language string which should include the
	 * filename as the first segment (separated by period).
	 *
	 * @param string $line
	 *
	 * @return string
	 */
	protected function parseLine(string $line): array
	{
		if (strpos($line, '.') === false)
		{
			throw new \InvalidArgumentException('No language file specified in line: '.$line);
		}

		$file = substr($line, 0, strpos($line, '.'));
		$line = substr($line, strlen($file)+1);

		if (! array_key_exists($line, $this->language))
		{
			$this->load($file, $this->locale);
		}

		return [
			$file,
			$this->language[$line] ?? $line
		];
	}

	//--------------------------------------------------------------------

	/**
	 * Loads a language file in the current locale. If $return is true,
	 * will return the file's contents, otherwise will merge with
	 * the existing language lines.
	 *
	 * @param string $file
	 * @param string $locale
	 * @param bool   $return
	 *
	 * @return array|null
	 */
	protected function load(string $file, string $locale, bool $return = false)
	{
		if (in_array($file, $this->loadedFiles))
		{
			return [];
		}

		if (! array_key_exists($file, $this->language))
		{
			$this->language[$file] = [];
		}

		$path = "Language/{$locale}/{$file}.php";

		$lang = $this->requireFile($path);

		// Don't load it more than once.
		$this->loadedFiles[] = $file;

		if ($return)
		{
			return $lang;
		}

		// Merge our string
		$this->language[$file] = $lang;
	}

	//--------------------------------------------------------------------

	/**
	 * A simple method for including files that can be
	 * overridden during testing.
	 *
	 * @todo - should look into loading from other locations, also probably...
	 *
	 * @param string $path
	 *
	 * @return array
	 */
	protected function requireFile(string $path): array
	{
        $files = service('locator')->search($path);

		foreach ($files as $file)
		{
			if (! is_file($file))
			{
				continue;
			}

			return require_once $file;
		}

		return [];
	}

	//--------------------------------------------------------------------

}
