<?php

class c_usuario extends c_controller
{


	public static function solvedProblems($request)
	{
		$query = "select distinct probID from Ejecucion where userID = ? AND status = 'OK' order by probID";
		$reques["userID"];
	}

	public static function canCreateContest($request)
	{
			$consulta = "select COUNT( DISTINCT probID ) from Ejecucion where ( userID = '". addslashes( $_SESSION['userID'] ) ."' AND  status = 'OK' )";
			$resultado = mysql_query($consulta) or die('Algo anda mal: ' . mysql_error());
			$row = mysql_fetch_array($resultado);
	}

	public static function runs($request)
	{
		$sql = "SELECT `execID`, `userID`, `probID`, `status`, `tiempo`, `fecha`, `LANG`, `Concurso`  FROM `Ejecucion`  where userID = ? order by fecha desc limit 100";
		$inputarray = array( $request["user"] );

		global $db;
		$result = $db->Execute($sql, $inputarray);

		return array(
				"result" => "ok",
				"runs" => $result->GetArray()
			);
	}

	/**
	 * @param nick
	 * @param mail
	 *
	 *
	 * */
	public static function getByNickOrEmail($request)
	{
		$searchValue = null;
		if (isset($request["nick"]))
		{
			$searchValue = $request["nick"];
		}
		else if (isset($request["mail"]))
		{
			$searchValue = $request["mail"];
		}
		else if (isset($request["user"]))
		{
			$searchValue = $request["user"];
		}

		$sql = "select * from Usuario where userID = ? or mail = ? limit 1";
		$inputarray = array($searchValue, $searchValue);

		global $db;
		$result = $db->Execute($sql, $inputarray);
		$resultData = $result->GetArray();

		if (sizeof($resultData) == 0)
		{
			return array(
					"result" => "ok",
					"user" => null
				);
		}

		// Calcular el rank
		if( $resultData[0]["solved"] != 0 )
		{
			$rat = ($resultData[0]["solved"]/$resultData[0]["tried"])*100;
			$resultData[0]["ratio"] = substr( $rat , 0 , 5 ) . "%";
		}
		else
			$resultData[0]["ratio"] = "0.0%";

		return array(
				"result" => "ok",
				"user" => $resultData[0]
			);
	}

	public static function rank($request = null)
	{
		$sql = "select * from Usuario order by solved DESC, tried ASC ;";
		$inputarray = array();

		global $db;
		$result = $db->Execute($sql, $inputarray);

		return array(
				"result" => "ok", 
				"rank" => $result->GetArray()
			);
	}

	/**
	 *
	 * @param nombre
	 * @param email
	 * @param password
	 * @param ubicacion
	 * @param escuela
	 * @param nick
	 * @param twitter
	 *
	 * */
	public static function nuevo($request)
	{
		$result = self::getByNickOrEmail($request);
		if (!is_null($result["user"]))
		{
			return array( "result" => "error", "reason" => "Este usuario/email ya estan registrados." );
		}

		$sql = "insert into Usuario (userID, nombre, pswd, ubicacion, escuela, mail, twitter) values (?,?,?,?,?,?,?)";

		$inputarray = array(
			$request["nick"],
			$request["nombre"],
			crypt($request["password"]),
			$request["ubicacion"],
			$request["escuela"],
			$request["email"],
			""
		);

		global $db;
		$res = $db->Execute($sql, $inputarray);

		if($res===false)
		{
			error_log("TEDDY:" . $db->ErrorNo() ." " . $db->ErrorMsg() );
			return array( "result" => "error", "reason" => "Error interno." );
		}

		$request["user"] = $request["nick"];
		$request["pass"] =	$request["password"];

		$result = c_sesion::login($request);
		if (!SUCCESS($result))
		{
			error_log("TEDDY: Error al iniciar sesion despues de registar usuario" );
			return array( "result" => "error", "reason" => "Error interno." );
		}

		return array( "result" => "ok" );
	}

	public static function resetpass($request)
	{
	
		return array( "result" => "ok" );
	}


	public static function editar($request)
	{
		$request["user"] = $request["nick"];

		$result = self::getByNickOrEmail($request);
		if (is_null($result["user"]))
		{
			return array( "result" => "error", "reason" => "Este usuario no existe." );
		}

		$sql = "update  `Usuario`  SET  nombre = ?, escuela = ?, mail = ?, `twitter` =  ? 
									WHERE  `Usuario`.`userID` =  ? LIMIT 1 ;";

		$inputarray = array(
			$request["nombre"],
			$request["escuela"],
			$request["email"],
			$request["twitter"],
			$request["nick"]
		);

		global $db;
		$res = $db->Execute($sql, $inputarray);

		if($res===false)
		{
			error_log("TEDDY:" . $db->ErrorNo() ." " . $db->ErrorMsg() );
			return array( "result" => "error", "reason" => "Error interno." );
		}

		return array( "result" => "ok" );
	}
}

