<?php 
function generatePassword(){
        // generate random password
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 18; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
        }
        return implode($pass);
}

function GetCoreInformation() {
        $data = file('/proc/stat');
        $cores = array();
        foreach( $data as $line ) {
                if( preg_match('/^cpu[0-9]/', $line) )
                {
                        $info = explode(' ', $line );
                        $cores[] = array(
                                'user' => $info[1],
                                'nice' => $info[2],
                                'sys' => $info[3],
                                'idle' => $info[4]
                        );
                }
        }
        return $cores;
}

function GetCpuPercentages($stat1, $stat2) {
        if( count($stat1) !== count($stat2) ) {
                return;
        }
        $cpus = array();
        for( $i = 0, $l = count($stat1); $i < $l; $i++) {
                $dif = array();
                $dif['user'] = $stat2[$i]['user'] - $stat1[$i]['user'];
                $dif['nice'] = $stat2[$i]['nice'] - $stat1[$i]['nice'];
                $dif['sys'] = $stat2[$i]['sys'] - $stat1[$i]['sys'];
                $dif['idle'] = $stat2[$i]['idle'] - $stat1[$i]['idle'];
                $total = array_sum($dif);
                $cpu = array();
                foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);
                $cpus['cpu' . $i] = $cpu;
        }
        return $cpus;
}

function GetMemoryInfo(){
        $fh = fopen('/proc/meminfo','r');
        $mem = array(0,0);
        while ($line = fgets($fh)) {
        $pieces = array();
        if (preg_match('/^MemTotal:\s+(\d+)\skB$/', $line, $pieces)) {
                $mem[0] = $pieces[1];
        }
                if (preg_match('/^MemFree:\s+(\d+)\skB$/', $line, $pieces)) {
                $mem[1] = $pieces[1];
        }
        }
        fclose($fh);
        return $mem;
}
?>
