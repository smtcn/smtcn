<?php die('Access Denied'); ?>
{include('header.php')}
<div class="alert alert-success">
    The page you have requested could not be found.
</div>

<div id="div1" class="div1"></div>

<script type="text/javascript" src="/dist/src/wangEditor.min.js"></script>
<script type="text/javascript">
    const E = window.wangEditor;
    const editor = new E("#div1");
    // 限制类型
    editor.config.uploadImgAccept = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    // 一次最多上传 5 个图片
    editor.config.uploadImgMaxLength = 5;
    editor.config.showLinkImg = false;
    editor.config.uploadImgTimeout = 1000 * 5;
    editor.config.showLinkVideo = false;
    editor.config.uploadVideoTimeout =  1000 * 60 * 5;
    // base64 保存图片
    editor.config.uploadImgShowBase64 = true;
    // 配置 图片上传 接口地址
    // editor.config.uploadImgServer = '/upload-img';
    // 配置 视频上传 接口地址
    editor.config.uploadVideoServer = '/upload-video';
    editor.create();
</script>

{include('footer.php')}