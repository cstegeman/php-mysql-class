<?php
declare(strict_types=1);

const DEFAULT_DB_USER = "";
const DEFAULT_DB_PASSWORD = "";
const DEFAULT_DB_SERVER = "";
const SHOW_ERRORS = true;

class DBconnection
{
    /** @var mysqli|false */
    public $connection;

    /** @var string */
    public $database;

    /** @var bool */
    public $connected;

    /** @var int */
    public $auto_increment;

    /**
     * DBconnection constructor.
     * @param string $database
     * @param string $login
     * @param string $password
     * @param string $server
     */
    function __construct(
        string $database = "",
        string $login = DEFAULT_DB_USER,
        string $password = DEFAULT_DB_PASSWORD,
        string $server = DEFAULT_DB_SERVER
    ) {
        $this->connected = false;
        if (!empty($database))
        {
            $this->Open($database,$login,$password,$server);
        }
    }

    /**
     * @param string $database
     * @param string $login
     * @param string $password
     * @param string $server
     */
    public function Open(
        string $database,
        string $login = DEFAULT_DB_USER,
        string $password = DEFAULT_DB_PASSWORD,
        string $server = DEFAULT_DB_SERVER
    ) {
        $this->connection = @mysqli_connect($server, $login, $password);
        if (!$this->connection)
        {
            if (SHOW_ERRORS)
            {
                die("\n<p><strong>Error:</strong> Could not make a connection with the database. MySQL says: " . mysqli_connect_error() . "</p>\n");
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
                die("\n<p><strong>Error:</strong> A database could not be selected. MySQL says: " . mysqli_error($this->connection) . "</p>\n");
            }
        }
        else
        {
            $this->database = $database;
        }
    }

    public function Close()
    {
        if (!@mysqli_close($this->connection))
        {
            if (SHOW_ERRORS)
            {
                die("\n<p><strong>Error:</strong> The connection to the database could not be closed.</p>\n");
            }
        }
        $this->connected = false;
    }

    /**
     * @param string $query
     * @return bool|DBresultset
     */
    public function GetValues(string $query)
    {
        if ($this->connected)
        {
            $result = @mysqli_query($this->connection,$query);
            if (!$result)
            {
                if (SHOW_ERRORS)
                {
                    die("\n<p><strong>Error:</strong> The database query failed:<br>\n " . $query . "<br>\n MySQL says: " . mysqli_error($this->connection) . "</p>\n");
                }
                return false;
            }
            return new DBresultset($result, $this->connection);
        }
        else
        {
            if (SHOW_ERRORS)
            {
                echo("\n<p>Error: Can't execute query when not connected to database</p>\n");
            }
            return false;
        }
    }

    /**
     * @param string $query
     * @return bool|int
     */
    public function Execute(string $query)
    {
        if ($this->connected)
        {
            $result = @mysqli_query($this->connection,$query);
            if (!$result)
            {
                if (SHOW_ERRORS)
                {
                    die("\n<p><strong>Error:</strong> The database query failed:<br>\n " . $query . "<br>\n MySQL says: " . mysqli_error($this->connection) . "</p>\n");
                }
                return false;
            }
            $this->auto_increment = mysqli_insert_id($this->connection);
            return @mysqli_affected_rows($this->connection);
        }
        else
        {
            if (SHOW_ERRORS)
            {
                die("\n<p>Error: Can't execute query when not connected to database</p>\n");
            }
            return false;
        }
    }

    /**
     * @return int
     */
    public function auto_increment()
    {
        return $this->auto_increment;
    }

    /**
     * @param string $value
     * @return string
     */
    public function CleanValue(string $value = '')
    {
        return mysqli_real_escape_string($this->connection, $value);
    }

    /**
     * @param string $database
     */
    public function ChangeDatabase(string $database)
    {
        $this->database = $database;
        if (!@mysqli_select_db($this->connection, $database))
        {
            if (SHOW_ERRORS)
            {
                die("\n<p><strong>Error:</strong> A database could not be selected. MySQL says: " . mysqli_error($this->connection) . "</p>\n");
            }
        }
    }

}

class DBresultset
{
    /** @var mysqli_result */
    public $result;

    /** @var bool */
    public $eof;

    /** @var bool */
    public $bof;

    /** @var int */
    public $numfields;

    /** @var int */
    public $numrows;

    /** @var array */
    public $fieldnames;

    /** @var array */
    public $fields;

    /** @var array */
    public $rows;

    /**
     * @param mysqli_result
     * @param mysqli
     */
    function __construct(
        $exec_result,
        $connection
    ) {
        $this->result = $exec_result;
        $this->eof = true;
        $this->bof = true;
        $this->numfields = 0;
        $this->numrows = @mysqli_affected_rows($connection);
        if (!$this->numrows)
        {
            $this->numrows = 0;
        }
        $this->fieldnames = [];
        $this->fields = [];
        $this->rows = [];
        $this->RetrieveRows();
    }

    private function RetrieveRows()
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

    public function MoveFirst()
    {
        $this->fields = reset($this->rows);
        $this->bof = true;
        $this->eof = false;
    }

    public function MovePrevious()
    {
        $row = prev($this->rows);
        if (!$row)
        {
            $this->bof = true;
        }
        else
        {
            $this->fields = $row;
            $this->eof = false;
        }
    }

    public function MoveNext()
    {
        $row = next($this->rows);
        if (!$row)
        {
            $this->eof = true;
        }
        else
        {
            $this->fields = $row;
            $this->bof = false;
        }
    }

    public function MoveLast()
    {
        $row = end($this->rows);
        if (!$row)
        {
            $this->eof = true;
        }
        else
        {
            $this->fields = $row;
            $this->bof = false;
        }
    }

    /**
     * @param int $record
     */
    public function Move($record = 0)
    {
        $this->MoveFirst();
        for ($i = 0; $i < $record; $i++) {
            if (!$this->eof) {
                $this->MoveNext();
            } else {
                break;
            }
        }
    }

    /**
     * @return bool
     */
    public function EoF()
    {
        return $this->eof;
    }

    /**
     * @return bool
     */
    public function BoF()
    {
        return $this->bof;
    }

    /**
     * @return int
     */
    public function NumResults()
    {
        return $this->numrows;
    }

    /**
     * @param $field
     * @param $value
     * @return bool
     */
    public function Find($field,$value)
    {
        $found = false;
        if (!$this->bof()) $this->MoveFirst();
        while(!$this->eof())
        {
            if ($this->fields[$field] === $value)
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