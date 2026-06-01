<?php

class Database {

  private $host;
  private $username;
  private $password;
  private $db_name;
  private $conn;

  function __construct($host, $username, $password, $db_name) {
    $this->host = $host;
    $this->username = $username;
    $this->password = $password;
    $this->db_name = $db_name;

    try {
      $this->conn = new PDO("mysql:host={$host};dbname={$db_name}", $username, $password, [PDO::ATTR_PERSISTENT => true]);
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->conn->exec("set names utf8");
    }
    catch(PDOException $exception){
      die("Connection error: ".$exception->getMessage());
    }
  }

  function query(string $query, mixed ...$params) {
    $result = $this->conn->prepare($query);
    $result->execute($params);
    return $result;
  }

  function queryBind(string $query, array $params) {
    $result = $this->conn->prepare($query);

    foreach ($params as $key => &$value) {
      $result->bindParam((is_int($key) ? $key + 1 : $key), $value);
    }
    $result->execute();
    return $result;
  }

  function getLastInsertId() {
    $result = $this->conn->lastInsertId();
    return $result;
  }

}
