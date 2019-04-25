<?php

// use OEB idsolv to resolve a compacted URI
function resolve_curie_via_idsolv ($curi){
        $idsolv = $GLOBALS['OEB_idsolv'];
        return  "$idsolv/$curi";
}
