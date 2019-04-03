<?php

class Email {

    public $_id; //= timestamp
    public $Email;
    public $timestamp;

    function __construct($f) {
        foreach (array('Email') as $k)
		$this->$k=$f[$k];

	$this->timestamp = date('Y/m/d H:i:s');
        $this->_id = time().generatePassword();
        return $this;
    }
}

?>
