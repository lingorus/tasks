<?php

/**
 * Created by PhpStorm.
 * User: Vlad
 * Date: 27.01.2016
 * Time: 10:10
 */
class Db
{
	private static $db;

	private static $host = 'localhost';
	private static $port = '3306';
	private static $dbName = 'videos';
	private static $user = '';
	private static $pass = '';



	private function __construct()
	{

	}

	public static function getInstance()
	{
		if (!self::$db){
			self::$db =  new PDO('mysql:host=' .self::$host .  ';dbname=' . self::$dbName . ';port=' . self::$port, self::$user, self::$pass);
		}
		return self::$db;
	}



}