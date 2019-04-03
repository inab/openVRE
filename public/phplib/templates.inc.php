<?php

function getTemplate($fn, $idioma = False) {
    if ($idioma)
        $fn = $GLOBALS['idioma'] . '/' . $fn;
    return file_get_contents($GLOBALS['htmlib'] . '/' . $fn, FILE_TEXT);
}

function existTemplate($fn, $idioma = False) {
    if ($idioma)
        $fn = $GLOBALS['idioma'] . '/' . $fn;
    return file_exists($GLOBALS['htmlib'] . "/$fn");
}


function parseTemplate($f, $txt, $indirFields = '', $dateFields = '', $incRec = True, $recursive = False) {
    if ($incRec)
        //$txt = replaceLabel($txt);

    foreach (array_keys($f) as $k) {
        if ($f[$k])
            $txt = preg_replace("/%%$k%([^%]+)%%/", '\\1', $txt);
        else
            $txt = preg_replace("/%%$k%([^%]+)%%/", '', $txt);
    }

    if ($indirFields) {
        foreach (array_keys($indirFields) as $k) {
            $txt = str_replace("##$k##", $GLOBALS[$indirFields[$k]][$f[$k]], $txt);
        }
    }
    if ($dateFields) {
        foreach (array_values($dateFields) as $k) {
            $txt = str_replace("##$k##", prdata($_SESSION['idioma'], $f[$k]), $txt);
        }
    }
    foreach (array_keys($f) as $k){
        if (is_array($f[$k]))
            continue;
        $txt = str_replace("##$k##", $f[$k], $txt);
    }
    if (!$recursive)
        $txt = preg_replace("/##([^#]*)##/", "<!--\\1-->", $txt);
    return $txt;
}

?>
