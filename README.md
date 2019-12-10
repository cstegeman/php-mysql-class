# php-mysql-class
PHP helper class to make MySQL database connections.

Update the database.php with the correct MySQL connection info:
```
const DEFAULT_DB_USER = "username";
const DEFAULT_DB_PASSWORD = "password";
const DEFAULT_DB_SERVER = "hostname";
const SHOW_ERRORS = true;
```

## Usage

### Include the database.php file in your project

`include ("database.php");`

### Instantiate a new connection:
```
$conn = new DBconnection();
$conn->Open("sampledatabase");
```

### Close the connection
`$conn->Close();`

### Retrieving data
```
$query = "SELECT id, name FROM tmp_table ORDER BY name ASC";
$rs = $conn->GetValues($query);
if (!$rs->Eof()) {
    while (!$rs->eof) {
        echo $rs->fields["name"] . "<br>";
        $rs->MoveNext();
    }
}
```

### Insert, update or delete actions
```
$query = "INSERT INTO table_name (column) VALUES ('value')";
$conn->Execute($query);
```

### Retrieving an auto increment value after insert
```
$query = "INSERT INTO table_name (column) VALUES ('value')";
$conn->Execute($query);
$id = $conn->auto_increment();
```

### Make a string database safe
`$conn->CleanValue('string')`

### Change database on the current connection
`$conn->ChangeDatabase('database_name')`