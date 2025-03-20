<?php

class User
{

    public $_id;
    public $Email;
    public $Surname;
    public $Name;
    public $Inst;
    public $lastLogin;
    public $registrationDate;
    public $Type;
    public $Status;
    public $diskQuota;
    public $dataDir;
    public $Vault;
    public $AuthProvider;
    public $id; // TODO: diff with _id?
    public $activeProject;

    public function __construct(string $email, string $surname, string $name, string $inst, int $type, string $diskQuota, string $dataDir, ?string $authProvider, string $activeProject)
    {
        if ($type != UserType::Guest->value && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 0;
        }

        if (!isset($_SESSION['userToken']) && $type != UserType::Guest->value) {
            return 0;
        }

        $this->Type = $type ?? UserType::Registered->value; // TODO: check if this is ok
        $this->Email = sanitizeString($email);
        $this->_id = sanitizeString($email);
        $this->Surname = ucfirst(sanitizeString($surname));
        $this->Name = ucfirst(sanitizeString($name));
        $this->Inst = sanitizeString($inst);
        $this->diskQuota = sanitizeString($diskQuota);
        $this->dataDir = sanitizeString($dataDir);
        $this->AuthProvider = sanitizeString($authProvider);
        $this->activeProject = sanitizeString($activeProject);
        $this->id = $this->Type == UserType::Guest->value
            ? uniqid($GLOBALS['AppPrefix'] . "ANON")
            : uniqid($GLOBALS['AppPrefix'] . "USER");
        $this->activeProject = $this->activeProject ?: createLabel_proj();
        $this->Status = userStatus::Active->value;
        $this->lastLogin = moment();
        $this->registrationDate = $this->registrationDate ?: moment();
        $this->diskQuota  = $this->diskQuota || $this->Type == UserType::Guest->value // TODO: check if this is ok
            ? $GLOBALS['DISKLIMIT_ANON']
            : $GLOBALS['DISKLIMIT'];

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
