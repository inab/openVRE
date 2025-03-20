<?php

class User
{

    public $_id; //= Email
    public $Surname;
    public $Name;
    public $Inst;
    public $Email;
    public $lastLogin;
    public $registrationDate;
    public $Type;
    public $Status;
    public $diskQuota;
    public $dataDir;
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
        foreach (array('Surname', 'Name', 'Inst', 'Email', 'dataDir', 'diskQuota', 'AuthProvider', 'activeProject') as $k) {
            if (isset($f[$k])) {
                $this->$k = sanitizeString($f[$k]);
            }
        }

        if (!isset($_SESSION['userToken']) && $f['Type'] != UserType::Guest->value) {
            return 0;
        }

        $this->Type = $f['Type'] ?? UserType::Registered->value;
        $this->_id = $this->Email;
        $this->id = ($this->Type != UserType::Guest->value ? uniqid($GLOBALS['AppPrefix'] . "USER") : uniqid($GLOBALS['AppPrefix'] . "ANON"));
        $this->activeProject = (!$this->activeProject ? createLabel_proj() : $this->activeProject);
        $this->Status = userStatus::Active->value;

        // set creation time and last login
        $this->lastLogin        = moment();
        $this->registrationDate = (!$this->registrationDate ? moment() : $this->registrationDate);

        // set user quota according to user type
        $this->diskQuota  = (!$this->diskQuota && $this->Type != UserType::Guest->value ? $GLOBALS['DISKLIMIT'] : $GLOBALS['DISKLIMIT_ANON']);

        // process given attributes 
        $this->Surname = ucfirst($this->Surname);
        $this->Name    = ucfirst($this->Name);
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
