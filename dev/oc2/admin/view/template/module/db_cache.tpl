<?php echo $header; ?>

<div id="content">

  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
    
<?php if ($error_warning) { ?>
<div class="warning"><?php echo $error_warning; ?></div>
<?php } ?>

  <div class="box">
    <div class="left"></div>
    <div class="right"></div>
    <div class="heading">
      <h1 style="background-image: url('view/image/feed.png') no-repeat;"><?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><span><?php echo $button_save; ?></span></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><span><?php echo $button_cancel; ?></span></a></div>
    </div>
    <div class="content">
      <div id="tabs" class="htabs">
        <a href="#tab-general"><?php echo $text_tab_general; ?></a>
      </div>


      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">

        <div id="tab-general">
          <table class="form">
            <tr>
              <td><?php echo $entry_cacheTimeoutSeconds; ?></td>
              <td><input name="db_cache_cacheTimeoutSeconds" type="text" value="<?php echo isset($db_cache_cacheTimeoutSeconds) ? $db_cache_cacheTimeoutSeconds : ''; ?>" />
              <?php if (isset($error['cacheTimeoutSeconds'])): ?>
                  <span style="color: red">$error['cacheTimeoutSeconds']</span>
            <?php endif;?>
              </td>
            </tr>

            <tr>
              <td><?php echo $entry_status; ?></td>
              <td><select name="db_cache_status">
                  <?php if ($db_cache_status) { ?>
                    <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                    <option value="0"><?php echo $text_disabled; ?></option>
                  <?php } else { ?>
                    <option value="1"><?php echo $text_enabled; ?></option>
                    <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                  <?php } ?>
                </select></td>
            </tr>
          </table>
        </div>

      </form>

    </div>

    <div style="text-align:center; opacity: .5">
      <p><a href="http://pnsols.com/en/Home/Products"><?php echo $text_homepage; ?></a></p>
    </div>
  </div>
</div>

<script type="text/javascript"><!--
$('#tabs a').tabs();
//--></script>


<?php echo $footer; ?>
