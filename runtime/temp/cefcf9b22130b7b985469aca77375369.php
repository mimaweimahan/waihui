<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:81:"/www/wwwroot/admin.seven78.top/public/../application/admin/view/product/edit.html";i:1729216807;s:73:"/www/wwwroot/admin.seven78.top/application/admin/view/layout/default.html";i:1725591948;s:70:"/www/wwwroot/admin.seven78.top/application/admin/view/common/meta.html";i:1725591948;s:72:"/www/wwwroot/admin.seven78.top/application/admin/view/common/script.html";i:1725591948;}*/ ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
<title><?php echo (isset($title) && ($title !== '')?$title:''); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="renderer" content="webkit">
<meta name="referrer" content="never">
<meta name="robots" content="noindex, nofollow">

<link rel="shortcut icon" href="/assets/img/favicon.ico" />
<!-- Loading Bootstrap -->
<link href="/assets/css/backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.css?v=<?php echo \think\Config::get('site.version'); ?>" rel="stylesheet">

<?php if(\think\Config::get('fastadmin.adminskin')): ?>
<link href="/assets/css/skins/<?php echo \think\Config::get('fastadmin.adminskin'); ?>.css?v=<?php echo \think\Config::get('site.version'); ?>" rel="stylesheet">
<?php endif; ?>

<!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
<!--[if lt IE 9]>
  <script src="/assets/js/html5shiv.js"></script>
  <script src="/assets/js/respond.min.js"></script>
<![endif]-->
<script type="text/javascript">
    var require = {
        config:  <?php echo json_encode($config ?? ''); ?>
    };
</script>

    </head>

    <body class="inside-header inside-aside <?php echo defined('IS_DIALOG') && IS_DIALOG ? 'is-dialog' : ''; ?>">
        <div id="main" role="main">
            <div class="tab-content tab-addtabs">
                <div id="content">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <section class="content-header hide">
                                <h1>
                                    <?php echo __('Dashboard'); ?>
                                    <small><?php echo __('Control panel'); ?></small>
                                </h1>
                            </section>
                            <?php if(!IS_DIALOG && !\think\Config::get('fastadmin.multiplenav') && \think\Config::get('fastadmin.breadcrumb')): ?>
                            <!-- RIBBON -->
                            <div id="ribbon">
                                <ol class="breadcrumb pull-left">
                                    <?php if($auth->check('dashboard')): ?>
                                    <li><a href="dashboard" class="addtabsit"><i class="fa fa-dashboard"></i> <?php echo __('Dashboard'); ?></a></li>
                                    <?php endif; ?>
                                </ol>
                                <ol class="breadcrumb pull-right">
                                    <?php foreach($breadcrumb as $vo): ?>
                                    <li><a href="javascript:;" data-url="<?php echo $vo['url']; ?>"><?php echo $vo['title']; ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <!-- END RIBBON -->
                            <?php endif; ?>
                            <div class="content">
                                <form id="edit-form" class="form-horizontal" role="form" data-toggle="validator" method="POST" action="">

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Code'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-code" class="form-control" name="row[code]" type="text" value="<?php echo htmlentities($row['code'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Symbol'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-symbol" class="form-control" name="row[symbol]" type="text" value="<?php echo htmlentities($row['symbol'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Title'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-title" class="form-control" name="row[title]" type="text" value="<?php echo htmlentities($row['title'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Image'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <div class="input-group">
                <input id="c-image" class="form-control" size="50" name="row[image]" type="text" value="<?php echo htmlentities($row['image'] ?? ''); ?>">
                <div class="input-group-addon no-border no-padding">
                    <span><button type="button" id="faupload-image" class="btn btn-danger faupload" data-input-id="c-image" data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp,image/webp" data-multiple="false" data-preview-id="p-image"><i class="fa fa-upload"></i> <?php echo __('Upload'); ?></button></span>
                    <span><button type="button" id="fachoose-image" class="btn btn-primary fachoose" data-input-id="c-image" data-mimetype="image/*" data-multiple="false"><i class="fa fa-list"></i> <?php echo __('Choose'); ?></button></span>
                </div>
                <span class="msg-box n-right" for="c-image"></span>
            </div>
            <ul class="row list-inline faupload-preview" id="p-image"></ul>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Min'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-min" class="form-control" name="row[min]" type="number" value="<?php echo htmlentities($row['min'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Max'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-max" class="form-control" name="row[max]" type="number" value="<?php echo htmlentities($row['max'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Type'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-type" class="form-control selectpage" data-source="product_type/index" name="row[type]" type="text" value="<?php echo htmlentities($row['type'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Weight'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <input id="c-weight" min="0" class="form-control" name="row[weight]" type="number" value="<?php echo htmlentities($row['weight'] ?? ''); ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Play_rule'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <dl class="fieldlist" data-name="row[play_rule]" data-template="testtpl">
                <dd>
                    <ins>时间(秒)</ins>
                    <ins>赢赔比例(%)</ins>
                    <ins>亏赔比例(%)</ins>
                    <ins>最小下单金额</ins>
                    <ins>最大下单金额</ins>
                    <ins>操作</ins>
                </dd>
                <dd>
                    <a href="javascript:;" class="btn btn-sm btn-success btn-append"><i class="fa fa-plus"></i> <?php echo __('Append'); ?></a>
                </dd>
                <textarea id="c-play_rule" name="row[play_rule]" class="form-control hide" cols="30" rows="5"><?php echo htmlentities($row['play_rule'] ?? ''); ?></textarea>
            </dl>
            <!--定义模板，模板语法使用Art-Template模板语法-->
            <script type="text/html" id="testtpl">
                <dd class="form-inline">
                    <ins><input size="5" type="text" name="<%=name%>[<%=index%>][time]" class="form-control" value="<%=row['time']%>" size="10"></ins>
                    <ins><input size="5" type="text" name="<%=name%>[<%=index%>][win]" class="form-control" value="<%=row['win']%>" size="30"></ins>
                    <ins><input size="5" type="text" name="<%=name%>[<%=index%>][lose]" class="form-control" value="<%=row['lose']%>" size="30"></ins>
                    <ins><input size="5" type="text" name="<%=name%>[<%=index%>][min]" class="form-control" value="<%=row['min']%>" size="30"></ins>
                    <ins><input size="5" type="text" name="<%=name%>[<%=index%>][max]" class="form-control" value="<%=row['max']%>" size="30"></ins>
                    <span class="btn btn-sm btn-danger btn-remove"><i class="fa fa-times"></i></span>
                    <span class="btn btn-sm btn-primary btn-dragsort"><i class="fa fa-arrows"></i></span>
                </dd>
            </script>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Status'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            <div class="radio">
                <?php if(is_array($statusList) || $statusList instanceof \think\Collection || $statusList instanceof \think\Paginator): if( count($statusList)==0 ) : echo "" ;else: foreach($statusList as $key=>$vo): ?>
                <label for="row[status]-<?php echo $key; ?>"><input id="row[status]-<?php echo $key; ?>" name="row[status]" type="radio" value="<?php echo $key; ?>" <?php if(in_array(($key), is_array($row['status'])?$row['status']:explode(',',$row['status']))): ?>checked<?php endif; ?> /> <?php echo $vo; ?></label>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>
        </div>
    </div>

    <div class="form-group layer-footer">
        <label class="control-label col-xs-12 col-sm-2"></label>
        <div class="col-xs-12 col-sm-8">
            <button type="submit" class="btn btn-primary btn-embossed disabled"><?php echo __('OK'); ?></button>
        </div>
    </div>
</form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="/assets/js/require<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js" data-main="/assets/js/require-backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js?v=<?php echo htmlentities($site['version'] ?? ''); ?>"></script>
    </body>
</html>
