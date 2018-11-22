<?php
use Destiny\Common\Utils\Tpl;
?>
<!DOCTYPE html>
<html>
<head>
    <?=Tpl::title($this->title)?>
    <?php include 'seg/meta.php' ?>
    <?=Tpl::manifestLink('common.vendor.css')?>
    <?=Tpl::manifestLink('web.css')?>
</head>
<body id="admin" class="no-contain">
<div id="page-wrap">

    <?php include 'seg/nav.php' ?>
    <?php include 'seg/alerts.php' ?>
    <?php include 'seg/admin.nav.php' ?>

    <section class="container">
        <h3 id="income-dates">
                <span id="date-selector">
                    <a href='#'><i class='fa fa-arrow-left'></i></a> <span class='date'></span> <a href='#'><i class='fa fa-arrow-right'></i></a>
                </span>
        </h3>
        <div class="row">
            <div class="col-md-6 col-sm-12 card">
                <div id="graph4" class="card-inner">
                    <div class="graph-outer">
                        <canvas height="350"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12 card">
                <div id="graph5" class="card-inner">
                    <div class="graph-outer">
                        <canvas height="350"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-12 card">
                <div id="graph1" class="card-inner">
                    <div class="graph-outer">
                        <canvas height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-12 card">
                <div id="graph2" class="card-inner">
                    <div class="graph-outer">
                        <canvas height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 card">
                <div id="graph3" class="card-inner">
                    <div class="graph-outer">
                        <canvas height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container">
        <div class="alert alert-danger" style="margin:0;">
            <strong>Note!</strong>
            Data shown here does NOT take into account fees and taxes.
        </div>
    </section>

</div>

<?php include 'seg/foot.php' ?>
<?php include 'seg/tracker.php' ?>
<?=Tpl::manifestScript('runtime.js')?>
<?=Tpl::manifestScript('common.vendor.js')?>
<?=Tpl::manifestScript('web.js')?>
<?=Tpl::manifestScript('admin.js')?>


</body>
</html>