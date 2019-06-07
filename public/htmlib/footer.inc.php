<div class="modal fade bs-modal" id="modalSessionExpired" tabindex="-1" role="basic" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Your session has expired!</h4>
            </div>
            <div class="modal-body table-responsive">
                <div class="row">
                    <div class="col-md-2" style="text-align:center;">
                        <i class="fa fa-clock-o font-green" aria-hidden="true" style="font-size: 50px;line-height: 45px;"></i>
                    </div>
                    <div class="col-md-10" id="session-text">
                        Your session has expired after N of inactivity, please log in again or keep using the VRE as a non-registered user.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($_SESSION["User"]["Type"] != 3) { ?>
                    <a class="btn green" href="<?php echo $GLOBALS['URL_login']; ?>">Log in</a>
                    <a class="btn green" href="<?php echo $GLOBALS['URL']; ?>">Non-registered</a>
                <?php } else { ?>
                    <a class="btn green" href="<?php echo $GLOBALS['URL'] . "?id=" . $_SESSION['User']['_id']; ?>">Continue</a>
                    <a class="btn green" href="<?php echo $GLOBALS['URL']; ?>">Start again</a>
                <?php }  ?>

            </div>
        </div>
    </div>
</div>

<div class="modal fade bs-modal" id="modalTerms" tabindex="-1" role="basic" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">Virtual research environment terms of use</h4>
            </div>
            <div class="modal-body table-responsive">
                <div class="container-terms" style="max-height: calc(100vh - 255px);"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="type-of-user" value="<?php echo $_SESSION['User']['Type']; ?>" />

<div class="modal fade bs-modal" id="modalLogoutGuest" tabindex="-1" role="basic" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">You are closing your session</h4>
            </div>
            <div class="modal-body table-responsive">
                Are you sure you want to close your session? As an anonymous user you won't be able to come back unless you save the restore link provided on the Workspace page.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <a class="btn btn-danger btn-ok">I'm aware</a>
            </div>
        </div>
    </div>
</div>

<div class="mt-cookie-consent-bar mt-cookie-consent-bar-light">
    <div class="mt-cookie-consent-bar-holder">
        <div class="mt-cookie-consent-bar-content"> This website uses cookies to ensure you get the best experience on our website.
            <a href="cookies/">Our Cookie Policy</a>
        </div>
        <div class="mt-cookie-consent-bar-action">
            <a href="javascript:;" class="mt-cookie-consent-btn btn green">Understand</a>
        </div>
    </div>
</div>


</div>
<!-- END CONTAINER -->
<!-- BEGIN FOOTER -->
<div class="page-footer">
    <div class="page-footer-inner"> &copy; <?php echo date("Y") . ' ' . $GLOBALS['NAME']; ?> :: <a href="javascript:openTermsOfUse();">Terms of Use</a></div>
    <div class="scroll-to-top">
        <i class="icon-arrow-up"></i>
    </div>
</div>
<!-- END FOOTER -->
</div>