
<?php

// temporary set user type with int to keep backwards compatibility
enum UserType: int
{
    case Admin = 0;
    case ToolDev = 1;
    case Registered = 2;
    case Guest = 3;
}
?>
