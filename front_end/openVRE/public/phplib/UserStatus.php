<?php

// temporary set user status with int to keep backwards compatibility
enum UserStatus: int
{
    case Inactive = 0;
    case Active = 1;
}
?>
