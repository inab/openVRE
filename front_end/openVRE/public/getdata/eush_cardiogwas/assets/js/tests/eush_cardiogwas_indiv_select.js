$(document).ready(function() {
    var urlJSON = "applib/eush_cardiogwas.php";
    var phenoGene_data = "";
    $.ajax({
            async: false,
            type: 'GET',
            url: urlJSON,
            data: {'action': 'getPhenoGeneAssociations'}
    }).done(function(data) {
        phenoGene_data = data;
    });

    console.log(phenoGene_data)

    var phenotypeOptions = '';
    var geneOptions = '';
          
    phenotypeOptions += '<option value="">Select phenotype</option>';
    
    $.each(phenoGene_data, function(key, element){
        phenotypeOptions += '<option value="'+element.phenotype+'">'+element.phenotype+'</option>';
    });
    
    $('#phenotype').html(phenotypeOptions);

          
    $(document).on('change', '#phenotype', function(){
        var phenotype_id = $(this).val();
              if(phenotype_id != '')
              {
                geneOptions += '<option value="">Select a gene</option>';
                $.each(phenoGene_data, function(key, element){
                    if(phenotype_id == element.phenotype)
                    {
                        for (i = 0; i < element.associations.length; i++) { 
                            geneOptions += '<option value="'+element.associations[i].gene+'">'+element.associations[i].gene+' - pValue: '+element.associations[i].pValue+'</option>';
                          }    
                    }
                  });
                  $('#gene').html(geneOptions);
              }
              else
              {
                 $('#phenotype').html('<option value="">Select phenotype</option>');
                 $('#gene').html('<option value="">Select gene</option>');
              }
    });

    var DT = ""

    $('#add').on("click", function() {
        var value = {};
        $('select').each(function() {
          var arr = $(':selected', this).map(function() {
            return this.value;
          }).get();
          value[$(this).attr("name")] = arr;
        });

        var dictionary = ""
        $.get('getdata/eush_cardiogwas/assets/js/dictionary.json', function(data) {
            dictionary = JSON.parse(data)
        }, 'text');

        DT = $('#gwasTable').DataTable( {
            //'rowsGroup': [7,8],
            "ajax": {
                url: 'applib/eush_cardiogwas.php?action=getGeneVariants&gene='+value.gene[0]+'',
                dataSrc: function (jsonData) {
                    console.log(jsonData)
                    var variants = jsonData.variants
                    var table = []
                  
                    var mnemonic = ""
                    for (var x = 0; x < dictionary.length; x++) {
                        if(Object.keys(dictionary[x]) == value.gene[0]){
                            mnemonic = Object.values(dictionary[x])
                        }
                    }

                    var url_mmb = "https://mmb.irbbarcelona.org/api/uniprot/" + mnemonic + "/entry" 
                    //var url_mmb = "https://www.uniprot.org/uniprot/?query=mnemonic:MK13_HUMAN"
                    var pdb_codes = []
                    
                    $.ajax({
                        async: false,
                        type: 'GET',
                        url: url_mmb
                    }).done(function(data) {
                        if(data["dbxref"].hasOwnProperty("PDB")){
                            pdb_codes = data["dbxref"]["PDB"];
                        } else {
                            pdb_codes = "N/A"
                        }
                    });

                    for (var i = 0; i < variants.length; i++) {
                        // var_ID.
                        varId = variants[i]["variant"]
                        // Statistic values.
                        statistical = variants[i]["statistical"]
                        
                        var present = false
                        var pValue = null

                        for (n = 0; n < statistical.length; n++) { 
                            if(statistical[n]["phenotype"] == value.phenotype[0]){
                                present = true
                                pValue = statistical[n]["pValue"]
                                break
                            }
                        }

                        if(present){
                            // Predictions.
                            predictions = variants[i]["predictions"]

                            for (j = 0; j < predictions.length; j++) { 
                                if ('gene_id' in predictions[j]) {
                                    geneId = predictions[j]["gene_id"]
                                } else {
                                    geneId = "NA"
                                }
                                if ('transcript_id' in predictions[j]) {
                                    transcriptId = predictions[j]["transcript_id"]
                                } else {
                                    transcriptId = "NA"
                                }
                                if ('proteinChange' in predictions[j]) {
                                    proteinChange = predictions[j]["proteinChange"]
                                } else {
                                    proteinChange = "NA"
                                }
                                if ('dann_score' in predictions[j]) {
                                    dann_score = predictions[j]["dann_score"]
                                } else {
                                    dann_score = "NA"
                                }
                                if ('sift_score' in predictions[j]) {
                                    sift_score = predictions[j]["sift_score"]
                                } else {
                                    sift_score = "NA"
                                }
                                if ('polyphen_score' in predictions[j]) {
                                    polyphen_score = predictions[j]["polyphen_score"]
                                } else {
                                    polyphen_score = "NA"
                                }

                                obj = { "varId" : varId, 
                                        "geneId" : geneId, 
                                        "transcriptId" : transcriptId,
                                        "proteinChange" : proteinChange,
                                        "dann_score" : dann_score,
                                        "sift_score" : sift_score,
                                        "polyphen_score" : polyphen_score,
                                        "pValue" : pValue,
                                        "pdb_codes" : pdb_codes 
                                }
                                table.push(obj)
                            }    
                        }
                    }
                    return table; 
                }
            },
            autoWidth: false,
            "columns" : [
                { "data": null, defaultContent: '' },
                { "data" : "varId"},
                { "data" : "geneId" },
                { "data" : "transcriptId" },
                { "data" : "proteinChange"},
                { "data" : "dann_score" },
                { "data" : "sift_score" },
                { "data" : "polyphen_score" },
                { "data" : "pValue" },
                { "data" : "pdb_codes" }
            ],
            "columnDefs": [
                { 'orderable': false, 'targets': 0, 'className': 'select-checkbox' },
                { "title": "Missense variant", "className": "dt-center", "targets": 1 },
                { "title": "Gene ID", "className": "dt-center", "targets": 2 },
                { "title": "Transcript ID", "className": "dt-center", "targets": 3 },
                { "title": "Predicted Protein change", "className": "dt-center", "targets": 4 },
                { "title": "DANN score", "className": "dt-center", "targets": 5 },
                { "title": "SIFT score", "className": "dt-center", "targets": 6 },
                { "title": "Polyphen score", "className": "dt-center", "targets": 7 },
                { "title": "pValue (Variant-Phenotype)", "className": "dt-center", "targets": 8 },
                { "title": "Crystallographic structures (PDB)", "className": "dt-center", "targets": 9 }
            ],

            'select': {
                style: 'multi'
            },
            'order': [[1, 'asc']]
        }).on( 'select.dt deselect.dt',  function (evtObj) {
            var all = DT.rows().nodes().length;
            var sel = $.map(DT.rows(".selected").data(), function (item) {
                return item
            });
            console.log(sel);
        
        } );
          
    })

    $('#gwasTable').on('click', '.toggle-all', function() {
        $(this).closest("tr").toggleClass("selected");
        if($(this).closest("tr").hasClass("selected")){
            table.rows().select();
         }
         else {
             table.rows().deselect();
         }
        
    });  


    $("#tableReload").click(function() {
        location.reload(true);
});
});