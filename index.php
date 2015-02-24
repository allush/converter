<?php
session_start();
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>CSV2PDF</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <script src="js/jquery-2.1.3.min.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
    <br><br>

    <div class="row">
        <div class="col-md-8 col-md-offset-2 col-sm-8 col-sm-offset-2 col-xs-10 col-xs-offset-1">
            <?php
            $pdf = isset($_GET['filename']) ? $_GET['filename'] . '.pdf' : null;
            if ($pdf) { ?>
                <div class="panel panel-default">
                    <div class="panel-body text-center">
                        <?php if (file_exists($pdf)) { ?>
                            <a class="btn btn-primary" href="<?= $pdf; ?>">Download file</a>
                        <?php } else { ?>
                        <img src="img/ajax-loader.gif">
                            <script type="text/javascript">
                                $(function () {
                                    setTimeout(function () {
                                        location.reload()
                                    }, 5000);
                                });
                            </script>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <?php if ($error) { ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <?= $error; ?>
                </div>
            <?php } ?>
            <div class="well well-lg">
                <form action="convert.php" method="post" enctype="multipart/form-data" class="form-horizontal">
                    <div class="form-group">
                        <div class="col-md-5 col-sm-5 col-md-offset-3 col-sm-offset-3">
                            <input type="text" name="name" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="csv" class="col-md-3 col-sm-3 control-label">CSV file</label>

                        <div class="col-md-5 col-sm-5 ">
                            <input type="file" name="csv" id="csv" class="form-control" required="required"
                                   accept="text/csv">
                        </div>

                        <button type="submit" class="btn btn-primary glyphicon glyphicon-refresh" title="Convert"
                                name="convert"></button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
</body>
</html>


