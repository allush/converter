<?php
include 'functions.php';

$groups = array(
    'DIGITAL',
    'PRESS',
);

try {
    include 'load.php';

    $file = fopen($_FILES['csv']['tmp_name'], 'r+');
    if (!$file) {
        throw new Exception('Could not open the uploaded file.');
    }

    $d = fread($file, filesize($_FILES['csv']['tmp_name']));
    if (strpos($d, "\r") !== false) {
        $d = str_replace("\r", "\n", $d);
        fseek($file, 0);
        $a = fwrite($file, $d);
        fclose($file);
        $file = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$file) {
            throw new Exception('Could not open the converted file.');
        }
    } else {
        fseek($file, 0);
    }

    $data = array();
    $i = 0;
    $passedRowCount = 0;
    $unrecognizedRows = array();
    $n = 0;
    while ($row = fgetcsv($file)) {
        $name = null;
        $size = null;
        $type = null;
        $color = null;
        $n++;

        if ($row[0] == 'product_title') {
            $passedRowCount++;
            $unrecognizedRows[] = implode(', ', array_merge(array($n), $row));
            continue;
        }

        $name = tryGetName($row[0]);

        $complexColumn = explode('/', $row[1]);
        foreach ($complexColumn as $complexItem) {
            $complexItem = strtolower(trim($complexItem));

            if ($size === null) {
                $size = tryGetSize($complexItem);
                if ($size !== null) {
                    continue;
                }
            }

            if ($type === null) {
                $type = tryGetType($complexItem);
                if ($type !== null) {
                    continue;
                }
            }

            if ($color === null) {
                $color = tryGetColor($complexItem);
                if ($color !== null) {
                    continue;
                }
            }
        }

        // попытка найти тип в названии
        if ($type === null) {
            foreach ($availableTypes as $availableType) {
                if (stripos($name, $availableType) !== false) {
                    $type = $availableType;
                    break;
                }
            }
            if (!$type) {
                $type = 'T-Shirt';
            }
        }

        if ($color === null) {
            $color = 'black';
        }

        if (!$name or !$type or !$color or !$size) {
            $passedRowCount++;
            $unrecognizedRows[] = implode(', ', array_merge(array($n), $row));
            continue;
        }

        $data[$i]['name'] = $name;
        $data[$i]['size'] = $size;
        $data[$i]['type'] = $type;
        $data[$i]['color'] = $color;
        $data[$i]['quantity'] = trim($row[3]);
        $data[$i]['price'] = trim($row[4]);
        $i++;
    }
    $rowCount = count($data);

    $sizes = array();
    $dataByName = array();
    foreach ($data as $row) {
        $sizes[] = $row['size'];
        if (!isset($dataByName[$row['name']][$row['type']][$row['color']][$row['size']])) {
            $dataByName[$row['name']][$row['type']][$row['color']][$row['size']] = 0;
        }
        $dataByName[$row['name']][$row['type']][$row['color']][$row['size']] += (int)$row['quantity'];
    }

    $productCount = 0;
    $dataByType = array();
    foreach ($data as $row) {
        if (!isset($dataByType[$row['type']][$row['color']][$row['size']])) {
            $dataByType[$row['type']][$row['color']][$row['size']] = 0;
        }
        $dataByType[$row['type']][$row['color']][$row['size']] += (int)$row['quantity'];
        $productCount += (int)$row['quantity'];
    }

    unset($data);

    $sizes = array_unique($sizes);
    usort($sizes, 'cmpSizes');

    $header .= '<br><table class="table">';
    $header .= '<tr>';
    $header .= '<td class="bold" style="width: 30%;">Product</td>';
    foreach ($sizes as $size) {
        $header .= '<td class="bold">' . strtoupper($size) . '</td>';
    }
    $header .= '<td class="bold">total</td>';
    $header .= '</tr>';
    $header .= '</table>';

    $headHtml = '<div id="logo">
                <img src="img/logo.jpg">
            </div>
            <div>' . (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '') . '</div>
            <div id="date">Report: ' . date('m/d/Y H:i', time()) . '</div>
            <div id="summary">Number of products: ' . $productCount . '</div>
            <div id="summary">Number of erroneous lines: ' . $passedRowCount . '</div>';

    $htmlByType = '';
    foreach ($dataByType as $type => $row) {
        $htmlByType .= '<br><table class="table">';

        $htmlByType .= '<tr>';
        $htmlByType .= '<td class="product-name" colspan="' . count($sizes) . '">' . $type . '</td>';
        $htmlByType .= '</tr>';

        $htmlByType .= '<tr>';
        $htmlByType .= '<td class="bold" style="width: 30%;"></td>';
        foreach ($sizes as $size) {
            $htmlByType .= '<td class="bold">' . strtoupper($size) . '</td>';
        }
        $htmlByType .= '<td class="bold">total</td>';
        $htmlByType .= '</tr>';

        $colors = array_keys($row);
        $totalColumn = 0;
        foreach ($colors as $color) {
            $htmlByType .= '<tr>';
            $htmlByType .= '<td class="product-color">' . $color . '</td>';
            $total = 0;
            foreach ($sizes as $size) {
                $quantity = isset($dataByType[$type][$color][$size]) ? $dataByType[$type][$color][$size] : 0;
                $total += (int)$quantity;
                $htmlByType .= '<td>' . $quantity . '</td>';
            }
            $totalColumn += $total;
            $htmlByType .= '<td class="bold">' . $total . '</td>';
            $htmlByType .= '</tr>';
        }
        $htmlByType .= '<tr class="no-border">';
        $htmlByType .= '<td colspan="' . (count($sizes) + 1) . '"></td>';
        $htmlByType .= '<td class="bold">' . $totalColumn . '</td>';
        $htmlByType .= '</tr>';

        $htmlByType .= '</table>';
    }

    $htmlByName .= '<br><table class="table">';
    $htmlByName .= '<tr>';
    $htmlByName .= '<td class="bold" style="width: 30%;">Product</td>';
    foreach ($sizes as $size) {
        $htmlByName .= '<td class="bold">' . strtoupper($size) . '</td>';
    }
    $htmlByName .= '<td class="bold">total</td>';
    $htmlByName .= '</tr>';

    foreach ($dataByName as $name => $row) {
        $htmlByName .= '<tr>';
        $htmlByName .= '<td class="product-name" colspan="' . count($sizes) . '">' . $name . '</td>';
        $htmlByName .= '</tr>';
        $types = array_keys($row);
        foreach ($types as $type) {
            $htmlByName .= '<tr>';
            $htmlByName .= '<td class="product-type" colspan="' . count($sizes) . '">' . $type . '</td>';
            $htmlByName .= '</tr>';

            $totalColumn = 0;
            $colors = array_keys($dataByName[$name][$type]);
            foreach ($colors as $color) {
                $htmlByName .= '<tr>';
                $htmlByName .= '<td class="product-color">' . $color . '</td>';
                $total = 0;
                foreach ($sizes as $size) {
                    $quantity = isset($dataByName[$name][$type][$color][$size]) ? $dataByName[$name][$type][$color][$size] : '0';
                    $total += (int)$quantity;
                    $htmlByName .= '<td>' . $quantity . '</td>';
                }
                $totalColumn += $total;
                $htmlByName .= '<td class="bold">' . $total . '</td>';
                $htmlByName .= '</tr>';
            }
            $htmlByName .= '<tr class="no-border">';
            $htmlByName .= '<td colspan="' . (count($sizes) + 1) . '"></td>';
            $htmlByName .= '<td class="bold">' . $totalColumn . '</td>';
            $htmlByName .= '</tr>';
        }
    }
    $htmlByName .= '</table>';

    $unrecognizedHtml = null;
    if (count($unrecognizedRows) > 0) {
        $unrecognizedHtml = '<h3>Unrecognized rows:</h3>';
        $unrecognizedHtml .= '<table class="table">';
        foreach ($unrecognizedRows as $unrecognizedRow) {
            $unrecognizedHtml .= '<tr>';
            $unrecognizedHtml .= '<td>' . $unrecognizedRow . '</td>';
            $unrecognizedHtml .= '</tr>';
        }
        $unrecognizedHtml .= '</table>';
    }


    $log = 'log-' . date('d-m-Y_H-m-s', time()) . '.txt';
    writeLog($log, 'Number of imported rows: ' . $rowCount);
    writeLog($log, 'Written to PDF: ' . $rowCount);
    writeLog($log, 'Number of erroneous lines: ' . $passedRowCount);


    require 'mpdf/mpdf.php';

    $mpdf = new mPDF('', 'A4', '', '', 8, 8, 12, 8, 0, 0);

    $css = file_get_contents('css/pdf.css');
    $mpdf->WriteHTML($css, 1);

    $mpdf->AddPage();
    $mpdf->WriteHTML($headHtml, 2);
    $mpdf->WriteHTML($htmlByType, 2);

    $mpdf->AddPage();
    $mpdf->SetHTMLHeader($header);
    $mpdf->WriteHTML($htmlByName, 2);

    if ($unrecognizedHtml) {
        $mpdf->SetHTMLHeader('');
        $mpdf->AddPage();
        $mpdf->WriteHTML($unrecognizedHtml, 2);
    }

    $filename = $_FILES['csv']['name'] . '.pdf';
    $mpdf->Output($filename, 'D');
} catch (Exception $e) {
    session_start();
    $_SESSION['error'] = '<strong>Error!</strong> ' . $e->getMessage();

    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
?>