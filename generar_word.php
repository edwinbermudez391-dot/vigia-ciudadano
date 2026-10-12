<?php

/**
 * Script para generar archivo Word desde el HTML de documentación
 * Word puede abrir archivos HTML guardados con extensión .doc
 */

// Ruta del archivo HTML fuente
$htmlFile = __DIR__ . '/documentacion_vigia_ciudadano.html';
$docFile = __DIR__ . '/documentacion_vigia_ciudadano.doc';

// Verificar que existe el archivo HTML
if (!file_exists($htmlFile)) {
    die("Error: No se encontró el archivo HTML fuente en: $htmlFile\n");
}

// Leer contenido HTML
$htmlContent = file_get_contents($htmlFile);

// Reemplazar la fecha dinámica si existe
$htmlContent = str_replace('<?= date(\'d/m/Y\') ?>', date('d/m/Y'), $htmlContent);
$htmlContent = str_replace('<?= date(\'Y\') ?>', date('Y'), $htmlContent);

// Agregar metadatos de Word al inicio del HTML
$wordHeader = <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml>
<w:WordDocument>
<w:View>Print</w:View>
<w:Zoom>100</w:Zoom>
<w:DoNotOptimizeForBrowser/>
</w:WordDocument>
</xml>
<![endif]-->
<style>
@page {
    size: A4;
    margin: 2.5cm;
}
body {
    font-family: 'Calibri', sans-serif;
    font-size: 11pt;
    line-height: 1.5;
}
h1 {
    font-size: 24pt;
    color: #1e40af;
    border-bottom: 3px solid #1e40af;
    page-break-before: always;
}
h2 {
    font-size: 18pt;
    color: #2563eb;
    border-bottom: 2px solid #2563eb;
    page-break-before: always;
}
h3 {
    font-size: 14pt;
    color: #3b82f6;
}
h4 {
    font-size: 12pt;
    color: #60a5fa;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin: 10pt 0;
}
th {
    background-color: #2563eb;
    color: white;
    padding: 8pt;
    text-align: left;
    font-weight: bold;
}
td {
    border: 1pt solid #cbd5e1;
    padding: 6pt;
}
.role-box {
    background-color: #eff6ff;
    border-left: 4pt solid #2563eb;
    padding: 10pt;
    margin: 10pt 0;
}
.highlight {
    background-color: #fef3c7;
    border-left: 4pt solid #f59e0b;
    padding: 10pt;
    margin: 10pt 0;
}
code {
    background-color: #f8fafc;
    padding: 2pt 4pt;
    font-family: 'Consolas', monospace;
    font-size: 10pt;
}
ul, ol {
    margin: 8pt 0;
    padding-left: 20pt;
}
li {
    margin: 4pt 0;
}
</style>
</head>
<body>
HTML;

// Extraer solo el contenido del body del HTML original
if (preg_match('/<body>(.*?)<\/body>/is', $htmlContent, $matches)) {
    $bodyContent = $matches[1];
} else {
    $bodyContent = $htmlContent;
}

// Combinar header con contenido
$wordContent = $wordHeader . $bodyContent . '</body></html>';

// Guardar como archivo .doc
$result = file_put_contents($docFile, $wordContent);

if ($result === false) {
    die("Error: No se pudo crear el archivo Word en: $docFile\n");
}

echo "✓ Archivo Word creado exitosamente: $docFile\n";
echo "✓ Tamaño: " . number_format(filesize($docFile)) . " bytes\n";
echo "✓ Puedes abrir este archivo directamente con Microsoft Word\n";
