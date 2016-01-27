<?php

/**
 * Created by PhpStorm.
 * User: Vlad
 * Date: 26.01.2016
 * Time: 22:13
 */


/**
 * Class fileParser
 */
class fileParser
{

	private static $tmpFile = "/tmp/tmp.txt";
	private static $mainFile = "/tmp/main.txt";
	private static $parseFile;

	const MAX_ARRAY_ELEMENT = 3;

	/**
	 * fileParser constructor.
	 * @param $fileParse
	 */
	public function __construct($fileParse)
	{
		self::$parseFile = $fileParse;
	}

	/**
	 * @return $this
	 * @throws Exception
	 */
	public function parse()
	{

		$handle = self::getFileHandler(self::$parseFile, "r");
		self::flushMainFile();
		self::flushTempFile();

		/**
		 * @param $handle
		 * @return Generator
		 */
		function getStr($handle)
		{
			while (($buffer = fgets($handle)) !== false) {
				$keywords = preg_split("/[\s+]/", preg_replace("/[^\d\w]+/ui", ' ', $buffer));
				yield $keywords;
			}
			fclose($handle);
		}
		$generator = getStr($handle);
		$a = [];
		foreach ($generator as $value) {
			foreach ($value as $item) {
				$a[strtolower($item)] = isset($a[strtolower($item)]) ? $a[strtolower($item)] + 1 : 1;
			}
			if (count($a) >= self::MAX_ARRAY_ELEMENT) {
				$this->writeData($a);
				$a = [];
			}
		}

		$this->writeData($a);
		return $this;
	}

	/**
	 * @throws Exception
	 */
	private static function flushMainFile()
	{
		$mainFile = self::getFileHandler(self::$mainFile, "w+");
		fclose($mainFile);
	}

	/**
	 * @throws Exception
	 */
	private static function flushTempFile()
	{
		$tempFile = self::getFileHandler(self::$tmpFile, "w+");
		fclose($tempFile);
	}

	/**
	 * @param $a
	 * @throws Exception
	 */
	private function writeData($a)
	{
		self::flushTempFile();//отчищаем/создаем временный файл для перебора данных словаря и скидывания промежуточныю данных
		$aDataFile = self::getFileHandler(self::$mainFile, "r");
		$arrayData = [];
		while (($buffer = fgets($aDataFile)) !== false) {

			$tempArray = explode(':', $buffer);
			if (array_key_exists($tempArray[0], $a)) {
				$a[$tempArray[0]] += (int)$tempArray[1];
			} else {
				$arrayData[$tempArray[0]] = (int)$tempArray[1];
			}
			if (count($arrayData) >= self::MAX_ARRAY_ELEMENT) {
				$this->writeToTemp($arrayData);
				$arrayData = [];
			}
		}

		$this->writeToTemp($arrayData);
		$this->writeToTemp($a);//скидываем дополненные массив a
		fclose($aDataFile);
		copy(self::$tmpFile, self::$mainFile);//основной файл заменяем временным

	}

	/**
	 * @param $a
	 * @throws Exception
	 */
	private function writeToTemp($a)
	{
		$tempFile = self::getFileHandler(self::$tmpFile, "a");
		array_walk($a, function ($item, $key) use ($tempFile) {
				fwrite($tempFile, $key . ':' . $item .PHP_EOL);
		});
		fclose($tempFile);
	}

	/**
	 * @param $file
	 * @param $mode
	 * @return resource
	 * @throws Exception
	 */
	public static function getFileHandler($file, $mode)
	{
		$handle = fopen($file, $mode);
		if (!$handle){
			throw new Exception("Can't open file " .  $file);
		}
		return $handle;
	}
	
}
