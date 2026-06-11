<?php
/**
 * Edunova API - Database Connection Class
 * Handles all database operations with prepared statements
 */

class Database {
    private $connection;
    private $statement;
    private static $instance = null;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                DB_PORT
            );

            // Check connection
            if ($this->connection->connect_error) {
                throw new Exception('Database Connection Failed: ' . $this->connection->connect_error);
            }

            // Set charset
            $this->connection->set_charset("utf8mb4");

        } catch (Exception $e) {
            if (DEBUG) {
                die('Database Error: ' . $e->getMessage());
            } else {
                die('A database error occurred');
            }
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prepare statement
     * @param string $query
     * @return $this
     */
    public function prepare($query) {
        $this->statement = $this->connection->prepare($query);
        if (!$this->statement) {
            throw new Exception('Prepare failed: ' . $this->connection->error);
        }
        return $this;
    }

    /**
     * Bind values to prepared statement
     * @param array $values
     * @return $this
     */
    public function bind($values = []) {
        if (!empty($values)) {
            $types = '';
            foreach ($values as $value) {
                if (is_int($value)) $types .= 'i';
                elseif (is_float($value)) $types .= 'd';
                elseif (is_string($value)) $types .= 's';
                else $types .= 's';
            }
            $this->statement->bind_param($types, ...$values);
        }
        return $this;
    }

    /**
     * Execute prepared statement
     * @return bool
     */
    public function execute() {
        return $this->statement->execute();
    }

    /**
     * Get result set
     * @return mixed
     */
    public function getResult() {
        return $this->statement->get_result();
    }

    /**
     * Get single row as associative array
     * @return array|null
     */
    public function fetch() {
        $result = $this->getResult();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get all rows as associative array
     * @return array
     */
    public function fetchAll() {
        $result = $this->getResult();
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * Get affected rows
     * @return int
     */
    public function getAffectedRows() {
        return $this->statement->affected_rows;
    }

    /**
     * Get insert ID
     * @return int
     */
    public function getLastId() {
        return $this->connection->insert_id;
    }

    /**
     * Query helper - execute and return result
     * @param string $query
     * @param array $values
     * @return array
     */
    public function query($query, $values = []) {
        $this->prepare($query);
        if (!empty($values)) {
            $this->bind($values);
        }
        $this->execute();
        return $this->fetchAll();
    }

    /**
     * Query single row
     * @param string $query
     * @param array $values
     * @return array|null
     */
    public function queryOne($query, $values = []) {
        $this->prepare($query);
        if (!empty($values)) {
            $this->bind($values);
        }
        $this->execute();
        return $this->fetch();
    }

    /**
     * Insert helper
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $query = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        
        $this->prepare($query);
        $this->bind(array_values($data));
        $this->execute();
        
        return $this->getLastId();
    }

    /**
     * Update helper
     * @param string $table
     * @param array $data
     * @param string $where
     * @param array $whereValues
     * @return int
     */
    public function update($table, $data, $where, $whereValues = []) {
        $setClause = implode(', ', array_map(fn($col) => "$col = ?", array_keys($data)));
        $query = "UPDATE $table SET $setClause WHERE $where";
        
        $values = array_merge(array_values($data), $whereValues);
        
        $this->prepare($query);
        $this->bind($values);
        $this->execute();
        
        return $this->getAffectedRows();
    }

    /**
     * Delete helper
     * @param string $table
     * @param string $where
     * @param array $whereValues
     * @return int
     */
    public function delete($table, $where, $whereValues = []) {
        $query = "DELETE FROM $table WHERE $where";
        
        $this->prepare($query);
        if (!empty($whereValues)) {
            $this->bind($whereValues);
        }
        $this->execute();
        
        return $this->getAffectedRows();
    }

    /**
     * Close connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialize
     */
    public function __wakeup() {}
}
