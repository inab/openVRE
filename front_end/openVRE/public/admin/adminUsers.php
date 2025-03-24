<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectAdminOutside();

$userAttributesProjection = ['projection' => ['Email' => 1, 'Surname' => 1, 'Name' => 1, 'Inst' => 1, 'diskQuota' => 1, 'lastLogin' => 1, 'Type' => 1, 'Status' => 1, 'id' => 1, 'lastReload' => 1]];
$filterNamedUsers = array("Type" => array('$ne' => UserType::Guest->value));
$namedUsers = getUsersByFilter($filterNamedUsers, $userAttributesProjection);

?>

<?php require "../htmlib/header.inc.php"; ?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
    <div class="page-wrapper">

        <?php require "../htmlib/top.inc.php"; ?>
        <?php require "../htmlib/menu.inc.php"; ?>


        <!-- BEGIN CONTENT -->
        <div class="page-content-wrapper">
            <!-- BEGIN CONTENT BODY -->
            <div class="page-content">
                <!-- BEGIN PAGE HEADER-->
                <!-- BEGIN PAGE BAR -->
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li>
                            <span>Admin</span>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Admin Users</span>
                        </li>
                    </ul>
                </div>
                <!-- END PAGE BAR -->
                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title"> Users Administration
                    <small>Edit user's data and create new users</small>
                </h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->

                <div class="row">
                    <div class="col-md-12">
                        <?php
                        $error_data = false;
                        if ($_SESSION['errorData']) {
                            $error_data = true;
                        ?>
                            <?php if ($_SESSION['errorData']['Info']) { ?>
                                <div class="alert alert-info">
                                <?php } else { ?>
                                    <div class="alert alert-danger">
                                    <?php } ?>

                                    <?php
                                    foreach ($_SESSION['errorData'] as $subTitle => $txts) {
                                        print "<strong>$subTitle</strong><br/>";
                                        foreach ($txts as $txt) {
                                            print "<div>$txt</div>";
                                        }
                                    }
                                    unset($_SESSION['errorData']);
                                    ?>
                                    </div>
                                <?php } ?>
                                </div>
                    </div>



                    <div class="row">
                        <div class="col-md-12">
                            <!-- BEGIN EXAMPLE TABLE PORTLET-->
                            <div class="portlet light portlet-fit bordered">

                                <div class="portlet-body">
                                    <div class="table-toolbar">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="btn-group">
                                                    <button class="btn green" onclick="location.href = 'admin/newUser.php'"> Add New
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

                                    <table class="table table-striped table-hover table-bordered" id="sample_editable_1">
                                        <thead>
                                            <tr>
                                                <th> Email </th>
                                                <th> Surname </th>
                                                <th> Name </th>
                                                <th> Institution </th>
                                                <th> Type of User </th>
                                                <th> Last login </th>
                                                <th> Status
                                                    <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="top" data-original-title="<p align='left' style='margin:0'>There are three possible status according to the last user's login:<br>- INACTIVE: more than 30 days from the last login<br>- ACTIVE: less than 30 days from the last login<br>- LOGGED IN: user is currently logged in</p>"></i>
                                                </th>
                                                <th> Disk </th>
                                                <th> Actions </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($namedUsers as $userAttributes):
                                                if (!$userAttributes["lastReload"]) {
                                                    continue;
                                                }
                                            ?>
                                                <tr>
                                                    <td><a href="mailto:<?php echo $userAttributes["Email"]; ?>"><?php echo $userAttributes["Email"]; ?></a><br /><?php echo $userAttributes["lastReload"]; ?></td>
                                                    <td><?php echo $userAttributes["Surname"]; ?></td>
                                                    <td><?php echo $userAttributes["Name"]; ?></td>
                                                    <td><?php echo $userAttributes["Inst"]; ?></td>
                                                    <td>
                                                        <!--<div class="btn-group">
														<?php if ($userAttributes["Type"] == 0) { ?>
														<button disabled class="btn btn-xs blue dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" style="opacity:1;"> <?php echo $GLOBALS['ROLES'][$userAttributes["Type"]]; ?>
                                                            <i class="fa fa-circle-thin"></i>
                                                        </button>	
														<?php } else { ?>
														<button class="btn btn-xs btn-default <?php echo $GLOBALS['ROLES_COLOR'][$userAttributes["Type"]]; ?> dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> <?php echo $GLOBALS['ROLES'][$userAttributes["Type"]]; ?>
                                                            <i class="fa fa-angle-down"></i>
                                                        </button>
														<ul class="dropdown-menu" role="menu">
														<?php foreach ($GLOBALS['ROLES'] as $k => $v): ?>
														<li><a class="role-usr role<?php echo $k; ?>"  href="javascript:;"><?php echo $v; ?></a></li>
														<?php endforeach; ?>
														</ul>
														<div style="display:none;">*<?php echo $userAttributes["Type"]; ?>*</div>
														<?php } ?>
														</div>-->
                                                        <?php
                                                        $colorRole = '';
                                                        if ($userAttributes["Type"] == 0) {
                                                            $colorRole = 'font-blue bold';
                                                        }

                                                        echo "<span class='$colorRole'>" . $GLOBALS['ROLES'][$userAttributes["Type"]] . "</span>";
                                                        ?>
                                                    </td>
                                                    <td><?php print returnHumanDate($userAttributes["lastLogin"]);
                                                        $hoursLastReload = (time() - momentToTime($userAttributes["lastReload"])) / 3600;
                                                        $daysLastLogin   = (time() - momentToTime($userAttributes["lastLogin"])) / (3600 * 24);

                                                        ?></td>
                                                    <td>
                                                        <?php
                                                        if ($hoursLastReload < 0.6) {
                                                            print "<span class='font-green'>LOGGED IN</span>";
                                                        } elseif ($daysLastLogin < 30) {
                                                            print "<span class='font-green-meadow'>ACTIVE</span>";
                                                        } else {
                                                            print "<span class='font-red'>INACTIVE</span>";
                                                        }
                                                        ?>
                                                    </td>

                                                    <td><?php echo (($userAttributes["diskQuota"] / 1024) / 1024) / 1024; ?> GB</td>
                                                    <td>
                                                        <?php if ($userAttributes["Type"] != 0) { ?>
                                                            <div class="btn-group">
                                                                <?php if ($userAttributes["Status"] == 0) { ?>
                                                                    <button class="btn btn-xs red dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions
                                                                        <i class="fa fa-angle-down"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu pull-right" role="menu">
                                                                        <li>
                                                                            <a class="enable" href="javascript:;">
                                                                                <i class="fa fa-check-circle"></i> Enable user</a>
                                                                        </li>
                                                                    </ul>
                                                                <?php } else { ?>
                                                                    <button class="btn btn-xs green dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions
                                                                        <i class="fa fa-angle-down"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu pull-right" role="menu">
                                                                        <li>
                                                                            <a class="" href="admin/editUser.php?id=<?php echo $userAttributes["_id"]; ?>">
                                                                                <i class="fa fa-pencil"></i> Edit user</a>
                                                                        </li>
                                                                        <li>
                                                                            <a class="" href="applib/loginImpersonate.php?id=<?php echo $key; ?>">
                                                                                <i class="fa fa-user"></i> Impersonate user</a>
                                                                        </li>
                                                                        <li>
                                                                            <a class="enable" href="javascript:;">
                                                                                <i class="fa fa-ban"></i> Disable user</a>
                                                                        </li>
                                                                        <li>
                                                                            <a href="javascript:deleteUser('<?php echo $userAttributes["_id"]; ?>');">
                                                                                <i class="fa fa-trash"></i> Delete user</a>
                                                                        </li>
                                                                    </ul>
                                                                <?php } ?>
                                                            </div>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php
                                            endforeach;
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- END EXAMPLE TABLE PORTLET-->
                        </div>
                    </div>
                </div>
                <!-- END CONTENT BODY -->
            </div>
            <!-- END CONTENT -->

            <div class="modal fade bs-modal" id="modalDelete" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">Warning!</h4>
                        </div>
                        <div class="modal-body">Are you sure you want to delete the selected file?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn dark btn-outline" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn red btn-modal-del">Delete</button>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal fade bs-modal-sm" id="myModal1" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">Warning!</h4>
                        </div>
                        <div class="modal-body"> You can't add a new user until you have finished editing the current one. </div>
                        <div class="modal-footer">
                            <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <div class="modal fade" id="myModal2" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">Validation error</h4>
                        </div>
                        <div class="modal-body"> Please fill in all fields and be sure that the e-mail has a proper format and the disk quota value is between 1 and 100 </div>
                        <div class="modal-footer">
                            <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <div class="modal fade bs-modal-sm" id="myModal3" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">Warning!</h4>
                        </div>
                        <div class="modal-body"> You can't edit a user until you have finished editing the new one. </div>
                        <div class="modal-footer">
                            <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <div class="modal fade bs-modal-sm" id="myModal5" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">Error!</h4>
                        </div>
                        <div class="modal-body"> Something happened updating data, please try again. </div>
                        <div class="modal-footer">
                            <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <div class="modal fade bs-modal-sm" id="myModal6" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h4 class="modal-title">Error!</h4>
                        </div>
                        <div class="modal-body"> A user with this email already exists in our database, please try with different data. </div>
                        <div class="modal-footer">
                            <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>




            <?php

            require "../htmlib/footer.inc.php";
            require "../htmlib/js.inc.php";

            ?>