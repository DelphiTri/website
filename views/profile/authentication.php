<?php
use Destiny\Common\Utils\Tpl;
use Destiny\Common\Utils\Date;
use Destiny\Common\Config;
?>
<!DOCTYPE html>
<html>
<head>
    <?=Tpl::title($this->title)?>
    <?php include 'seg/meta.php' ?>
    <?=Tpl::manifestLink('common.vendor.css')?>
    <?=Tpl::manifestLink('web.css')?>
</head>
<body id="authentication" class="no-contain">
<div id="page-wrap">

    <?php include 'seg/nav.php' ?>
    <?php include 'seg/alerts.php' ?>
    <?php include 'menu.php' ?>

    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#authentication-content">Login providers</h3>
        <div id="authentication-content" class="content content-dark collapse clearfix">
            <div class="ds-block">
                <p>Connect all the providers to the same destiny.gg user.</p>
            </div>
            <form id="auth-profile-form" method="post">
                <table class="grid" style="width:100%">
                    <thead>
                    <tr>
                        <td>Profile</td>
                        <td style="width:100%;"></td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach(Config::$a ['authProfiles'] as $id): ?>
                        <tr>
                            <td><?=ucwords($id)?></td>
                            <td>
                                <?php if(in_array($id, $this->authProfileTypes)): ?>
                                    <a href="/profile/remove/<?=$id?>" class="btn btn-danger btn-xs btn-post">Remove</a>
                                <?php else: ?>
                                    <a href="/profile/connect/<?=$id?>" class="btn btn-primary btn-xs btn-post">Connect</a>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <br />
        </div>
    </section>

    <section class="container active">
        <h3 class="collapsed" data-toggle="collapse" data-target="#login-key-content">Authentication</h3>
        <div id="login-key-content" class="content content-dark clearfix collapse">
            <div class="ds-block">
                <p>Login keys allow you to authenticate without the need for a username or password.</p>
            </div>
            <form id="authtoken-form" action="/profile/authtoken/create" method="post">

                <?php if(!empty($this->authTokens)): ?>
                    <table class="grid" style="width:100%">
                        <thead>
                        <tr>
                            <td style="width:100%;">Token</td>
                            <td>Created</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($this->authTokens as $authToken): ?>
                            <tr>
                                <td>
                                    <a href="/profile/authtoken/<?=$authToken['authTokenId']?>/delete" class="btn btn-danger btn-xs btn-post">Delete</a>
                                    <span><?=$authToken['authToken']?></span>
                                </td>
                                <td><?=Date::getDateTime($authToken['createdDate'])->format(Date::STRING_FORMAT)?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif ?>

                <div id="recaptcha" class="form-group ds-block hidden">
                    <label>How Can Mirrors Be Real If Our Eyes Aren't Real?</label>
                    <div class="controls">
                        <div class="g-recaptcha" data-sitekey="<?= Config::$a ['g-recaptcha'] ['key'] ?>"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" id="btn-create-key">Create new key</button>
                </div>

            </form>
        </div>
    </section>
</div>

<?php include 'seg/foot.php' ?>
<?php include 'seg/tracker.php' ?>
<?=Tpl::manifestScript('runtime.js')?>
<?=Tpl::manifestScript('common.vendor.js')?>
<?=Tpl::manifestScript('web.js')?>
<script src="https://www.google.com/recaptcha/api.js"></script>

</body>
</html>