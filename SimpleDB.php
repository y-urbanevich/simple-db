<?php

class SimpleDB
{
    private $driver;

    /**
     *
     * @param string $driver
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param string $database
     */
    public function __construct($driver, $hostname, $username, $password, $database)
    {
        $class = 'DB' . ucfirst($driver);
        if (class_exists($class)) {
            $this->driver = new $class($hostname, $username, $password, $database);
        } else {
            exit('Error: Could not load database driver type ' . $driver . '!');
        }
    }

    public function query($sql)
    {
        return $this->driver->query($sql);
    }

    public function escape($value)
    {
        return $this->driver->escape($value);
    }

    public function countAffected()
    {
        return $this->driver->countAffected();
    }

    public function getLastId()
    {
        return $this->driver->getLastId();
    }
}

final class DBMysql
{
    private $link;

    public function __construct($hostname, $username, $password, $database)
    {
        if (!$this->link = mysql_connect($hostname, $username, $password)) {
            trigger_error('Error: Could not make a database link using ' . $username . '@' . $hostname);
        }

        if (!mysql_select_db($database, $this->link)) {
            trigger_error('Error: Could not connect to database ' . $database);
        }

        mysql_query("SET NAMES 'utf8'", $this->link);
        mysql_query("SET CHARACTER SET utf8", $this->link);
        mysql_query("SET CHARACTER_SET_CONNECTION=utf8", $this->link);
        mysql_query("SET SQL_MODE = ''", $this->link);
    }

    public function query($sql)
    {
        if ($this->link) {
            $resource = mysql_query($sql, $this->link);

            if ($resource) {
                if (is_resource($resource)) {
                    $i = 0;

                    $data = array();

                    while ($result = mysql_fetch_assoc($resource)) {
                        $data[$i] = $result;

                        $i++;
                    }

                    mysql_free_result($resource);

                    $query           = new stdClass();
                    $query->row      = isset($data[0]) ? $data[0] : array();
                    $query->rows     = $data;
                    $query->num_rows = $i;

                    unset($data);

                    return $query;
                } else {
                    return true;
                }
            } else {
                trigger_error('Error: ' . mysql_error($this->link) . '<br />Error No: ' . mysql_errno($this->link) . '<br />' . $sql);
                exit();
            }
        }
    }

    public function escape($value)
    {
        if ($this->link) {
            return mysql_real_escape_string($value, $this->link);
        }
    }

    public function countAffected()
    {
        if ($this->link) {
            return mysql_affectedRows($this->link);
        }
    }

    public function getLastId()
    {
        if ($this->link) {
            return mysql_insertId($this->link);
        }
    }

    public function __destruct()
    {
        if ($this->link) {
            mysql_close($this->link);
        }
    }
}

final class DBMysqli
{
    private $link;

    public function __construct($hostname, $username, $password, $database)
    {
        $this->link = new mysqli($hostname, $username, $password, $database);

        if (mysqli_connect_error()) {
            throw new ErrorException('Error: Could not make a database link (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        }

        $this->link->set_charset("utf8");
        $this->link->query("SET SQL_MODE = ''");
    }

    public function query($sql)
    {
        $query = $this->link->query($sql);

        if (!$this->link->errno) {
            if (isset($query->num_rows)) {
                $data = array();

                while ($row = $query->fetch_assoc()) {
                    $data[] = $row;
                }

                $result          = new stdClass();
                $result->numRows = $query->num_rows;
                $result->row     = isset($data[0]) ? $data[0] : array();
                $result->rows    = $data;

                unset($data);

                $query->close();

                return $result;
            } else {
                return true;
            }
        } else {
            throw new ErrorException('Error: ' . $this->link->error . '<br />Error No: ' . $this->link->errno . '<br />' . $sql);
            exit();
        }
    }

    public function escape($value)
    {
        return $this->link->real_escape_string($value);
    }

    public function countAffected()
    {
        return $this->link->affected_rows;
    }

    public function getLastId()
    {
        return $this->link->insert_id;
    }

    public function __destruct()
    {
        $this->link->close();
    }
}

final class DBMssql
{
    private $link;

    public function __construct($hostname, $username, $password, $database)
    {
        if (!$this->link = mssql_connect($hostname, $username, $password)) {
            exit('Error: Could not make a database connection using ' . $username . '@' . $hostname);
        }

        if (!mssql_select_db($database, $this->link)) {
            exit('Error: Could not connect to database ' . $database);
        }

        mssql_query("SET NAMES 'utf8'", $this->link);
        mssql_query("SET CHARACTER SET utf8", $this->link);
    }

    public function query($sql)
    {
        $resource = mssql_query($sql, $this->link);

        if ($resource) {
            if (is_resource($resource)) {
                $i = 0;

                $data = array();

                while ($result = mssql_fetch_assoc($resource)) {
                    $data[$i] = $result;

                    $i++;
                }

                mssql_free_result($resource);

                $query           = new stdClass();
                $query->row      = isset($data[0]) ? $data[0] : array();
                $query->rows     = $data;
                $query->num_rows = $i;

                unset($data);

                return $query;
            } else {
                return true;
            }
        } else {
            trigger_error('Error: ' . mssql_get_last_message($this->link) . '<br />' . $sql);
            exit();
        }
    }

    public function escape($value)
    {
        $unpacked = unpack('H*hex', $value);

        return '0x' . $unpacked['hex'];
    }

    public function countAffected()
    {
        return mssql_rows_affected($this->link);
    }

    public function getLastId()
    {
        $lastId = false;

        $resource = mssql_query("SELECT @@identity AS id", $this->link);

        if ($row = mssql_fetch_row($resource)) {
            $lastId = trim($row[0]);
        }

        mssql_free_result($resource);

        return $lastId;
    }

    public function __destruct()
    {
        mssql_close($this->link);
    }
}

final class DBPostgre
{
    private $link;

    public function __construct($hostname, $username, $password, $database)
    {
        if (!$this->link = pg_connect('hostname=' . $hostname . ' username=' . $username . ' password=' . $password . ' database=' . $database)) {
            trigger_error('Error: Could not make a database link using ' . $username . '@' . $hostname);
        }

        if (!mysql_select_db($database, $this->link)) {
            trigger_error('Error: Could not connect to database ' . $database);
        }

        pg_query($this->link, "SET CLIENT_ENCODING TO 'UTF8'");
    }

    public function query($sql)
    {
        $resource = pg_query($this->link, $sql);

        if ($resource) {
            if (is_resource($resource)) {
                $i = 0;

                $data = array();

                while ($result = pg_fetch_assoc($resource)) {
                    $data[$i] = $result;

                    $i++;
                }

                pg_free_result($resource);

                $query           = new stdClass();
                $query->row      = isset($data[0]) ? $data[0] : array();
                $query->rows     = $data;
                $query->num_rows = $i;

                unset($data);

                return $query;
            } else {
                return true;
            }
        } else {
            trigger_error('Error: ' . pg_result_error($this->link) . '<br />' . $sql);
            exit();
        }
    }

    public function escape($value)
    {
        return pg_escape_string($this->link, $value);
    }

    public function countAffected()
    {
        return pg_affectedRows($this->link);
    }

    public function getLastId()
    {
        $query = $this->query("SELECT LASTVAL() AS `id`");

        return $query->row['id'];
    }

    public function __destruct()
    {
        pg_close($this->link);
    }
}
?>