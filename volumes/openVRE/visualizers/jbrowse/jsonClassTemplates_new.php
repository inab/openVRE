<?php

//require "../../phplib/genlibraries.php";

//redirectOutside();

function attribute_help ($type,$name){
		$attr = $type . "_" . $name;
        $descri = exec("grep $attr metadata_attribute_help.txt | cut -f2");
        return "$name <span style='color:grey;border:1px solid grey;font-size:10px;border-radius: 8px;padding: 0px 3px;vertical-align:top' title='$descri' >?</span>";
}


class Base {

        public $key = "";
        public $label  = "";
        public $track = "";
        public $urlTemplate = "";
	    public $metadata = "";

        public function __construct($type,$label,$path,$file){
	        $this->key= "<span title='Track from run: ".$path."'>" . $file . "</span>";
	        $this->label= $label;
        	$this->track= $type . "_" . $file;
        	$this->urlTemplate= "../" . $path . "/" . $file;
            $this->metadata = array("category" => "Your Data / Projects / $path");
	}
}


class Alignment extends Base{

   	public $style= array("className" => "bam");
  	public $storeClass = "JBrowse/Store/SeqFeature/BAM";
	public $description = "Alignment Reads";
	public $feature = array("bamf");
    public $type = "JBrowse/View/Track/Alignments2";

	public function __construct($label,$path,$file){
		parent::__construct("Alignment",$label."_bam",$path,$file);
###PROVA!!!
        $this->chunkSizeLimit = "10000000"; 
        $this->key = $this->key . " (Reads)";
	}
}


class AlignmentCoverage extends Base{

	public $storeClass = "JBrowse/Store/SeqFeature/BAM";
	public $type = "JBrowse/View/Track/SNPCoverage";
	public $description = "Alignment Coverage";
	
    public function __construct($label,$path,$file){
        parent::__construct("BAM_Coverage",$label,$path,$file);
        $this->key= $this->key . " (Coverage)";
        }
}


class Coverage extends Base{

	public $storeClass = "JBrowse/Store/SeqFeature/BigWig";
    public $type = "JBrowse/View/Track/Wiggle/XYPlot";
	public $max_score = 400;
    public $style= array("pos_color" => "purple","neg_color" => "green");

        public function __construct($label,$path,$file){
                $this->metadata = array("category" => "Your Data / Projects / $path", "Description" => "Coverage from bigwig");
                parent::__construct("Coverage",$label,$path,$file);
        
        }
}

class BW extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/BigWig";
        public $type = "JBrowse/View/Track/Wiggle/XYPlot";
 	public $autoscale = "local";
	public $style = array("height" => "60");

        public function __construct($label,$path,$file){
                parent::__construct("BigWig",$label,$path,$file);

        }


}


class BW_P extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/BigWig";
        public $type = "JBrowse/View/Track/Wiggle/XYPlot";
        public $autoscale = "local";

        public $style = array("height" => "60","pos_color" => "#D8D8D8","neg_color" => "#D8D8D8");

        public function __construct($label,$path,$file){
                parent::__construct("BigWig",$label,$path,$file);
                $this->metadata = array("category" => "Your Data / Projects / $path", "description" => "This track represents a theoretical periodic nucleosome coverage prediction, taking into account the location of the first and last nucleosomes in a gene.");
        }

}


class BW_ND extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/BigWig";
        public $type = "JBrowse/View/Track/Wiggle/XYPlot";
        public $autoscale = "local";

        public $style = array("height" => "60","pos_color" => "#D8D8D8","neg_color" => "#D8D8D8");

        public function __construct($label,$path,$file){
                parent::__construct("BigWig",$label,$path,$file);
                $this->metadata = array("category" => "Your Data / Projects / $path", "description" => "The bigWig file contains the -log10 of the p-value of how significant is the difference between both experiments (<a href=\"https://vre.multiscalegenomics.eu/tools/nucldynwf/help/method.php#nd\" target='_blank'>see methods</a>).");
        }

}

class GFF extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/GFF3";
        public $type = "JBrowse/View/Track/CanvasFeatures";
        public $style = array("className" => "feature");

        public function __construct($label,$path,$file){
                parent::__construct("GFF",$label,$path,$file);
	}

}


class BED extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/BED";
        public $type = "JBrowse/View/Track/CanvasFeatures";
        public $style = array("className" => "feature");

        public function __construct($label,$path,$file){
                parent::__construct("BED",$label,$path,$file);
        }

}


# GFF per tx_class
class GFF_TX extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/GFF3";
        public $type = "JBrowse/View/Track/CanvasFeatures";
        public $style = array("className" => "feature", "color" => "function( feature, variableName, glyphObject, track ) {switch (feature.get('classification')) { case '+1_missing': return '#A9F5E1'; case '+1_too_fuzzy': return '#F6CEF5'; case '-1_missing': return '#CEECF5'; case 'F-close-F': return '#F5F6CE'; case 'F-close-W': return '#F6D8CE'; case 'F-open-F': return '#FAAC58'; case 'F-open-W': return '#F4FA58'; case 'F-overlap-F': return '#ACFA58'; case 'F-overlap-W': return '#58FA58'; case 'NA': return '#58FAAC'; case 'W-close-F': return '#58FAF4'; case 'W-close-W': return '#58ACFA'; case 'W-open-F': return '#5858FA'; case 'W-open-W': return '#AC58FA'; case 'W-overlap-F': return '#FA58F4'; case 'W-overlap-W': return '#FA5882';}}");

        public $fmtDetailField_seq_id = "function(seq_id) { return null; }";
        public $fmtDetailField_id = "function(id) { return null; }";
        public $fmtDetailField_Length = "function(Length) { return null; }";


        public $fmtDetailValue_Name = "function(name) { var patt=/_/; if (!patt.test(name)) {var str=name.split(\" \"); return '<a href=\"http://www.yeastgenome.org/locus/'+str[0]+'\" target=\"_blank\">'+name+'</a>';} else {return name;}}";
//        public $fmtDetailValue_gene_id = "function(name) { var patt=/_/; if (!patt.test(name)) { return '<a href=\"http://www.yeastgenome.org/locus'+name+'\"  target=\"_blank\">'+name+'</a>';} else {return name;}}";


        public function __construct($label,$path,$file){
                parent::__construct("GFF",$label,$path,$file);
                $this->metadata = array("category" => "Your Data / Projects / $path", "type" => "TSS nucleosome classification", "description" => "Classification of The Transcription Start Sites (TSS) according to the nucleosome architecture. They are classified based on the width of the NFRs (closed (c), open (o) or overlapping(overlap))<br/> and on the fuzziness of the -1 and + 1  nucleosomes (missed (M), fuzzy (F) or well-positioned (W)).", "Details" => "A Transcription Start Site can be classified as any of the following:<br/>
<span style=' background: #A9F5E1;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> +1_missing</b><br/>
<span style=' background: #F6CEF5;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> +1_too_fuzzy</b><br/>
<span style=' background: #CEECF5;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> -1_missing</b><br/>
<span style=' background: #F5F6CE;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> F-close-F</b><br/>
<span style=' background: #F6D8CE;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> F-close-W</b><br/>
<span style=' background: #FAAC58;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> F-open-F</b><br/>
<span style=' background: #F4FA58;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> F-open-W</b><br/>
<span style=' background: #ACFA58;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> F-overlap-F</b><br/>
<span style=' background: #58FA58;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> F-overlap-W</b><br/>
<span style=' background: #58FAF4;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> W-close-F</b><br/>
<span style=' background: #58ACFA;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> W-close-W</b><br/>
<span style=' background: #5858FA;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> W-open-F</b><br/>
<span style=' background: #AC58FA;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> W-open-W</b><br/>
<span style=' background: #FA58F4;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> W-overlap-F</b><br/>
<span style=' background: #FA5882;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> W-overlap-W</b><br/>");

		$this->fmtDetailField_classification = "function () { return \"".attribute_help("TSS","classification")."\"; }";
		$this->fmtDetailField_distance = "function () { return \"".attribute_help("TSS","distance")."\"; }";
//		$this->fmtDetailField_gene_id = "function () { return \"".attribute_help("TSS","gene_id")."\"; }";
		$this->fmtDetailField_nucleosome_minus1 = "function () { return \"".attribute_help("TSS","nucleosome_minus1")."\"; }";
		$this->fmtDetailField_nucleosome_plus1 = "function () { return \"".attribute_help("TSS","nucleosome_plus1")."\"; }";
		$this->fmtDetailField_tss_position = "function () { return \"".attribute_help("TSS","TSS_position")."\"; }";        
		$this->fmtDetailField_Position = "function () { return \"".attribute_help("TSS","Position")."\"; }";

        }
}


# GFF per peridiocity
class GFF_P extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/GFF3";
        public $type = "JBrowse/View/Track/CanvasFeatures";
        public $style = array("className" => "feature", "color" => "function( feature, variableName, glyphObject, track ) { if (feature.get('score_phase') <= 25 ) { return 'green';} else if (feature.get('score_phase') <= 55) {return 'orange';} else {return 'red';}}");

#       public $style = array("className" => "feature", "color" => "function( feature, variableName, glyphObject, track ) { if (feature.get('score') < 20 ) { return '#CEF6CE';} else if (feature.get('score') < 40) {return '#81F781'} else if (feature.get('score') < 60) {return '#04B404'} else {return '#0B6121'}}");
        public $displayMode = "compact";

        public $fmtDetailField_seq_id = "function(seq_id) { return null; }";
        public $fmtDetailField_id = "function(id) { return null; }";

        public $fmtDetailValue_Name = "function(name) { var patt=/_/; if (!patt.test(name)) {var str=name.split(\" \");return '<a href=\"http://www.yeastgenome.org/locus/'+str[0]+'\" target=\"_blank\">'+name+'</a>';} else {return name;}}";


        public function __construct($label,$path,$file){
                parent::__construct("GFF",$label,$path,$file);
                $this->metadata = array("category" => "Your Data / Projects / $path", "type" => "Phasing","description" => "Nucleosome phasing along a given gene between the first and last nucleosome", "Details" => "<b>Score phase:</b> remainder of the division of the distance between first and last nucleosomes by the period specified by the user. Values between 0 and 82. Number of nucleotides left after dividing the distance between the first and the last nucleosome by the period. A score phase of 0 means the nucleosomes are completeley phased and a score of 82 corresponds to totally anti-phased nucleosomes. Depending on the score phase, the tracks have the following colors:<br/>
<span style='background: green;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> Score [0-25]<br/><span style='background: orange'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> Score [26-55]<br/><span style='background: red'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> Score [56-82]<br/>
<b>Score autocorrelation:</b>  autocorrelation function computed from the experimental coverage of a given gene using the period given by the user. Values are between 0 (nonperiodic) and 1 (periodic).");
                $this->fmtDetailField_nucleosome_first = "function () { return \"".attribute_help("P","nucleosome_first")."\"; }";
                $this->fmtDetailField_nucleosome_last = "function () { return \"".attribute_help("P","nucleosome_last")."\"; }";
                $this->fmtDetailField_score_phase = "function () { return \"".attribute_help("P","score_phase")."\"; }";
//              $this->fmtDetailField_nucleosome = "function () { return \"".attribute_help("P","nucleosome")."\"; }";
                $this->fmtDetailField_score_autocorrelation = "function () { return \"".attribute_help("P","score_autocorrelation")."\"; }";
        }
}


/*
# GFF per peridiocity
class GFF_P extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/GFF3";
        public $type = "JBrowse/View/Track/CanvasFeatures";
        public $metadata = "";
      //  public $style = array("className" => "function( feature, variableName, glyphObject, track ) { if (feature.get('score_phase') <= 25 ) { return 'feature_green';} else if (feature.get('score_phase') <= 55) {return 'feature_yellow';} else {return 'feature_red';}}");

	public $style = array("className" => "feature", "color" => "function( feature, variableName, glyphObject, track ) { if (feature.get('score') < 20 ) { return '#CEF6CE';} else if (feature.get('score') < 40) {return '#81F781'} else if (feature.get('score') < 60) {return '#04B404'} else {return '#0B6121'}}");

	public $fmtDetailField_seq_id = "function(seq_id) { return null; }";
        public $fmtDetailField_id = "function(id) { return null; }";

        public $fmtDetailValue_Name = "function(name) { var patt=/_/; if (!patt.test(name)) { return '<a href=\"http://www.yeastgenome.org/locus/'+name+'\" target=\"_blank\">'+name+'</a>';} else {return name;}}";

	public $displayMode = "compact";

        public function __construct($label,$path,$file){
                parent::__construct("GFF",$label,$path,$file);
                $this->metadata = array("category" => "Your Data / Projects / $path", "type" => "Periodicity","description" => "Periodicity in nucleosome positioning along the gene.", "Details" => "<b>Score phase</b> is a measure of the phase between the first and the last nucleosomes.  <br/>Specifically it is computed as the residue of the nuc.length divided by the period (165 bp).  Therefore, a score phase of 0 means the nucleosomes are completeley phased and a score of 82 corresponds to totally anti-phased nucleosomes. Depending on the score phase, the tracks have the following colors:<br/><span style='background: green;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> Score [0-25]<br/><span style='background: orange'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> Score [26-55]<br/><span style='background: red'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> Score [56-82]<br/>");
		$this->fmtDetailField_nucleosome_first = "function () { return \"".attribute_help("P","nucleosome_first")."\"; }";
		$this->fmtDetailField_nucleosome_last = "function () { return \"".attribute_help("P","nucleosome_last")."\"; }";
		$this->fmtDetailField_score_phase = "function () { return \"".attribute_help("P","score_phase")."\"; }";
//		$this->fmtDetailField_nucleosome = "function () { return \"".attribute_help("P","nucleosome")."\"; }";
		$this->fmtDetailField_score_autocorrelation = "function () { return \"".attribute_help("P","score_autocorrelation")."\"; }";
        }
}
*/

# GFF per NFR
class GFF_NFR extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/GFF3";
        public $type = "JBrowse/View/Track/CanvasFeatures";
        public $style = array("className" => "feature", "color" => "orange");
        public $displayMode = "collapsed";

        public $fmtDetailField_seq_id = "function(seq_id) { return null; }";


        public function __construct($label,$path,$file){
                parent::__construct("GFF",$label,$path,$file);
                $this->metadata = array("category" => "Your Data / Projects / $path", "description" => "Nucleosome-free regions (NFR) are regions larger than an average linker fragment that are depleted of nucleosomes.");

        }
}

# GFF per GAU
class GFF_GAU extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/GFF3";
        public $type = "JBrowse/View/Track/CanvasFeatures";

		public $style = array("className" => "feature", "color" => "function( feature, variableName, glyphObject, track ) { if (feature.get('score') < 0.1 ) { return '#CEF6F5';} else if (feature.get('score') < 0.2) {return '#81F7F3';} else if (feature.get('score') < 0.3) {return '#2EFEF7';} else if (feature.get('score') < 0.4) {return '#01DFD7';} else {return '#04B4AE';}}");


        public $displayMode = "compact";

        public $fmtDetailField_seq_id = "function(seq_id) { return null; }";

        public function __construct($label,$path,$file){
                parent::__construct("GFF",$label,$path,$file);
                $this->metadata = array("category" => "Your Data / Projects / $path", "description" => "Measures the resistance of a given nucleosome to be displaced. The stiffness is derived from the properties of the nucleosome calls fitted into a Gaussian distribution.", "Details" => "The resulting Gaussian distribution is defined by three parameters: m, sd, and k: <br><b>k</b> is the height of the curve's peak.<br/><b>m</b> is the position of the center of the peak.<br/><b>sd</b> is it's standard deviation and controls the width of the bell.<br/><b>stiffness</b> for the given nucleosome derived from the <b>sd</b> of the gaussian function. Color code:<br/><span style='background: #CEF6F5;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> 0 - 0.1<br/><span style='background: #81F7F3;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> 0.1 - 0.2<br/><span style='background: #2EFEF7;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> 0.2 - 0.3<br/><span style='background: #01DFD7'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> 0.3 - 0.4<br/><span style='background: #04B4AE;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> > 0.4");

		$this->fmtDetailField_Score = "function () { return \"".attribute_help("STF","Score")."\"; }";
		$this->fmtDetailField_nucler_class = "function () { return \"".attribute_help("STF","nucleR_class")."\"; }";
		$this->fmtDetailField_gauss_k = "function () { return \"".attribute_help("STF","gauss_k")."\"; }";
		$this->fmtDetailField_gauss_m = "function () { return \"".attribute_help("STF","gauss_m")."\"; }";
		$this->fmtDetailField_gauss_sd = "function () { return \"".attribute_help("STF","gauss_sd")."\"; }";
		$this->fmtDetailField_nucleR_score = "function () { return \"".attribute_help("STF","nucleR_score")."\"; }";
        }
}



# GFF per nucleR
class GFF_NR extends Base {

        public $storeClass = "JBrowse/Store/SeqFeature/GFF3";
        public $type = "JBrowse/View/Track/CanvasFeatures";
        public $style = array("className" => "feature", "color" => "function( feature, variableName, glyphObject, track ) { if (feature.get('class') == 'W') { return 'blue'; } else if (feature.get('class') == 'F' ) {return '#819FF7'} else { return 'grey'; }}");
	public $displayMode = "compact";

        public $fmtDetailField_seq_id = "function(seq_id) { return null; }";
#        public $fmtDetailField_class = "function(class) { return \"<a title='descri class'>class</a>\"; }";
//        public $fmtDetailField_nmerge = "function(nmerge) { return 'nmerge <img src=\"img/question.png\" title=\"descripcio score_h\" height=\"12px\"/>';}";
//        public $fmtDetailField_score_h = "function(score_h) { return 'score_h <span style=\"color:grey;border:1px solid grey;font-size:10px;border-radius: 8px;padding: 0px 3px;vertical-align:top\" title=\"descripcio Score\" >?</span>';}";
//        public $fmtDetailField_Score = "function(Score) { return 'Score <span style=\"color:grey;border:1px solid grey;font-size:10px;border-radius: 8px;padding: 0px 3px;vertical-align:top\" title=\"descripcio Score\" >?</span>';}";

        public function __construct($label,$path,$file){
                parent::__construct("GFF",$label,$path,$file);
		$this->metadata = array("category" => "Your Data / Projects / $path", "description" => "Peak calling and nucleosome positioning from MNase-seq data.", "Details" => "Each annotated region corresponds to a predicted nucleosome position. They are colored according their score:
<br/><b>score</b> is defined by the height and the width of the nucleosome. Higher and sharper peaks will have a higher score.<br/><b>score_width</b> and <b>score_height</b> relate to width and height of the peaks respectively and are used to determine the fuzziness of that nucleosome. Values are between 0 (wide/low peak) and 1 (sharp/high peak).<br/><b>nmerge</b> is the number of original nucleosome calls that were merged into a bigger nucleosome when they overlap by more than nmerge base pairs.<br/><b>class:</b> nucleosomes can be classified based on score_height and score_width as:<br><span style='background: blue;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> well-positioned (class= W) <br/> <span style='background: #819FF7;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> fuzzy (class= F)<br/> <span style='background: grey;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> uncertain: when more than 2 overlapping nucleosome calls are detected");
                $this->fmtDetailField_class = "function () { return \"".attribute_help("NR","class")."\"; }";
                //$this->fmtDetailField_nmerge = "function () { return \"".attribute_help("NR","nmerge")."\"; }";
                $this->fmtDetailField_score_height = "function () { return \"".attribute_help("NR","score_height")."\"; }";
                $this->fmtDetailField_score_width = "function () { return \"".attribute_help("NR","score_width")."\"; }";
                $this->fmtDetailField_Score = "function () { return \"".attribute_help("NR","Score")."\"; }";
        }
}


class newWindow {
	public $label = "";
	public $title = "";
	public $iconClass = "dijitIconChart";
	public $action = "";
	public $url = "";

        public function __construct($label,$title,$url){
                $this->label = $label;
                $this->title = $title;
                $this->url = $url;
		$this->action = "newWindow";
        }

	public function iconClass($str){
		$this->iconClass = $str;
	}

	public function action($str){
		$this->action = $str;
	}
}


class iframeDialog extends newWindow {
	
	public function __construct($label,$title,$url){
		parent::__construct($label,$title,$url);
		parent::action("iframeDialog");
	}
}


# GFF for NucleosomeDynamics, includes pop-ups right clicking
class GFF_ND extends GFF_NR{

//        public $style = array("className" => "feature", "color" => "#01DF01");

	public function __construct($label,$path,$file){
		parent::__construct($label,$path,$file);

                $this->style = array("className" => "feature", "color" => "function( feature, variableName, glyphObject, track ) { switch (feature.get('class')) { case 'INCLUSION': return '#04B431'; case 'EVICTION': return '#FF0000'; case 'INCREASED FUZZINESS': return '#424242'; case 'DECREASED FUZZINESS': return '#BDBDBD'; case 'SHIFT -': return '#0040FF'; case 'SHIFT +': return '#8000FF'}}");
                $this->metadata = array("category" => "Your Data / Projects / $path", "description" => "Detection of local changes in the position of nucleosomes at the single read level between two nucleosome maps.", "Details" =>"Changes are classified as: Shift upstream or downstrean, Inclusion or Eviction.<br> 
The movement of hotspots is scored according to the number of reads involved on the change.<br/>


<b>nreads</b> is the number of reads involved in the hotspot.<br/>

<b>Score</b> is the area of the coverage of the reads involved in the movement relative to the area of the coverage of all the reads present in the movement range.</br>

<b>Class</b> The track shows accumulations (hotspots) of movements detected at the read-level. Each detected change is classified into several different classes:<br/>
<span style='background: #8000FF;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> SHIFT +:</b> Indicates an upstream shift of the nucleosomes.<br/>
<span style='background: #0040FF;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> SHIFT -:</b> Indicates a downstream shift.<br/>
<span style='background: #04B431;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> INCLUSION:</b> Condition 2 has a local increase in nucleosome coverage relative to condition 1.<br/>
<span style='background: #FF0000;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><b> EVICTION:</b> Condition 2 has a local decrease in nucleosome coverage relative to condition 1.
");

		$newWin = new newWindow("View 1000 bp. around the hotspot","Nucleosome Dynamics plot",$GLOBALS['jbrowseURL']."getGraph.php?start={start}&end={end}&chr={seq_id}&label=".$label."&window=1000&win=no");
//		$iframe = new iframeDialog("iframe","Nucleosome Dynamics plot",$GLOBALS['jbrowseURL']."getGraph.php?start={start}&end={end}&chr={seq_id}&label=".$label."&window=1000&win=no");
//		$newWin2 = new newWindow("newWindow","Nucleosome Dynamics plot",$GLOBALS['jbrowseURL']."getGraph.php?start={start}&end={end}&chr={seq_id}&label=".$label."&window=1000&win=yes");
//		$this->menuTemplate = array(array("label" => "View details"),$newWin,array("label" => "View 1000 bp. around the hotspot","children" => array($iframe,$newWin2)));
        $this->menuTemplate = array(array("label" => "View details"),$newWin);
	$this->fmtDetailField_Score = "function () { return \"".attribute_help("ND","Score")."\"; }";
    $this->fmtDetailField_Position = "function () { return \"".attribute_help("ND","Position")."\"; }";
    $this->fmtDetailField_Type = "function () { return \"".attribute_help("ND","Type")."\"; }";

//	$this->fmtDetailField_coord = "function () { return \"".attribute_help("ND","coord")."\"; }";
	$this->fmtDetailField_class = "function () { return \"".attribute_help("ND","class")."\"; }";
//	$this->fmtDetailField_nuc = "function () { return \"".attribute_help("ND","nuc")."\"; }";
//	$this->fmtDetailField_number_of_reads = "function () { return \"".attribute_help("ND","number_of_reads")."\"; }";
//	$this->fmtDetailField_readsinvolved = "function () { return \"".attribute_help("ND","readsInvolved")."\"; }";
	$this->fmtDetailField_nreads = "function () { return \"".attribute_help("ND","nreads")."\"; }";
//        $this->fmtDetailField_freads = "function () { return \"".attribute_help("ND","freads")."\"; }";



//    $this->fmtDetailValue_Type = "function (type,feature) { start=feature.get('start');end=feature.get('end');seq_id=feature.get('seq_id'); return type+\"  <a href='".$GLOBALS['jbrowseURL']."getGraph.php?start=\"+start+\"&end=\"+end+\"&chr=\"+seq_id+\"&label=".$label."&window=1000&win=no' target='_blank' style='text-decoration:none;' title='View 1000bp around the hotspot'> [ + ] </a>\"; }";
      $this->fmtDetailValue_Type = "function (type,feature) { start=feature.get('start');end=feature.get('end');seq_id=feature.get('seq_id'); return type+\" <br/> <a href='".$GLOBALS['jbrowseURL']."getGraph.php?start=\"+start+\"&end=\"+end+\"&chr=\"+seq_id+\"&label=".$label."&window=1000&win=no' target='_blank' style='text-decoration:none;' title='View 1000bp around the hotspot'> [Click to view a detailed plot 1000bp around the change] </a>\"; }";
//        public $fmtDetailValue_Name = "function(name) { var patt=/_/; if (!patt.test(name)) { return '<a href=\"http://www.yeastgenome.org/cgi-bin/locus.fpl?locus='+name+'\" target=\"_blank\">'+name+'</a>';} else {return name;}}";

	}

}

# GFF for gene classification
class GFF3 extends GFF_NR{
	public $style = array("className" => "feature", "color" => "function(feature) { if (feature.get('descr') == 'W-close-W') { return 'red'} else { return 'blue'}");

}




?>
