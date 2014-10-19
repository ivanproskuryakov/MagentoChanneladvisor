<?php

class Scan
{

    function updateCsvFromRemoteFTP($server, $user, $pass)
    {
        $log = '';
        $log .= 'FTP SERVER => ' . $server . '<br/>';;
        $log .= 'FTP USER => ' . $user . '<br/>';
        $log .= 'FTP PASSWORD => ' . $pass . '<br/>';
        $conn_id = ftp_connect($server);
        $result = ftp_login($conn_id, $user, $pass);
        if ($result) $log .= 'FTP CONNECTED !!!' . '<br/>';
        if (!$result) {
            $log .= 'FTP NOT CONNECTED !!!' . '<br/>';
        }
        // enter passive mode
        $result = ftp_pasv($conn_id, TRUE);
        if (!$result) $log .= 'could not enable passive mode' . '<br/>';
        echo $log;
        // get files
        $contents = ftp_nlist($conn_id, ".");
        var_dump($contents);


        foreach ($contents as $file) {

            var_dump($file);
            exit();
            // get the file
            // change products.csv to the file you want
            $local = fopen("products.csv", "w");
            $result = ftp_fget($conn_id, $local, "products.csv", FTP_BINARY);
            fwrite($local, $result);
            fclose($local);

            // check upload status
            if (!$result) {
                echo "FTP download has failed!";
            } else {
                echo "Downloaded ";
            }
        }
        exit();

        // close the connection
//        ftp_close($conn_id);

//        if ((!$conn_id) || (!$login_result)) {
//            echo "FTP connection has failed!";
//            exit;
//        } else {
//            echo "Connected";
//        }
//

//
        // close the FTP stream
        ftp_close($conn_id);


    }


    function ScanDirByTime($folder)
    {
        $dircontent = scandir($folder);
        $arr = array();
        foreach ($dircontent as $filename) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (strtolower($ext) == strtolower('csv')) {
                if (filemtime($folder . $filename) === false)
                    return false;
                $dat = date("YmdHis", filemtime($folder . $filename));
                $arr[$dat] = $filename;
            }
        }
        if (!ksort($arr))
            return false;
        return $arr;
    }

    function BuildArrayFromCSV($file)
    {
        if (!file_exists($file)) exit($file . ' does not exists');
        $csv = array();
        $file = fopen($file, 'r');
        while (($result = fgetcsv($file)) !== false) {
            $csv[] = $result;
        }

        fclose($file);
        return $csv;

    }

}

?>