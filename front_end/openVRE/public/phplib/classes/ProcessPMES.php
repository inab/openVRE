<?php
class ProcessPMES{

	public $jobid       = 0;
	public $listening = false;
	public $lastCall;

	private $server;
	private $cloud;
    private $APIroot = "pmes/";
    private $stderr;


	public function __construct($cloudName="local",$data=array(),$service=false){

		//get infrastructure info
		if ($cloudName == "local"){
			$this->cloud = $this->getCurrentCloud();
			if ($this->cloud == "0")
				$cloudName="mug-bsc";
		}elseif(!in_array($cloudName,array_keys($GLOBALS['clouds'])) ){
			$_SESSION['errorData']['Warning'][]="No MuG cloud named '$cloudName' is registered. Instead, attempting 'mug-bsc' infrastructure";
			$cloudName="mug-bsc";
		}else{
			$this->cloud = $GLOBALS['clouds'][$cloudName];
		}
        logger("PMES invocation in $cloudName");

		//PMES url server
		$this->server = "http://".$this->cloud['PMESserver_domain'].":".$this->cloud['PMESserver_port']."/".$this->cloud['PMESserver_address'];

		//test server access
        $test = get_headers($this->server.$this->APIroot,1);
        if (!$test[0]){
            $this->stderr = "Cannot connect to ".$this->server.$this->APIroot;
            return 0;
        }
		if (preg_match('/404/', $test[0])){
			$this->stderr = $test[0];
			return 0;
		}
		$this->listening   = true;
		$this->lastCall = $test;
		$this->lastCall['url'] = $this->server.$this->APIroot;

		//if data, post it
		if ($service === true && $data){
			$r = $this->post($data,$service);
			return $r;
		}
		return 1;
    }


    private function post($data,$service){

		$url = $this->server.$this->APIroot.$service;
		logger("PMES POST call. URL = '$url'");

		$data_string = json_encode($data);
	
		//print "<br>POST DATA IS <br>";
        //print "<pre>".json_encode($data, JSON_PRETTY_PRINT)."</pre>";
		
	
		if (!strlen($data_string)){
		    $_SESSION['errorData']['Error'][]="Curl: cannot POST request. Data to send is empty";
		    return 0;
		}
		logger("PMES POST call. POST_DATA = '".json_encode($data). "'");
        logger("curl -H \"Content-Type: application/json\" -H \"Content-Length: ".strlen($data_string)."\" -X POST -d '".json_encode($data)."'  $url");
        
        $headers = array(
            'Content-Type: application/json',
            'Content-Length: '. strlen($data_string)
        );

        list($r,$info) = post($data_string,$url,$headers);

		logger("RESPONSE => ".json_encode($r). "'");


        if ($r == "0"){
            if ($_SESSION['errorData']['Error']){
    			$err = array_pop($_SESSION['errorData']['Error']);
                logger("ERROR:" .$err);
            }
            if ($info['http_code'] != 200){
                logger("ERROR: Unexpected http code. HTTP code: ".$info['http_code']);
                logger("ERROR: calling PMES. POST_RESPONSE = '".strip_tags($r). "'");
            }
            return 0;
        }

		//print "<br>AFTER CURL EXEC RETURNS<br>";
		//var_dump($r);

		return $r;

    }

	public function runPMES($data){
		$service= "createActivity";
		$r = $this->post($data,$service);

		if ($r != "0"){
			$jobids      = json_decode($r);
			$this->jobid = $jobids[0];
		}

		return $this->jobid;
	}

	public function getActivityInfo($jobid){
		$jobInfo =array();
		$service = "getActivityReport";
		$r = $this->post(array($jobid),$service);

		if ($r != "0"){
            if (preg_match('/<html>/',$r)){
                $jobInfo = array('jobStatus' => "IN LIMBO",
                                 'jobOutputMessage' => $r
                            );
                log_addError($jobid,"PMES/getActivityReport: ".json_encode($jobInfo,TRUE),0);
            }else{
                $jobInfo = flattenArray(array_shift(json_decode($r,TRUE)));
            }
        }
        return $jobInfo;
    }

    public function getRunningJobInfo($jobid){
		$job=array();
        $jobPMES = $this->getActivityInfo($jobid);
        if (count($jobPMES)){
            // Consider NOT_RUNNING for finished jobs
    		if ($jobPMES['jobStatus'] && in_array($jobPMES['jobStatus'], array("FINISHED","ERROR","FAILED","UNKNOWN", "CANCELLED", "IN LIMBO" )) ){
                log_addInfo($jobid,"Job not running anymore. State: ".$jobPMES['jobStatus']);
                return $job;
            }
            // For RUNNING, set job['state']
    		$job = $jobPMES;
    		$job['state']          = ($jobPMES['jobStatus']?$jobPMES['jobStatus']:"UNKNOWN");
    		$job['submission_time']= "";
	    }
		return $job;
	}

    public function getSystemStatus(){
        $service= "getSystemStatus";
		$url = $this->server.$this->APIroot.$service;

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_USERAGENT, 'Codular Sample cURL Request');

		$r = curl_exec($c);
		curl_close($c);
    }


	public function stop($jobid){
		$service = "terminateActivity";
        $r = $this->post(array($jobid),$service);
        log_addInfo($jobid,"PMES/terminateActivity: ".$r);
        if ($r == "0"){
           $_SESSION['errorData']['Error'][]="Error calling 'terminateActivity' via POST";
           return 0;
        }
        if (preg_match('/will be cancelled/',$r)){
            return 1;
        }elseif(preg_match('/cannot be cancelled, the job has been finished in Failed state/',$r)){
            $_SESSION['errorData']['Warning'][]="Forcing job cancellation for [ID $jobid]. Job was found in error";
            return 1;
        }else{
            $_SESSION['errorData']['Error'][]=$r;
            return 0;
        }
	}

	public function getErr(){
        	if ($this->stderr)
			return $this->stderr;
		else
			return NULL;
	}


	public function getJobId(){
           return $this->jobid;
    }
	public function getServer(){
           return $this->server;
    }

    public function getCurrentCloud(){
        if (isset($GLOBALS['cloud']) and isset($GLOBALS['clouds'][$GLOBALS['cloud']]) ){
            return $GLOBALS['clouds'][$GLOBALS['cloud']];
        }
		if (!isset($_SERVER['HTTP_X_FORWARDED_SERVER'])){
            return 0;
        }else{
            $serverName = explode(",",$_SERVER['HTTP_X_FORWARDED_SERVER'])[0];
        }
		foreach ($GLOBALS['clouds'] as $cloudName => $cloudInfo){
			if ($cloudInfo['http_host'] == $serverName){
			    return $cloudInfo;
            }
		}
		$_SESSION['errorData']['Warning'][]="No MuG cloud associated to current host domain '".$_SERVER['HTTP_HOST']."'";
		return 0;
	}

    private function openstack_getAccessToken(){

        if ( !isset($this->cloud['auth']) ){
            $_SESSION['errorData']['Error'][]="No authorization data set. Cannot connect to openstack cloud.";
            return 0;
        }

        // request openstack token via REST
        //
        $auth_data = $this->cloud['auth'];

        $data = array ("auth" => array( "tenantName"          => "'".$auth_data["OS_TENANT_NAME"]."'",
                                        "passwordCredentials" => array ( "username" => "'".$auth_data["OS_USERNAME"]."'",
                                                                         "password" => "'".$auth_data["OS_PASSWORD"]."'")));

        $url = $auth_data["OS_AUTH_URL"]."/tokens";

        $data_string = json_encode($data);

        if (!strlen($data_string)){
            $_SESSION['errorData']['Error'][]="Curl: cannot POST openstack token request. Data to send is empty";
            return 0;
        }
        logger("OS TOKEN request: ".$cmd);

        $cmd = "curl -H \"Content-Type: application/json\" -X POST -d '".json_encode($data)."'  $url";
        subprocess($cmd,$r_str,$stdErr);

        $r = json_decode($r_str, true); 
        var_dump($r);


        print "<br/> CMD --> $cmd <br/>";
        print "<br/><br/><br/>----------------------------------------------------R2 ---------> $r";

        // curl_init returns 401. Instead, using curl via subprocess
        //$headers = array('Content-Type: application/json');
        //list($r,$info) = post($data_string,$url,$headers);

        if (!isset($r['access']["token"])){
            if ($_SESSION['errorData']['Error']){
                $_SESSION['errorData']['Error'] = "Cannot access to the requestes cloud. Authorization failed.";
                logger("ERROR: Cannot access to the requestes cloud. Authorization failed. Response: $r");
            }
            return 0;
        }
        return $r['access']["token"];
    }

    private function openstack_isTokenExpired($token){
        $token_expires = strtotime($token["expires"]);
        date_default_timezone_set("UTC");
        return $token_expires < time();
    }
}
?>
