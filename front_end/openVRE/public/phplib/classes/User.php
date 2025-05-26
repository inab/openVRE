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
    }

    
    public function getType(): int
    {
        return $this->Type;
    }

    public function setType(int $type): void
    {
        $this->Type = $type;
    }

    public function getEmail(): string
    {
        return $this->Email;
    }

    public function setEmail(string $email): void
    {
        $this->Email = $email;
    }

    public function get_id(): string
    {
        return $this->_id;
    }

    public function set_id(string $id): void
    {
        $this->_id = $id;
    }

    public function getSurname(): string
    {
        return $this->Surname;
    }

    public function setSurname(string $surname): void
    {
        $this->Surname = $surname;
    }

    public function getName(): string
    {
        return $this->Name;
    }

    public function setName(string $name): void
    {
        $this->Name = $name;
    }

    public function getInst(): string
    {
        return $this->Inst;
    }

    public function setInst(string $inst): void
    {
        $this->Inst = $inst;
    }

    public function getDiskQuota(): string
    {
        return $this->diskQuota;
    }

    public function setDiskQuota(string $diskQuota): void
    {
        $this->diskQuota = $diskQuota;
    }

    public function getDataDir(): string
    {
        return $this->dataDir;
    }

    public function setDataDir(string $dataDir): void
    {
        $this->dataDir = $dataDir;
    }

    public function getAuthProvider(): string
    {
        return $this->AuthProvider;
    }

    public function setAuthProvider(string $authProvider): void
    {
        $this->AuthProvider = $authProvider;
    }

    public function getActiveProject(): string
    {
        return $this->activeProject;
    }

    public function setActiveProject(string $activeProject): void
    {
        $this->activeProject = $activeProject;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getStatus(): int
    {
        return $this->Status;
    }

    public function setStatus(int $status): void
    {
        $this->Status = $status;
    }

    public function getLastLogin(): string
    {
        return $this->lastLogin;
    }

    public function setLastLogin(string $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    public function getRegistrationDate(): string
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(string $registrationDate): void
    {
        $this->registrationDate = $registrationDate;
    }
}
