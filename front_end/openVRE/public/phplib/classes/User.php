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
    public $AuthProvider;
    public $id; // TODO: diff with _id?
    public $activeProject;

    public function __construct(string $email, string $surname, string $name, string $inst, int $type, string $diskQuota, string $dataDir, ?string $authProvider, string $activeProject, ?string $jwt)
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

        $_SESSION['userVaultInfo'] = array(
            "vaultClient" => array(
                "jwtToken"    => $jwt ??  "",
                "credentials" => array("data" => array("SSH" => array()))
            ),
            "vaultKey"     => null,
            "secret_path"   => $GLOBALS['secretPath'] ?? '',
            "vault_role_name" => $GLOBALS['vaultRolename'] ?? '',
            "vault_token"   => $GLOBALS['vaultToken'] ?? '',
            "vault_url"     => $GLOBALS['vaultUrl'] ?? ''
        );

        return $this;
    }
}
