<?php
class Database
{
    private $conn;
    private $stmt;

    public function __construct()
    {
        // Define DB_PORT if not defined
        if (!defined('DB_PORT')) {
            define('DB_PORT', '5432');
        }

        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        try {
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }

    public function query($sql)
    {
        $this->stmt = $this->conn->prepare($sql);
    }

    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute()
    {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            error_log("Database Execute Error: " . $e->getMessage() . " - SQL: " . $this->stmt->queryString);
            throw $e;
        }
    }

    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function single()
    {
        $this->execute();
        $result = $this->stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }

    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollBack()
    {
        return $this->conn->rollBack();
    }

    public function lastInsertId($sequence = null)
    {
        try {
            // For PostgreSQL, we need to use lastval() or specify sequence
            if ($sequence) {
                $stmt = $this->conn->query("SELECT CURRVAL('" . $sequence . "') as last_id");
            } else {
                $stmt = $this->conn->query("SELECT LASTVAL() as last_id");
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['last_id'] : null;
        } catch (PDOException $e) {
            error_log("LastInsertId Error: " . $e->getMessage());
            return null;
        }
    }

    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->query($sql);

        foreach ($data as $key => $value) {
            $this->bind(':' . $key, $value);
        }

        $this->execute();

        // For PostgreSQL, get the last insert ID
        // Try common sequence naming patterns
        $possibleSequences = [
            $table . '_id_seq',
            $table . '_id_seq',
            str_replace('_', '', $table) . '_id_seq'
        ];

        foreach ($possibleSequences as $seq) {
            $id = $this->lastInsertId($seq);
            if ($id) {
                return $id;
            }
        }

        return null;
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = :$column";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE $table SET $setClause WHERE $where";
        $this->query($sql);

        foreach ($data as $key => $value) {
            $this->bind(':' . $key, $value);
        }

        foreach ($whereParams as $key => $value) {
            $this->bind(':' . $key, $value);
        }

        return $this->execute();
    }

    public function select($table, $columns = "*", $where = "", $params = [], $orderBy = "", $limit = "")
    {
        $sql = "SELECT $columns FROM $table";

        if (!empty($where)) {
            $sql .= " WHERE $where";
        }

        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }

        if (!empty($limit)) {
            $sql .= " LIMIT $limit";
        }

        $this->query($sql);

        foreach ($params as $key => $value) {
            $this->bind(':' . $key, $value);
        }

        return $this->resultSet();
    }

    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM $table WHERE $where";
        $this->query($sql);

        foreach ($params as $key => $value) {
            $this->bind(':' . $key, $value);
        }

        return $this->execute();
    }

    public function debug()
    {
        return $this->stmt->debugDumpParams();
    }
}
