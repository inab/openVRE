$(document).ready(function() {
    console.log("hi there")
    // GET geneName, geneProteinVars

    getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
    
            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
    };

    geneName= getUrlParameter('geneName');
    geneProtein = getUrlParameter('geneProteinVars');

    console.log(geneName)
    console.log(geneProtein)

    // LOAD DICTIONARY GENE_NAME -> MNEMONIC (dictionary.json)

    // GET PDBs mmb -> Dispatcher: getPDBs($var, $var2)

    $("#tableReload").click(function() {
        location.reload(true);
    });
});