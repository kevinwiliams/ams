<?php 
    require_once 'env_load.php';

    try {
        $env = new EnvLoader(__DIR__ . '/.env');
        $dbHost = $env->get('DB_HOST');
        $dbUser = $env->get('DB_USER');
        $dbPass = $env->get('DB_PASS');
        $dbName = $env->get('DB_NAME');

        $MSSQLdbHost = $env->get('MSSQL_DB_HOST');
        $MSSQLdbUser = $env->get('MSSQL_DB_USER');
        $MSSQLdbPass = $env->get('MSSQL_DB_PASS');
        $MSSQLdbName = $env->get('MSSQL_DB_NAME');

        $conn= new mysqli($dbHost, $dbUser,$dbPass,$dbName ) or die("Could not connect to mysql".mysqli_error($con));

        $serverName = $MSSQLdbHost;
        $connectionInfo = array(
            "UID" => $MSSQLdbUser, 
            "PWD" => $MSSQLdbPass, 
            "Database"=> $MSSQLdbName, 
            "ReturnDatesAsStrings" => true, 
            "Encrypt" => false
        );
        
        $mssqlconn = sqlsrv_connect( $serverName, $connectionInfo);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }

    

?>