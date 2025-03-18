<?php

class User
{

    public $_id; //= Email
    public $Surname;
    public $Name;
    public $Inst;
    public $Country;
    public $Email;
    public $lastLogin;
    public $registrationDate;
    public $Type;
    public $Status;
    public $diskQuota;
    public $dataDir;
    public $DataSample;
    public $Token;
    public $TokenInfo;
    public $Vault;
    public $AuthProvider;
    public $id;
    public $activeProject;

    function __construct($f)
    {

        // stop unless Email
        if (!$f['Email'])
            return 0;

        // set attributes from arguments
        foreach (array('Surname', 'Name', 'Inst', 'Country', 'Email', 'Type', 'dataDir', 'diskQuota', 'DataSample', 'AuthProvider', 'activeProject') as $k) {
            if (isset($f[$k])) {
                $this->$k = sanitizeString($f[$k]);
            }
        }

        if (isset($f['Token'])) {
            $this->Token = $f['Token'];
        } elseif (isset($f['TokenInfo'])) {
            $this->TokenInfo = $f['TokenInfo'];
        } elseif ($f['Type'] != UserType::Guest) {
            return 0;
        }

        $this->Type = $this->Type ?? $this->Type = UserType::Registered;
        $this->_id = $this->Email;
        $this->id = ($this->Type != UserType::Guest ? uniqid($GLOBALS['AppPrefix'] . "USER") : uniqid($GLOBALS['AppPrefix'] . "ANON"));
        $this->activeProject = (!$this->activeProject ? createLabel_proj() : $this->activeProject);

        // set status (1: active, ...)
        $this->Status = "1";

        // set creation time and last login
        $this->lastLogin        = moment();
        $this->registrationDate = (!$this->registrationDate ? moment() : $this->registrationDate);

        // set user quota according to user type
        $this->diskQuota  = (!$this->diskQuota && $this->Type != UserType::Guest ? $GLOBALS['DISKLIMIT'] : $GLOBALS['DISKLIMIT_ANON']);

        // process given attributes 
        $this->Surname = ucfirst($this->Surname);
        $this->Name    = ucfirst($this->Name);

        // set inicial sample data for user workspace 
        $this->DataSample = ($this->DataSample ? $this->DataSample : $GLOBALS['sampleData_default']);

        $this->Vault = array(
            "vaultClient" => array(
                "jwtToken"    => isset($f['jwtToken']) ? $f['jwtToken'] : "", // Optionally pass jwtToken via $f or fetch from $_SESSION
                "credentials" => array("data" => array("SSH" => array()))
            ),
            "vaultKey"     => null,
            "secretPath"   => isset($GLOBALS['secretPath']) ? $GLOBALS['secretPath'] : '',
            "vaultRolename" => isset($GLOBALS['vaultRolename']) ? $GLOBALS['vaultRolename'] : '',
            "vaultToken"   => isset($GLOBALS['vaultToken']) ? $GLOBALS['vaultToken'] : '',
            "vaultUrl"     => isset($GLOBALS['vaultUrl']) ? $GLOBALS['vaultUrl'] : ''
        );


        return $this;
    }
}
