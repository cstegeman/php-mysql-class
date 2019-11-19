<?php
define("DEFAULT_DB_USER","");
define("DEFAULT_DB_PASSWORD","");
define("DEFAULT_DB_SERVER","");
define("TABLE_SYSTEM",1);
define("TABLE_NORMAL",2);
define("TABLE_VIEW",3);
define("PREVIOUS_DATABASE",0);
define("SHOW_ERRORS",true);

	/********************************************************************************
	Method / Class:	DBconnection
	Input:					none (class)
	Output:					none (class)
	Description:			Database connection class
	********************************************************************************/
	class DBconnection 
	{
		/********************************************************************************
		Property:			connection
		Type:					Handle
		Description:	ODBC connection handle
		********************************************************************************/
		var $connection;
		/********************************************************************************
		Property:			persistent
		Type:					Boolean
		Description:	Determines use of persistent connections
		********************************************************************************/
		var $persistent;
		/********************************************************************************
		Property:			database
		Type:					String
		Description:	Name of selected database
		********************************************************************************/
		var $database;
		/********************************************************************************
		Property:			connected
		Type:					Boolean
		Description:	Connected? True = yes / False = no
		********************************************************************************/
		var $connected;
		/********************************************************************************
		Property:				auto_increment
		Type:						integer
		Description:		returns (if there is) the last auto_increment ID 
		********************************************************************************/
		var $auto_increment;
		
		/********************************************************************************
		Method / Class:	DBconnection (constructor)
		Input:					$database:	Database to open
										$login:		Database login name
										$password:	Database password
										$server:	MySQL server
										(all input is optional)
		Output:					(none)
		Description:		Initiliazes DBconnection instance and opens database connection
		Examples:				$conn = new DBconnection("database","login","password");
										$conn = new DBconnection();
		********************************************************************************/
		function __construct($database = "", $login = DEFAULT_DB_USER, $password = DEFAULT_DB_PASSWORD, $server = DEFAULT_DB_SERVER)
		{
			$this->connected = false;
			if (!empty($database))
			{
				$this->Open($database,$login,$password,$server);
			}
		}

		/********************************************************************************
		Method / Class:	Open
		Input:					$database:	Database to open
										$login:		Database login name
										$password:	Database password
										$server:	MySQL server
		Output:					(none)
		Description:		Opens database connection
		Examples:				$conn->Open("database","login","password");
		********************************************************************************/
		function Open($database,$login = DEFAULT_DB_USER,$password = DEFAULT_DB_PASSWORD,$server = DEFAULT_DB_SERVER)
		{
			$this->connection = @mysqli_connect($server,$login,$password);
			if (!$this->connection)
			{
				if (SHOW_ERRORS)
				{
					die("\n<p><B>Error:</B> Could not make a connection with the database. MySQL says: " . mysqli_connect_error() . "</p>\n");
				}
			}
			else
			{
				$this->connected = true;
			}
			if (!@mysqli_select_db($this->connection,$database))
			{
				if (SHOW_ERRORS)
				{
					die("\n<p><B>Error:</B> A database could not be selected. MySQL says: " . mysqli_error($this->connection) . "</p>\n");
				}
			}
			else
			{
				$this->database = $database;
			}
			
			$this->persistent = false;
		}

		/********************************************************************************
		Method / Class:	OpenPersistent
		Input:					$database:	Database to open
										$login:		Database login name
										$password:	Database password
										$server:	MySQL Server 
		Output:					(none)
		Description:		Opens persistent database connection
		Examples:				$conn->Open("database","login","password");
		********************************************************************************/
		function OpenPersistent($database,$login = DEFAULT_DB_USER,$password = DEFAULT_DB_PASSWORD,$server = DEFAULT_DB_SERVER)
		{
			$this->connection = @mysqli_pconnect($server,$login,$password);
			if (!$this->connection)
			{
				if (SHOW_ERRORS)
				{
					die("\n<p><B>Error:</B> Could make a connection with the database. MySQL says: " . mysqli::$connect_error . "</p>\n");
				}
			}
			else
			{
				$this->connected = true;
			}
			if (!@mysqli_select_db($database,$this->connection))
			{
				if (SHOW_ERRORS)
				{
					die("\n<p><B>Error:</B> A database could not be selected. MySQL says: " . mysqli::$connect_error . "</p>\n");
				}
			}
			else
			{
				$this->database = $database;
			}
			
			$this->persistent = true;
		}
		
		/********************************************************************************
		Method / Class:	Close
		Input:					(none)
		Output:					(none)
		Description:		Close database connection
		Examples:				$conn->Close();
		********************************************************************************/
		function Close()
		{
			if (!$this->persistent)
			{
				// Quiet Mode (does not display error when connection can't be closed)
				if (!@mysqli_close($this->connection))
				{
					if (SHOW_ERRORS)
					{
						die("\n<p><B>Error:</B> The connection to the database could not be closed. MySQL says: " . mysqli::$connect_error . "</p>\n");
					}
				}
			}
			$this->connected = false;
		}
		
		/********************************************************************************
		Method / Class:	GetValues
		Input:					$query: query to get values from
		Output:					An instance of the DBresultset class
		Description:		Do a query on the database.
		Examples:				$rs = $conn->Execute("SELECT * FROM table");
		********************************************************************************/
		function GetValues($query)
		{
			if ($this->connected)
			{
				$result = @mysqli_query($this->connection,$query);
				if (!$result)
				{
					if (SHOW_ERRORS)
					{
						die("\n<p><B>Error:</B> The database query failed:<br>\n " . addslashes($query) . "<br>\n MySQL says: " . mysqli_error($this->connection) . "</p>\n");
					}
					return false;
				}
				else
				{
					$rs = new DBresultset($result);
					return $rs;
				}
			}
			else
			{
				if (SHOW_ERRORS)
				{
					echo("\n<p>Error: Can't execute query when not connected to database</p>\n");
				}
			}
		}

		/********************************************************************************
		Method / Class:	Execute
		Input:					$query: query to execute
		Output:					An instance of the DBresultset class
		Description:		Do a query on the database, this function is mainly used to update or delete records.
		Examples:				$rs = $conn->Execute("INSERT INTO table(field) VALUES(value)");
		********************************************************************************/
		function Execute($query)
		{
			if ($this->connected)
			{
				$result = @mysqli_query($this->connection,$query);
				if (!$result)
				{
					if (SHOW_ERRORS)
					{
						die("\n<p><B>Error:</B> The database query failed:<br>\n " . addslashes($query) . "<br>\n MySQL says: " . mysqli_error($this->connection) . "</p>\n");
					}
					return false;
				}
				else
				{
					$this->auto_increment = mysqli_insert_id($this->connection);
					return @mysqli_affected_rows($this->connection);
				}
			}
			else
			{
				if (SHOW_ERRORS)
				{
					die("\n<p>Error: Can't execute query when not connected to database</p>\n");
				}
			}
		}

		/********************************************************************************
		Method / Class:	auto_increment
		Input:					none
		Output:					auto increment ID
		Description:		returns (if there is) the last auto_increment ID 
		Examples:				$rs = $conn->auto_increment();
		********************************************************************************/
		function auto_increment()
		{
			return $this->auto_increment;
		}

		function CleanValue($value = '')
        {
            return mysqli_real_escape_string($this->connection, $value);
        }
		
		/********************************************************************************
		Method / Class:	ChangeDatabase
		Input:					$database: database name
		Output:					(none)
		Description:			Change the current working database
		Examples:				$conn->ChangeDatabase("content")
		********************************************************************************/
		function ChangeDatabase($database)
		{
			$this->database = $database;
			if (!@mysqli_select_db($database,$this->connection))
			{
				if (SHOW_ERRORS)
				{
					die("\n<p><B>Error:</B> A database could not be selected. MySQL says: " . mysqli_error($this->connection) . "</p>\n");
				}
			}
		}

	}
	
	/********************************************************************************
	Method / Class:	DBresultset
	Input:						none (class)
	Output:						none (class)
	Description:			Database resultset class (returned by DBconnection->Execute)
	********************************************************************************/
	class DBresultset
	{
		/********************************************************************************
		Property:				result
		Type:						Handle
		Description:		ODBC resultset handle
		********************************************************************************/
		var $result;
		/********************************************************************************
		Property:				eof
		Type:						Boolean
		Description:		End of File (recordset) status
		********************************************************************************/
		var $eof;
		/********************************************************************************
		Property:				bof
		Type:						Boolean
		Description:		Begin of File (recordset) status
		********************************************************************************/
		var $bof;
		/********************************************************************************
		Property:				numfields
		Type:						Integer
		Description:		Number of Fields
		********************************************************************************/
		var $numfields;
		/********************************************************************************
		Property:				numrows
		Type:						Integer
		Description:		Number of Rows (records)
		********************************************************************************/
		var $numrows;
		/********************************************************************************
		Property:				fieldnames
		Type:						Array of String
		Description:		Names of all fields
		********************************************************************************/
		var $fieldnames;
		/********************************************************************************
		Property:				fields
		Type:						Array
		Description:		Current row (determined by Move...() functions)
		********************************************************************************/
		var $fields;
		/********************************************************************************
		Property:				rows
		Type:						Array of Array
		Description:		2 dimensional array containing all rows
		********************************************************************************/
		var $rows;
	
		/***********************************************************************
		Method / Class:	DBresultset
		Input:					$exec_result:
		Output:					none
		Description:		Initiliazes DBresultset instance and retrieves rows
		Note:						FOR INTERNAL USE ONLY (PRIVATE FUNCTION)
		***********************************************************************/
		function __construct($exec_result)
		{
			$this->result = $exec_result;
			$this->eof = true;
			$this->bof = true;
			$this->numfields = 0;
			// Quiet Mode (does not display error when there're no results)
			$this->numrows = @mysqli_affected_rows($this->result);
			if (!$this->numrows)
			{
				$this->numrows = 0;
			}
			$this->fieldnames = Array();
			$this->fields = Array();
			$this->rows = Array();
			$this->RetrieveRows();
		}
		
		/***********************************************************************
		Method / Class:	RetrieveRows()
		Input:					none
		Output:					none
		Description:		Retrieves all rows from database and loads them into arrays
		Note:						FOR INTERNAL USE ONLY (PRIVATE FUNCTION)
		***********************************************************************/
		function RetrieveRows()
		{
			while($row = @mysqli_fetch_array($this->result))
			{
				array_push($this->rows, $row);
				$this->bof = false;
				$this->eof = false;
			}
			if (!$this->bof)
			{
				$this->MoveFirst();
			}
		}
		
		/***********************************************************************
		Method / Class:	MoveFirst
		Input:					none
		Output:					none
		Description:		Returns to first row in recordset
		Examples:				$conn->MoveFirst();
		***********************************************************************/
		function MoveFirst()
		{
			$this->fields = reset($this->rows);
			$this->bof = true;
			$this->eof = false;
		}
		
		/***********************************************************************
		Method / Class:	MovePrevious
		Input:					none
		Output:					none
		Description:		Returns to previous row in recordset
		Examples:				$conn->MovePrevious();
		***********************************************************************/
		function MovePrevious()
		{
			$row = prev($this->rows);
			if (!$row)
			{
				// Begin of File
				$this->bof = true;
			}
			else
			{
				$this->fields = $row;
				$this->eof = false;
			}
		}
		
		/***********************************************************************
		Method / Class:	MoveNext
		Input:					none
		Output:					none
		Description:		Returns to next row in recordset
		Examples:				$conn->MoveNext();
		***********************************************************************/
		function MoveNext()
		{
			$row = next($this->rows);
			if (!$row)
			{
				// End of File
				$this->eof = true;
			}
			else
			{
				$this->fields = $row;
				$this->bof = false;
			}
		}

		/***********************************************************************
		Method / Class:	MoveLast
		Input:					none
		Output:					none
		Description:		Returns to last row in recordset
		Examples:				$conn->MoveLast();
		***********************************************************************/
		function MoveLast()
		{
			$row = end($this->rows);
			if (!$row)
			{
				// end of File
				$this->eof = true;
			}
			else
			{
				$this->fields = $row;
				$this->bof = false;
			}
		}

		/***********************************************************************
		Method / Class:	Move
		Input:					number
		Output:					none
		Description:		Moves to row in recordset
		Examples:				$rs->Move();
		***********************************************************************/
		function Move($record = 0)
		{
			//eerst maar even naar begin springen
			$this->MoveFirst();
			//nu loopen naar juiste record
			for ($i = 0; $i < $record; $i++) {
				if (!$this->eof) {
					$this->MoveNext();
				} else {
					break;
				}
			}
		}		

		/***********************************************************************
		Method / Class:	EoF
		Input:					none
		Output:					none
		Description:		Returns End of File status
		Examples:				$conn->EoF();
		***********************************************************************/
		function EoF()
		{
			return $this->eof;
		}
		
		/***********************************************************************
		Method / Class:	BoF
		Input:					none
		Output:					none
		Description:		Returns Begin of File status
		Examples:				$conn->BoF();
		***********************************************************************/
		function BoF()
		{
			return $this->bof;
		}

		/***********************************************************************
		Method / Class:	NumResults
		Input:					none
		Output:					none
		Description:		Returns number of results (rows)
		Examples:				$conn->NumResults();
		***********************************************************************/
		function NumResults()
		{
			return $this->numrows;
		}
		
		/***********************************************************************
		Method / Class:	Find
		Input:					$field:		look in this field for $value
										$value:		value to look for
		Output:					none
		Description:		Move to row (if found) or first (if not found)
		Examples:				$conn->Find("customer","name");
		***********************************************************************/
		function Find($field,$value)
		{
			$found = false;
			if (!$this->bof()) $this->MoveFirst();
			while(!$this->eof())
			{
				if ($this->fields[$field] == $value)
				{
					$found = true;
					break;
				}
				$this->MoveNext();
			}
			if (!$this->eof())
			{
				if (!$found)
				{
					$this->MoveFirst();
				}
			}
			return $found;
		}
	}