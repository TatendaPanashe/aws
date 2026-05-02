<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title><?php echo $__env->yieldContent('title', 'GRUMA Workspace'); ?></title>
    <link rel="icon" href="<?php echo e(asset('assets/img/gruma.png')); ?>" type="image/x-icon">
    <meta content="GRUMA operations and collections workspace" name="description">
    <meta content="gruma, collections, insurance, dashboard" name="keywords">
    <meta name="theme-color" content="#102d36">

    <link href="<?php echo e(asset('assets/img/favicon.png')); ?>" rel="icon">
    <link href="<?php echo e(asset('assets/img/apple-touch-icon.png')); ?>" rel="apple-touch-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="<?php echo e(asset('assets/vendor/bootstrap/css/bootstrap.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/vendor/bootstrap-icons/bootstrap-icons.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/vendor/boxicons/css/boxicons.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/vendor/quill/quill.snow.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/vendor/quill/quill.bubble.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/vendor/remixicon/remixicon.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/vendor/simple-datatables/style.css')); ?>" rel="stylesheet">

    <link href="<?php echo e(asset('assets/css/style.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/css/gruma-ui.css')); ?>" rel="stylesheet">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>

<body class="app-body">

    <main id="main" class="main main-shell">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="<?php echo e(asset('assets/vendor/apexcharts/apexcharts.min.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/chart.js/chart.umd.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/echarts/echarts.min.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/quill/quill.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/simple-datatables/simple-datatables.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/tinymce/tinymce.min.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/vendor/php-email-form/validate.js')); ?>"></script>


    <script src="<?php echo e(asset('assets/js/jquery.min.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/js/main.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/js/gruma-ui.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/js/chart.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/js/xlsx.full.min.js')); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>

</body>

</html>
<?php /**PATH /Users/macbookair/Documents/Projects/app/resources/views/layouts/main.blade.php ENDPATH**/ ?>