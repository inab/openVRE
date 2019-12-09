<?php

class User {

    public $_id; //= Email
    public $Surname;
    public $Name;
    public $Inst;
    public $Country;
    public $Email;
    public $crypPassword;
    public $lastLogin;
    public $registrationDate;
    public $Type;
    public $Status;
    public $diskQuota;
    public $dataDir;
    public $DataSample;
    public $Token;
    public $TokenInfo;
    public $AuthProvider;
    public $id;
    public $activeProject;

    function __construct($f) {

        // stop unless Email
        if (!$f['Email'])
            return 0;

        // set attributes from arguments
	foreach (array('Surname','Name','Inst','Country','Email','Type','dataDir','diskQuota','DataSample','AuthProvider','activeProject') as $k)
	    if (isset($f[$k]))
		$this->$k = sanitizeString($f[$k]);

        // set credential attributes (crypPassword or Token or ANON)
        if (isset($f['pass1'])){
            //$this->crypPassword = password_hash($f['pass1'], PASSWORD_DEFAULT);
            //$this->crypPassword = crypt($f['pass1'], '$6$'.randomSalt(8).'$');
            $salt = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',4)),0,4);
            $this->crypPassword = '{SSHA}' . base64_encode(sha1( $f['pass1'].$salt, TRUE ). $salt);
        }elseif (isset($f['Token'])){
            $this->Token = $f['Token'];
        }elseif (isset($f['TokenInfo'])){
            $this->TokenInfo = $f['TokenInfo'];
        }elseif ($f['Type'] == 3){
        }else{
            return 0;
        }
        $this->Token_mug_ebi= array();
        
        // set user type (0: admin, 1:Tool dev, 2:registered user, 3:guest)
        $this->Type = (!isset($this->Type)?$this->Type=2:$this->Type=$this->Type);

        // set ids
    	$this->_id           = $this->Email;
        $this->id            = ($this->Type!=3?uniqid($GLOBALS['AppPrefix'] . "USER"):uniqid($GLOBALS['AppPrefix'] . "ANON"));
        $this->activeProject = (!$this->activeProject?createLabel_proj():$this->activeProject);
        
        // set status (1: active, ...)
        $this->Status = "1";

        // set creation time and last login
    	$this->lastLogin        = moment();
        $this->registrationDate = (!$this->registrationDate?moment():$this->registrationDate);

        // set user quota according to user type
    	$this->diskQuota  = (!$this->diskQuota && $this->Type!=3? $GLOBALS['DISKLIMIT']:$GLOBALS['DISKLIMIT_ANON']);

        // process given attributes 
    	$this->Surname = ucfirst($this->Surname);
        $this->Name    = ucfirst($this->Name);

        // set inicial sample data for user workspace 
        $this->DataSample = ($this->DataSample?$this->DataSample:$GLOBALS['sampleData_default']);

        return $this;
    }

}

?>
