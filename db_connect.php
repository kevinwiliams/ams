<?php
try {
    // Load environment variables
    require_once 'env_load.php';
    
    $env = new EnvLoader(__DIR__ . '/.env');
    $dbHost = $env->get('DB_HOST');
    $dbUser = $env->get('DB_USER');
    $dbPass = $env->get('DB_PASS');
    $dbName = $env->get('DB_NAME');

    $MSSQLdbHost = $env->get('MSSQL_DB_HOST');
    $MSSQLdbUser = $env->get('MSSQL_DB_USER');
    $MSSQLdbPass = $env->get('MSSQL_DB_PASS');
    $MSSQLdbName = $env->get('MSSQL_DB_NAME');


    // MySQL Connection
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

    if ($conn->connect_error) {
        die("MySQL Connection failed: " . $conn->connect_error);
    }

    // MSSQL Connection
    $serverName = $MSSQLdbHost;
    $connectionInfo = array(
        "UID" => $MSSQLdbUser,
        "PWD" => $MSSQLdbPass,
        "Database" => $MSSQLdbName,
        "ReturnDatesAsStrings" => true,
        "Encrypt" => false
    );

    $mssqlconn = sqlsrv_connect($serverName, $connectionInfo);

    if ($mssqlconn === false) {
        die(print_r(sqlsrv_errors(), true));
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>