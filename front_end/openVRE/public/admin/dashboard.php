<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectAdminOutside();


$userAttributesProjection = ['projection' => ['Email' => 1, 'Surname' => 1, 'Name' => 1, 'Inst' => 1, 'diskQuota' => 1,  'Type' => 1, 'Status' => 1, 'registrationDate' => 1]];
$filterNamedUsers = array("Type" => array('$ne' => UserType::Guest->value));
$namedUsers = iterator_to_array($GLOBALS['usersCol']->find($filterNamedUsers, $userAttributesProjection));

// emails chart and data
$emails = array();
$count_emails = 0;
if ($GLOBALS['logMailCol']) {
    $ops = ['projection' => ['timestamp' => 1], 'sort' => ['timestamp' => 1]];
    foreach (array_values(iterator_to_array($GLOBALS['logMailCol']->find(array(), $ops))) as $v) {
        array_push($emails, date('m/d/Y', strtotime($v['timestamp'])));
        $count_emails++;
    }
}

$emails = array_count_values($emails);
if ($emails) {
    $date_min = min(array_keys($emails));
    $date_max = max(array_keys($emails));

    $datetime1 = new DateTime($date_min);
    $datetime2 = new DateTime($date_max);
    $interval = $datetime1->diff($datetime2);
    $totalDays = $interval->format('%a') + 1;

    $averagePerDay = number_format(($count_emails / $totalDays), 2);
} else {
    $averagePerDay = 0;
}

// total used disk
$percent_total_disk = round(((disk_total_space($GLOBALS['shared']) - disk_free_space($GLOBALS['shared'])) / disk_total_space($GLOBALS['shared'])) * 100);
$space_total_disk = round(disk_total_space($GLOBALS['shared']) / pow(1024, 3));
$space_used_disk = round((disk_total_space($GLOBALS['shared']) - disk_free_space($GLOBALS['shared'])) / pow(1024, 3));

// CPU stats
$cpu_stat1 = GetCoreInformation();
sleep(1);
$cpu_stat2 = GetCoreInformation();
$cpu_data = GetCpuPercentages($cpu_stat1, $cpu_stat2);

$number_of_cpus = sizeof($cpu_data);

// users register
for ($i = 0; $i <= 11; $i++) {
    $months_list[date("Y/m", strtotime(date('Y-m') . " -$i months"))] = 0;
}

$types_of_user = array();
foreach ($GLOBALS['ROLES'] as $k => $v) {
    $types_of_user[$k] = 0;
}

foreach ($namedUsers as $userAttributes) {
    if (isset($userAttributes["registrationDate"])) {
        $monthyear = substr($userAttributes["registrationDate"], 0, 7);
        if (array_key_exists($monthyear, $months_list)) {
            $months_list[$monthyear]++;
        }
    }

    $types_of_user[$userAttributes["Type"]]++;
}

$months_list = array_reverse($months_list);

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
                            <span>Dashboard</span>
                        </li>
                    </ul>
                </div>
                <!-- END PAGE BAR -->
                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title"> Dashboard
                    <small>dashboard & statistics</small>
                </h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->
                <!-- BEGIN DASHBOARD STATS 1-->
                <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

                <div class="row">
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="admin/adminUsers.php">
                            <div class="visual">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="details">
                                <div class="number">
                                    <span data-counter="counterup" data-value="<?php echo sizeof($namedUsers); ?>">0</span>
                                </div>
                                <div class="desc"> Total registered users </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <a class="dashboard-stat dashboard-stat-v2 green" href="#tableDisk">
                            <div class="visual">
                                <i class="fa fa-database"></i>
                            </div>
                            <div class="details">
                                <div class="number">
                                    <span data-counter="counterup" data-value="5"></span>
                                </div>
                                <div class="desc"> Users with +50%<br>disk used </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <a class="dashboard-stat dashboard-stat-v2 blue" href="javascript:;">
                            <div class="visual">
                                <i class="fa fa-envelope"></i>
                            </div>
                            <div class="details">
                                <div class="number">
                                    <span data-counter="counterup" data-value="<?php echo $averagePerDay; ?>">0</span>
                                </div>
                                <div class="desc"> Mails per day </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <a class="dashboard-stat dashboard-stat-v2 blue-madison" href="javascript:;">
                            <div class="visual">
                                <i class="fa fa-send-o"></i>
                            </div>
                            <div class="details">
                                <div class="number">
                                    <span data-counter="counterup" data-value="<?php echo round($averagePerDay * 30); ?>">0</span>
                                </div>
                                <div class="desc"> Mails per month </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="clearfix"></div>
                <!-- END DASHBOARD STATS 1-->
                <div class="row">
                    <div class="col-md-3 col-sm-3">
                        <!-- BEGIN PORTLET-->
                        <div class="portlet light bordered" style="height:395px;">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-share font-red-sunglo hide"></i>
                                    <span class="caption-subject font-dark bold uppercase">Total Used Disk</span>
                                </div>

                            </div>
                            <div class="portlet-body" style="text-align:center;padding-top:30px;">
                                <input class="knob" data-fgColor="#f3c200" data-bgColor="#eeeeee" readonly value="<?php echo $percent_total_disk; ?>">
                                <p style="font-size:20px; margin-top:30px;"><?php echo $space_used_disk; ?>GB of <?php echo $space_total_disk; ?>GB</p>
                            </div>
                        </div>
                        <!-- END PORTLET-->
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <!-- BEGIN PORTLET-->
                        <div class="portlet light bordered" style="height:395px;">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-share font-red-sunglo hide"></i>
                                    <span class="caption-subject font-dark bold uppercase">Average Used Disk</span>
                                </div>

                            </div>
                            <div class="portlet-body" style="text-align:center;padding-top:30px;">
                                <input class="knob" data-fgColor="#006b8f" data-bgColor="#eeeeee" readonly value="6.5">
                                <p style="font-size:20px; margin-top:30px;">1.3GB of 20GB</p>
                            </div>
                        </div>
                        <!-- END PORTLET-->
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <!-- BEGIN PORTLET-->
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-bar-chart font-dark hide"></i>
                                    <span class="caption-subject font-dark bold uppercase">Mails Sent</span>
                                    <span class="caption-helper">monthly stats</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div id="site_statistics_loading">
                                    <img src="assets/global/img/loading.gif" alt="loading" />
                                </div>
                                <div id="site_statistics_content" class="display-none">
                                    <div id="site_statistics" class="chart"> </div>
                                </div>
                            </div>
                        </div>
                        <!-- END PORTLET-->
                    </div>
                </div>
                <!--<div class="row">
							<a name="tableDisk"></a>
                            <div class="col-md-12">
                                <div class="portlet light portlet-fit bordered">
                                  <div class="portlet-title">
                                      <div class="caption">
                                          <i class="icon-share font-red-sunglo hide"></i>
                                          <span class="caption-subject font-dark bold uppercase">Handle Users Disk Quota </span>
                                      </div>

                                  </div>
                                    <div class="portlet-body">

                                        <table class="table table-striped table-hover table-bordered" id="sample_editable_1">
                                            <thead>
                                                <tr>
                                                    <th> Email </th>
                                                    <th> Surname </th>
                                                    <th> Name </th>
                                                    <th> Institution </th>
                                                    <th> Type </th>
                                                    <th> Disk Used </th>
                                                    <th> Disk Quota </th>
                                                    <th> Actions </th>
                                                </tr>
                                            </thead>
											<tbody>	
											<?php
                                            $mock_disk = array();
                                            foreach ($namedUsers as $userAttributes):
                                            ?>
												<tr>
													<td><a href="mailto:<?php echo $userAttributes["Email"]; ?>"><?php echo $userAttributes["Email"]; ?></a></td>
													<td><?php echo $userAttributes["SurName"]; ?></td>
													<td><?php echo $userAttributes["Name"]; ?></td>
													<td><?php echo $userAttributes["Inst"]; ?></td>
													<td>
                                                      <div class="btn-group">
                                                        <button class="btn btn-xs btn-default <?php echo $GLOBALS['ROLES_COLOR'][$userAttributes["Type"]]; ?> dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" disabled="" style="opacity:1;"> <?php echo $GLOBALS['ROLES'][$userAttributes["Type"]]; ?> </button>
                                                      </div>
                                                    </td>
													<td>
														<?php
                                                        $value_disk = rand(0, 200) / 10;
                                                        array_push($mock_disk, $value_disk);
                                                        echo $value_disk;
                                                        ?>
                                                    </td>
													<td><?php echo $userAttributes["Inst"]; ?></td>
													<td>
													  <?php if ($userAttributes["Type"] != 0) { ?>
													  <div class="btn-group">
														  <?php if ($userAttributes["Status"] == 0) { ?>
														  <button class="btn btn-xs red dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" disabled style="opacity:1"> Disabled 
                                                              <i class="fa fa-ban"></i>
                                                          </button>
														  <?php } else { ?>
                                                          <button class="btn btn-xs green dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions
                                                              <i class="fa fa-angle-down"></i>
                                                          </button>
                                                          <ul class="dropdown-menu pull-right" role="menu">
                                                            <li>
                                                                <a class="edit" href="javascript:;">
                                                                    <i class="fa fa-pencil"></i> Change Disk Quota</a>
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
                            </div>
                        </div>-->

                <div class="row">
                    <div class="col-lg-12 col-xs-12 col-sm-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-equalizer font-dark hide"></i>
                                    <span class="caption-subject font-dark bold uppercase">Global Users Stats</span>
                                </div>
                                <!--<div class="actions">
                                            <a href="javascript:;" class="btn btn-sm green sparkline-reload">
                                                <i class="fa fa-repeat"></i> Reload </a>
                                        </div>-->
                            </div>
                            <div class="portlet-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="sparkline-chart">
                                            <span id="spark_registers"><?php $i = 1;
                                                                        foreach ($months_list as $k => $v) {
                                                                            if ($i < sizeof($months_list)) {
                                                                                echo $v . ',';
                                                                            } else {
                                                                                echo $v;
                                                                            }
                                                                            $i++;
                                                                        } ?></span>
                                            <p style="margin-top:15px;">Monthly new users</p>
                                        </div>
                                    </div>
                                    <div class="margin-bottom-10 visible-sm"> </div>
                                    <div class="col-md-4">
                                        <div class="sparkline-chart">
                                            <span id="spark_types"><?php foreach ($types_of_user as $k => $v) {
                                                                        echo $v . ',';
                                                                    } ?></span>
                                            <p style="margin-top:15px;">Types of users</p>
                                        </div>
                                    </div>
                                    <div class="margin-bottom-10 visible-sm"> </div>
                                    <div class="col-md-4">
                                        <div class="sparkline-chart">
                                            <span id="spark_disk"><?php foreach ($mock_disk as $k => $v) {
                                                                        echo $v . ',';
                                                                    } ?></span>
                                            <p style="margin-top:15px;">Average disk used</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12 col-xs-12 col-sm-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="icon-cursor font-dark hide"></i>
                                    <span class="caption-subject font-dark bold uppercase">vre server CPU Stats</span>
                                </div>
                                <!--<div class="actions">
                                            <a href="javascript:;" class="btn btn-sm green easy-pie-chart-reload">
                                                <i class="fa fa-repeat"></i> Reload </a>
                                        </div>-->
                            </div>
                            <div class="portlet-body">
                                <div class="row">
                                    <?php foreach ($cpu_data as $k => $v) { ?>
                                        <div class="col-md-<?php echo 12 / $number_of_cpus;  ?>">
                                            <div class="easy-pie-chart">
                                                <div class="number cpu_info <?php echo $k; ?>" data-percent="<?php echo ($v['user'] + $v['nice'] + $v['sys']); ?>">
                                                    <span><?php echo round($v['user'] + $v['nice'] + $v['sys']); ?></span>%
                                                </div>
                                                <p style="margin-top:15px;"><?php echo $k; ?></p>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 col-xs-12 col-sm-12">
                        <!-- BEGIN DYNAMIC CHART PORTLET-->
                        <div class="portlet light portlet-fit bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <span class="caption-subject font-dark bold uppercase">vre server Memory Usage</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div id="chart_4" class="chart"> </div>
                            </div>
                        </div>
                        <!-- END DYNAMIC CHART PORTLET-->

                    </div>

                    <div class="col-lg-6 col-xs-12 col-sm-12">

                        <!-- BEGIN DYNAMIC CHART PORTLET-->
                        <div class="portlet light portlet-fit bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <span class="caption-subject font-dark bold uppercase">vre server CPU Usage</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div id="chart_5" class="chart"> </div>
                            </div>
                        </div>
                        <!-- END DYNAMIC CHART PORTLET-->
                    </div>
                </div>


            </div>
            <!-- END CONTENT BODY -->
        </div>
        <!-- END CONTENT -->
        <div class="modal fade bs-modal-sm" id="myModal1" tabindex="-1" role="basic" aria-hidden="true">
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


        <?php

        require "../htmlib/footer.inc.php";
        require "../htmlib/js.inc.php";

        ?>