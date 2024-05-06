<?php
class DataBase {
    private $conn;
    private $q;

    public function __construct() {
        $this->connect();
    }

    public function connect() {
        global $log;
        $this->conn = mysqli_init();
		/*if(!mysqli_real_connect($this->conn, settings::MYSQL_HOST, settings::MYSQL_USER, settings::MYSQL_PASS, settings::MYSQL_BASE, settings::MYSQL_PORT)) {
			die('Ошибка подключения (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
		}*/
		
        $this->conn->real_connect(settings::MYSQL_HOST, settings::MYSQL_USER, settings::MYSQL_PASS, settings::MYSQL_BASE, settings::MYSQL_PORT);
        if (!mysqli_set_charset($this->conn, "utf8")) {
            $log->addMessage(mysqli_error($this->conn));
            die;
        }
    }

    public function escapeString($string) {
        return $this->conn->real_escape_string($string);
    }

    public function query($sql, &$strError = ''){
        global $log;
        $this->q = $this->conn->query($sql);
        if($error = mysqli_error($this->conn)) {
            $strError = "\r\n" . $sql . "\r\n" . $error . "\r\n";
            $log->addMessage($strError);
        } else return $this;

        return false;
    }

    public function fetch() {
        return $this->q->fetch_assoc();
    }

    public function __destruct() {
        $this->conn->close();
    }
}