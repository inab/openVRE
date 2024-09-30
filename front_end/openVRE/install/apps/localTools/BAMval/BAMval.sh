#!/bin/bash

VERSION=0.1.0
SUBJECT=BAMval
USAGE="USAGE: $SUBJECT --bam BAMFILE --sort [0|1] --index [0|1] --replace \"'s/chr/CHR/g'\""

# --- Option processing --------------------------------------------


while [[ $# -gt 1 ]]
do
key="$1"

case $key in
    -b|--bam)
    BamFn="$2"
    shift
    ;;
    -s|--sort)
    Sort="$2"
    shift
    ;;
    -i|--index)
    Index="$2"
    shift 
    ;;
    -s|--replace)
    Subs="$2"
    shift 
    ;;
    -d|--working_dir)
    Tmpdir="$2"
    shift
    ;;
    *)
    # unknown option
    ;;
esac
shift # past argument or value
done

Tmpdir=${Tmpdir:-$(pwd)}

#if [ -v TmpDir ]; then
#	Tmpdir=$(pwd)
#fi

echo Input BAM file is "${BamFn}"
echo Working on directory "${Tmpdir}"
echo ''

if [[ -n $1 ]]; then
    echo $USAGE
    tail -1 $1
fi


#if [ $# == 0 ] ; then
#    echo $USAGE
#    exit 1;
#fi

#Tmpdir=$1
#Outdir=$2 # NOT USED
#BamFn=$3
#Type=$4
#Sort=$5
#Subs=$6
#Index=$7
#Cores=$8 # NOT USED

# -----------------------------------------------

samtools="samtools"

Basename=${BamFn##*/}
Rootname=${Basename%.*}
 
bamTmp=${Tmpdir}/${Basename}
bai=${BamFn}.bai

cd $Tmpdir


## TODO
if [ "0" -eq "1" ]; then
	echo "# BAM has no header. Extract chromosome names reading the entire BAM";
	$samtools view '$BamFn' | cut -f3 | sort -u 
	echo "# Matching chromosome names to reference genome names"
	php matchChrNames.php?format=format&chrs=chrFile&refGenome=refGenome
fi

## Substitute and sort
if [ ${#Subs} -gt 0  ]; then
	echo "# Normalizing chromose names and sorting BAM file..."
	cmd="$samtools view -h $BamFn  | $Subs | $samtools view -uhS - | $samtools sort - -o $bamTmp"
	echo "> $cmd"
	eval $cmd
        echo $!

        if [ ! -f  $bamTmp ]; then
		echo "Error sorting $BamFn, aborting"
		exit 2
	else
		echo "Temporal BAM created $bamTmp -- $(date)"
        	mv $bamTmp $BamFn
	fi

## Sort
elif [ $Sort -ne "0"  ]; then

	echo "# Sorting BAM file..."
	echo "> $samtools sort $BamFn -o $bamTmp"
	$samtools sort $BamFn -o $bamTmp
    echo $!
    if [ ! -f  $bamTmp ]; then
		echo "Error sorting $BamFn, aborting"
		exit 2
	else
		echo "Temporal BAM created $bamTmp -- $(date)"
        mv $bamTmp $BamFn
	fi
else 
	echo "# Bam already sorted"
fi

#Index
if [ ${#Subs} -gt 0 ] || [ $Sort -ne "0" ] || [ $Index -ne "0" ] ; then
    echo "# Indexing BAM file..."
	echo "> $samtools index $BamFn"
	$samtools index $BamFn
	echo '# End time:' $(date)
	if [ ! -f  $bai ]; then
		echo 'Error indexing $BamFn, aborting. Is the BAM file sorted? If not, mark it as \"unsorted\"'
		exit 2
	else
		echo 'BAM successfully created'
	fi
fi
echo '# End time:' $(date)
