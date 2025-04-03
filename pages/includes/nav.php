<!-- Navigation -->
<nav class="navbar navbar-default navbar-fixed-top" role="navigation" style="margin-bottom: 0">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="index.php"><i class=""></i>BAPS MODASA DHARMADO MANAGEMENT SYSTEM</a>
    </div>
    <!-- /.navbar-header -->

    <ul class="nav navbar-top-links navbar-right">
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-user fa-fw"></i> <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-user">
                <li><a href="../logout.php"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                </li>
            </ul>
            <!-- /.dropdown-user -->
        </li>
    </ul>
    <!-- /.navbar-top-links -->

    <div class="navbar-default sidebar" role="navigation">
        <div class="sidebar-nav navbar-collapse">
            <ul class="nav" id="side-menu">
                <li>
                    <a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                </li>
                <li>
                    <a href="userprofile.php"><i class="fa fa-user"></i> User Profile</a>
                </li>
                <li>
                    <a href="addblood.php"><i class="fa fa-user-plus"></i> Add User Details</a>
                </li>
                <li>
                    <a href="manageblood.php"><i class="fa fa-tasks"></i> Manage User Details</a>
                </li>
                <li>
                    <a href="downloadpdf.php"><i class="fa fa-file-pdf-o"></i> Download PDF</a>
                </li>
            </ul>
        </div>
        <!-- /.sidebar-collapse -->
    </div>
    <!-- /.navbar-static-side -->
</nav>

<style>
    body {
        padding-top: 50px; /* Add padding to prevent content overlap with fixed navbar */
    }

    .fixed-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 35px;
        background-color: #424558;
        text-align: center;
        color: #CCC;
        z-index: 1000;
    }

    .fixed-footer p {
        padding: 10.5px;
        margin: 0px;
        line-height: 100%;
    }
</style>